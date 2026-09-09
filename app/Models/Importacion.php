<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Importacion extends Model
{
    use HasFactory;

    protected $table = 'importaciones';

    protected $fillable = [
        'user_id',
        'archivo',
        'nombre_original',
        'ciclo',
        'tipo',
        'total_filas',
        'procesadas',
        'insertadas',
        'duplicadas',
        'errores',
        'lotes_pendientes',
        'ultimos_codigos',
        'duplicados_detalle',
        'errores_detalle',
        'estado',
        'mensaje_error',
    ];

    protected $casts = [
        'ultimos_codigos' => 'array',
        'duplicados_detalle' => 'array',
        'errores_detalle' => 'array',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function alumnos()
    {
        return $this->belongsToMany(Alumno::class, 'alumno_importacion');
    }

    /**
     * Invalida y regenera el caché del listado de alumnos (página 1) y de los
     * dropdowns (carreras/status) de este ciclo, usado por AlumnoController::index.
     * Al regenerarlo al completar una importación, la primera carga del usuario
     * ya tiene el caché caliente (evita el cold start de ~7s).
     */
    public function regenerarCacheListado(): void
    {
        if (!$this->id) {
            return;
        }

        $cicloFilter = fn ($q) => $q->where('importaciones.id', $this->id);

        Cache::forget("dropdown-carreras-{$this->id}");
        Cache::forget("dropdown-statuses-{$this->id}");
        Cache::forget("listado-alumnos-{$this->id}-1");

        // Pre-cargar (warm) la página 1 y los dropdowns para evitar el cold start.
        Cache::remember("listado-alumnos-{$this->id}-1", 300, function () use ($cicloFilter) {
            return \App\Models\Alumno::withCount(['documentos', 'firmas'])
                ->whereHas('importaciones', $cicloFilter)
                ->orderBy('nombre_completo')
                ->paginate(20);
        });

        // Subconsulta de los ids de alumnos de este ciclo, para evitar expandir
        // 127k+ placeholders en un whereIn.
        $alumnoIds = function ($q) {
            $q->select('alumno_id')->from('alumno_importacion')->where('importacion_id', $this->id);
        };

        Cache::remember("dropdown-carreras-{$this->id}", 300, function () use ($alumnoIds) {
            return \App\Models\Alumno::whereIn('id', $alumnoIds)
                ->whereNotNull('carrera')
                ->where('carrera', '!=', '')
                ->distinct()
                ->orderBy('carrera')
                ->pluck('carrera');
        });

        Cache::remember("dropdown-statuses-{$this->id}", 300, function () use ($alumnoIds) {
            return \App\Models\Alumno::whereIn('id', $alumnoIds)
                ->whereNotNull('status')
                ->where('status', '!=', '')
                ->distinct()
                ->orderBy('status')
                ->pluck('status');
        });
    }
}
