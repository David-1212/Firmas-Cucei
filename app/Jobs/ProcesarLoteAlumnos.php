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
        $reutilizadas = 0;
        $insertados = [];
        $reutilizados = [];
        $primeroPorCodigo = [];

        // Alumnos de ESTE ciclo que ya tienen al menos una firma: al re-importar
        // un CSV con datos repetidos no se duplican ni se sobreescriben; se
        // reutiliza el registro existente (con sus documentos firmados) y se le
        // vincula a la importación actual.
        $firmadosEnCiclo = $this->alumnosFirmadosEnCiclo(
            array_column($this->filas, 'codigo'),
            $importacion->ciclo
        );

        foreach ($this->filas as $fila) {
            $codigo = $fila['codigo'] ?? '';
            if (isset($firmadosEnCiclo[$codigo])) {
                $reutilizados[] = $firmadosEnCiclo[$codigo];
                $reutilizadas++;
                continue;
            }

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

        // Vincular los registros de este lote a la importación (y por tanto a su
        // ciclo): los nuevos y los reutilizados (ya firmados en este ciclo). Los
        // registros de ciclos anteriores no se tocan.
        $this->vincularAlumnosAImportacion(array_merge($insertados, $reutilizados));

        $importacion->increment('insertadas', $insertadas);
        $importacion->increment('duplicadas', $reutilizadas);
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
     * Devuelve un mapa codigo => id del alumno que, en el ciclo indicado, ya
     * tiene al menos una firma registrada. Se usa para no duplicar ni
     * sobreescribir registros con documentos firmados al re-importar un CSV.
     */
    private function alumnosFirmadosEnCiclo(array $codigos, ?string $ciclo): array
    {
        $codigos = array_filter(array_map('trim', $codigos));
        if (empty($codigos) || $ciclo === null || $ciclo === '') {
            return [];
        }

        return Alumno::query()
            ->whereIn('codigo', $codigos)
            ->whereHas('importaciones', fn ($q) => $q->where('importaciones.ciclo', $ciclo))
            ->whereHas('firmas')
            ->get(['id', 'codigo'])
            ->groupBy('codigo')
            ->map(fn ($grupo) => $grupo->first()->id)
            ->all();
    }

    /**
     * Al volver a importar un código que antes fue eliminado, re-vincula los
     * documentos (y sus firmas) que quedaron huérfanos (alumno_id NULL) al
     * registro del alumno reimportado. Se casa por el código del alumno
     * guardado en el documento (codigo_alumno) y, como fallback para datos
     * viejos, por el prefijo del folio (código-idAlumno-secuencia).
     * El mapa es codigo => id del registro nuevo.
     */
    private function religarDocumentosHuérfanos(array $primeroPorCodigo): void
    {
        if (empty($primeroPorCodigo)) {
            return;
        }

        foreach ($primeroPorCodigo as $codigo => $alumnoId) {
            $documentos = Documento::whereNull('alumno_id')
                ->where(function ($q) use ($codigo) {
                    $q->where('codigo_alumno', $codigo)
                        ->orWhere('folio', 'like', $codigo . '-%');
                })
                ->get(['id']);

            if ($documentos->isEmpty()) {
                continue;
            }

            $docIds = $documentos->pluck('id')->all();

            Documento::whereIn('id', $docIds)->update(['alumno_id' => $alumnoId, 'codigo_alumno' => $codigo]);
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