<?php

namespace App\Jobs;

use App\Models\Alumno;
use App\Models\Documento;
use App\Models\Importacion;
use App\Models\TipoDocumento;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ProcesarLoteDocumentos implements ShouldQueue
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
        if (!$importacion || $importacion->tipo !== 'documentos') {
            return;
        }

        $credencial = TipoDocumento::fijos()->where('nombre', 'Credencial')->first();
        if (!$credencial) {
            $importacion->update([
                'estado' => 'fallido',
                'mensaje_error' => 'No existe el tipo de documento "Credencial".',
            ]);
            return;
        }

        $insertadas = 0;
        $duplicadas = 0;
        $errores = 0;

        // Mapa codigo => alumno del ÚLTIMO ciclo importado, que es el listado de
        // trabajo. Un mismo código puede existir en varios ciclos.
        $ultimoCiclo = Importacion::where('ciclo', '!=', '')
            ->whereNotNull('ciclo')
            ->latest('id')
            ->value('ciclo');

        $codigos = array_values(array_unique(array_filter(array_map(
            fn ($f) => trim($f['codigo'] ?? ''),
            $this->filas
        ))));

        $alumnosPorCodigo = [];
        if (!empty($codigos) && $ultimoCiclo) {
            $alumnosPorCodigo = Alumno::query()
                ->whereIn('codigo', $codigos)
                ->whereHas('importaciones', fn ($q) => $q->where('importaciones.ciclo', $ultimoCiclo))
                ->get(['id', 'codigo'])
                ->groupBy('codigo')
                ->map(fn ($g) => $g->first()->id)
                ->all();
        }

        foreach ($this->filas as $fila) {
            $codigo = trim($fila['codigo'] ?? '');
            $uniqueId = trim($fila['unique_id'] ?? '');

            if ($codigo === '' && $uniqueId === '') {
                continue;
            }

            $alumnoId = $alumnosPorCodigo[$codigo] ?? null;
            if ($alumnoId === null) {
                $errores++;
                continue;
            }

            // Si el documento (UNIQUE ID) ya existe y está firmado o pendiente,
            // no se duplica ni se sobreescribe. Reutilizar el registro existente.
            $existente = Documento::where('folio', $uniqueId)->first();
            if ($existente) {
                if ($existente->alumno_id === null) {
                    // El alumno fue eliminado y el documento quedó huérfano:
                    // re-vincularlo al alumno del ciclo actual (mismo código).
                    $existente->update(['alumno_id' => $alumnoId, 'codigo_alumno' => $codigo]);
                    $duplicadas++;
                    continue;
                }
                if ($existente->alumno_id !== $alumnoId) {
                    // La credencial ya pertenece a otro alumno: no se toca.
                    $duplicadas++;
                    continue;
                }
                if ($existente->codigo_alumno !== $codigo) {
                    $existente->update(['codigo_alumno' => $codigo]);
                }
                $duplicadas++;
                continue;
            }

            try {
                Documento::create([
                    'alumno_id' => $alumnoId,
                    'codigo_alumno' => $codigo,
                    'tipo_documento_id' => $credencial->id,
                    'folio' => $uniqueId,
                    'observaciones' => 'Importada por CSV',
                    'estado' => 'pendiente',
                    'fecha' => now(),
                    'user_id' => auth()->id() ?? $importacion->user_id,
                ]);
                $insertadas++;
            } catch (\Throwable $e) {
                $errores++;
            }
        }

        $importacion->increment('insertadas', $insertadas);
        $importacion->increment('duplicadas', $duplicadas);
        $importacion->increment('errores', $errores);
        $importacion->increment('procesadas', count($this->filas));

        $ultimos = collect($this->filas)->take(-40)->map(fn ($f) => [
            'codigo' => $f['codigo'] ?? '',
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
    }
}