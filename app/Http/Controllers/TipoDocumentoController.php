<?php

namespace App\Http\Controllers;

use App\Models\TipoDocumento;
use Illuminate\Http\Request;

class TipoDocumentoController extends Controller
{
    public function index()
    {
        $tipos = TipoDocumento::withCount('documentos')->orderBy('nombre')->get();
        return view('tipos.index', compact('tipos'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:255|unique:tipo_documentos,nombre',
            'descripcion' => 'nullable|string|max:1000',
        ], [
            'nombre.unique' => 'Ya existe un tipo de documento con ese nombre.',
        ]);

        TipoDocumento::create([
            'nombre' => $data['nombre'],
            'descripcion' => $data['descripcion'] ?? null,
            'activo' => $request->boolean('activo', true),
        ]);

        return redirect()->route('tipos.index')
            ->with('success', 'Tipo de documento creado correctamente.');
    }

    public function update(Request $request, TipoDocumento $tipo)
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:255|unique:tipo_documentos,nombre,' . $tipo->id,
            'descripcion' => 'nullable|string|max:1000',
        ], [
            'nombre.unique' => 'Ya existe un tipo de documento con ese nombre.',
        ]);

        $tipo->update([
            'nombre' => $data['nombre'],
            'descripcion' => $data['descripcion'] ?? null,
            'activo' => $request->boolean('activo', true),
        ]);

        return redirect()->route('tipos.index')
            ->with('success', 'Tipo de documento actualizado correctamente.');
    }

    public function destroy(TipoDocumento $tipo)
    {
        if ($tipo->documentos()->exists()) {
            return back()->withErrors(['error' => 'No se puede eliminar porque existen documentos asociados a este tipo.']);
        }

        $tipo->delete();

        return redirect()->route('tipos.index')
            ->with('success', 'Tipo de documento eliminado.');
    }
}
