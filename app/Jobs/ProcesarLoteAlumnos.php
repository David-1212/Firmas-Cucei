<?php

namespace App\Jobs;

use App\Models\Alumno;
use App\Models\Importacion;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

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
        $duplicadas = 0;
        $errores = 0;
        $duplicadosDetalle = [];
        $erroresDetalle = [];

        $existentes = Alumno::whereIn('matricula', array_column($this->filas, 'matricula'))
            ->pluck('codigo', 'matricula');

        $vistosEnLote = [];
        $nuevos = [];
        $actualizaCodigo = [];
        foreach ($this->filas as $fila) {
            try {
                $matricula = $fila['matricula'];
                $codigo = $fila['codigo'] ?? null;

                // Ya existe en BD: en lugar de descartarlo, unificar códigos si difieren
                // (un alumno con doble carrera comparte matrícula pero tiene códigos distintos).
                if ($existentes->has($matricula)) {
                    $codigoExistente = $existentes->get($matricula);
                    $unificado = $this->unificarCodigos($codigoExistente, $codigo);
                    if ($unificado !== $codigoExistente) {
                        $actualizaCodigo[$matricula] = $unificado;
                    }
                    $duplicadas++;
                    $duplicadosDetalle[] = [
                        'matricula' => $matricula,
                        'nombre' => $fila['nombre_completo'] ?? '',
                        'codigo' => $codigo,
                    ];
                    continue;
                }
                // Ya se agregó en este lote (misma matrícula repetida): unificar código
                if (isset($vistosEnLote[$matricula])) {
                    $actualizaCodigo[$matricula] = $this->unificarCodigos(
                        $nuevos[$vistosEnLote[$matricula]]['codigo'],
                        $codigo
                    );
                    $duplicadas++;
                    $duplicadosDetalle[] = [
                        'matricula' => $matricula,
                        'nombre' => $fila['nombre_completo'] ?? '',
                        'codigo' => $codigo,
                    ];
                    continue;
                }
                $vistosEnLote[$matricula] = count($nuevos);
                $nuevos[] = [
                    'matricula' => $matricula,
                    'codigo' => $codigo,
                    'nombre_completo' => $fila['nombre_completo'],
                    'carrera' => $fila['carrera'] ?? null,
                    'ciclo_ingreso' => $fila['ciclo_ingreso'] ?? null,
                    'status' => $fila['status'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            } catch (\Throwable $e) {
                $errores++;
                $erroresDetalle[] = [
                    'matricula' => $fila['matricula'] ?? '',
                    'nombre' => $fila['nombre_completo'] ?? '',
                    'motivo' => $e->getMessage(),
                ];
            }
        }

        // Aplicar la unificación de códigos sobre los registros nuevos del lote
        foreach ($actualizaCodigo as $matricula => $codigoUnificado) {
            if (isset($vistosEnLote[$matricula])) {
                $nuevos[$vistosEnLote[$matricula]]['codigo'] = $codigoUnificado;
            }
        }

        // Unificar códigos de alumnos que ya existían en BD (una matrícula puede
        // tener dos códigos: licenciatura + maestría).
        $cargarActualizados = array_filter($actualizaCodigo, fn ($m) => !isset($vistosEnLote[$m]), ARRAY_FILTER_USE_KEY);
        foreach ($cargarActualizados as $matricula => $codigoUnificado) {
            try {
                Alumno::where('matricula', $matricula)->update(['codigo' => $codigoUnificado]);
            } catch (\Throwable $e) {
                $errores++;
                $erroresDetalle[] = [
                    'matricula' => $matricula,
                    'nombre' => '',
                    'motivo' => 'No se pudo unificar código: ' . $e->getMessage(),
                ];
            }
        }

        // Sin duplicados internos, el insert en bloque no debería colisionar.
        // El reintento fila por fila queda solo como red de seguridad por si hay
        // carreras concurrentes sobre los mismos códigos.
        foreach (array_chunk($nuevos, 500) as $lote) {
            try {
                Alumno::insert($lote);
                $insertadas += count($lote);
            } catch (\Throwable $e) {
                foreach ($lote as $fila) {
                    try {
                        unset($fila['created_at'], $fila['updated_at']);
                        Alumno::create($fila);
                        $insertadas++;
                    } catch (\Throwable $e2) {
                        $errores++;
                        $erroresDetalle[] = [
                            'matricula' => $fila['matricula'] ?? '',
                            'nombre' => $fila['nombre_completo'] ?? '',
                            'motivo' => $e2->getMessage(),
                        ];
                    }
                }
            }
        }

        $importacion->increment('insertadas', $insertadas);
        $importacion->increment('duplicadas', $duplicadas);
        $importacion->increment('errores', $errores);
        $importacion->increment('procesadas', count($this->filas));

        $ultimos = collect($nuevos)->take(-40)->map(fn ($f) => [
            'codigo' => $f['codigo'],
            'nombre' => $f['nombre_completo'] ?? '',
        ])->values()->all();

        $importacion->forceFill([
            'ultimos_codigos' => $ultimos,
            'duplicados_detalle' => array_merge($importacion->duplicados_detalle ?? [], $duplicadosDetalle),
            'errores_detalle' => array_merge($importacion->errores_detalle ?? [], $erroresDetalle),
        ])->save();
    }

    private function unificarCodigos(?string $existente, ?string $nuevo): ?string
    {
        $existente = trim((string)($existente ?? ''));
        $nuevo = trim((string)($nuevo ?? ''));

        if ($nuevo === '') {
            return $existente !== '' ? $existente : null;
        }
        if ($existente === '') {
            return $nuevo;
        }

        $partes = array_map('trim', preg_split('/\s*\/\s*|\s*;\s*/', $existente));
        if (in_array(strtoupper($nuevo), array_map('strtoupper', $partes), true)) {
            return $existente;
        }

        return $existente . ' / ' . $nuevo;
    }
}
