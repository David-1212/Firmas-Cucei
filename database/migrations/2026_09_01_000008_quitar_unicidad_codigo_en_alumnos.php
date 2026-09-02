<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Un mismo código (no. de control) puede repetirse: al importar un ciclo
        // nuevo se crea un registro de alumno distinto aunque sea la misma persona.
        $indexes = array_column(DB::select('SHOW INDEX FROM alumnos'), 'Key_name');

        Schema::table('alumnos', function (Blueprint $table) use ($indexes) {
            if (in_array('alumnos_codigo_unique', $indexes)) {
                $table->dropUnique('alumnos_codigo_unique');
            }
        });
    }

    public function down(): void
    {
        $indexes = array_column(DB::select('SHOW INDEX FROM alumnos'), 'Key_name');

        Schema::table('alumnos', function (Blueprint $table) use ($indexes) {
            if (!in_array('alumnos_codigo_unique', $indexes)) {
                $table->string('codigo', 30)->unique()->change();
            }
        });
    }
};