<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BuscarAlumnosTest extends TestCase
{
    public function test_buscar_alumnos_returns_json_partial_match(): void
    {
        \App\Models\Alumno::factory()->create([
            'codigo' => '323066222',
            'nombre_completo' => 'Ursula Schowalter Braun',
        ]);

        $user = User::factory()->create(['role' => 'admin']);

        // Un término con dígitos se trata como código y se busca por PREFIJO
        // (aprovecha el índice en alumnos.codigo).
        $response = $this->actingAs($user)->getJson('/documentos/buscar-alumnos?q=323');

        $response->assertStatus(200);
        $response->assertJsonFragment(['codigo' => '323066222']);

        $parcial = $this->actingAs($user)->getJson('/documentos/buscar-alumnos?q=323066');
        $parcial->assertStatus(200);
        $parcial->assertJsonFragment(['codigo' => '323066222']);
    }
}
