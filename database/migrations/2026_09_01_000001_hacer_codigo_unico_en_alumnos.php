<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Primer paso del esquema: la matrícula deja de ser única y se amplía
        // para poder contener valores unificados ("mat1 / mat2 / ...").
        $indexes = array_column(DB::select('SHOW INDEX FROM alumnos'), 'Key_name');

        Schema::table('alumnos', function (Blueprint $table) use ($indexes) {
            if (in_array('alumnos_matricula_unique', $indexes)) {
                $table->dropUnique('alumnos_matricula_unique');
            }

            $table->string('matricula', 255)->nullable()->change();
        });

        // Unificar los alumnos que comparten código (no. de control).
        $this->unificarAlumnosPorCodigo();

        // Segundo paso del esquema: el código pasa a ser el identificador único.
        Schema::table('alumnos', function (Blueprint $table) {
            $table->string('codigo', 30)->unique()->change();
        });
    }

    public function down(): void
    {
        $indexes = array_column(DB::select('SHOW INDEX FROM alumnos'), 'Key_name');

        Schema::table('alumnos', function (Blueprint $table) use ($indexes) {
            if (in_array('alumnos_codigo_unique', $indexes)) {
                $table->dropUnique('alumnos_codigo_unique');
            }

            $table->string('matricula', 30)->nullable()->unique()->change();
        });
    }

    /**
     * Unifica los alumnos que comparten el mismo código (no. de control).
     * Se conserva el registro más antiguo, se juntan sus matrículas en un solo
     * campo ("mat1 / mat2") y se reasignan documentos y firmas al registro que
     * permanece antes de eliminar los duplicados.
     */
    private function unificarAlumnosPorCodigo(): void
    {
        $codigosDuplicados = DB::table('alumnos')
            ->select('codigo')
            ->whereNotNull('codigo')
            ->where('codigo', '<>', '')
            ->groupBy('codigo')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('codigo');

        if ($codigosDuplicados->isEmpty()) {
            return;
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        foreach ($codigosDuplicados->chunk(500) as $loteCodigos) {
            $grupos = DB::table('alumnos')
                ->whereIn('codigo', $loteCodigos)
                ->orderBy('id')
                ->get()
                ->groupBy('codigo');

            foreach ($grupos as $codigo => $filas) {
                if ($filas->count() < 2) {
                    continue;
                }

                // El de menor id es el canónico.
                $canonical = $filas->first();
                $otros = $filas->slice(1);

                $matriculas = $filas
                    ->pluck('matricula')
                    ->filter(fn ($m) => $m !== null && trim((string) $m) !== '')
                    ->map(fn ($m) => trim((string) $m))
                    ->unique()
                    ->sort()
                    ->values();

                $update = ['matricula' => $matriculas->implode(' / ')];

                // Completar campos vacíos del registro canónico con los de otros.
                foreach (['nombre_completo', 'carrera', 'ciclo_ingreso', 'status'] as $campo) {
                    if ($canonical->{$campo} === null || trim((string) $canonical->{$campo}) === '') {
                        foreach ($otros as $otro) {
                            if ($otro->{$campo} !== null && trim((string) $otro->{$campo}) !== '') {
                                $update[$campo] = $otro->{$campo};
                                break;
                            }
                        }
                    }
                }

                // Reasignar documentos y firmas al registro que permanece.
                $otrosIds = $otros->pluck('id');
                DB::table('documentos')->whereIn('alumno_id', $otrosIds)->update(['alumno_id' => $canonical->id]);
                DB::table('firmas')->whereIn('alumno_id', $otrosIds)->update(['alumno_id' => $canonical->id]);

                DB::table('alumnos')->where('id', $canonical->id)->update($update);
                DB::table('alumnos')->whereIn('id', $otrosIds)->delete();
            }
        }

        // Regenerar folios de los alumnos afectados por si dos registros que se
        // unificaron tenían folios iguales (<codigo>-<secuencia>).
        $idsConDocumentos = DB::table('documentos')->distinct()->pluck('alumno_id');
        foreach ($idsConDocumentos as $alumnoId) {
            $codigo = DB::table('alumnos')->where('id', $alumnoId)->value('codigo') ?: 'SINCOD';
            $secuencia = 0;
            $documentos = DB::table('documentos')->where('alumno_id', $alumnoId)->orderBy('id')->get();
            foreach ($documentos as $documento) {
                $secuencia++;
                DB::table('documentos')->where('id', $documento->id)->update([
                    'folio' => $codigo . '-' . $secuencia,
                ]);
            }
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
};