<?php

namespace App\Jobs;

use App\Models\Importacion;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcesarImportacionCsv implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 1200;
    public $tries = 1;

    public int $importacionId;

    public function __construct(int $importacionId)
    {
        $this->importacionId = $importacionId;
    }

    public function handle(): void
    {
        $importacion = Importacion::findOrFail($this->importacionId);
        $archivo = storage_path('app/private/importaciones/' . $importacion->archivo);

        if (!file_exists($archivo)) {
            $importacion->update([
                'estado' => 'fallido',
                'mensaje_error' => 'El archivo ya no existe en el servidor.',
            ]);
            return;
        }

        $importacion->update(['estado' => 'procesando']);

        $lote = [];
        $chunkSize = (int)(config('import.csv_chunk_size', 2000));
        $total = 0;
        $initialized = false;
        $nuevoJob = null;
        $linea = 0;
        $erroresDetalle = [];
        // Mapa matricula => registro. Un mismo alumno puede aparecer en varios
        // registros (maestría y licenciatura) con códigos distintos: se unifican.
        $porMatricula = [];

        // Línea por línea para no cargar todo en memoria
        if (($fh = fopen($archivo, 'r')) !== false) {
            while (($fila = fgetcsv($fh, 0, ',', '"', '\\')) !== false) {
                $linea++;
                // Encabezados
                if (!$initialized) {
                    $initialized = true;

                    $encabezado = array_map(function ($h) {
                        // Quitar BOM UTF-8 (EF BB BF) si viene al inicio del primer encabezado
                        $h = preg_replace('/^\xEF\xBB\xBF/', '', $h);
                        return mb_strtolower(trim($h));
                    }, $fila);

                    $mapeo = $this->mapearEncabezados($encabezado);
                    if ($mapeo === null) {
                        // Sin encabezados conocidos, asumir orden estándar
                        $mapeo = ['matricula', 'codigo', 'nombre_completo', 'carrera', 'ciclo_ingreso', 'status'];
                    }

                    continue;
                }

                $registro = $this->construirRegistro($fila, $mapeo ?? ['matricula', 'codigo', 'nombre_completo', 'carrera', 'ciclo_ingreso', 'status']);
                if ($registro === null) {
                    $importacion->increment('errores');
                    $erroresDetalle[$linea] = [
                        'matricula' => trim((string)($fila[0] ?? '')),
                        'nombre' => trim((string)($fila[2] ?? '')),
                        'motivo' => 'Fila inválida o incompleta',
                    ];
                    continue;
                }

                $matricula = $registro['matricula'];
                if (isset($porMatricula[$matricula])) {
                    // Misma matrícula: unificar códigos de alumno (licenciatura + maestría).
                    $existente = &$porMatricula[$matricula];
                    $existente['codigo'] = $this->unificarCodigos($existente['codigo'], $registro['codigo']);
                    // Completar campos vacíos con los del segundo registro.
                    foreach (['nombre_completo', 'carrera', 'ciclo_ingreso', 'status'] as $campo) {
                        if (($existente[$campo] === null || $existente[$campo] === '') && !empty($registro[$campo])) {
                            $existente[$campo] = $registro[$campo];
                        }
                    }
                    unset($existente);
                } else {
                    $porMatricula[$matricula] = $registro;
                }
                $total++;
            }
            fclose($fh);
        }

        // Despachar las matrículas ya unificadas en lotes (evita cargar todo el
        // proceso en un solo job y garantiza la unificación de códigos completa).
        if (count($porMatricula) > 0) {
            foreach (array_chunk(array_values($porMatricula), $chunkSize) as $lote) {
                ProcesarLoteAlumnos::dispatch($lote, $importacion->id);
            }
        }

        $importacion->update([
            'total_filas' => $total,
            'estado' => 'completado',
        ]);

        if ($erroresDetalle) {
            $importacion->forceFill([
                'errores_detalle' => array_merge($importacion->errores_detalle ?? [], array_values($erroresDetalle)),
            ])->save();
        }

        // Limpiar archivo temporal
        if (file_exists($archivo)) {
            @unlink($archivo);
        }
    }

    private function mapearEncabezados(array $encabezado): ?array
    {
        $posibles = [
            'matricula' => ['matricula', 'matrícula', 'mat', 'no. matricula', 'nomatricula'],
            'codigo' => ['codigo de alumno', 'codigo de alumno', 'codigo', 'código', 'codigo alumno', 'code', 'clave', 'no. control', 'nocontrol', 'folio'],
            'nombre_completo' => ['nombre completo', 'nombrecompleto', 'nombre', 'name', 'nombres', 'nombre y apellidos', 'alumno'],
            'carrera' => ['carrera', 'programa', 'licenciatura', 'ingenieria'],
            'ciclo_ingreso' => ['ciclo de ingreso', 'ciclodeingreso', 'ciclo', 'ciclo ingreso', 'cicloingreso', 'ciclo_ingreso', 'generacion', 'ingreso'],
            'status' => ['status', 'estatus', 'estado', 'stat'],
        ];

        $mapeo = array_fill(0, count($encabezado), null);
        $usados = [];

        foreach ($encabezado as $idx => $h) {
            foreach ($posibles as $campo => $alias) {
                if (in_array($h, $alias, true) && !in_array($campo, $usados, true)) {
                    $mapeo[$idx] = $campo;
                    $usados[] = $campo;
                    break;
                }
            }
        }

        // Al menos necesitamos codigo y nombre completo
        if (!in_array('codigo', $usados, true) || !in_array('nombre_completo', $usados, true)) {
            return null;
        }

        return $mapeo;
    }

    private function construirRegistro(array $fila, array $mapeo): ?array
    {
        $registro = [
            'matricula' => null,
            'codigo' => null,
            'nombre_completo' => null,
            'carrera' => null,
            'ciclo_ingreso' => null,
            'status' => null,
        ];

        foreach ($fila as $idx => $valor) {
            $campo = $mapeo[$idx] ?? null;
            if ($campo === null) {
                continue;
            }
            $valor = trim((string)$valor);
            if ($valor !== '') {
                $registro[$campo] = $valor;
            }
        }

        // Validación mínima: si no se pudieron detectar los encabezados,
        // usar posición fija [matricula, codigo, nombre_completo, carrera, ciclo_ingreso, status]
        if ($registro['matricula'] === null && isset($fila[0])) {
            $registro['matricula'] = trim((string)($fila[0] ?? ''));
            $registro['codigo'] = trim((string)($fila[1] ?? ''));
            $registro['nombre_completo'] = trim((string)($fila[2] ?? ''));
            $registro['carrera'] = trim((string)($fila[3] ?? ''));
            $registro['ciclo_ingreso'] = trim((string)($fila[4] ?? ''));
            $registro['status'] = trim((string)($fila[5] ?? ''));
        }

        if ($registro['matricula'] === null || $registro['matricula'] === '' || $registro['nombre_completo'] === '') {
            return null;
        }

        return $registro;
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

        // Evitar duplicar un código que ya está presente
        $partes = array_map('trim', preg_split('/\s*\/\s*|\s*;\s*/', $existente));
        if (in_array(strtoupper($nuevo), array_map('strtoupper', $partes), true)) {
            return $existente;
        }

        return $existente . ' / ' . $nuevo;
    }
}
