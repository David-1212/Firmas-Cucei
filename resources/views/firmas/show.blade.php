<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Firma #{{ $firma->id }}</h2>
            <a href="{{ $firma->alumno ? route('alumnos.firmas', $firma->alumno) : '#' }}" class="btn-secondary">← Todas las firmas de {{ $firma->alumno?->nombre_completo ?? 'alumno eliminado' }}</a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="card overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="font-semibold text-gray-800">Detalle de la firma</h3>
                </div>
                <div class="p-6">
                    <div class="mb-4 bg-gray-50 rounded-xl p-3">
                        <img src="{{ $firma->imagen_src }}" class="border border-gray-200 rounded-lg bg-white max-w-full shadow-sm" alt="Firma">
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                        <div class="rounded-lg bg-gray-50 px-3 py-2"><span class="text-gray-500">Alumno:</span> <span class="font-medium">{{ $firma->alumno?->nombre_completo ?? 'Alumno eliminado' }}</span></div>
                        <div class="rounded-lg bg-gray-50 px-3 py-2"><span class="text-gray-500">Código:</span> <span class="font-mono text-brand-700">{{ $firma->alumno?->codigo ?? '—' }}</span></div>
                        <div class="rounded-lg bg-gray-50 px-3 py-2"><span class="text-gray-500">Documento:</span>
                            <a href="{{ route('documentos.show', $firma->documento) }}" class="text-brand-600 hover:underline font-medium">{{ $firma->documento?->tipoDocumento?->nombre }}</a>
                        </div>
                        <div class="rounded-lg bg-gray-50 px-3 py-2"><span class="text-gray-500">Fecha de firma:</span> {{ $firma->created_at->format('d/m/Y H:i:s') }}</div>
                        <div class="md:col-span-2 rounded-lg bg-gray-50 px-3 py-2"><span class="text-gray-500">Registrada por:</span> {{ $firma->usuario?->name ?? '—' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
