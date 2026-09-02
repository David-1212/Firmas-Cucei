<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\Documento;
use App\Models\TipoDocumento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FolioTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_folio_se_genera_automaticamente_con_datos_del_alumno(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $tipo = TipoDocumento::factory()->create();
        $alumno = Alumno::factory()->create(['codigo' => '111111111']);

        $this->actingAs($admin)->post(route('documentos.store'), [
            'alumno_id' => $alumno->id,
            'tipo_documento_id' => $tipo->id,
        ])->assertRedirect();

        $doc = Documento::where('alumno_id', $alumno->id)->first();
        $this->assertNotNull($doc->folio);
        $this->assertEquals('111111111-' . $alumno->id . '-1', $doc->folio);
    }

    public function test_los_folios_son_unicos_para_el_mismo_alumno(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $tipo = TipoDocumento::factory()->create();
        $alumno = Alumno::factory()->create(['codigo' => '222333444']);

        $this->actingAs($admin);

        $this->post(route('documentos.store'), [
            'alumno_id' => $alumno->id,
            'tipo_documento_id' => $tipo->id,
        ]);

        $this->post(route('documentos.store'), [
            'alumno_id' => $alumno->id,
            'tipo_documento_id' => $tipo->id,
        ]);

        $folios = Documento::where('alumno_id', $alumno->id)->pluck('folio')->sort()->values();
        $this->assertEquals(['222333444-' . $alumno->id . '-1', '222333444-' . $alumno->id . '-2'], $folios->all());
        $this->assertCount(2, $folios->unique());
    }

    public function test_los_folios_son_unicos_entre_alumnos(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $tipo = TipoDocumento::factory()->create();
        $a1 = Alumno::factory()->create(['codigo' => '101010101']);
        $a2 = Alumno::factory()->create(['codigo' => '202020202']);

        $this->actingAs($admin);

        $this->post(route('documentos.store'), ['alumno_id' => $a1->id, 'tipo_documento_id' => $tipo->id]);
        $this->post(route('documentos.store'), ['alumno_id' => $a2->id, 'tipo_documento_id' => $tipo->id]);

        $folios = Documento::pluck('folio');
        $this->assertCount(2, $folios->unique());
        $this->assertContains('101010101-' . $a1->id . '-1', $folios);
        $this->assertContains('202020202-' . $a2->id . '-1', $folios);
    }

    public function test_el_endpoint_muestra_el_siguiente_folio(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $alumno = Alumno::factory()->create(['codigo' => '555555555']);

        $this->actingAs($admin);

        $this->getJson(route('documentos.siguienteFolio', ['alumno_id' => $alumno->id]))
            ->assertOk()
            ->assertJson(['folio' => '555555555-' . $alumno->id . '-1']);
    }

    public function test_el_folio_no_choca_con_documentos_huerfanos_del_mismo_codigo(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $tipo = TipoDocumento::factory()->create();
        $alumno = Alumno::factory()->create(['codigo' => '666666666']);

        // Documentos huérfanos (alumno eliminado y re-importado): ocupan folios 1 y 2.
        Documento::factory()->create(['alumno_id' => null, 'tipo_documento_id' => $tipo->id, 'folio' => '666666666-1']);
        Documento::factory()->create(['alumno_id' => null, 'tipo_documento_id' => $tipo->id, 'folio' => '666666666-2']);

        $this->actingAs($admin);

        // El endpoint debe sugerir el siguiente número libre, no colisionar.
        $this->getJson(route('documentos.siguienteFolio', ['alumno_id' => $alumno->id]))
            ->assertOk()
            ->assertJson(['folio' => '666666666-' . $alumno->id . '-3']);

        // Al crear el documento, se re-vinculan los huérfanos y el nuevo no choca.
        $this->post(route('documentos.store'), [
            'alumno_id' => $alumno->id,
            'tipo_documento_id' => $tipo->id,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('documentos', ['folio' => '666666666-' . $alumno->id . '-3', 'alumno_id' => $alumno->id]);
        $this->assertDatabaseHas('documentos', ['folio' => '666666666-1', 'alumno_id' => $alumno->id]);
        $this->assertDatabaseHas('documentos', ['folio' => '666666666-2', 'alumno_id' => $alumno->id]);
    }

    public function test_el_folio_usa_maximo_no_el_conteo_cuando_hay_huecos(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $tipo = TipoDocumento::factory()->create();
        $alumno = Alumno::factory()->create(['codigo' => '777777777']);

        // Documento 1 y 3 existen (el 2 se eliminó en el pasado).
        Documento::factory()->create(['alumno_id' => $alumno->id, 'tipo_documento_id' => $tipo->id, 'folio' => '777777777-1']);
        Documento::factory()->create(['alumno_id' => $alumno->id, 'tipo_documento_id' => $tipo->id, 'folio' => '777777777-3']);

        $this->actingAs($admin);

        $this->getJson(route('documentos.siguienteFolio', ['alumno_id' => $alumno->id]))
            ->assertOk()
            ->assertJson(['folio' => '777777777-' . $alumno->id . '-4']);
    }
}
