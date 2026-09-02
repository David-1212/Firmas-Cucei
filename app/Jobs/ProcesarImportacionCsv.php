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

        $chunkSize = (int)(config('import.csv_chunk_size', 2000));
        $total = 0;
        $initialized = false;
        $linea = 0;
        // Mapa codigo => registro. Un mismo alumno (mismo código / no. de control)
        // puede aparecer en varios registros del archivo (licenciatura y maestría)
        // con matrículas distintas: se unifican en una sola fila y el registro se
        // inserta como nuevo para este ciclo.
        $porCodigo = [];

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
                        $sinEncabezados = true;
                    } else {
                        $sinEncabezados = false;
                    }

                    continue;
                }

                $registro = $this->construirRegistro($fila, $mapeo ?? ['matricula', 'codigo', 'nombre_completo', 'carrera', 'ciclo_ingreso', 'status'], $sinEncabezados ?? false);
                if ($registro === null) {
                    continue;
                }

                $codigo = $registro['codigo'];
                if (isset($porCodigo[$codigo])) {
                    // Mismo código: unificar el alumno (licenciatura + maestría)
                    // combinando matrículas, carreras y ciclos de ingreso.
                    $existente = &$porCodigo[$codigo];
                    $existente['matricula'] = $this->unificarValores($existente['matricula'], $registro['matricula']);
                    $existente['carrera'] = $this->unificarValores($existente['carrera'], $registro['carrera']);
                    $existente['ciclo_ingreso'] = $this->unificarValores($existente['ciclo_ingreso'], $registro['ciclo_ingreso']);
                    // Completar campos vacíos con los del segundo registro.
                    foreach (['nombre_completo', 'status'] as $campo) {
                        if (($existente[$campo] === null || $existente[$campo] === '') && !empty($registro[$campo])) {
                            $existente[$campo] = $registro[$campo];
                        }
                    }
                    unset($existente);
                } else {
                    $porCodigo[$codigo] = $registro;
                }
                $total++;
            }
            fclose($fh);
        }

        // Despachar los códigos ya unificados en lotes (evita cargar todo el
        // proceso en un solo job y garantiza la unificación de matrículas completa).
        $lotes = array_chunk(array_values($porCodigo), $chunkSize);

        if (count($lotes) === 0) {
            // Sin registros válidos: no hay lotes que procesar, terminar ya.
            $importacion->forceFill([
                'total_filas' => $total,
                'estado' => 'completado',
            ])->save();
            $importacion->regenerarCacheListado();
        } else {
            // Contar lotes pendientes: el último lote que termine marca la
            // importación como "completado", para que el progreso en vivo no
            // desaparezca antes de que terminen de procesarse los alumnos.
            $importacion->forceFill([
                'total_filas' => $total,
                'lotes_pendientes' => count($lotes),
            ])->save();

            foreach ($lotes as $lote) {
                ProcesarLoteAlumnos::dispatch($lote, $importacion->id);
            }
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

    private function construirRegistro(array $fila, array $mapeo, bool $sinEncabezados = false): ?array
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
        if ($sinEncabezados && $registro['matricula'] === null && isset($fila[0])) {
            $registro['matricula'] = trim((string)($fila[0] ?? ''));
            $registro['codigo'] = trim((string)($fila[1] ?? ''));
            $registro['nombre_completo'] = trim((string)($fila[2] ?? ''));
            $registro['carrera'] = trim((string)($fila[3] ?? ''));
            $registro['ciclo_ingreso'] = trim((string)($fila[4] ?? ''));
            $registro['status'] = trim((string)($fila[5] ?? ''));
        }

        if ($registro['codigo'] === null || $registro['codigo'] === '' || $registro['nombre_completo'] === '') {
            return null;
        }

        return $registro;
    }

    private function unificarValores(?string $existente, ?string $nuevo): ?string
    {
        $existente = trim((string)($existente ?? ''));
        $nuevo = trim((string)($nuevo ?? ''));

        if ($nuevo === '') {
            return $existente !== '' ? $existente : null;
        }
        if ($existente === '') {
            return $nuevo;
        }

        // Evitar duplicar una matrícula que ya está presente
        $partes = array_map('trim', preg_split('/\s*\/\s*|\s*;\s*/', $existente));
        if (in_array(strtoupper($nuevo), array_map('strtoupper', $partes), true)) {
            return $existente;
        }

        return $existente . ' / ' . $nuevo;
    }
}
