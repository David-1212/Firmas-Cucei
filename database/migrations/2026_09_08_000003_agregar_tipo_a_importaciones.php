<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Indica qué se importa: 'alumnos' (listado de alumnos) o 'documentos'
        // (credenciales). Se mantiene default 'alumnos' para históricos.
        Schema::table('importaciones', function (Blueprint $table) {
            $table->string('tipo')->default('alumnos')->index()->after('ciclo');
        });
    }

    public function down(): void
    {
        Schema::table('importaciones', function (Blueprint $table) {
            $table->dropColumn('tipo');
        });
    }
};