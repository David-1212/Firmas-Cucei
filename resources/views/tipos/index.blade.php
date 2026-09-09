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
                        <div class="border border-gray-100 rounded-xl bg-gray-50/50 p-4 {{ $tipo->sistema ? 'border-emerald-200 bg-emerald-50/40' : '' }}">
                            @if($tipo->sistema)
                                <div class="mb-3">
                                    <span class="inline-flex items-center gap-1.5 badge bg-emerald-100 text-emerald-700">
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/></svg>
                                        Tipo fijo del sistema
                                    </span>
                                    <span class="text-xs text-gray-500 ml-2">No se puede modificar ni eliminar.</span>
                                </div>
                            @endif
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div>
                                    <div class="text-sm font-semibold text-gray-800">{{ $tipo->nombre }}</div>
                                    @if($tipo->descripcion)<div class="text-xs text-gray-500">{{ $tipo->descripcion }}</div>@endif
                                </div>
                                <div class="flex items-center gap-3">
                                    @if($tipo->activo)
                                        <span class="badge bg-green-100 text-green-700">Activo</span>
                                    @else
                                        <span class="badge bg-gray-200 text-gray-600">Inactivo</span>
                                    @endif
                                    <span class="text-xs text-gray-400">{{ $tipo->documentos_count }} docs</span>
                                </div>
                            </div>
                            @if(!$tipo->sistema && $tipo->documentos_count === 0)
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
