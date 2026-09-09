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

class ImportacionDocumentosTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    public function test_el_tipo_credencial_es_fijo_del_sistema(): void
    {
        $credencial = TipoDocumento::where('nombre', 'Credencial')->first();
        $this->assertNotNull($credencial);
        $this->assertTrue($credencial->sistema);

        // No puede eliminarse aunque no tenga documentos.
        $this->actingAs($this->admin)
            ->delete(route('tipos.destroy', $credencial))
            ->assertSessionHasErrors();

        $this->assertDatabaseHas('tipo_documentos', ['id' => $credencial->id]);
    }

    public function test_no_se_puede_modificar_ni_desactivar_el_tipo_credencial(): void
    {
        $credencial = TipoDocumento::where('nombre', 'Credencial')->firstOrFail();

        $this->actingAs($this->admin)
            ->patch(route('tipos.update', $credencial), [
                'nombre' => 'Credencial nueva',
                'descripcion' => null,
                'activo' => 1,
            ])
            ->assertSessionHasErrors();

        $this->assertDatabaseHas('tipo_documentos', ['id' => $credencial->id, 'nombre' => 'Credencial']);
    }

    public function test_importar_credenciales_crea_documentos_ligados_por_codigo_y_uso_de_unique_id_como_folio(): void
    {
        $tipo = TipoDocumento::where('nombre', 'Credencial')->firstOrFail();
        $alumno = Alumno::factory()->create(['codigo' => '202340223', 'nombre_completo' => 'Reginald Parker']);

        $impAlumnos = Importacion::factory()->create(['ciclo' => '2026-B', 'tipo' => 'alumnos']);
        $alumno->importaciones()->attach($impAlumnos);

        $csv = UploadedFile::fake()->createWithContent('credenciales.csv', implode("\n", [
            'No.,CODIGO,NOMBRE,UNIQUE ID,SEDE,CARRERA',
            '1,202340223,Reginald Parker,UID-1001,CUCEI,Ingenieria en Computacion',
            '2,202340223,Reginald Parker,UID-1002,CUCEI,Ingenieria en Computacion',
        ]));

        $this->actingAs($this->admin)
            ->post(route('importaciones.store'), [
                'archivo' => $csv,
                'tipo' => 'documentos',
            ])->assertRedirect()->assertSessionHasNoErrors();

        $importacion = Importacion::where('tipo', 'documentos')->first();
        $this->assertNotNull($importacion);
        $this->assertSame('documentos', $importacion->tipo);

        $job = new \App\Jobs\ProcesarImportacionCsv($importacion->id);
        $job->handle();

        $importacion->refresh();
        $this->assertEquals(2, $importacion->insertadas);

        $doc1 = Documento::where('folio', 'UID-1001')->first();
        $doc2 = Documento::where('folio', 'UID-1002')->first();
        $this->assertNotNull($doc1);
        $this->assertNotNull($doc2);
        $this->assertSame($alumno->id, $doc1->alumno_id);
        $this->assertSame($tipo->id, $doc1->tipo_documento_id);
        $this->assertSame('pendiente', $doc1->estado);
    }

    public function test_reimportar_no_duplica_ni_sobreescribe_credencial_ya_existente(): void
    {
        $tipo = TipoDocumento::where('nombre', 'Credencial')->firstOrFail();
        $alumno = Alumno::factory()->create(['codigo' => '202340223', 'nombre_completo' => 'Reginald Parker']);

        $impAlumnos = Importacion::factory()->create(['ciclo' => '2026-B', 'tipo' => 'alumnos']);
        $alumno->importaciones()->attach($impAlumnos);

        $doc = Documento::factory()->create([
            'alumno_id' => $alumno->id,
            'tipo_documento_id' => $tipo->id,
            'folio' => 'UID-1001',
            'estado' => 'firmado',
        ]);
        Firma::factory()->create(['alumno_id' => $alumno->id, 'documento_id' => $doc->id]);

        $csv = UploadedFile::fake()->createWithContent('credenciales.csv', implode("\n", [
            'No.,CODIGO,NOMBRE,UNIQUE ID,SEDE,CARRERA',
            '1,202340223,Reginald Parker,UID-1001,CUCEI,Ingenieria en Computacion',
        ]));

        $this->actingAs($this->admin)
            ->post(route('importaciones.store'), [
                'archivo' => $csv,
                'tipo' => 'documentos',
            ])->assertRedirect()->assertSessionHasNoErrors();

        $importacion = Importacion::where('tipo', 'documentos')->first();
        $job = new \App\Jobs\ProcesarImportacionCsv($importacion->id);
        $job->handle();

        $importacion->refresh();
        $this->assertEquals(1, $importacion->procesadas);
        $this->assertEquals(0, $importacion->insertadas);
        $this->assertEquals(1, $importacion->duplicadas);

        // No se duplicó ni se sobreescribió.
        $this->assertEquals(1, Documento::where('folio', 'UID-1001')->count());
        $this->assertDatabaseHas('documentos', ['id' => $doc->id, 'estado' => 'firmado', 'alumno_id' => $alumno->id]);
        $this->assertDatabaseHas('firmas', ['documento_id' => $doc->id]);
    }

    public function test_credencial_sin_codigo_encontrado_se_omite_como_error(): void
    {
        $csv = UploadedFile::fake()->createWithContent('credenciales.csv', implode("\n", [
            'No.,CODIGO,NOMBRE,UNIQUE ID,SEDE,CARRERA',
            '1,999999999,Alguien,UID-555,CUCEI,Ingenieria en Computacion',
        ]));

        $this->actingAs($this->admin)
            ->post(route('importaciones.store'), [
                'archivo' => $csv,
                'tipo' => 'documentos',
            ])->assertRedirect()->assertSessionHasNoErrors();

        $importacion = Importacion::where('tipo', 'documentos')->first();
        $job = new \App\Jobs\ProcesarImportacionCsv($importacion->id);
        $job->handle();

        $importacion->refresh();
        $this->assertEquals(1, $importacion->procesadas);
        $this->assertEquals(0, $importacion->insertadas);
        $this->assertEquals(1, $importacion->errores);
        $this->assertEquals(0, Documento::where('folio', 'UID-555')->count());
    }

    public function test_solo_admin_puede_importar_credenciales(): void
    {
        $ventanilla = User::factory()->create(['role' => 'ventanilla']);

        $csv = UploadedFile::fake()->createWithContent('credenciales.csv', "CODIGO,UNIQUE ID\n202340223,UID-1001\n");

        $this->actingAs($ventanilla)
            ->post(route('importaciones.store'), [
                'archivo' => $csv,
                'tipo' => 'documentos',
            ])->assertForbidden();
    }

    public function test_importacion_de_alumnos_sigue_requiriendo_ciclo(): void
    {
        $csv = UploadedFile::fake()->createWithContent('alumnos.csv', "codigo,nombre completo\n111,Ana\n");

        $this->actingAs($this->admin)
            ->post(route('importaciones.store'), [
                'archivo' => $csv,
                'tipo' => 'alumnos',
            ])->assertSessionHasErrors('ciclo');
    }
}