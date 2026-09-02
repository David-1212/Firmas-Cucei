<?php

namespace App\Jobs;

use App\Models\Alumno;
use App\Models\Documento;
use App\Models\Firma;
use App\Models\Importacion;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ProcesarLoteAlumnos implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300;

    public array $filas;
    public int $importacionId;

    public function __construct(array $filas, int $importacionId)
    {
        $this->filas = $filas;
        $this->importacionId = $importacionId;
    }

    public function handle(): void
    {
        $importacion = Importacion::find($this->importacionId);
        if (!$importacion) {
            return;
        }

        $insertadas = 0;
        $insertados = [];
        $primeroPorCodigo = [];

        // Cada fila del CSV se inserta como un registro de alumno nuevo, aunque
        // el código ya exista (misma persona en otro ciclo): los ciclos viven en
        // registros distintos y el listado se filtra por ciclo.
        foreach ($this->filas as $fila) {
            try {
                $alumno = Alumno::create([
                    'matricula' => $fila['matricula'] ?? null,
                    'codigo' => $fila['codigo'],
                    'nombre_completo' => $fila['nombre_completo'],
                    'carrera' => $fila['carrera'] ?? null,
                    'ciclo_ingreso' => $fila['ciclo_ingreso'] ?? null,
                    'status' => $fila['status'] ?? null,
                ]);
                $insertadas++;
                $insertados[] = $alumno->id;
                if (!isset($primeroPorCodigo[$alumno->codigo])) {
                    $primeroPorCodigo[$alumno->codigo] = $alumno->id;
                }
            } catch (\Throwable $e) {
                continue;
            }
        }

        // Re-vincular documentos (y firmas) que quedaron huérfanos al eliminar
        // al alumno: al volver a importar el mismo código, el folio del documento
        // (código-idAlumno-secuencia) se relaciona de nuevo con el nuevo registro.
        $this->religarDocumentosHuérfanos($primeroPorCodigo);

        // Vincular SOLO los registros insertados en este lote a la importación
        // (y por tanto a su ciclo): los registros de ciclos anteriores no se
        // tocan, así cada ciclo tiene sus propios registros.
        $this->vincularAlumnosAImportacion($insertados);

        $importacion->increment('insertadas', $insertadas);
        $importacion->increment('procesadas', count($this->filas));

        $ultimos = collect($this->filas)->take(-40)->map(fn ($f) => [
            'codigo' => $f['codigo'],
            'nombre' => $f['nombre_completo'] ?? '',
        ])->values()->all();

        $importacion->forceFill([
            'ultimos_codigos' => $ultimos,
        ])->save();

        // El último lote en terminar marca la importación como completada.
        $completado = false;
        DB::transaction(function () use ($importacion, &$completado) {
            $fila = DB::table('importaciones')->where('id', $importacion->id)->lockForUpdate()->first();
            $pendientes = ($fila->lotes_pendientes ?? 0) - 1;
            if ($pendientes <= 0) {
                DB::table('importaciones')->where('id', $importacion->id)->update([
                    'lotes_pendientes' => 0,
                    'estado' => 'completado',
                    'updated_at' => now(),
                ]);
                $completado = true;
            } else {
                DB::table('importaciones')->where('id', $importacion->id)->update([
                    'lotes_pendientes' => $pendientes,
                    'updated_at' => now(),
                ]);
            }
        });

        // Solo el último lote invalida y regenera el caché del listado, para que
        // el cache no se limpie entre medio mientras otros lotes siguen
        // insertando, y para dejar caliente el listado para la primera carga.
        if ($completado) {
            $importacion->regenerarCacheListado();
        }
    }

    /**
     * Al volver a importar un código que antes fue eliminado, re-vincula los
     * documentos (y sus firmas) que quedaron huérfanos (alumno_id NULL) al
     * registro del alumno reimportado, usando el folio del documento
     * (código-idAlumno-secuencia). El mapa es codigo => id del registro nuevo.
     */
    private function religarDocumentosHuérfanos(array $primeroPorCodigo): void
    {
        if (empty($primeroPorCodigo)) {
            return;
        }

        foreach ($primeroPorCodigo as $codigo => $alumnoId) {
            $documentos = Documento::whereNull('alumno_id')
                ->where('folio', 'like', $codigo . '-%')
                ->get(['id']);

            if ($documentos->isEmpty()) {
                continue;
            }

            $docIds = $documentos->pluck('id')->all();

            Documento::whereIn('id', $docIds)->update(['alumno_id' => $alumnoId]);
            Firma::whereIn('documento_id', $docIds)
                ->whereNull('alumno_id')
                ->update(['alumno_id' => $alumnoId]);
        }
    }

    /**
     * Relaciona los registros insertados en este lote con la importación/ciclo
     * actual. Cada ciclo conserva sus propios registros de alumno.
     */
    private function vincularAlumnosAImportacion(array $insertados): void
    {
        if (empty($insertados)) {
            return;
        }

        $ahora = now();
        $pivot = array_map(fn ($id) => [
            'alumno_id' => $id,
            'importacion_id' => $this->importacionId,
            'created_at' => $ahora,
            'updated_at' => $ahora,
        ], $insertados);

        DB::table('alumno_importacion')->insertOrIgnore($pivot);
    }
}