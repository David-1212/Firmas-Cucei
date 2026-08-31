<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Editar alumno') }}</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="card overflow-hidden">
                <div class="p-6">
                    <form method="POST" action="{{ route('alumnos.update', $alumno) }}" class="space-y-4">
                        @csrf @method('PATCH')

                        <div>
                            <x-input-label for="codigo" value="Código escolar" />
                            <x-text-input id="codigo" class="block mt-1 w-full" type="text" name="codigo" :value="old('codigo', $alumno->codigo)" required autofocus />
                            <x-input-error :messages="$errors->get('codigo')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="matricula" value="Matrícula" />
                            <x-text-input id="matricula" class="block mt-1 w-full" type="text" name="matricula" :value="old('matricula', $alumno->matricula)" />
                            <x-input-error :messages="$errors->get('matricula')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="nombre_completo" value="Nombre completo" />
                            <x-text-input id="nombre_completo" class="block mt-1 w-full" type="text" name="nombre_completo" :value="old('nombre_completo', $alumno->nombre_completo)" required />
                            <x-input-error :messages="$errors->get('nombre_completo')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="carrera" value="Carrera" />
                            <x-text-input id="carrera" class="block mt-1 w-full" type="text" name="carrera" :value="old('carrera', $alumno->carrera)" />
                            <x-input-error :messages="$errors->get('carrera')" class="mt-2" />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="ciclo_ingreso" value="Ciclo de ingreso" />
                                <x-text-input id="ciclo_ingreso" class="block mt-1 w-full" type="text" name="ciclo_ingreso" :value="old('ciclo_ingreso', $alumno->ciclo_ingreso)" placeholder="ej. 2026-A" />
                                <x-input-error :messages="$errors->get('ciclo_ingreso')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="status" value="Status" />
                                <x-text-input id="status" class="block mt-1 w-full" type="text" name="status" :value="old('status', $alumno->status)" />
                                <x-input-error :messages="$errors->get('status')" class="mt-2" />
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100">
                            <a href="{{ route('alumnos.show', $alumno) }}" class="btn-secondary">Cancelar</a>
                            <x-primary-button>Guardar cambios</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
