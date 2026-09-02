<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\Documento;
use App\Models\Firma;
use App\Models\TipoDocumento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FirmaPersistenciaTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_firma_se_guarda_en_carpeta_del_codigo_con_id_del_documento(): void
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
        $this->assertEquals('firmas/101010101/' . $doc->id . '.png', $firma->ruta_imagen);
        $this->assertTrue(Storage::disk('public')->exists($firma->ruta_imagen));
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

        $ruta = Firma::where('documento_id', $doc->id)->first()->ruta_imagen;
        $contenido = Storage::disk('public')->get($ruta);

        // Firmar de nuevo: reutiliza la misma ruta y no crea otra firma.
        $this->actingAs($admin)->post(route('documentos.storeFirma', $doc), [
            'firma_data' => 'data:image/png;base64,' . base64_encode($this->pngMinimo()),
        ])->assertRedirect();

        $this->assertEquals(1, Firma::where('documento_id', $doc->id)->count());
        $this->assertEquals(Storage::disk('public')->get($ruta), $contenido);
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

        $ruta = Firma::where('documento_id', $doc->id)->first()->ruta_imagen;
        $this->assertTrue(Storage::disk('public')->exists($ruta));

        // Simular vaciarTodo directamente con captcha resuelto vía sesión
        session(['captcha_answer' => 42]);
        $this->actingAs($admin)->post(route('importaciones.vaciar'), [
            'captcha' => '42',
        ])->assertRedirect();

        $this->assertEquals(0, \App\Models\Alumno::count());
        $this->assertTrue(Storage::disk('public')->exists($ruta));
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
        $ruta = $firma->ruta_imagen;
        $this->assertTrue(Storage::disk('public')->exists($ruta));

        $this->actingAs($admin)
            ->delete(route('alumnos.destroy', $alumno))
            ->assertRedirect();

        $this->assertDatabaseMissing('alumnos', ['id' => $alumno->id]);
        $this->assertDatabaseHas('documentos', ['id' => $doc->id, 'alumno_id' => null]);
        $this->assertDatabaseHas('firmas', ['id' => $firma->id, 'alumno_id' => null]);
        $this->assertTrue(Storage::disk('public')->exists($ruta));
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

    private function pngMinimo(): string
    {
        return base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=');
    }
}