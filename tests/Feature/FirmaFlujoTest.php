<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\Documento;
use App\Models\Firma;
use App\Models\TipoDocumento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FirmaFlujoTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_admin_puede_crear_un_documento_y_firmarlo(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $tipo = TipoDocumento::factory()->create();
        $alumno = Alumno::factory()->create();

        // Crear documento
        $this->actingAs($admin);
        $res = $this->post(route('documentos.store'), [
            'alumno_id' => $alumno->id,
            'tipo_documento_id' => $tipo->id,
        ]);
        $res->assertRedirect();
        $documento = Documento::where('alumno_id', $alumno->id)->first();
        $this->assertNotNull($documento);
        $this->assertEquals('pendiente', $documento->estado);
        $this->assertNotNull($documento->fecha);
        $this->assertNotNull($documento->folio);

        // Firmar sin captcha
        $png = base64_encode($this->pngMinimo());
        $resFirma = $this->post(route('documentos.storeFirma', $documento), [
            'firma_data' => 'data:image/png;base64,' . $png,
        ]);
        $resFirma->assertRedirect(route('documentos.show', $documento));
        $resFirma->assertSessionHas('success');

        $documento->refresh();
        $this->assertEquals('firmado', $documento->estado);
        $this->assertEquals(1, $documento->firmas()->count());
    }

    public function test_la_firma_vacia_es_rechazada(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $tipo = TipoDocumento::factory()->create();
        $alumno = Alumno::factory()->create();
        $documento = Documento::factory()->create(['alumno_id' => $alumno->id, 'tipo_documento_id' => $tipo->id, 'estado' => 'pendiente']);

        $this->actingAs($admin);
        $res = $this->post(route('documentos.storeFirma', $documento), [
            'firma_data' => '',
        ]);
        $res->assertSessionHasErrors('firma_data');
        $this->assertEquals('pendiente', $documento->fresh()->estado);
        $this->assertEquals(0, $documento->firmas()->count());
    }

    public function test_un_ventanilla_no_puede_borrar_un_alumno(): void
    {
        $ventanilla = User::factory()->create(['role' => 'ventanilla']);
        $alumno = Alumno::factory()->create();

        $this->actingAs($ventanilla)
            ->delete(route('alumnos.destroy', $alumno))
            ->assertForbidden();

        $this->assertDatabaseHas('alumnos', ['id' => $alumno->id]);
    }

    public function test_un_ventanilla_puede_crear_documentos(): void
    {
        $ventanilla = User::factory()->create(['role' => 'ventanilla']);
        $tipo = TipoDocumento::factory()->create();
        $alumno = Alumno::factory()->create();

        $this->actingAs($ventanilla);
        $res = $this->post(route('documentos.store'), [
            'alumno_id' => $alumno->id,
            'tipo_documento_id' => $tipo->id,
        ]);
        $res->assertRedirect();
        $this->assertEquals(1, $alumno->documentos()->count());
    }

    public function test_un_ventanilla_no_puede_acceder_a_la_gestion_de_usuarios(): void
    {
        $ventanilla = User::factory()->create(['role' => 'ventanilla']);

        $this->actingAs($ventanilla)
            ->get(route('usuarios.index'))
            ->assertForbidden();
    }

    public function test_el_dashboard_es_solo_para_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $ventanilla = User::factory()->create(['role' => 'ventanilla']);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk();

        $this->actingAs($ventanilla)
            ->get(route('dashboard'))
            ->assertForbidden();
    }

    public function test_un_ventanilla_no_puede_crear_un_alumno(): void
    {
        $ventanilla = User::factory()->create(['role' => 'ventanilla']);

        $this->actingAs($ventanilla)
            ->post(route('alumnos.store'), [
                'codigo' => 'TEST123',
                'nombre_completo' => 'Prueba Apellido',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('alumnos', ['codigo' => 'TEST123']);
    }

    private function pngMinimo(): string
    {
        // PNG 1x1 transparente
        return base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=');
    }
}
