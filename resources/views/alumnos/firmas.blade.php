<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Firmas de {{ $alumno->nombre_completo }}</h2>
            <a href="{{ route('alumnos.show', $alumno) }}" class="btn-secondary">← Volver al alumno</a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="card overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="font-semibold text-gray-800">Galería de firmas ({{ $firmas->total() }})</h3>
                    <p class="text-sm text-gray-500 mt-1">Compara las firmas del alumno a lo largo del tiempo para verificar coincidencias.</p>
                </div>
                <div class="p-6">
                    @forelse($firmas as $firma)
                        <div class="mb-6">
                            <div class="flex items-center justify-between mb-2">
                                <div class="text-sm text-gray-600">
                                    <span class="font-medium text-gray-800">{{ $firma->documento?->tipoDocumento?->nombre ?? 'Sin documento' }}</span>
                                    · {{ $firma->created_at->format('d/m/Y H:i:s') }}
                                </div>
                                <div class="text-xs text-gray-400">Capturó: {{ $firma->usuario?->name ?? '—' }}</div>
                            </div>
                            <a href="{{ route('firmas.show', $firma) }}" class="block border border-gray-200 rounded-xl p-3 inline-block bg-gray-50 hover:border-brand-300 hover:shadow-lg transition">
                                <img src="{{ asset('storage/' . $firma->ruta_imagen) }}" class="h-32 bg-white" alt="Firma {{ $loop->iteration }}">
                            </a>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">Este alumno no tiene firmas registradas.</p>
                    @endforelse

                    <div class="mt-4">
                        {{ $firmas->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
