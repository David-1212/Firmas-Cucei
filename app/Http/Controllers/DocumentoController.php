<?php

namespace App\Http\Controllers;

use App\Models\Alumno;
use App\Models\Documento;
use App\Models\Firma;
use App\Models\TipoDocumento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DocumentoController extends Controller
{
    public function index(Request $request)
    {
        $busqueda = trim($request->input('q', ''));
        $estado = $request->input('estado', '');
        $tipo = $request->input('tipo', '');

        $documentos = Documento::with(['alumno', 'tipoDocumento', 'firma'])
            ->when($busqueda !== '', fn ($q) => $q->buscar($busqueda))
            ->when($estado !== '', fn ($q) => $q->where('estado', $estado))
            ->when($tipo !== '', fn ($q) => $q->where('tipo_documento_id', $tipo))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        $tipos = TipoDocumento::orderBy('nombre')->get();

        return view('documentos.index', compact('documentos', 'busqueda', 'estado', 'tipo', 'tipos'));
    }

    public function create(Request $request)
    {
        $alumnoId = $request->input('alumno_id');
        $alumno = $alumnoId ? Alumno::find($alumnoId) : null;
        $tipos = TipoDocumento::activos()->orderBy('nombre')->get();

        return view('documentos.create', compact('tipos', 'alumno'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'alumno_id' => 'required|exists:alumnos,id',
            'tipo_documento_id' => 'required|exists:tipo_documentos,id',
            'observaciones' => 'nullable|string|max:1000',
        ]);

        DB::beginTransaction();
        try {
            // Bloquear la fila del alumno para serializar la generación de folios
            // del mismo alumno y evitar folios duplicados en concurrencia.
            $alumno = Alumno::whereKey($data['alumno_id'])->lockForUpdate()->firstOrFail();

            $secuencia = Documento::where('alumno_id', $alumno->id)->count() + 1;
            $folio = $alumno->codigo . '-' . $secuencia;

            $documento = Documento::create([
                'alumno_id' => $alumno->id,
                'tipo_documento_id' => $data['tipo_documento_id'],
                'folio' => $folio,
                'observaciones' => $data['observaciones'] ?? null,
                'estado' => 'pendiente',
                'fecha' => now(),
                'user_id' => auth()->id(),
            ]);
            DB::commit();

            return redirect()->route('documentos.index')
                ->with('success', 'Documento ' . $folio . ' creado exitosamente.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->route('documentos.create')
                ->with('error', 'Ocurrió un error al generar el documento: ' . $e->getMessage());
        }
    }

    public function show(Documento $documento)
    {
        $documento->load(['alumno', 'tipoDocumento', 'firma.usuario', 'usuario']);
        return view('documentos.show', compact('documento'));
    }

    public function firmar(Documento $documento)
    {
        $documento->load(['alumno', 'tipoDocumento', 'firma']);

        if ($documento->estado === 'firmado' && $documento->firma) {
            return redirect()->route('documentos.show', $documento)
                ->with('info', 'Este documento ya está firmado.');
        }

        return view('documentos.firmar', compact('documento'));
    }

    /**
     * Guarda la firma dibujada (imagen base64) de un documento.
     */
    public function storeFirma(Request $request, Documento $documento)
    {
        $this->authorizeFirmar($documento);

        $data = $request->validate([
            'firma_data' => 'required|string',
        ], [
            'firma_data.required' => 'Debes dibujar tu firma.',
        ]);

        // Decodificar imagen base64 (data:image/png;base64,xxxx)
        $payload = $data['firma_data'];
        if (str_starts_with($payload, 'data:')) {
            $partes = explode(',', $payload, 2);
            if (!isset($partes[1])) {
                return back()->withErrors(['firma_data' => 'Imagen de firma inválida.']);
            }
            $payload = $partes[1];
        }
        $imagen = base64_decode($payload, true);
        if ($imagen === false || $imagen === '') {
            return back()->withErrors(['firma_data' => 'La firma está vacía o es inválida.']);
        }

        // Limitar tamaño (aprox. 5MB)
        if (strlen($imagen) > 5 * 1024 * 1024) {
            return back()->withErrors(['firma_data' => 'La imagen de la firma es demasiado grande.']);
        }

        $nombre = 'firmas/' . $documento->alumno_id . '_' . $documento->id . '_' . time() . '.png';
        \Storage::disk('public')->put($nombre, $imagen);

        $firma = Firma::create([
            'alumno_id' => $documento->alumno_id,
            'documento_id' => $documento->id,
            'ruta_imagen' => $nombre,
            'formato' => 'png',
            'user_id' => auth()->id(),
        ]);

        $documento->update(['estado' => 'firmado']);

        return redirect()->route('documentos.show', $documento)
            ->with('success', 'Firma registrada correctamente.');
    }

    public function destroy(Documento $documento)
    {
        $this->authorizeAdmin();

        foreach ($documento->firmas as $firma) {
            if ($firma->ruta_imagen && \Storage::disk('public')->exists($firma->ruta_imagen)) {
                \Storage::disk('public')->delete($firma->ruta_imagen);
            }
        }

        $documento->delete();

        return redirect()->route('documentos.index')
            ->with('success', 'Documento eliminado correctamente.');
    }

    /**
     * API para búsqueda en vivo de alumnos (datalist).
     */
    public function buscarAlumnos(Request $request)
    {
        $termino = trim($request->input('q', ''));
        $alumnos = Alumno::buscar($termino)
            ->limit(15)
            ->get(['id', 'codigo', 'matricula', 'nombre_completo', 'carrera', 'ciclo_ingreso', 'status']);

        return response()->json($alumnos->map(fn ($a) => [
            'id' => $a->id,
            'codigo' => $a->codigo,
            'nombre_completo' => $a->nombre_completo,
            'etiqueta' => $a->codigo . ' — ' . $a->nombre_completo . ($a->carrera ? ' (' . $a->carrera . ')' : ''),
        ]));
    }

    /**
     * Devuelve el siguiente folio que se asignará a un alumno (vista previa).
     */
    public function siguienteFolio(Request $request)
    {
        $alumno = Alumno::find($request->integer('alumno_id'));
        if (!$alumno) {
            return response()->json(['folio' => null], 404);
        }

        $secuencia = Documento::where('alumno_id', $alumno->id)->count() + 1;

        return response()->json(['folio' => $alumno->codigo . '-' . $secuencia]);
    }

    private function authorizeFirmar(Documento $documento): void
    {
        abort_if($documento->estado !== 'pendiente' && !$documento->firmas()->exists(), 403, 'Este documento no puede firmarse en este momento.');
        // Permite que ventanilla y admin registren la firma
    }

    private function authorizeAdmin(): void
    {
        abort_unless(auth()->user()->role === 'admin', 403, 'Solo el administrador puede realizar esta acción.');
    }
}
