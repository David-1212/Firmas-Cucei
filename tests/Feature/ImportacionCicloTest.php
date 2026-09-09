<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\Documento;
use App\Models\Firma;
use App\Models\Importacion;
use App\Models\TipoDocumento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ImportacionCicloTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    public function test_el_ciclo_es_obligatorio_al_importar(): void
    {
        $csv = UploadedFile::fake()->createWithContent('alumnos.csv', "codigo,nombre completo\n111,Ana\n");

        $response = $this->actingAs($this->admin)->post(route('importaciones.store'), [
            'archivo' => $csv,
        ]);

        $response->assertSessionHasErrors('ciclo');
        $this->assertDatabaseCount('importaciones', 0);
    }

    public function test_cada_fila_se_inserta_como_registro_nuevo_con_su_ciclo(): void
    {
        $existente = Alumno::factory()->create(['codigo' => '202340223', 'nombre_completo' => 'Reginald Parker']);

        $csv = UploadedFile::fake()->createWithContent('alumnos.csv', implode("\n", [
            'codigo,nombre completo',
            '202340223,Reginald Parker',
            '999999901,Nuevo Uno',
        ]));

        $response = $this->actingAs($this->admin)->post(route('importaciones.store'), [
            'archivo' => $csv,
            'ciclo' => '2026-B',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $importacion = Importacion::first();
        $this->assertSame('2026-B', $importacion->ciclo);

        // Procesar en segundo plano (modo síncrono en tests).
        $job = new \App\Jobs\ProcesarImportacionCsv($importacion->id);
        $job->handle();

        $importacion->refresh();
        $this->assertEquals(2, $importacion->total_filas);
        $this->assertEquals(2, $importacion->insertadas);

        // Cada fila crea su propio registro (aunque el código ya existía antes),
        // y SOLO los registros de este ciclo quedan vinculados a la importación.
        $nuevo = Alumno::where('codigo', '202340223')->where('id', '!=', $existente->id)->first();
        $this->assertNotNull($nuevo);
        $this->assertDatabaseHas('alumno_importacion', [
            'alumno_id' => $nuevo->id,
            'importacion_id' => $importacion->id,
        ]);
        $this->assertDatabaseHas('alumno_importacion', [
            'alumno_id' => Alumno::where('codigo', '999999901')->first()->id,
            'importacion_id' => $importacion->id,
        ]);

        // El registro preexistente se conserva pero no se toca ni se vincula.
        $this->assertDatabaseMissing('alumno_importacion', [
            'alumno_id' => $existente->id,
            'importacion_id' => $importacion->id,
        ]);
    }

    public function test_un_mismo_codigo_unifica_motriculas_en_un_solo_registro(): void
    {
        $csv = UploadedFile::fake()->createWithContent('alumnos.csv', implode("\n", [
            'matricula,codigo,nombre completo,carrera,ciclo de ingreso',
            '210201010,202340111,Ana Marin,Licenciatura,2021A',
            '220302020,202340111,Ana Marin,Maestría,2023B',
        ]));

        $response = $this->actingAs($this->admin)->post(route('importaciones.store'), [
            'archivo' => $csv,
            'ciclo' => '2026-B',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $importacion = Importacion::first();
        $job = new \App\Jobs\ProcesarImportacionCsv($importacion->id);
        $job->handle();

        $importacion->refresh();
        $this->assertEquals(2, $importacion->total_filas);
        $this->assertEquals(1, $importacion->insertadas);

        // Las dos filas con el mismo código se unifican en un solo registro nuevo
        // con ambas matrículas, ambas carreras y ambos ciclos de ingreso.
        $alumno = Alumno::where('codigo', '202340111')->first();
        $this->assertEquals(1, Alumno::where('codigo', '202340111')->count());
        $this->assertSame('210201010 / 220302020', $alumno->matricula);
        $this->assertSame('Licenciatura / Maestría', $alumno->carrera);
        $this->assertSame('2021A / 2023B', $alumno->ciclo_ingreso);

        // En el listado de alumnos aparecen ambas matrículas de la persona.
        $ventanilla = User::factory()->create(['role' => 'ventanilla']);
        $response = $this->actingAs($ventanilla)->get('/alumnos');
        $response->assertOk();
        $response->assertSee('210201010');
        $response->assertSee('220302020');
    }

    public function test_el_listado_solo_muestra_el_ultimo_ciclo_registrado(): void
    {
        $ventanilla = User::factory()->create(['role' => 'ventanilla']);

        $a1 = Alumno::factory()->create(['nombre_completo' => 'Ana Uno']);
        $a2 = Alumno::factory()->create(['nombre_completo' => 'Bruno Dos']);

        $imp1 = Importacion::factory()->create(['ciclo' => '2026-A']);
        $imp2 = Importacion::factory()->create(['ciclo' => '2026-B']);

        $a1->importaciones()->attach($imp1);
        $a2->importaciones()->attach($imp2);

        $response = $this->actingAs($ventanilla)->get('/alumnos');

        $response->assertOk();
        $body = $this->body($response->getContent());
        $this->assertStringContainsString('Bruno Dos', $body);
        $this->assertStringNotContainsString('Ana Uno', $body);
    }

    private function body(string $html): string
    {
        $start = strpos($html, '<tbody');
        $end = strpos($html, '</tbody>');
        if ($start === false || $end === false) {
            return '';
        }
        return substr($html, $start, $end - $start + 8);
    }

    public function test_csv_con_columnas_no_codigo_nombre_unique_id_sede_carrera_se_importa(): void
    {
        $csv = UploadedFile::fake()->createWithContent('alumnos.csv', implode("\n", [
            'No.,CODIGO,NOMBRE,UNIQUE ID,SEDE,CARRERA',
            '1,111111111,Ana Marin,U22222222,Sede Centro,Ingenieria en Computacion',
        ]));

        $this->actingAs($this->admin)->post(route('importaciones.store'), [
            'archivo' => $csv,
            'ciclo' => '2026-B',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $importacion = Importacion::orderByDesc('id')->first();
        $job = new \App\Jobs\ProcesarImportacionCsv($importacion->id);
        $job->handle();

        $importacion->refresh();
        $this->assertEquals(1, $importacion->insertadas);

        // Solo se toman los datos necesarios: CODIGO/UNIQUE ID/NOMBRE/CARRERA,
        // ignorando No. y SEDE.
        $alumno = Alumno::where('codigo', '111111111')->first();
        $this->assertNotNull($alumno);
        $this->assertSame('111111111', $alumno->codigo);
        $this->assertSame('U22222222', $alumno->matricula);
        $this->assertSame('Ana Marin', $alumno->nombre_completo);
        $this->assertSame('Ingenieria en Computacion', $alumno->carrera);
    }

    public function test_reimportar_no_duplica_ni_sobreescribe_alumno_ya_firmado_en_el_mismo_ciclo(): void
    {
        $tipo = TipoDocumento::factory()->create();

        $alumno = Alumno::factory()->create([
            'codigo' => '202340223',
            'nombre_completo' => 'Reginald Parker',
            'matricula' => 'M-2000',
        ]);
        $imp = Importacion::factory()->create(['ciclo' => '2026-B']);
        $alumno->importaciones()->attach($imp);

        $doc = Documento::factory()->create([
            'alumno_id' => $alumno->id,
            'tipo_documento_id' => $tipo->id,
            'folio' => '202340223-' . $alumno->id . '-1',
            'estado' => 'firmado',
        ]);
        Firma::factory()->create(['alumno_id' => $alumno->id, 'documento_id' => $doc->id]);

        $csv = UploadedFile::fake()->createWithContent('alumnos.csv', implode("\n", [
            'No.,CODIGO,NOMBRE,UNIQUE ID,SEDE,CARRERA',
            '1,202340223,Reginald Parker,9999,Sede,Ingenieria',
        ]));

        $this->actingAs($this->admin)->post(route('importaciones.store'), [
            'archivo' => $csv,
            'ciclo' => '2026-B',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $importacion = Importacion::orderByDesc('id')->first();
        $job = new \App\Jobs\ProcesarImportacionCsv($importacion->id);
        $job->handle();

        $importacion->refresh();
        $this->assertEquals(1, $importacion->total_filas);
        $this->assertEquals(0, $importacion->insertadas);
        $this->assertEquals(1, $importacion->duplicadas);

        // No se creó un registro duplicado: sigue habiendo un solo alumno.
        $this->assertEquals(1, Alumno::where('codigo', '202340223')->count());

        // El registro existente quedó vinculado a la nueva importación (mismo ciclo).
        $this->assertDatabaseHas('alumno_importacion', [
            'alumno_id' => $alumno->id,
            'importacion_id' => $importacion->id,
        ]);

        // El documento firmado y su firma siguen intactos.
        $this->assertDatabaseHas('documentos', ['id' => $doc->id, 'alumno_id' => $alumno->id, 'estado' => 'firmado']);
        $this->assertDatabaseHas('firmas', ['documento_id' => $doc->id, 'alumno_id' => $alumno->id]);
    }
}