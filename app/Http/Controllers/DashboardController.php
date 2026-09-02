<?php

namespace App\Http\Controllers;

use App\Models\Documento;
use App\Models\Firma;
use App\Models\Importacion;
use App\Models\TipoDocumento;
use App\Models\User;

class DashboardController extends Controller
{
    public function __invoke()
    {
        // El contador de alumnos solo refleja los del último ciclo (la
        // importación más reciente), coherente con el listado de trabajo.
        $ultimaImportacionId = Importacion::whereNotNull('ciclo')
            ->where('ciclo', '!=', '')
            ->latest('id')
            ->value('id');

        $stats = [
            'alumnos' => $ultimaImportacionId
                ? \Illuminate\Support\Facades\DB::table('alumno_importacion')
                    ->where('importacion_id', $ultimaImportacionId)
                    ->count()
                : 0,
            'documentos' => Documento::count(),
            'firmas' => Firma::count(),
            'usuarios' => User::count(),
            'tiposDocumento' => TipoDocumento::count(),
        ];

        // Documentos por tipo (top por cantidad), con índice tipo+created_at.
        $documentosPorTipo = Documento::selectRaw('tipo_documento_id, count(*) as total')
            ->groupBy('tipo_documento_id')
            ->orderByDesc('total')
            ->limit(8)
            ->with(['tipoDocumento'])
            ->get();

        // Firmas del último mes, para ver actividad reciente.
        $firmasUltimoMes = Firma::where('created_at', '>=', now()->subDays(30))
            ->count();

        $ultimasImportaciones = Importacion::with('usuario')
            ->latest()
            ->limit(10)
            ->get();

        $ultimosDocumentos = Documento::with(['alumno', 'tipoDocumento', 'firma'])
            ->latest()
            ->limit(10)
            ->get();

        $listo = Importacion::where('estado', 'procesando')->exists();

        return view('dashboard', compact(
            'stats',
            'documentosPorTipo',
            'firmasUltimoMes',
            'ultimasImportaciones',
            'ultimosDocumentos',
            'listo'
        ));
    }
}
