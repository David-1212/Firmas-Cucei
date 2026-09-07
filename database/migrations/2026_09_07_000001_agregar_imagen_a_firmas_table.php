<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('firmas', function (Blueprint $table) {
            $table->string('ruta_imagen')->nullable()->default(null)->change();
            $table->longText('imagen')->nullable()->after('ruta_imagen')->comment('Imagen de la firma en base64 (PNG)');
        });
    }

    public function down(): void
    {
        Schema::table('firmas', function (Blueprint $table) {
            $table->dropColumn('imagen');
            $table->string('ruta_imagen')->nullable(false)->change();
        });
    }
};