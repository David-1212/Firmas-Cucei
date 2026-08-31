<?php

namespace Database\Factories;

use App\Models\Alumno;
use App\Models\TipoDocumento;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Documento>
 */
class DocumentoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'alumno_id' => Alumno::factory(),
            'tipo_documento_id' => TipoDocumento::factory(),
            'folio' => 'F-' . $this->faker->unique()->numberBetween(1000, 9999),
            'estado' => 'pendiente',
            'fecha' => now(),
        ];
    }
}
