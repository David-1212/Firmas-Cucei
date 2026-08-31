<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('alumnos', function (Blueprint $table) {
            // Quitar índice full-text que referenciaba columnas que se eliminan
            $table->dropIndex('alumnos_nombre_apellido_paterno_apellido_materno_codigo_fulltext');

            // Eliminar columnas que ya no se usan
            $table->dropColumn(['nombre', 'apellido_paterno', 'apellido_materno', 'email', 'semestre']);

            // Nuevo esquema: matricula, codigo, nombre_completo, carrera, ciclo_ingreso, status
            $table->string('matricula')->nullable()->after('codigo');
            $table->string('nombre_completo')->nullable()->after('matricula');
            $table->string('ciclo_ingreso')->nullable()->after('carrera');
            $table->string('status')->nullable()->after('ciclo_ingreso');

            $table->fullText(['nombre_completo', 'codigo']);
        });
    }

    public function down(): void
    {
        Schema::table('alumnos', function (Blueprint $table) {
            $table->dropIndex('alumnos_nombre_completo_codigo_fulltext');
            $table->dropColumn(['matricula', 'nombre_completo', 'ciclo_ingreso', 'status']);
            $table->string('nombre');
            $table->string('apellido_paterno')->index();
            $table->string('apellido_materno')->nullable()->index();
            $table->string('email')->nullable();
            $table->integer('semestre')->nullable();
            $table->fullText(['nombre', 'apellido_paterno', 'apellido_materno', 'codigo']);
        });
    }
};
