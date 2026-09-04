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

            // Al crear el primer documento de un alumno re-importado, re-vincular
            // sus documentos huérfanos del mismo código para que vuelvan a su historial.
            $this->religarDocumentosHuérfanosDe($alumno);

            $folio = $this->siguienteFolioPara($alumno);

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
        $documento->load(['alumno.importaciones', 'tipoDocumento', 'firma.usuario', 'usuario']);

        $ciclo = $documento->alumno
            ? ($documento->alumno->importaciones()
                ->whereNotNull('importaciones.ciclo')
                ->where('importaciones.ciclo', '!=', '')
                ->latest('importaciones.id')
                ->value('importaciones.ciclo') ?? '—')
            : '—';

        // Alumno "visible": si el documento pertenece a otro ciclo pero existe un
        // registro del último ciclo con el mismo código, se muestra como de ese
        // alumno (sin alterar el vínculo real alumno_id del documento).
        $alumnoVisible = $documento->alumno;
        if ($alumnoVisible) {
            $alumnoActual = \App\Models\Alumno::where('codigo', $alumnoVisible->codigo)
                ->whereHas('importaciones', function ($qi) use ($ciclo) {
                    $qi->where('importaciones.ciclo', $ciclo);
                })
                ->latest('id')
                ->first();
            if ($alumnoActual) {
                $alumnoVisible = $alumnoActual;
            }
        }

        return view('documentos.show', compact('documento', 'ciclo', 'alumnoVisible'));
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
        $documento->load(['alumno']);

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

        // Guardar en una ruta determinística por código de alumno y id de documento
        // para que la firma sobreviva a borrados / re-importaciones del listado.
        $codigo = $documento->alumno?->codigo ?: (string) $documento->alumno_id;
        $carpeta = 'firmas/' . $codigo;
        $nombre = $carpeta . '/' . $documento->id . '.png';

        // Si ya existe una firma para este documento, reutilizarla.
        if (!\Storage::disk('public')->exists($nombre)) {
            \Storage::disk('public')->put($nombre, $imagen);
        }

        $firma = Firma::where('documento_id', $documento->id)->latest()->first();
        if (!$firma) {
            $firma = Firma::create([
                'alumno_id' => $documento->alumno_id,
                'documento_id' => $documento->id,
                'ruta_imagen' => $nombre,
                'formato' => 'png',
                'user_id' => auth()->id(),
            ]);
        }

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

        // Limitar al último ciclo, igual que el listado de alumnos: una misma
        // persona (mismo código) puede existir en varios ciclos y aquí cada
        // persona debe aparecer una sola vez (la del ciclo vigente).
        $ultimaImportacion = \App\Models\Importacion::whereNotNull('ciclo')
            ->where('ciclo', '!=', '')
            ->latest('id')
            ->first();

        $query = Alumno::buscar($termino);
        if ($ultimaImportacion) {
            $query->whereHas('importaciones', fn ($qi) => $qi->where('importaciones.id', $ultimaImportacion->id));
        }

        $alumnos = $query->limit(15)
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

        return response()->json(['folio' => $this->siguienteFolioPara($alumno)]);
    }

    /**
     * Re-vincula al alumno los documentos huérfanos del mismo código
     * (y sus firmas), que quedaron con alumno_id NULL tras eliminar y
     * volver a importar al alumno.
     */
    private function religarDocumentosHuérfanosDe(Alumno $alumno): void
    {
        $documentos = Documento::whereNull('alumno_id')
            ->where('folio', 'like', $alumno->codigo . '-%')
            ->get(['id']);

        if ($documentos->isEmpty()) {
            return;
        }

        $docIds = $documentos->pluck('id')->all();

        Documento::whereIn('id', $docIds)->update(['alumno_id' => $alumno->id]);
        Firma::whereIn('documento_id', $docIds)
            ->whereNull('alumno_id')
            ->update(['alumno_id' => $alumno->id]);
    }

    /**
     * Calcula el siguiente folio del alumno sin chocar con el índice único.
     * Considera tanto sus documentos actuales como los huérfanos del mismo
     * código (alumnos eliminados con documentos que luego se re-importaron),
     * y encuentra el máximo número de secuencia existente + 1 (no el conteo).
     *
     * El folio incluye el id del alumno (código-idAlumno-secuencia) para que
     * el mismo código repetido en distintos ciclos no colisione.
     */
    private function siguienteFolioPara(Alumno $alumno): string
    {
        $libres = Documento::where('alumno_id', $alumno->id)
            ->orWhere(fn ($q) => $q->whereNull('alumno_id')->where('folio', 'like', $alumno->codigo . '-%'))
            ->pluck('folio');

        $max = 0;
        foreach ($libres as $folio) {
            $partes = explode('-', $folio);
            $n = (int) end($partes);
            if ($n > $max) {
                $max = $n;
            }
        }

        return $alumno->codigo . '-' . $alumno->id . '-' . ($max + 1);
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
