<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\Documento;
use App\Models\Firma;
use App\Models\TipoDocumento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PreviewFirmaDocumentosTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_indice_de_documentos_muestra_el_preview_de_la_firma(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $tipo = TipoDocumento::factory()->create();
        $alumno = Alumno::factory()->create(['codigo' => '101010101']);

        $doc = Documento::factory()->create([
            'alumno_id' => $alumno->id,
            'tipo_documento_id' => $tipo->id,
            'folio' => '101010101-1',
            'estado' => 'firmado',
        ]);

        $ruta = 'firmas/101010101/' . $doc->id . '.png';
        if (!is_dir(public_path(dirname($ruta)))) {
            mkdir(public_path(dirname($ruta)), 0755, true);
        }
        file_put_contents(public_path($ruta), 'firma');

        Firma::factory()->create([
            'alumno_id' => $alumno->id,
            'documento_id' => $doc->id,
            'ruta_imagen' => $ruta,
        ]);

        $response = $this->actingAs($admin)->get(route('documentos.index'));

        $response->assertOk();
        $response->assertSee($ruta, false);
    }

    public function test_el_indice_de_documentos_muestra_sin_firma(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $tipo = TipoDocumento::factory()->create();
        $alumno = Alumno::factory()->create(['codigo' => '202020202']);

        Documento::factory()->create([
            'alumno_id' => $alumno->id,
            'tipo_documento_id' => $tipo->id,
            'folio' => '202020202-1',
            'estado' => 'pendiente',
        ]);

        $response = $this->actingAs($admin)->get(route('documentos.index'));

        $response->assertOk();
        $response->assertSee('Sin firma');
    }
}