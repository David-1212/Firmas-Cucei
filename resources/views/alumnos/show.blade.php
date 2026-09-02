<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $alumno->nombre_completo }}</h2>
            <div class="flex gap-2">
                @if(auth()->user()->role === 'admin')
                    <a href="{{ route('alumnos.edit', $alumno) }}" class="btn-secondary">Editar</a>
                    <form method="POST" action="{{ route('alumnos.destroy', $alumno) }}" data-confirm="¿Eliminar este alumno y todos sus documentos y firmas? Esta acción no se puede deshacer.">
                        @csrf @method('DELETE')
                        <button class="btn-danger">Eliminar</button>
                    </form>
                @endif
                <a href="{{ route('documentos.create', ['alumno_id' => $alumno->id]) }}" class="btn-primary">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    Generar documento
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Datos del alumno -->
            <div class="card overflow-hidden mb-6">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="font-semibold text-gray-800">Datos del alumno</h3>
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 text-sm">
                    <div class="rounded-lg bg-gray-50 px-3 py-2"><span class="text-gray-500">Matrícula:</span> <span class="font-mono font-medium text-brand-700">{{ $alumno->matricula ?? '—' }}</span></div>
                    <div class="rounded-lg bg-gray-50 px-3 py-2"><span class="text-gray-500">Código:</span> <span class="font-mono font-medium text-brand-700">{{ $alumno->codigo }}</span></div>
                    <div class="rounded-lg bg-gray-50 px-3 py-2"><span class="text-gray-500">Nombre completo:</span> {{ $alumno->nombre_completo }}</div>
                    <div class="rounded-lg bg-gray-50 px-3 py-2"><span class="text-gray-500">Carrera:</span> {{ $alumno->carrera ?? '—' }}</div>
                    <div class="rounded-lg bg-gray-50 px-3 py-2"><span class="text-gray-500">Ciclo de ingreso:</span> {{ $alumno->ciclo_ingreso ?? '—' }}</div>
                    <div class="rounded-lg bg-gray-50 px-3 py-2"><span class="text-gray-500">Status:</span> {{ $alumno->status ?? '—' }}</div>
                    <div class="rounded-lg bg-gray-50 px-3 py-2"><span class="text-gray-500">Alta:</span> {{ $alumno->created_at->format('d/m/Y H:i') }}</div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Documentos -->
                <div class="lg:col-span-2 card overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                        <h3 class="font-semibold text-gray-800">Documentos ({{ $documentos->total() }})</h3>
                        <a href="{{ route('documentos.create', ['alumno_id' => $alumno->id]) }}" class="text-brand-600 hover:underline text-sm font-medium">+ Nuevo</a>
                    </div>
                    <div class="p-6">
                        <!-- Filtro por tipo -->
                        <form method="GET" class="mb-4 flex gap-2">
                            <select name="tipo" class="rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm" onchange="this.form.submit()">
                                <option value="">Todos los tipos</option>
                                @foreach($tipos as $t)
                                    <option value="{{ $t->id }}" {{ $filtroTipo == $t->id ? 'selected' : '' }}>{{ $t->nombre }}</option>
                                @endforeach
                            </select>
                        </form>

                        <div class="space-y-3">
                            @forelse($documentos as $doc)
                                <a href="{{ route('documentos.show', $doc) }}" class="block border border-gray-100 rounded-xl bg-gray-50/50 p-4 hover:bg-brand-50/50 hover:border-brand-200 transition">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <div class="font-medium text-gray-800">{{ $doc->tipoDocumento->nombre }}
                                                @if($doc->alumno_id !== $alumno->id)
                                                    @php($docCiclo = $doc->alumno?->ultimoCiclo())
                                                    @if($docCiclo)
                                                        <span class="badge bg-purple-100 text-purple-700 text-xs">{{ $docCiclo }}</span>
                                                    @endif
                                                @endif
                                            </div>
                                            <div class="text-sm text-gray-500">Fecha: {{ $doc->fecha?->format('d/m/Y H:i:s') ?? '—' }}</div>
                                            @if($doc->folio)
                                                <div class="text-sm text-gray-500">Folio: <span class="font-mono text-brand-700">{{ $doc->folio }}</span></div>
                                            @endif
                                        </div>
                                        <div class="text-right">
                                            @if($doc->firma)
                                                <span class="badge bg-green-100 text-green-700">Firmado</span>
                                            @else
                                                <span class="badge bg-amber-100 text-amber-700">{{ ucfirst($doc->estado) }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </a>
                            @empty
                                <p class="text-sm text-gray-500">Este alumno no tiene documentos registrados.</p>
                            @endforelse
                        </div>

                        <div class="mt-4">
                            {{ $documentos->links() }}
                        </div>
                    </div>
                </div>

                <!-- Firmas -->
                <div class="card overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100">
                        <h3 class="font-semibold text-gray-800">Firmas registradas ({{ $firmas->count() }})</h3>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-3 gap-2">
                            @forelse($firmas as $firma)
                                <a href="{{ asset('storage/' . $firma->ruta_imagen) }}" target="_blank" title="{{ $firma->created_at->format('d/m/Y H:i') }} · {{ $firma->documento?->tipoDocumento?->nombre }}">
                                    <img src="{{ asset('storage/' . $firma->ruta_imagen) }}" class="border border-gray-200 rounded-lg bg-white shadow-sm" alt="Firma {{ $loop->iteration }}">
                                </a>
                            @empty
                                <p class="text-sm text-gray-500 col-span-3">Este alumno aún no tiene firmas.</p>
                            @endforelse
                        </div>
                        @if($firmas->count() > 0)
                            <a href="{{ route('alumnos.firmas', $alumno) }}" class="mt-4 inline-block text-sm text-emerald-600 hover:underline font-medium">Ver todas las firmas y comparar →</a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
