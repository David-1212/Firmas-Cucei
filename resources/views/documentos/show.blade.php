<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Documento #{{ $documento->id }}</h2>
            <div class="flex gap-2 flex-wrap">
                @if($documento->estado !== 'firmado')
                    <a href="{{ route('documentos.firmar', $documento) }}" class="btn-success">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487zm0 0L19.5 7.125"/></svg>
                        Firmar
                    </a>
                @endif
                @if(auth()->user()->role === 'admin')
                    <form method="POST" action="{{ route('documentos.destroy', $documento) }}" data-confirm="¿Eliminar este documento y su firma asociada?">
                        @csrf @method('DELETE')
                        <button class="btn-danger">Eliminar</button>
                    </form>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Datos del documento -->
            <div class="card overflow-hidden mb-6">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="font-semibold text-gray-800">Detalle del documento</h3>
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                    <div class="rounded-lg bg-gray-50 px-3 py-2"><span class="text-gray-500">Alumno:</span> <span class="font-medium">{{ $alumnoVisible?->nombre_completo ?? 'Alumno eliminado' }}</span>
                        @if($alumnoVisible && $alumnoVisible->id !== $documento->alumno?->id)
                            <span class="badge bg-purple-100 text-purple-700 text-xs">ciclo actual</span>
                        @endif
                    </div>
                    <div class="rounded-lg bg-gray-50 px-3 py-2"><span class="text-gray-500">Código:</span> <span class="font-mono font-medium text-brand-700">{{ $alumnoVisible?->codigo ?? '—' }}</span></div>
                    <div class="rounded-lg bg-gray-50 px-3 py-2"><span class="text-gray-500">Tipo de documento:</span> {{ $documento->tipoDocumento->nombre }}</div>
                    <div class="rounded-lg bg-gray-50 px-3 py-2"><span class="text-gray-500">Folio:</span> <span class="font-mono font-medium text-brand-700">{{ $documento->folio ?? '—' }}</span></div>
                    <div class="rounded-lg bg-gray-50 px-3 py-2"><span class="text-gray-500">Ciclo:</span> <span class="badge bg-purple-100 text-purple-700">{{ $ciclo }}</span></div>
                    <div class="rounded-lg bg-gray-50 px-3 py-2"><span class="text-gray-500">Fecha y hora de generación:</span> <span class="font-medium">{{ $documento->fecha?->format('d/m/Y H:i:s') ?? '—' }}</span></div>
                    <div class="rounded-lg bg-gray-50 px-3 py-2"><span class="text-gray-500">Generado por:</span> {{ $documento->usuario?->name ?? '—' }}</div>
                    @if($documento->observaciones)
                        <div class="col-span-2 rounded-lg bg-gray-50 px-3 py-2"><span class="text-gray-500">Observaciones:</span> {{ $documento->observaciones }}</div>
                    @endif
                </div>
            </div>

            <!-- Firma -->
            <div class="card overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="font-semibold text-gray-800">Firma del alumno</h3>
                    @if($documento->firma)
                        <span class="badge bg-green-100 text-green-700">Firmado</span>
                    @else
                        <span class="badge bg-amber-100 text-amber-700">Pendiente de firma</span>
                    @endif
                </div>
                <div class="p-6">
                    @if($documento->firma)
                        <img src="{{ asset($documento->firma->ruta_imagen) }}" class="border border-gray-200 rounded-lg bg-white max-h-48 shadow-sm" alt="Firma del alumno">
                        <p class="text-xs text-gray-500 mt-2">
                            Firmado el {{ $documento->firma->created_at->format('d/m/Y H:i:s') }}
                            @if($documento->firma->usuario)
                                por {{ $documento->firma->usuario->name }}
                            @endif
                        </p>
                    @else
                        <p class="text-sm text-gray-500">
                            El documento aún no ha sido firmado. Haz clic en <a href="{{ route('documentos.firmar', $documento) }}" class="text-emerald-600 underline font-medium">Firmar</a> para que el alumno coloque su firma.
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
