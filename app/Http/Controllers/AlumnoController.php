<?php

namespace App\Http\Controllers;

use App\Models\Alumno;
use App\Models\Documento;
use App\Models\Firma;
use Illuminate\Http\Request;

class AlumnoController extends Controller
{
    public function index(Request $request)
    {
        $busqueda = trim($request->input('q', ''));

        $alumnos = Alumno::withCount(['documentos', 'firmas'])
            ->when($busqueda !== '', fn ($q) => $q->buscar($busqueda))
            ->orderBy('nombre_completo')
            ->paginate(20)
            ->withQueryString();

        return view('alumnos.index', compact('alumnos', 'busqueda'));
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
            'matricula' => 'required|string|max:30|unique:alumnos,matricula',
            'codigo' => 'nullable|string|max:30',
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

        $documentos = $alumno->documentos()
            ->with(['tipoDocumento', 'firma', 'usuario'])
            ->when($filtroTipo !== '', fn ($q) => $q->where('tipo_documento_id', $filtroTipo))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $firmas = $alumno->firmas()
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
            'matricula' => 'required|string|max:30|unique:alumnos,matricula,' . $alumno->id,
            'codigo' => 'nullable|string|max:30',
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

        // Eliminar imágenes de firmas del storage
        foreach ($alumno->firmas as $firma) {
            if ($firma->ruta_imagen && \Storage::disk('public')->exists($firma->ruta_imagen)) {
                \Storage::disk('public')->delete($firma->ruta_imagen);
            }
        }

        $alumno->delete();

        return redirect()->route('alumnos.index')
            ->with('success', 'Alumno eliminado correctamente.');
    }

    private function authorizeAdmin(): void
    {
        abort_unless(auth()->user()->role === 'admin', 403, 'Solo el administrador puede realizar esta acción.');
    }
}
