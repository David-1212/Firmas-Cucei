<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Tipos de documento') }}</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Crear -->
            <div class="card overflow-hidden mb-6">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="font-semibold text-gray-800">Nuevo tipo de documento</h3>
                </div>
                <div class="p-6">
                    <form method="POST" action="{{ route('tipos.store') }}" class="flex flex-col sm:flex-row gap-3 sm:items-end">
                        @csrf
                        <div class="flex-1 sm:max-w-md">
                            <x-input-label for="nombre" value="Nombre" />
                            <x-text-input id="nombre" class="block mt-1 w-full" type="text" name="nombre" :value="old('nombre')" required placeholder="ej. Constancia, Carta de pasante, ..." />
                            <x-input-error :messages="$errors->get('nombre')" class="mt-2" />
                        </div>
                        <div class="sm:shrink-0">
                            <x-primary-button class="w-full justify-center">Agregar</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Lista -->
            <div class="card overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="font-semibold text-gray-800">Tipos registrados ({{ $tipos->count() }})</h3>
                </div>
                <div class="p-6 space-y-3">
                    @forelse($tipos as $tipo)
                        <div class="border border-gray-100 rounded-xl bg-gray-50/50 p-4 space-y-3">
                            <form method="POST" action="{{ route('tipos.update', $tipo) }}" class="flex flex-col lg:flex-row gap-4 lg:items-end">
                                @csrf @method('PATCH')
                                <div class="flex-1 lg:max-w-sm">
                                    <x-input-label for="nombre_{{ $tipo->id }}" value="Nombre" />
                                    <x-text-input id="nombre_{{ $tipo->id }}" class="block mt-1 w-full" type="text" name="nombre" :value="old('nombre', $tipo->nombre)" required />
                                </div>
                                <div class="flex-[2]">
                                    <x-input-label for="descripcion_{{ $tipo->id }}" value="Descripción (opcional)" />
                                    <x-text-input id="descripcion_{{ $tipo->id }}" class="block mt-1 w-full" type="text" name="descripcion" :value="old('descripcion', $tipo->descripcion)" />
                                </div>
                                <div class="flex flex-wrap items-center gap-x-3 gap-y-2 lg:shrink-0">
                                    <label class="inline-flex items-center gap-2 text-xs font-medium text-gray-700 cursor-pointer select-none">
                                        <input type="checkbox" name="activo" value="1" {{ $tipo->activo ? 'checked' : '' }} class="h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                                        Activo
                                    </label>
                                    <button type="submit" class="action-edit" title="Guardar cambios">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 18.052a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125"/></svg>
                                        Guardar
                                    </button>
                                    <span class="text-xs text-gray-400">{{ $tipo->documentos_count }} docs</span>
                                </div>
                            </form>
                            @if($tipo->documentos_count === 0)
                                <form method="POST" action="{{ route('tipos.destroy', $tipo) }}" class="mt-2" data-confirm="¿Eliminar este tipo de documento?">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="action-danger" title="Eliminar">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                        Eliminar
                                    </button>
                                </form>
                            @endif
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">No hay tipos de documento registrados. Agrega el primero arriba.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
