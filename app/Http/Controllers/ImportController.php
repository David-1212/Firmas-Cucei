<?php

namespace App\Http\Controllers;

use App\Jobs\ProcesarImportacionCsv;
use App\Models\Alumno;
use App\Models\Importacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ImportController extends Controller
{
    /**
     * Elimina todos los alumnos (y su historial de documentos/firmas).
     * Solo admin. Requiere confirmación con captcha.
     */
    public function vaciarTodo(Request $request)
    {
        abort_unless($request->user()->role === 'admin', 403, 'Solo el administrador puede realizar esta acción.');

        $data = $request->validate([
            'captcha' => 'required|string',
        ], [
            'captcha.required' => 'Debes ingresar el resultado del captcha para confirmar.',
        ]);

        $captchaService = app(\App\Services\CaptchaService::class);
        if (!$captchaService->verificar($data['captcha'])) {
            return back()->withErrors(['captcha' => 'El resultado del captcha es incorrecto. No se eliminó nada.']);
        }

        // Las imágenes de firmas se conservan en el storage público (guardadas en
        // 'firmas/{codigo}/{documento_id}.png') para que no se pierdan al borrar
        // el listado; se reutilizan si el mismo documento vuelve a firmarse.
        $total = Alumno::count();
        Alumno::query()->delete(); // cascada a documentos y firmas (constrained cascadeOnDelete)

        // Limpiar archivos temporales de importación y su historial
        Importacion::query()->delete();

        return redirect()->route('alumnos.index')
            ->with('success', "Se eliminaron {$total} alumnos y todo su historial correctamente.");
    }

    /**
     * Elimina todos los documentos (y sus firmas), conservando a los alumnos.
     * Solo admin. Requiere confirmación con captcha.
     */
    public function vaciarDocumentos(Request $request)
    {
        abort_unless($request->user()->role === 'admin', 403, 'Solo el administrador puede realizar esta acción.');

        $data = $request->validate([
            'captcha' => 'required|string',
        ], [
            'captcha.required' => 'Debes ingresar el resultado del captcha para confirmar.',
        ]);

        $captchaService = app(\App\Services\CaptchaService::class);
        if (!$captchaService->verificar($data['captcha'])) {
            return back()->withErrors(['captcha' => 'El resultado del captcha es incorrecto. No se eliminó nada.']);
        }

        $total = \App\Models\Documento::count();
        \App\Models\Documento::query()->delete(); // cascada a firmas (constrained cascadeOnDelete)

        return redirect()->route('documentos.index')
            ->with('success', "Se eliminaron {$total} documentos con su historial correctamente.");
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'archivo' => 'required|file|mimes:csv,txt|max:40960',
            'ciclo' => ['nullable', 'string', 'max:20', 'required_unless:tipo,documentos'],
            'tipo' => 'nullable|in:alumnos,documentos',
        ], [
            'archivo.required' => 'Selecciona un archivo CSV.',
            'archivo.mimes' => 'El archivo debe ser CSV.',
            'archivo.max' => 'El archivo no puede ser mayor a 40 MB.',
            'ciclo.required_if' => 'El ciclo (semestre) es obligatorio para importar alumnos.',
            'ciclo.required' => 'El ciclo (semestre) es obligatorio.',
        ]);

        // Guardar en storage privado
        $archivo = $request->file('archivo');
        $nombre = 'importacion_' . now()->format('Ymd_His') . '_' . uniqid() . '.csv';
        $ruta = Storage::disk('local')->putFileAs('importaciones', $archivo, $nombre);

        $importacion = Importacion::create([
            'user_id' => auth()->id(),
            'archivo' => $nombre,
            'nombre_original' => $archivo->getClientOriginalName(),
            'ciclo' => $data['ciclo'] ?? null,
            'tipo' => $data['tipo'] ?? 'alumnos',
            'estado' => 'pendiente',
        ]);

        // Encolar el procesamiento
        ProcesarImportacionCsv::dispatch($importacion->id);

        // Subida por AJAX: devolver JSON para mostrar el cuadrito de progreso en vivo al instante
        if ($request->ajax()) {
            return response()->json([
                'ok' => true,
                'importacion' => $importacion->fresh()->only(['id', 'ciclo', 'tipo', 'nombre_original', 'estado', 'total_filas', 'procesadas', 'insertadas']),
            ]);
        }

        return redirect()->route('alumnos.index')
            ->with('success', 'Archivo ' . $archivo->getClientOriginalName() . ' recibido. Se está procesando en segundo plano. Refresca la página para ver el progreso.');
    }

    public function cancelar(Importacion $importacion)
    {
        if ($importacion->estado === 'procesando' || $importacion->estado === 'pendiente') {
            $importacion->update(['estado' => 'cancelado']);
            return back()->with('success', 'Importación cancelada.');
        }

        return back()->withErrors(['error' => 'Esta importación ya terminó y no puede cancelarse.']);
    }

    /**
     * API para consultar el progreso de las importaciones en curso.
     */
    public function progreso(Request $request)
    {
        $activas = Importacion::whereIn('estado', ['pendiente', 'procesando'])
            ->latest()
            ->get(['id', 'ciclo', 'tipo', 'nombre_original', 'estado', 'total_filas', 'procesadas', 'insertadas', 'duplicadas', 'errores', 'ultimos_codigos', 'created_at', 'updated_at']);

        return response()->json($activas);
    }
}
