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
        $this->assertEquals('111111111-1', $doc->folio);
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
        $this->assertEquals(['222333444-1', '222333444-2'], $folios->all());
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
        $this->assertContains('101010101-1', $folios);
        $this->assertContains('202020202-1', $folios);
    }

    public function test_el_endpoint_muestra_el_siguiente_folio(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $alumno = Alumno::factory()->create(['codigo' => '555555555']);

        $this->actingAs($admin);

        $this->getJson(route('documentos.siguienteFolio', ['alumno_id' => $alumno->id]))
            ->assertOk()
            ->assertJson(['folio' => '555555555-1']);
    }
}
