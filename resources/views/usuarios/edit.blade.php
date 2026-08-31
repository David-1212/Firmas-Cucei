<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Editar usuario') }}</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="card overflow-hidden">
                <div class="p-6">
                    <form method="POST" action="{{ route('usuarios.update', $usuario) }}" class="space-y-4">
                        @csrf @method('PATCH')
                        @php $esMismo = $usuario->id === auth()->id(); @endphp

                        <div>
                            <x-input-label for="name" value="Nombre completo" />
                            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name', $usuario->name)" required autofocus />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="email" value="Correo electrónico" />
                            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email', $usuario->email)" required />
                            <x-input-error :messages="$errors->get('email')" class="mt-2" />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="password" value="Nueva contraseña (dejar vacío para no cambiar)" />
                                <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" autocomplete="new-password" />
                                <x-input-error :messages="$errors->get('password')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="password_confirmation" value="Confirmar nueva contraseña" />
                                <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" />
                            </div>
                        </div>

                        <div>
                            <x-input-label for="role" value="Rol" />
                            <select name="role" id="role" class="input block mt-1 w-full border-gray-300" {{ $esMismo || auth()->user()->role !== 'admin' ? 'disabled' : '' }}>
                                <option value="ventanilla" {{ $usuario->role === 'ventanilla' ? 'selected' : '' }}>Ventanilla</option>
                                <option value="admin" {{ $usuario->role === 'admin' ? 'selected' : '' }}>Administrador</option>
                            </select>
                            <x-input-error :messages="$errors->get('role')" class="mt-2" />
                            @if($esMismo)
                                <p class="text-xs text-gray-400 mt-1">No puedes cambiar tu propio rol.</p>
                            @endif
                        </div>

                        <div class="flex items-center gap-2">
                            <input type="checkbox" name="activo" id="activo" value="1" {{ $usuario->activo ? 'checked' : '' }} {{ $esMismo ? 'disabled' : '' }} class="h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                            <label for="activo" class="text-sm text-gray-700">Usuario activo</label>
                            @if($esMismo)
                                <span class="text-xs text-gray-400">No puedes desactivarte a ti mismo.</span>
                            @endif
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100">
                            <a href="{{ route('usuarios.index') }}" class="btn-secondary">Cancelar</a>
                            <x-primary-button>Guardar cambios</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
