<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documentos', function (Blueprint $table) {
            $table->string('codigo_alumno')->nullable()->after('alumno_id');
            $table->index('codigo_alumno');
        });

        // Relleno: copiar el código del alumno a sus documentos actuales, para
        // que el re-link por código funcione también sobre datos existentes.
        DB::statement(
            'UPDATE documentos d INNER JOIN alumnos a ON a.id = d.alumno_id ' .
            'SET d.codigo_alumno = a.codigo WHERE d.codigo_alumno IS NULL'
        );
    }

    public function down(): void
    {
        Schema::table('documentos', function (Blueprint $table) {
            $table->dropIndex(['codigo_alumno']);
            $table->dropColumn('codigo_alumno');
        });
    }
};