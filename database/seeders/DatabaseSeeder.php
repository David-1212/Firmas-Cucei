<?php

namespace Database\Seeders;

use App\Models\Alumno;
use App\Models\TipoDocumento;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Usuario administrador inicial
        $admin = User::firstOrCreate(
            ['email' => 'admin@cucei.com'],
            [
                'name' => 'Administrador',
                'password' => 'Admin12345',
                'role' => 'admin',
                'activo' => true,
            ]
        );

        if (!$admin->wasRecentlyCreated && $admin->role !== 'admin') {
            $admin->update(['role' => 'admin', 'activo' => true]);
        }

        // Usuario de ventanilla inicial
        $ventanilla = User::firstOrCreate(
            ['email' => 'ventanilla@cucei.com'],
            [
                'name' => 'Ventanilla',
                'password' => 'Ventanilla12345',
                'role' => 'ventanilla',
                'activo' => true,
            ]
        );

        // Tipos de documento iniciales
        $tipos = [
            'Constancia de estudios',
            'Carta de pasante',
            'Carta de liberación de servicio social',
            'Constancia de inscripción',
            'Carta de recomendación',
        ];

        foreach ($tipos as $tipo) {
            TipoDocumento::firstOrCreate(
                ['nombre' => $tipo],
                ['activo' => true]
            );
        }

        // Alumnos de ejemplo con datos aleatorios (solo si no hay ninguno)
        if (Alumno::count() === 0) {
            Alumno::factory()->count(25)->create();
        }
    }
}
