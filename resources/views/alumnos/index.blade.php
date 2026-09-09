<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Alumnos') }}</h2>
            <div class="flex items-center gap-3">
                @if(auth()->user()->role === 'admin')
                    <button type="button" id="btnToggleImportar" class="btn-secondary">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/></svg>
                        Importar alumnos (CSV)
                    </button>
                @endif
@if(auth()->user()->role === 'admin')
                            <a href="{{ route('alumnos.create') }}" class="btn-primary">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                                Nuevo alumno
                            </a>
                            <button type="button" id="btnBorrarTodo" class="btn-danger">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                Borrar todos los alumnos
                            </button>
                        @endif
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            @if(auth()->user()->role === 'admin')
            <!-- Importación de alumnos (collapsable) -->
            <div id="panelImportar" class="card overflow-hidden mb-6 hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="font-semibold text-gray-800">Importar alumnos desde CSV</h3>
                    <span class="badge bg-blue-100 text-blue-700">Solo admin</span>
                </div>
                <div class="p-6 grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div>
                        <form method="POST" action="{{ route('importaciones.store') }}" enctype="multipart/form-data" class="space-y-4" id="formImportar">
                            @csrf

                            <div>
                                <x-input-label for="archivo" value="Archivo CSV" />
                                <input type="file" name="archivo" id="archivo" accept=".csv,.txt" required
                                       class="block mt-1 w-full text-sm text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100 file:font-medium">
                                <x-input-error :messages="$errors->get('archivo')" class="mt-2" />
                                <div id="errorSubida" class="hidden mt-2 rounded-xl bg-red-50 border border-red-200 p-3 text-sm text-red-700"></div>
                                <div id="estadoSubida" class="hidden mt-2 rounded-xl bg-blue-50 border border-blue-200 p-3 text-sm text-blue-800"></div>
                            </div>

                            <div>
                                <x-input-label for="ciclo" value="Ciclo / semestre (obligatorio)" />
                                <input type="text" name="ciclo" id="ciclo" required maxlength="20"
                                       placeholder="Ej. 2026-A, 2026-B… el ciclo al que pertenece este listado"
                                       value="{{ old('ciclo') }}"
                                       class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm">
                                <x-input-error :messages="$errors->get('ciclo')" class="mt-2" />
                                <p class="mt-1 text-xs text-gray-500">Cada importación de alumnos corresponde a un ciclo/semestre. El listado de trabajo se podrá filtrar por este ciclo.</p>
                            </div>

                            <div class="rounded-xl bg-blue-50 border border-blue-200 p-4 text-sm text-blue-800">
                                <strong>Formato aceptado para alumnos (se detectan por encabezado):</strong>
                                <code class="block mt-1 bg-white/60 border border-blue-100 rounded px-2 py-1">matricula, codigo de alumno, nombre completo, carrera, ciclo de ingreso, status</code>
                                <p class="mt-2">Los encabezados se detectan por nombre (matricula, código, nombre completo, etc.); también puede venir "unique id" como matrícula. Solo son obligatorios <strong>codigo</strong> y <strong>nombre completo</strong>; las demás columnas son opcionales y se ignoran si vienen vacías. Si un alumno ya <strong>firmó</strong> en este ciclo, no se duplica ni se sobreescribe al re-importar. El procesamiento ocurre en <strong>segundo plano</strong>.</p>
                            </div>

                            <div>
                                <x-primary-button>Subir y comenzar a importar</x-primary-button>
                            </div>
                        </form>

                        <div id="progresoImportacion" class="mt-4 space-y-3"></div>
                    </div>

                    <div>
                        <!-- Historial de importaciones -->
                        <h4 class="text-sm font-semibold text-gray-700 mb-2">Historial de importaciones</h4>
                        <div id="lista_importaciones" class="space-y-3 max-h-[26rem] overflow-y-auto pr-1">
                            @include('importaciones._lista')
                        </div>
                    </div>
                </div>
            </div>
            @endif

            @if(auth()->user()->role === 'admin')
            <!-- Confirmación de borrar todo -->
            <form method="POST" action="{{ route('importaciones.vaciar') }}" id="formBorrarTodo" class="card border-red-200 overflow-hidden mb-6 hidden">
                @csrf
                <div class="px-4 py-3 border-b border-red-100 bg-red-50/50 flex items-center justify-between">
                    <h3 class="font-semibold text-red-700">Zona de peligro · Borrar todos los alumnos</h3>
                </div>
                <div class="p-4">
                    <p class="text-sm text-gray-500">Esta acción elimina todos los alumnos, sus documentos, firmas e historial de importaciones de forma definitiva. No se puede deshacer.</p>
                    <div class="mt-4 rounded-xl bg-red-50 border border-red-200 p-4">
                        <div class="flex items-center gap-3">
                            <img id="captcha-borrar-imagen" src="{{ $captcha_imagen }}" alt="Captcha" class="h-14 border border-gray-300 rounded-lg bg-gray-50">
                            <button type="button" id="btnCaptchaBorrar" class="text-sm text-brand-600 hover:underline font-medium">Actualizar imagen</button>
                        </div>
                        <div class="mt-3">
                            <x-input-label for="captcha_borrar" value="Resuelve el captcha para confirmar la eliminación" />
                            <x-text-input id="captcha_borrar" class="block mt-1 w-48" type="text" name="captcha" maxlength="4" required autocomplete="off" placeholder="Resultado" />
                            <x-input-error :messages="$errors->get('captcha')" class="mt-2" />
                        </div>
                        <button type="submit" class="btn-danger mt-3">Confirmar eliminación definitiva</button>
                    </div>
                </div>
            </form>
            @endif

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
        // Panel de importación de alumnos (collapsable)
        (function () {
            const btn = document.getElementById('btnToggleImportar');
            const panel = document.getElementById('panelImportar');
            if (btn && panel) {
                btn.addEventListener('click', function () {
                    panel.classList.toggle('hidden');
                });
            }

            // Progreso en vivo
            let intervaloAlumnos = null;
            let despuesDeSubir = false;

            async function consultarProgreso() {
                let res, datos;
                try {
                    res = await fetch('{{ route('importaciones.progreso') }}', { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                    datos = await res.json();
                } catch (e) {
                    return;
                }
                const activas = (datos || []).filter(function (d) { return d.tipo !== 'documentos'; });
                if (activas.length === 0) {
                    if (despuesDeSubir) {
                        location.reload();
                    }
                    return;
                }
                const contenedor = document.getElementById('progresoImportacion');
                if (!contenedor) return;
                activas.forEach(function (imp) {
                    let fila = document.getElementById('imp_' + imp.id);
                    if (!fila) {
                        fila = document.createElement('div');
                        fila.id = 'imp_' + imp.id;
                        fila.className = 'border border-amber-200 rounded-xl bg-amber-50/60 p-4';
                        fila.innerHTML = '<div class="flex flex-wrap items-center justify-between gap-2">'
                            + '<div class="flex items-center gap-2">'
                            + '<svg class="h-4 w-4 text-brand-600 animate-spin" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>'
                            + '<div>'
                            + '<div class="text-sm font-medium text-gray-800"></div>'
                            + '<div class="text-xs text-gray-500">Importándose en segundo plano</div>'
                            + '</div></div>'
                            + '<span class="badge bg-amber-100 text-amber-700">Procesando</span>'
                            + '</div>'
                            + '<div class="mt-3 bg-white border border-gray-100 rounded-xl p-3">'
                            + '<div class="flex flex-wrap items-center justify-between gap-2 text-sm">'
                            + '<span class="font-medium text-gray-700">Procesadas: <span class="procs text-gray-500 font-normal"></span></span>'
                            + '<span id="contador_' + imp.id + '" class="font-semibold text-right text-brand-700 text-sm"></span>'
                            + '</div>'
                            + '<div class="mt-2 h-3 w-full bg-gray-200 rounded-full overflow-hidden"><div class="h-full bg-gradient-to-r from-brand-600 to-brand-400 transition-all" id="barra_' + imp.id + '" style="width:0%"></div></div>'
                            + '<div class="ins mt-2 flex flex-wrap items-center gap-2"></div>'
                            + '</div>';
                        contenedor.appendChild(fila);
                    }
                    const nom = fila.querySelector('.text-sm.font-medium.text-gray-800');
                    if (nom) nom.textContent = imp.nombre_original || 'archivo.csv';

                    const proc = fila.querySelector('.procs');
                    if (proc) proc.textContent = Number(imp.procesadas).toLocaleString() + ' de ' + Number(imp.total_filas).toLocaleString();

                    const contador = document.getElementById('contador_' + imp.id);
                    const barra = document.getElementById('barra_' + imp.id);
                    if (contador && imp.total_filas) {
                        const pct = Math.min(100, Math.round(imp.procesadas / imp.total_filas * 100));
                        contador.innerHTML = '<span class="font-mono">#' + Number(imp.procesadas).toLocaleString()
                            + ' / ' + Number(imp.total_filas).toLocaleString() + ' (' + pct + '%)</span>';
                        if (barra) barra.style.width = pct + '%';
                    }

                    const ins = fila.querySelector('.ins');
                    if (ins) {
                        ins.innerHTML = ''
                            + '<span class="badge bg-green-100 text-green-700">' + Number(imp.insertadas).toLocaleString() + ' alumnos</span>'
                            + (Number(imp.duplicadas) > 0 ? ' <span class="badge bg-purple-100 text-purple-700">' + Number(imp.duplicadas).toLocaleString() + ' ya firmados (sin duplicar)</span>' : '')
                            + (Number(imp.errores) > 0 ? ' <span class="badge bg-red-100 text-red-700">' + Number(imp.errores).toLocaleString() + ' errores</span>' : '');
                    }
                });
            }

            const formImportar = document.getElementById('formImportar');
            if (formImportar) {
                formImportar.addEventListener('submit', async function (e) {
                    e.preventDefault();
                    const btn = formImportar.querySelector('button[type="submit"]');
                    const estado = document.getElementById('estadoSubida');
                    const error = document.getElementById('errorSubida');
                    const archivo = document.getElementById('archivo');
                    if (estado) {
                        estado.classList.remove('hidden');
                        estado.innerHTML = '<strong>Subiendo ' + (archivo && archivo.files[0] ? archivo.files[0].name : 'archivo') + '…</strong>';
                    }
                    if (error) error.classList.add('hidden');
                    if (btn) btn.disabled = true;
                    const formData = new FormData(formImportar);
                    if (archivo && archivo.files[0]) formData.set('archivo', archivo.files[0], archivo.files[0].name);
                    try {
                        const res = await fetch(formImportar.action, {
                            method: 'POST',
                            body: formData,
                            headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
                        });
                        let datos;
                        try { datos = await res.json(); } catch (e) { datos = {}; }
                        if (!res.ok) {
                            const motivo = (datos.errors && datos.errors.archivo && datos.errors.archivo[0])
                                || (datos.errors && datos.errors.ciclo && datos.errors.ciclo[0])
                                || datos.message
                                || ('Error HTTP ' + res.status + ' al subir el archivo.');
                            throw new Error(motivo);
                        }
                        despuesDeSubir = true;
                        if (estado) estado.innerHTML = '<strong>Archivo recibido.</strong> Procesando en segundo plano…';
                        formImportar.reset();
                        consultarProgreso();
                        if (intervaloAlumnos) clearInterval(intervaloAlumnos);
                        intervaloAlumnos = setInterval(consultarProgreso, 3000);
                    } catch (err) {
                        if (error) {
                            error.classList.remove('hidden');
                            error.textContent = err.message;
                        }
                        if (estado) estado.classList.add('hidden');
                    } finally {
                        if (btn) btn.disabled = false;
                    }
                });
            }

            const hayEnCurso = {{ $procesando ? 'true' : 'false' }};
            if (hayEnCurso) {
                consultarProgreso();
                intervaloAlumnos = setInterval(consultarProgreso, 3000);
            }

            // Zona de peligro: borrar todos los alumnos + captcha
            const btnBorrarTodo = document.getElementById('btnBorrarTodo');
            const formBorrarTodo = document.getElementById('formBorrarTodo');
            if (btnBorrarTodo && formBorrarTodo) {
                btnBorrarTodo.addEventListener('click', function () {
                    formBorrarTodo.classList.toggle('hidden');
                });
                document.getElementById('btnCaptchaBorrar').addEventListener('click', async function () {
                    const res = await fetch('{{ route('captcha.refresh') }}', {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        }
                    });
                    const datos = await res.json();
                    document.getElementById('captcha-borrar-imagen').src = datos.imagen;
                });
            }
        })();

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
