<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Relaciona cada alumno con las importaciones/ciclos en los que aparece.
        // Al volver a importar un ciclo, los alumnos ya existentes también se
        // vinculan a la importación (sin duplicar el registro del alumno), así
        // el listado de trabajo se puede filtrar por el ciclo cargado.
        Schema::create('alumno_importacion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alumno_id')->constrained('alumnos')->cascadeOnDelete();
            $table->foreignId('importacion_id')->constrained('importaciones')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['alumno_id', 'importacion_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alumno_importacion');
    }
};