<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $ruta = database_path('data/alumnos_multi_carrera_ciclo.json');

        if (!file_exists($ruta)) {
            return;
        }

        $data = json_decode(file_get_contents($ruta), true);

        if (!is_array($data) || $data === []) {
            return;
        }

        foreach (array_chunk($data, 500, true) as $lote) {
            foreach ($lote as $codigo => $info) {
                $carrera = implode(' / ', $info['c'] ?? []);
                $ciclo = implode(' / ', $info['i'] ?? []);

                $update = [];
                if ($carrera !== '') {
                    $update['carrera'] = $carrera;
                }
                if ($ciclo !== '') {
                    $update['ciclo_ingreso'] = $ciclo;
                }

                if ($update !== []) {
                    // (string) es clave: MySQL/MariaDB mezcla el código como
                    // DECIMAL si llega como entero y falla con 1292.
                    DB::table('alumnos')->where('codigo', (string) $codigo)->update($update);
                }
            }
        }

        // Limpiar matrículas que quedaron con valores repetidos tras una
        // re-importación (p. ej. "A / B / A / B" -> "A / B").
        $this->deduplicarMatriculas();
    }

    private function deduplicarMatriculas(): void
    {
        $rows = DB::table('alumnos')
            ->where('matricula', 'like', '% / %')
            ->orWhere('matricula', 'like', '%;%')
            ->get(['id', 'matricula']);

        foreach ($rows as $fila) {
            $partes = preg_split('/\s*\/\s*|\s*;\s*/', trim((string) $fila->matricula));
            $partes = array_values(array_filter(array_map('trim', $partes), fn ($p) => $p !== ''));

            if (count($partes) < 2) {
                continue;
            }

            $vistos = [];
            $limpio = [];
            foreach ($partes as $p) {
                $clave = mb_strtoupper($p);
                if (isset($vistos[$clave])) {
                    continue;
                }
                $vistos[$clave] = true;
                $limpio[] = $p;
            }

            $resultado = implode(' / ', $limpio);

            if ($resultado !== trim((string) $fila->matricula)) {
                DB::table('alumnos')->where('id', $fila->id)->update(['matricula' => $resultado]);
            }
        }
    }

    public function down(): void
    {
        // No reversible de forma segura: se pierde el valor único original.
    }
};
