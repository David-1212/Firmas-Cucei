<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Al eliminar un alumno, sus documentos y firmas deben conservarse
        // (alumno_id pasa a NULL) en lugar de borrarse en cascada.
        Schema::table('documentos', function (Blueprint $table) {
            $table->dropForeign(['alumno_id']);
        });
        Schema::table('documentos', function (Blueprint $table) {
            $table->unsignedBigInteger('alumno_id')->nullable()->change();
        });
        Schema::table('documentos', function (Blueprint $table) {
            $table->foreign('alumno_id')->references('id')->on('alumnos')->nullOnDelete();
        });

        Schema::table('firmas', function (Blueprint $table) {
            $table->dropForeign(['alumno_id']);
        });
        Schema::table('firmas', function (Blueprint $table) {
            $table->unsignedBigInteger('alumno_id')->nullable()->change();
        });
        Schema::table('firmas', function (Blueprint $table) {
            $table->foreign('alumno_id')->references('id')->on('alumnos')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('documentos', function (Blueprint $table) {
            $table->dropForeign(['alumno_id']);
        });
        Schema::table('documentos', function (Blueprint $table) {
            $table->unsignedBigInteger('alumno_id')->nullable(false)->change();
        });
        Schema::table('documentos', function (Blueprint $table) {
            $table->foreign('alumno_id')->references('id')->on('alumnos')->cascadeOnDelete();
        });

        Schema::table('firmas', function (Blueprint $table) {
            $table->dropForeign(['alumno_id']);
        });
        Schema::table('firmas', function (Blueprint $table) {
            $table->unsignedBigInteger('alumno_id')->nullable(false)->change();
        });
        Schema::table('firmas', function (Blueprint $table) {
            $table->foreign('alumno_id')->references('id')->on('alumnos')->cascadeOnDelete();
        });
    }
};