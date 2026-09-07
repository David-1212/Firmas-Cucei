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
            'ruta_imagen' => '',
            'imagen' => base64_encode($this->faker->sha256()),
            'formato' => 'png',
        ];
    }
}