<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Backfill de folios faltantes para documentos existentes
        $documentos = DB::table('documentos')
            ->whereNull('folio')
            ->orWhere('folio', '')
            ->get();

        if ($documentos->count()) {
            $alumnoCodigo = DB::table('alumnos')->pluck('codigo', 'id');

            $conteo = [];
            foreach ($documentos as $doc) {
                $codigo = $alumnoCodigo[$doc->alumno_id] ?? 'SINCOD';
                $conteo[$doc->alumno_id] = ($conteo[$doc->alumno_id] ?? 0) + 1;
                $folio = $codigo . '-' . $conteo[$doc->alumno_id];
                DB::table('documentos')->where('id', $doc->id)->update(['folio' => $folio]);
            }
        }

        // Garantizar que no existan folios duplicados antes del índice único.
        // (En teoría el backfill ya generó folios únicos por alumno con secuencia propia.)
        Schema::table('documentos', function (Blueprint $table) {
            $table->unique('folio');
        });
    }

    public function down(): void
    {
        Schema::table('documentos', function (Blueprint $table) {
            $table->dropUnique(['folio']);
        });
    }
};
