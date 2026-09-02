<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Documentos') }}</h2>
            <a href="{{ route('documentos.create') }}" class="btn-primary">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                Nuevo documento
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="card overflow-hidden">
                <div class="p-6">
                    <!-- Búsqueda y filtros -->
                    <div class="mb-4 grid grid-cols-1 md:grid-cols-4 gap-3">
                        <div class="md:col-span-2 relative">
                            <span class="absolute inset-y-0 left-3 flex items-center text-gray-400"><svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg></span>
                            <input type="text" id="buscador"
                                   value="{{ $busqueda }}"
                                   placeholder="Buscar por alumno, folio, observaciones o tipo..."
                                   class="w-full pl-10 rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm">
                        </div>
                        <div>
                            <select id="filtroTipo" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm">
                                <option value="">Todos los tipos</option>
                                @foreach($tipos as $t)
                                    <option value="{{ $t->id }}" {{ $tipo == $t->id ? 'selected' : '' }}>{{ $t->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <select id="filtroEstado" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm">
                                <option value="">Todos los estados</option>
                                <option value="pendiente" {{ $estado === 'pendiente' ? 'selected' : '' }}>Pendiente</option>
                                <option value="firmado" {{ $estado === 'firmado' ? 'selected' : '' }}>Firmado</option>
                                <option value="entregado" {{ $estado === 'entregado' ? 'selected' : '' }}>Entregado</option>
                            </select>
                        </div>
                    </div>

                    <div id="resultados">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="th">Firma</th>
                                        <th class="th">Alumno</th>
                                        <th class="th">Tipo</th>
                                        <th class="th">Folio</th>
                                        <th class="th">Fecha</th>
                                        <th class="th text-center">Estado</th>
                                        <th class="th text-right">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-100">
                                    @forelse($documentos as $doc)
                                        <tr class="hover:bg-brand-50/40 transition">
                                            <td class="px-6 py-4">
                                                @if($doc->firma)
                                                    <a href="{{ route('documentos.show', $doc) }}" title="Ver firma del documento {{ $doc->folio }}" class="block">
                                                        <img src="{{ asset('storage/' . $doc->firma->ruta_imagen) }}" alt="Firma del documento {{ $doc->folio }}"
                                                             class="h-14 w-28 object-contain object-center rounded-lg border border-gray-200 bg-white shadow-sm hover:shadow transition">
                                                    </a>
                                                @else
                                                    <span class="text-gray-300">Sin firma</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="text-sm font-medium text-gray-800">{{ $doc->alumno?->nombre_completo ?? 'Alumno eliminado' }}</div>
                                                <div class="text-xs text-gray-500 font-mono">{{ $doc->alumno?->codigo ?? '—' }}</div>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-700">{{ $doc->tipoDocumento->nombre }}</td>
                                            <td class="px-6 py-4 text-sm text-gray-500 font-mono">{{ $doc->folio ?? '—' }}</td>
                                            <td class="px-6 py-4 text-sm text-gray-500">{{ $doc->fecha?->format('d/m/Y H:i') }}</td>
                                            <td class="px-6 py-4 text-center">
                                                @if($doc->estado === 'firmado')
                                                    <span class="badge bg-green-100 text-green-700">Firmado</span>
                                                @elseif($doc->estado === 'entregado')
                                                    <span class="badge bg-blue-100 text-blue-700">Entregado</span>
                                                @else
                                                    <span class="badge bg-amber-100 text-amber-700">Pendiente</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 text-right whitespace-nowrap space-x-2">
                                                <x-action-link :href="route('documentos.show', $doc)" color="view" icon='<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>'>Ver</x-action-link>
                                                @if($doc->estado !== 'firmado')
                                                    <x-action-link :href="route('documentos.firmar', $doc)" color="sign" icon='<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487zm0 0L19.5 7.125"/></svg>'>Firmar</x-action-link>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="px-6 py-10 text-center text-sm text-gray-500">No se encontraron documentos.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4">
                            {{ $documentos->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        (function () {
            const input = document.getElementById('buscador');
            const tipo = document.getElementById('filtroTipo');
            const estado = document.getElementById('filtroEstado');
            const resultados = document.getElementById('resultados');
            let timer = null;

            function construirUrl(url) {
                const base = url || window.location.pathname;
                const params = new URLSearchParams(window.location.search);
                params.set('q', input.value);
                if (tipo.value) params.set('tipo', tipo.value); else params.delete('tipo');
                if (estado.value) params.set('estado', estado.value); else params.delete('estado');
                return base + '?' + params.toString();
            }

            async function buscar(url = null) {
                const res = await fetch(construirUrl(url), { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                const html = await res.text();
                const doc = new DOMParser().parseFromString(html, 'text/html');
                const nuevo = doc.getElementById('resultados');
                if (nuevo) resultados.innerHTML = nuevo.innerHTML;
                window.history.pushState({}, '', construirUrl(url));
            }

            input.addEventListener('input', function () {
                clearTimeout(timer);
                timer = setTimeout(() => buscar(), 300);
            });

            tipo.addEventListener('change', () => buscar());
            estado.addEventListener('change', () => buscar());

            document.addEventListener('click', function (e) {
                if (e.target.closest('.pagination a')) {
                    e.preventDefault();
                    buscar(e.target.closest('a').getAttribute('href'));
                }
            });
        })();
    </script>
    @endpush
</x-app-layout>
