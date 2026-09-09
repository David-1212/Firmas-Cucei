<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\Documento;
use App\Models\Firma;
use App\Models\TipoDocumento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class FirmaPersistenciaTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_firma_se_guarda_como_base64_en_la_base_de_datos(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $tipo = TipoDocumento::factory()->create();
        $alumno = Alumno::factory()->create(['codigo' => '101010101']);
        $doc = Documento::factory()->create(['alumno_id' => $alumno->id, 'tipo_documento_id' => $tipo->id, 'estado' => 'pendiente']);

        $this->actingAs($admin)->post(route('documentos.storeFirma', $doc), [
            'firma_data' => 'data:image/png;base64,' . base64_encode($this->pngMinimo()),
        ])->assertRedirect();

        $firma = Firma::where('documento_id', $doc->id)->first();
        $this->assertNotNull($firma);
        $this->assertEquals(base64_encode($this->pngMinimo()), $firma->imagen);
        $this->assertStringStartsWith('data:image/png;base64,', $firma->imagen_src);
    }

    public function test_la_firma_se_reutiliza_si_ya_estaba_firmado(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $tipo = TipoDocumento::factory()->create();
        $alumno = Alumno::factory()->create(['codigo' => '202020202']);
        $doc = Documento::factory()->create(['alumno_id' => $alumno->id, 'tipo_documento_id' => $tipo->id, 'estado' => 'pendiente']);

        $this->actingAs($admin)->post(route('documentos.storeFirma', $doc), [
            'firma_data' => 'data:image/png;base64,' . base64_encode($this->pngMinimo()),
        ])->assertRedirect();

        $firma = Firma::where('documento_id', $doc->id)->first();
        $imagen = $firma->imagen;

        // Firmar de nuevo: reutiliza la misma firma y no crea otra.
        $this->actingAs($admin)->post(route('documentos.storeFirma', $doc), [
            'firma_data' => 'data:image/png;base64,' . base64_encode($this->pngMinimo()),
        ])->assertRedirect();

        $this->assertEquals(1, Firma::where('documento_id', $doc->id)->count());
        $this->assertEquals($imagen, Firma::find($firma->id)->imagen);
    }

    public function test_vaciar_todo_no_borra_las_imagenes_de_firmas(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $tipo = TipoDocumento::factory()->create();
        $alumno = Alumno::factory()->create(['codigo' => '303030303']);
        $doc = Documento::factory()->create(['alumno_id' => $alumno->id, 'tipo_documento_id' => $tipo->id, 'estado' => 'pendiente']);

        $this->actingAs($admin)->post(route('documentos.storeFirma', $doc), [
            'firma_data' => 'data:image/png;base64,' . base64_encode($this->pngMinimo()),
        ])->assertRedirect();

        $firma = Firma::where('documento_id', $doc->id)->first();
        $imagen = $firma->imagen;
        $this->assertNotEmpty($imagen);

        // Simular vaciarTodo directamente con captcha resuelto vía sesión
        session(['captcha_answer' => 42]);
        $this->actingAs($admin)->post(route('importaciones.vaciar'), [
            'captcha' => '42',
        ])->assertRedirect();

        $this->assertEquals(0, \App\Models\Alumno::count());
        $this->assertEquals($imagen, Firma::find($firma->id)?->imagen);
    }

    public function test_borrar_un_alumno_conserva_sus_documentos_y_firmas(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $tipo = TipoDocumento::factory()->create();
        $alumno = Alumno::factory()->create(['codigo' => '404040404']);
        $doc = Documento::factory()->create(['alumno_id' => $alumno->id, 'tipo_documento_id' => $tipo->id, 'estado' => 'pendiente']);

        $this->actingAs($admin)->post(route('documentos.storeFirma', $doc), [
            'firma_data' => 'data:image/png;base64,' . base64_encode($this->pngMinimo()),
        ])->assertRedirect();

        $firma = Firma::where('documento_id', $doc->id)->first();
        $imagen = $firma->imagen;
        $this->assertNotEmpty($imagen);

        $this->actingAs($admin)
            ->delete(route('alumnos.destroy', $alumno))
            ->assertRedirect();

        $this->assertDatabaseMissing('alumnos', ['id' => $alumno->id]);
        $this->assertDatabaseHas('documentos', ['id' => $doc->id, 'alumno_id' => null]);
        $this->assertDatabaseHas('firmas', ['id' => $firma->id, 'alumno_id' => null]);
        $this->assertEquals($imagen, Firma::find($firma->id)?->imagen);
    }

    public function test_reimportar_el_codigo_religa_documentos_y_firmas_al_nuevo_alumno(): void
    {
        $tipo = TipoDocumento::factory()->create();
        $alumno = Alumno::factory()->create(['codigo' => '505050505']);
        $doc = Documento::factory()->create(['alumno_id' => $alumno->id, 'tipo_documento_id' => $tipo->id, 'folio' => '505050505-1']);
        $firma = Firma::factory()->create(['alumno_id' => $alumno->id, 'documento_id' => $doc->id]);

        // Eliminar el alumno: documentos y firmas quedan huérfanos (alumno_id NULL).
        $alumno->delete();
        $this->assertDatabaseHas('documentos', ['id' => $doc->id, 'alumno_id' => null]);
        $this->assertDatabaseHas('firmas', ['id' => $firma->id, 'alumno_id' => null]);

        // Re-importar el listado con el mismo código: el nuevo alumno debe
        // recuperar sus documentos y firmas huérfanos.
        $importacion = \App\Models\Importacion::factory()->create();
        $job = new \App\Jobs\ProcesarLoteAlumnos([
            [
                'codigo' => '505050505',
                'matricula' => '50505',
                'nombre_completo' => 'Nuevo Alumno',
                'carrera' => 'ING',
                'ciclo_ingreso' => '2026A',
                'status' => 'ACTIVO',
            ],
        ], $importacion->id);
        $job->handle();

        $nuevoAlumno = Alumno::where('codigo', '505050505')->first();
        $this->assertNotNull($nuevoAlumno);
        $this->assertNotEquals($nuevoAlumno->id, $alumno->id);
        $this->assertDatabaseHas('documentos', ['id' => $doc->id, 'alumno_id' => $nuevoAlumno->id]);
        $this->assertDatabaseHas('firmas', ['id' => $firma->id, 'alumno_id' => $nuevoAlumno->id]);
    }

    public function test_despues_de_reimportar_se_puede_crear_un_documento_nuevo(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $tipo = TipoDocumento::factory()->create();
        $alumno = Alumno::factory()->create(['codigo' => '606060606']);
        $doc = Documento::factory()->create(['alumno_id' => $alumno->id, 'tipo_documento_id' => $tipo->id, 'folio' => '606060606-1']);

        // Eliminar al alumno: el documento queda huérfano.
        $alumno->delete();
        $this->assertDatabaseHas('documentos', ['id' => $doc->id, 'alumno_id' => null]);

        // Re-importar el mismo código.
        $importacion = \App\Models\Importacion::factory()->create();
        $job = new \App\Jobs\ProcesarLoteAlumnos([
            [
                'codigo' => '606060606',
                'matricula' => '60606',
                'nombre_completo' => 'Alumno Reimportado',
                'carrera' => 'ING',
                'ciclo_ingreso' => '2026A',
                'status' => 'ACTIVO',
            ],
        ], $importacion->id);
        $job->handle();

        $nuevoAlumno = Alumno::where('codigo', '606060606')->first();
        $this->assertNotNull($nuevoAlumno);

        // Crear un documento nuevo: no debe chocar con el folio de los
        // documentos re-vinculados ni con posibles huérfanos del código.
        $this->actingAs($admin)->post(route('documentos.store'), [
            'alumno_id' => $nuevoAlumno->id,
            'tipo_documento_id' => $tipo->id,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $folios = Documento::where('alumno_id', $nuevoAlumno->id)->pluck('folio')->sort()->values();
        $this->assertEquals(['606060606-1', '606060606-' . $nuevoAlumno->id . '-2'], $folios->all());
        $this->assertCount(2, $folios->unique());
    }

    public function test_importacion_alumnos_religa_credenciales_huerfanas_de_vaciar_todo(): void
    {
        // Escenario real: borrar todos los alumnos (vaciarTodo) deja las
        // credenciales (folio = UNIQUE ID, con codigo_alumno) huérfanas. Al
        // re-importar el listado, deben re-vincularse al nuevo alumno por el código.
        $tipoCredencial = TipoDocumento::where('nombre', 'Credencial')->firstOrFail();
        $alumnoViejo = Alumno::factory()->create(['codigo' => '707070707', 'nombre_completo' => 'Alumno Viejo']);

        $doc = Documento::factory()->create([
            'alumno_id' => $alumnoViejo->id,
            'codigo_alumno' => '707070707',
            'tipo_documento_id' => $tipoCredencial->id,
            'folio' => 'UID-9999',
        ]);
        $firma = Firma::factory()->create(['alumno_id' => $alumnoViejo->id, 'documento_id' => $doc->id]);

        // vaciarTodo: se borran todos los alumnos; documentos y firmas quedan huérfanos.
        session(['captcha_answer' => 42]);
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->post(route('importaciones.vaciar'), ['captcha' => '42'])->assertRedirect();

        $this->assertDatabaseHas('documentos', ['id' => $doc->id, 'alumno_id' => null]);
        $this->assertDatabaseHas('firmas', ['id' => $firma->id, 'alumno_id' => null]);

        // Re-importar los alumnos.
        $importacion = \App\Models\Importacion::factory()->create(['ciclo' => '2026-B', 'tipo' => 'alumnos']);
        $job = new \App\Jobs\ProcesarLoteAlumnos([
            [
                'codigo' => '707070707',
                'matricula' => '70707',
                'nombre_completo' => 'Alumno Nuevo',
                'carrera' => 'ING',
                'ciclo_ingreso' => '2026A',
                'status' => 'ACTIVO',
            ],
        ], $importacion->id);
        $job->handle();

        $nuevoAlumno = Alumno::where('codigo', '707070707')->first();
        $this->assertNotNull($nuevoAlumno);
        $this->assertNotEquals($nuevoAlumno->id, $alumnoViejo->id);

        // La credencial (folio UNIQUE ID) y su firma vuelven a pertenecer al alumno.
        $this->assertDatabaseHas('documentos', ['id' => $doc->id, 'alumno_id' => $nuevoAlumno->id]);
        $this->assertDatabaseHas('firmas', ['id' => $firma->id, 'alumno_id' => $nuevoAlumno->id]);
    }

    public function test_reimportar_credenciales_religa_credencial_vieja_huerfana(): void
    {
        // Escenario para credenciales creadas antes de guardar codigo_alumno:
        // al volver a importar el CSV de credenciales, la huérfana se
        // re-vincula al alumno del ciclo actual usando el código del archivo.
        $tipoCredencial = TipoDocumento::where('nombre', 'Credencial')->firstOrFail();
        $alumno = Alumno::factory()->create(['codigo' => '808080808', 'nombre_completo' => 'Alumno Reimportado']);
        $alumno->delete();

        // Credencial vieja huérfana (sin codigo_alumno), creada antes del fix.
        $doc = Documento::factory()->create([
            'alumno_id' => null,
            'codigo_alumno' => null,
            'tipo_documento_id' => $tipoCredencial->id,
            'folio' => 'UID-OLD-888',
        ]);

        // El alumno vuelve a existir en el último ciclo (re-importación de alumnos).
        $impAlumnos = \App\Models\Importacion::factory()->create(['ciclo' => '2026-B', 'tipo' => 'alumnos']);
        $nuevoAlumno = Alumno::factory()->create(['codigo' => '808080808', 'nombre_completo' => 'Alumno Reimportado']);
        $nuevoAlumno->importaciones()->attach($impAlumnos);
        $this->assertNotEquals($nuevoAlumno->id, $alumno->id);

        // Re-importar credenciales: la huérfana se re-vincula por código.
        $csv = UploadedFile::fake()->createWithContent('credenciales.csv', implode("\n", [
            'No.,CODIGO,NOMBRE,UNIQUE ID,SEDE,CARRERA',
            '1,808080808,Alumno Reimportado,UID-OLD-888,CUCEI,Ingenieria en Computacion',
        ]));
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)
            ->post(route('importaciones.store'), [
                'archivo' => $csv,
                'tipo' => 'documentos',
            ])->assertRedirect()->assertSessionHasNoErrors();

        $importacion = \App\Models\Importacion::where('tipo', 'documentos')->first();
        $job = new \App\Jobs\ProcesarImportacionCsv($importacion->id);
        $job->handle();

        $this->assertDatabaseHas('documentos', [
            'id' => $doc->id,
            'alumno_id' => $nuevoAlumno->id,
            'codigo_alumno' => '808080808',
        ]);
    }

    public function test_vaciar_documentos_borra_documentos_y_firmas_pero_conserva_alumnos(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $tipo = TipoDocumento::factory()->create();
        $alumno = Alumno::factory()->create(['codigo' => '909090909']);
        $doc = Documento::factory()->create(['alumno_id' => $alumno->id, 'tipo_documento_id' => $tipo->id, 'estado' => 'pendiente']);
        $firma = Firma::factory()->create(['alumno_id' => $alumno->id, 'documento_id' => $doc->id]);

        $this->assertSame(1, Documento::count());

        // Captcha resuelto vía sesión.
        session(['captcha_answer' => 42]);
        $this->actingAs($admin)
            ->post(route('documentos.vaciar'), [
                'captcha' => '42',
            ])->assertRedirect(route('documentos.index'));

        $this->assertSame(0, Documento::count());
        $this->assertSame(0, Firma::count());
        $this->assertDatabaseHas('alumnos', ['id' => $alumno->id]);
        $this->assertSame(1, Alumno::where('id', $alumno->id)->count());
    }

    public function test_vaciar_documentos_rechaza_captcha_incorrecto(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $tipo = TipoDocumento::factory()->create();
        $alumno = Alumno::factory()->create();
        $doc = Documento::factory()->create(['alumno_id' => $alumno->id, 'tipo_documento_id' => $tipo->id]);

        session(['captcha_answer' => 42]);
        $this->actingAs($admin)
            ->post(route('documentos.vaciar'), [
                'captcha' => '99',
            ])->assertSessionHasErrors('captcha');

        $this->assertSame(1, Documento::count());
    }

    public function test_ventanilla_no_puede_vaciar_documentos(): void
    {
        $ventanilla = User::factory()->create(['role' => 'ventanilla']);
        $tipo = TipoDocumento::factory()->create();
        $alumno = Alumno::factory()->create();
        $doc = Documento::factory()->create(['alumno_id' => $alumno->id, 'tipo_documento_id' => $tipo->id]);

        $this->actingAs($ventanilla)
            ->post(route('documentos.vaciar'), [
                'captcha' => '42',
            ])->assertForbidden();

        $this->assertSame(1, Documento::count());
    }

    private function pngMinimo(): string
    {
        return base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=');
    }
}