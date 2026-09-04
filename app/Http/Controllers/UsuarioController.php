<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UsuarioController extends Controller
{
    public function index(Request $request)
    {
        $busqueda = trim($request->input('q', ''));

        $usuarios = User::when($busqueda !== '', function ($q) use ($busqueda) {
            $q->where('name', 'like', "%{$busqueda}%")
                ->orWhere('email', 'like', "%{$busqueda}%");
        })->orderBy('name')->paginate(20)->withQueryString();

        return view('usuarios.index', compact('usuarios', 'busqueda'));
    }

    public function create()
    {
        return view('usuarios.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:admin,ventanilla',
            'activo' => 'nullable|boolean',
        ], [
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'La confirmación de contraseña no coincide.',
            'email.unique' => 'Ya existe un usuario con ese correo.',
        ]);

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => $data['role'],
            'activo' => $request->boolean('activo'),
        ]);

        return redirect()->route('usuarios.index')
            ->with('success', 'Usuario creado correctamente.');
    }

    public function edit(User $usuario)
    {
        return view('usuarios.edit', compact('usuario'));
    }

    public function update(Request $request, User $usuario)
    {
        // El admin no puede desactivarse a sí mismo, ni quitarse su propio rol admin
        $esMismo = $usuario->id === auth()->id();

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($usuario->id)],
            'password' => 'nullable|string|min:8|confirmed',
            'role' => $esMismo ? 'nullable|in:admin,ventanilla' : 'required|in:admin,ventanilla',
            'activo' => 'nullable|boolean',
        ], [
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'La confirmación de contraseña no coincide.',
            'email.unique' => 'Ya existe un usuario con ese correo.',
        ]);

        $datos = [
            'name' => $data['name'],
            'email' => $data['email'],
        ];

        if ($esMismo) {
            // No permitir cambiar el rol propio ni desactivarse
            $datos['role'] = 'admin';
            $datos['activo'] = true;
        } else {
            $datos['role'] = $data['role'];
            $datos['activo'] = $request->boolean('activo');
        }

        if (!empty($data['password'])) {
            $datos['password'] = $data['password'];
        }

        $usuario->update($datos);

        return redirect()->route('usuarios.index')
            ->with('success', 'Usuario actualizado correctamente.');
    }

    public function destroy(User $usuario)
    {
        if ($usuario->id === auth()->id()) {
            return back()->withErrors(['error' => 'No puedes eliminar tu propia cuenta.']);
        }

        $usuario->delete();

        return redirect()->route('usuarios.index')
            ->with('success', 'Usuario eliminado correctamente.');
    }
}
