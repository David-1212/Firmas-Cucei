<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alumnos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 30)->unique()->index();
            $table->string('nombre');
            $table->string('apellido_paterno')->index();
            $table->string('apellido_materno')->nullable()->index();
            $table->string('email')->nullable();
            $table->string('carrera')->nullable()->index();
            $table->integer('semestre')->nullable();
            $table->timestamps();

            $table->fullText(['nombre', 'apellido_paterno', 'apellido_materno', 'codigo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alumnos');
    }
};
