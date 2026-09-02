<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('alumno_importacion', function (Blueprint $table) {
            $table->index('importacion_id');
        });

        Schema::table('alumnos', function (Blueprint $table) {
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('alumno_importacion', function (Blueprint $table) {
            $table->dropIndex(['importacion_id']);
        });

        Schema::table('alumnos', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });
    }
};
