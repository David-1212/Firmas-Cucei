<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $indexes = array_column(DB::select('SHOW INDEX FROM alumnos'), 'Key_name');

        Schema::table('alumnos', function (Blueprint $table) use ($indexes) {
            // La matrícula es el identificador único real; el código deja de serlo.
            if (in_array('alumnos_codigo_unique', $indexes)) {
                $table->dropUnique('alumnos_codigo_unique');
            }

            $table->string('matricula', 30)->nullable()->unique()->change();
        });
    }

    public function down(): void
    {
        Schema::table('alumnos', function (Blueprint $table) {
            $table->dropUnique('alumnos_matricula_unique');
            $table->string('codigo', 30)->unique();
        });
    }
};
