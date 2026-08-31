<?php

namespace App\Http\Controllers;

use App\Models\Alumno;
use App\Models\Firma;
use Illuminate\Http\Request;

class FirmaController extends Controller
{
    /**
     * Vista de todas las firmas de un alumno, como galería en imagen,
     * para comparación de firmas.
     */
    public function alumno(Request $request, Alumno $alumno)
    {
        $firmas = $alumno->firmas()
            ->with(['documento.tipoDocumento', 'usuario'])
            ->latest()
            ->paginate(24);

        return view('firmas.alumno', compact('alumno', 'firmas'));
    }

    public function show(Firma $firma)
    {
        $firma->load(['alumno', 'documento.tipoDocumento', 'usuario']);
        return view('firmas.show', compact('firma'));
    }
}
