<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FiltrosAlumnosTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 'ventanilla']);
    }

    private function tablaBody($html): string
    {
        $start = strpos($html, '<tbody');
        $end = strpos($html, '</tbody>');
        if ($start === false || $end === false) {
            return '';
        }
        return substr($html, $start, $end - $start + 8);
    }

    public function test_filtro_carrera(): void
    {
        Alumno::factory()->create(['carrera' => 'Ingeniería Civil']);
        Alumno::factory()->create(['carrera' => 'Derecho']);

        $response = $this->actingAs($this->user)->get('/alumnos?carrera=Ingeniería Civil');

        $response->assertOk();
        $response->assertSee('Ingeniería Civil');
        $this->assertStringNotContainsString('Derecho', $this->tablaBody($response->getContent()));
    }

    public function test_solo_muestra_los_del_ultimo_ciclo_registrado(): void
    {
        $imp2025B = \App\Models\Importacion::factory()->create(['ciclo' => '2025-B']);
        $imp2025A = \App\Models\Importacion::factory()->create(['ciclo' => '2025-A']);

        $a1 = Alumno::factory()->create();
        $a2 = Alumno::factory()->create();

        // El ciclo más reciente (por id) es 2025-A, aunque se haya creado después.
        $a1->importaciones()->attach($imp2025B);
        $a2->importaciones()->attach($imp2025A);

        // Sin parámetro ciclo: solo muestra los del último ciclo registrado (2025-A).
        $response = $this->actingAs($this->user)->get('/alumnos');

        $response->assertOk();
        $response->assertSee($imp2025A->ciclo);
        $this->assertStringContainsString($a2->nombre_completo, $this->tablaBody($response->getContent()));
        $this->assertStringNotContainsString($a1->nombre_completo, $this->tablaBody($response->getContent()));
    }

    public function test_filtro_ciclo_por_query_no_existe_y_muestra_el_ultimo(): void
    {
        $imp2025A = \App\Models\Importacion::factory()->create(['ciclo' => '2025-A']);
        $imp2025B = \App\Models\Importacion::factory()->create(['ciclo' => '2025-B']);
        $imp2025C = \App\Models\Importacion::factory()->create(['ciclo' => '2025-C']);

        $aA = Alumno::factory()->create();
        $aB = Alumno::factory()->create();
        $aC = Alumno::factory()->create();

        $aA->importaciones()->attach($imp2025A);
        $aB->importaciones()->attach($imp2025B);
        $aC->importaciones()->attach($imp2025C);

        // Aunque se pida ?ciclo=2025-A, el listado siempre muestra el último (2025-C).
        $response = $this->actingAs($this->user)->get('/alumnos?ciclo=2025-A');

        $response->assertOk();
        $this->assertStringContainsString($aC->nombre_completo, $this->tablaBody($response->getContent()));
        $this->assertStringNotContainsString($aA->nombre_completo, $this->tablaBody($response->getContent()));
        $this->assertStringNotContainsString($aB->nombre_completo, $this->tablaBody($response->getContent()));
    }

    public function test_filtro_status(): void
    {
        Alumno::factory()->create(['status' => 'Activo']);
        Alumno::factory()->create(['status' => 'Baja']);

        $response = $this->actingAs($this->user)->get('/alumnos?status=Baja');

        $response->assertOk();
        $response->assertSee('Baja');
        $this->assertStringNotContainsString('Activo', $this->tablaBody($response->getContent()));
    }

    public function test_filtros_combinados(): void
    {
        Alumno::factory()->create(['carrera' => 'Ingeniería Civil', 'status' => 'Activo']);
        Alumno::factory()->create(['carrera' => 'Derecho', 'status' => 'Activo']);
        Alumno::factory()->create(['carrera' => 'Ingeniería Civil', 'status' => 'Baja']);

        $response = $this->actingAs($this->user)->get('/alumnos?carrera=Ingeniería Civil&status=Activo');

        $response->assertOk();
        $body = $this->tablaBody($response->getContent());
        $this->assertStringContainsString('Ingeniería Civil', $body);
        $this->assertStringContainsString('Activo', $body);
        $this->assertStringNotContainsString('Baja', $body);
        $this->assertStringNotContainsString('Derecho', $body);
    }

    public function test_opciones_select_carrera(): void
    {
        Alumno::factory()->create(['carrera' => 'Ingeniería en Computación']);
        Alumno::factory()->create(['carrera' => 'Ingeniería en Computación']); // duplicada → solo 1 opción

        $response = $this->actingAs($this->user)->get('/alumnos');

        $response->assertOk();
        $response->assertSee('Ingeniería en Computación');
        $response->assertSee('Todas las carreras');
    }

    public function test_opciones_select_status(): void
    {
        Alumno::factory()->create(['status' => 'Activo']);

        $response = $this->actingAs($this->user)->get('/alumnos');

        $response->assertOk();
        $response->assertSee('Activo');
        $response->assertSee('Todos los status');
    }

    public function test_filtros_se_preservan_en_paginacion(): void
    {
        Alumno::factory()->count(25)->create(['carrera' => 'Filtrada']);

        $response = $this->actingAs($this->user)->get('/alumnos?carrera=Filtrada');

        $response->assertOk();
        $response->assertSee('carrera=Filtrada');
    }

    public function test_filtros_vacios_devuelven_todos(): void
    {
        Alumno::factory()->count(3)->create();

        $response = $this->actingAs($this->user)->get('/alumnos');

        $response->assertOk();
        $body = $this->tablaBody($response->getContent());
        // Deben aparecer 3 filas (3 `<tr class="hover:`)
        $this->assertStringContainsString('hover:bg-brand-50/40 transition', $body);
    }
}
