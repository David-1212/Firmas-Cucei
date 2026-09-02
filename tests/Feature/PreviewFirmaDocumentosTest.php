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

class PreviewFirmaDocumentosTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_indice_de_documentos_muestra_el_preview_de_la_firma(): void
    {
        Storage::fake('public');

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
        Storage::disk('public')->put($ruta, 'firma');

        Firma::factory()->create([
            'alumno_id' => $alumno->id,
            'documento_id' => $doc->id,
            'ruta_imagen' => $ruta,
        ]);

        $response = $this->actingAs($admin)->get(route('documentos.index'));

        $response->assertOk();
        $response->assertSee('storage/' . $ruta, false);
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