<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Alumno>
 */
class AlumnoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'matricula' => (string) $this->faker->unique()->numberBetween(100000000, 999999999),
            'codigo' => (string) $this->faker->unique()->numberBetween(200000000, 299999999),
            'nombre_completo' => $this->faker->name(),
            'carrera' => $this->faker->randomElement(['Ingeniería en Computación', 'Ingeniería Civil', 'Medicina', 'Derecho', 'Arquitectura']),
            'ciclo_ingreso' => $this->faker->randomElement(['2025-A', '2025-B', '2026-A']),
            'status' => $this->faker->randomElement(['Activo', 'Baja', 'Egresado']),
        ];
    }
}
