<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Cada importación corresponde a un ciclo/semestre. El campo es obligatorio
        // al cargar, pero se mantiene nullable en BD para no romper históricos.
        Schema::table('importaciones', function (Blueprint $table) {
            $table->string('ciclo')->nullable()->after('nombre_original');
        });
    }

    public function down(): void
    {
        Schema::table('importaciones', function (Blueprint $table) {
            $table->dropColumn('ciclo');
        });
    }
};