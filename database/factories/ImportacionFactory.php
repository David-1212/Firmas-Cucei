<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Importacion>
 */
class ImportacionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'archivo' => null,
            'nombre_original' => 'alumnos.csv',
            'total_filas' => 0,
            'procesadas' => 0,
            'insertadas' => 0,
            'duplicadas' => 0,
            'errores' => 0,
            'estado' => 'pendiente',
        ];
    }
}