<?php

namespace App\Http\Controllers;

use App\Models\Alumno;
use App\Models\Documento;
use App\Models\Firma;
use App\Models\Importacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AlumnoController extends Controller
{
    public function index(Request $request)
    {
        $busqueda = trim($request->input('q', ''));
        $filtroCarrera = trim($request->input('carrera', ''));
        $filtroStatus = trim($request->input('status', ''));
        $sinFiltros = $busqueda === '' && $filtroCarrera === '' && $filtroStatus === '';

        // El listado de trabajo solo muestra los alumnos del último ciclo
        // registrado: la importación más reciente determina el ciclo visible.
        $ultimaImportacion = Importacion::whereNotNull('ciclo')
            ->where('ciclo', '!=', '')
            ->latest('id')
            ->first();

        $cicloId = $ultimaImportacion?->id ?? 0;
        $cicloUltimo = $ultimaImportacion?->ciclo ?? '';

        $cicloFilter = fn ($qi) => $qi->where('importaciones.id', $cicloId);

        $page = $request->integer('page', 1);

        // Cuando no hay búsqueda ni filtros, cacheamos el listado por defecto
        // para evitar el count y filesort de 127k+ filas en cada carga de la
        // pestaña. Se invalida al completar una importación.
        if ($sinFiltros && $cicloId) {
            $cacheKey = "listado-alumnos-{$cicloId}-{$page}";
            $ttl = 300; // 5 minutos
            $alumnos = Cache::remember($cacheKey, $ttl, function () use ($cicloFilter) {
                return Alumno::withCount(['documentos', 'firmas'])
                    ->whereHas('importaciones', $cicloFilter)
                    ->orderBy('nombre_completo')
                    ->paginate(20);
            });
        } else {
            $alumnos = Alumno::withCount(['documentos', 'firmas'])
                ->when($busqueda !== '', fn ($q) => $q->buscar($busqueda))
                ->when($filtroCarrera !== '', fn ($q) => $q->where('carrera', $filtroCarrera))
                ->when($cicloUltimo !== '', fn ($q) => $q->whereHas('importaciones', $cicloFilter))
                ->when($filtroStatus !== '', fn ($q) => $q->where('status', $filtroStatus))
                ->orderBy('nombre_completo')
                ->paginate(20)
                ->withQueryString();
        }

        $carreras = Cache::remember("dropdown-carreras-{$cicloId}", 300, function () use ($cicloId) {
            return Alumno::whereIn('id', function ($q) use ($cicloId) {
                $q->select('alumno_id')->from('alumno_importacion')->where('importacion_id', $cicloId);
            })
                ->whereNotNull('carrera')
                ->where('carrera', '!=', '')
                ->distinct()
                ->orderBy('carrera')
                ->pluck('carrera');
        });

        $statuses = Cache::remember("dropdown-statuses-{$cicloId}", 300, function () use ($cicloId) {
            return Alumno::whereIn('id', function ($q) use ($cicloId) {
                $q->select('alumno_id')->from('alumno_importacion')->where('importacion_id', $cicloId);
            })
                ->whereNotNull('status')
                ->where('status', '!=', '')
                ->distinct()
                ->orderBy('status')
                ->pluck('status');
        });

        return view('alumnos.index', compact(
            'alumnos',
            'busqueda',
            'filtroCarrera',
            'filtroStatus',
            'carreras',
            'cicloUltimo',
            'statuses'
        ));
    }

    public function create()
    {
        $this->authorizeAdmin();
        return view('alumnos.create');
    }

    public function store(Request $request)
    {
        $this->authorizeAdmin();

        $data = $request->validate([
            'codigo' => 'required|string|max:30',
            'matricula' => 'nullable|string|max:60',
            'nombre_completo' => 'required|string|max:255',
            'carrera' => 'nullable|string|max:255',
            'ciclo_ingreso' => 'nullable|string|max:20',
            'status' => 'nullable|string|max:50',
        ]);

        $alumno = Alumno::create($data);

        return redirect()->route('alumnos.show', $alumno)
            ->with('success', 'Alumno registrado correctamente.');
    }

    public function show(Request $request, Alumno $alumno)
    {
        $filtroTipo = $request->input('tipo', '');

        // El historial incluye los documentos propios (alumno_id) y también los
        // documentos de otros registros del mismo código (otros ciclos): se
        // muestran como de este alumno sin modificar su vínculo real (alumno_id).
        $mismosCodigos = \App\Models\Alumno::where('codigo', $alumno->codigo)->pluck('id');

        $documentos = Documento::whereIn('alumno_id', $mismosCodigos)
            ->with(['tipoDocumento', 'firma', 'usuario', 'alumno.importaciones'])
            ->when($filtroTipo !== '', fn ($q) => $q->where('tipo_documento_id', $filtroTipo))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $firmas = \App\Models\Firma::whereIn('alumno_id', $mismosCodigos)
            ->with(['documento.tipoDocumento', 'usuario'])
            ->latest()
            ->limit(50)
            ->get();

        $tipos = \App\Models\TipoDocumento::activos()->orderBy('nombre')->get();

        return view('alumnos.show', compact('alumno', 'documentos', 'firmas', 'tipos', 'filtroTipo'));
    }

    public function firmas(Alumno $alumno)
    {
        $firmas = $alumno->firmas()
            ->with(['documento.tipoDocumento', 'usuario'])
            ->paginate(20);

        return view('alumnos.firmas', compact('alumno', 'firmas'));
    }

    public function edit(Alumno $alumno)
    {
        $this->authorizeAdmin();
        return view('alumnos.edit', compact('alumno'));
    }

    public function update(Request $request, Alumno $alumno)
    {
        $this->authorizeAdmin();

        $data = $request->validate([
            'codigo' => 'required|string|max:30',
            'matricula' => 'nullable|string|max:60',
            'nombre_completo' => 'required|string|max:255',
            'carrera' => 'nullable|string|max:255',
            'ciclo_ingreso' => 'nullable|string|max:20',
            'status' => 'nullable|string|max:50',
        ]);

        $alumno->update($data);

        return redirect()->route('alumnos.show', $alumno)
            ->with('success', 'Alumno actualizado correctamente.');
    }

    public function destroy(Alumno $alumno)
    {
        $this->authorizeAdmin();

        // Los documentos y firmas se conservan (alumno_id pasa a NULL):
        // la FK usa nullOnDelete, no se borran imágenes ni registros.
        $alumno->delete();

        return redirect()->route('alumnos.index')
            ->with('success', 'Alumno eliminado correctamente.');
    }

    private function authorizeAdmin(): void
    {
        abort_unless(auth()->user()->role === 'admin', 403, 'Solo el administrador puede realizar esta acción.');
    }
}
