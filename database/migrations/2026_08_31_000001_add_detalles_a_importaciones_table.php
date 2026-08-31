<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('importaciones', function (Blueprint $table) {
            $table->json('duplicados_detalle')->nullable()->after('ultimos_codigos');
            $table->json('errores_detalle')->nullable()->after('duplicados_detalle');
        });
    }

    public function down(): void
    {
        Schema::table('importaciones', function (Blueprint $table) {
            $table->dropColumn(['duplicados_detalle', 'errores_detalle']);
        });
    }
};
