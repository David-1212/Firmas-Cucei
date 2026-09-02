<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Alumnos') }}</h2>
            @if(auth()->user()->role === 'admin')
                <a href="{{ route('alumnos.create') }}" class="btn-primary">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    Nuevo alumno
                </a>
            @endif
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="card overflow-hidden">
                <div class="p-6">
                    <div class="mb-4 flex items-center justify-between">
                        <div class="text-sm text-gray-600">
                            @if($cicloUltimo !== '')
                                <span class="inline-flex items-center gap-1.5 rounded-lg bg-purple-50 border border-purple-200 px-3 py-1.5 text-sm font-medium text-purple-800">
                                    <svg class="h-4 w-4 text-purple-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/></svg>
                                    Ciclo actual: <span class="badge bg-purple-100 text-purple-700 ml-1">{{ $cicloUltimo }}</span>
                                </span>
                            @else
                                <span class="text-gray-400">Aún no hay importaciones con ciclo asignado.</span>
                            @endif
                        </div>
                    </div>
                    <!-- Búsqueda en vivo -->
                    <div class="mb-4 grid grid-cols-1 md:grid-cols-3 gap-3">
                        <div class="md:col-span-1 relative">
                            <span class="absolute inset-y-0 left-3 flex items-center text-gray-400"><svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg></span>
                            <input type="text" id="buscador"
                                   value="{{ $busqueda }}"
                                   placeholder="Buscar por código, nombre, apellido, carrera o correo..."
                                   class="w-full pl-10 rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm">
                        </div>
                        <div>
                            <select id="filtroCarrera" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm">
                                <option value="">Todas las carreras</option>
                                @foreach($carreras as $carrera)
                                    <option value="{{ $carrera }}" {{ $filtroCarrera === $carrera ? 'selected' : '' }}>{{ $carrera }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <select id="filtroStatus" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm">
                                <option value="">Todos los status</option>
                                @foreach($statuses as $status)
                                    <option value="{{ $status }}" {{ $filtroStatus === $status ? 'selected' : '' }}>{{ $status }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div id="resultados">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="th">Matrícula</th>
                                        <th class="th">Código</th>
                                        <th class="th">Nombre completo</th>
                                        <th class="th">Carrera</th>
                                        <th class="th">Ciclo de ingreso</th>
                                        <th class="th">Status</th>
                                        <th class="th text-right">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-100">
                                    @forelse($alumnos as $alumno)
                                        <tr class="hover:bg-brand-50/40 transition">
                                            <td class="px-6 py-4 text-sm font-mono text-gray-700">
                                                @php
                                                    $matriculas = collect(explode('/', (string) $alumno->matricula))
                                                        ->map(fn ($m) => trim($m))
                                                        ->filter(fn ($m) => $m !== '')
                                                        ->values();
                                                @endphp
                                                @if($matriculas->isEmpty())
                                                    <span class="text-gray-400">—</span>
                                                @else
                                                    <span class="inline-flex flex-wrap gap-1">
                                                        @foreach($matriculas as $matricula)
                                                            <span class="inline-flex items-center rounded bg-gray-100 px-1.5 py-0.5 text-xs font-medium text-gray-700">{{ $matricula }}</span>
                                                        @endforeach
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 text-sm font-mono text-brand-700">{{ $alumno->codigo }}</td>
                                            <td class="px-6 py-4 text-sm text-gray-800 font-medium">{{ $alumno->nombre_completo }}</td>
                                            <td class="px-6 py-4 text-sm text-gray-500">{{ $alumno->carrera ?? '—' }}</td>
                                            <td class="px-6 py-4 text-sm text-gray-500">{{ $alumno->ciclo_ingreso ?? '—' }}</td>
                                            <td class="px-6 py-4 text-sm">
                                                @if($alumno->status)
                                                    <span class="badge {{ $alumno->status === 'Activo' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">{{ $alumno->status }}</span>
                                                @else
                                                    <span class="text-gray-400">—</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 text-right whitespace-nowrap space-x-2">
                                                <x-action-link :href="route('alumnos.show', $alumno)" color="view" icon='<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>'>Ver</x-action-link>
                                                <x-action-link :href="route('alumnos.firmas', $alumno)" color="sign" icon='<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487zm0 0L19.5 7.125"/></svg>'>Firmas</x-action-link>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="px-6 py-10 text-center text-sm text-gray-500">
                                                @if($busqueda !== '')
                                                    No se encontraron alumnos con «{{ $busqueda }}».
                                                @else
                                                    No hay alumnos registrados.
                                                @endif
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4">
                            {{ $alumnos->links() }}
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
            const carrera = document.getElementById('filtroCarrera');
            const status = document.getElementById('filtroStatus');
            const resultados = document.getElementById('resultados');
            let timer = null;

            function construirUrl(url) {
                const idx = url ? url.indexOf('?') : -1;
                const base = (idx >= 0 ? url.slice(0, idx) : (url || window.location.pathname));
                const params = new URLSearchParams(idx >= 0 ? url.slice(idx + 1) : window.location.search);
                params.set('q', input.value);
                if (carrera.value) params.set('carrera', carrera.value); else params.delete('carrera');
                if (status.value) params.set('status', status.value); else params.delete('status');
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

            carrera.addEventListener('change', () => buscar());
            status.addEventListener('change', () => buscar());

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
