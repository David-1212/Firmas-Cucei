<?php

namespace App\Http\Controllers;

use App\Models\Alumno;
use App\Models\Documento;
use App\Models\Firma;
use App\Models\Importacion;
use App\Models\TipoDocumento;
use App\Models\User;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $stats = [
            'alumnos' => Alumno::count(),
            'documentos' => Documento::count(),
            'firmas' => Firma::count(),
            'usuarios' => User::count(),
            'tiposDocumento' => TipoDocumento::count(),
        ];

        $ultimasImportaciones = Importacion::with('usuario')
            ->latest()
            ->limit(10)
            ->get();

        $ultimosDocumentos = Documento::with(['alumno', 'tipoDocumento', 'firma'])
            ->latest()
            ->limit(10)
            ->get();

        $listo = Importacion::where('estado', 'procesando')->exists();

        return view('dashboard', compact('stats', 'ultimasImportaciones', 'ultimosDocumentos', 'listo'));
    }
}
