<?php

namespace Database\Factories;

use App\Models\Documento;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Firma>
 */
class FirmaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'documento_id' => Documento::factory(),
            'ruta_imagen' => 'firmas/test/' . $this->faker->unique()->numberBetween(1000, 9999) . '.png',
            'formato' => 'png',
        ];
    }
}