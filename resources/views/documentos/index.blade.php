<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Documentos') }}</h2>
            <div class="flex items-center gap-3">
                @if(auth()->user()?->role === 'admin')
                    <button type="button" id="btnToggleImportar" class="btn-secondary">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/></svg>
                        Importar credenciales (CSV)
                    </button>
                    <button type="button" id="btnBorrarDocs" class="btn-danger">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                        Borrar todos los documentos
                    </button>
                @endif
                <a href="{{ route('documentos.create') }}" class="btn-primary">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    Nuevo documento
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            @if(auth()->user()?->role === 'admin')
            <!-- Importación de credenciales (collapsable) -->
            <div id="panelImportar" class="card overflow-hidden mb-6 hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="font-semibold text-gray-800">Importar credenciales desde CSV</h3>
                    <span class="badge bg-emerald-100 text-emerald-700">Solo admin</span>
                </div>
                <div class="p-6">
                    <form method="POST" action="{{ route('importaciones.store') }}" enctype="multipart/form-data" class="space-y-4" id="formImportarDocs">
                        @csrf
                        <input type="hidden" name="tipo" value="documentos">

                        <div>
                            <x-input-label for="archivo_docs" value="Archivo CSV" />
                            <input type="file" name="archivo" id="archivo_docs" accept=".csv,.txt" required
                                   class="block mt-1 w-full text-sm text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100 file:font-medium">
                            <x-input-error :messages="$errors->get('archivo')" class="mt-2" />
                            <div id="errorSubidaDocs" class="hidden mt-2 rounded-xl bg-red-50 border border-red-200 p-3 text-sm text-red-700"></div>
                            <div id="estadoSubidaDocs" class="hidden mt-2 rounded-xl bg-blue-50 border border-blue-200 p-3 text-sm text-blue-800"></div>
                        </div>

                        <div class="rounded-xl bg-emerald-50 border border-emerald-200 p-4 text-sm text-emerald-800">
                            <strong>Formato aceptado para credenciales (se detectan por encabezado):</strong>
                            <code class="block mt-1 bg-white/60 border border-emerald-100 rounded px-2 py-1">No., CODIGO, NOMBRE, UNIQUE ID, SEDE, CARRERA</code>
                            <p class="mt-2">Los encabezados se detectan por nombre (codigo, unique id, nombre, etc.); también pueden venir en español con acentos o con "codigo de alumno". Solo son obligatorios <strong>CODIGO</strong> (liga la credencial al alumno del último listado de alumnos) y <strong>UNIQUE ID</strong> (es el folio/identificador de la credencial, diferente para cada estudiante); las demás columnas (No., NOMBRE, SEDE, CARRERA) son opcionales y se ignoran si vienen vacías. Si una credencial ya existe (firmada o pendiente) con ese UNIQUE ID, <strong>no se duplica ni se sobreescribe</strong> al re-importar. El procesamiento ocurre en <strong>segundo plano</strong>.</p>
                        </div>

                        <div>
                            <x-primary-button>Subir y comenzar a importar</x-primary-button>
                        </div>
                    </form>

                    <div id="progresoImportacionDocs" class="mt-4 space-y-3"></div>

                    @if($importacionesDocs->isNotEmpty())
                        <div class="mt-6">
                            <h4 class="text-sm font-semibold text-gray-700 mb-2">Últimas importaciones de credenciales</h4>
                            <div class="space-y-2">
                            @foreach($importacionesDocs as $impDoc)
                                <div class="flex items-center justify-between border border-gray-100 rounded-xl bg-gray-50/50 p-3">
                                    <div>
                                        <div class="text-sm font-medium text-gray-800">{{ $impDoc->nombre_original }}</div>
                                        <div class="text-xs text-gray-500">{{ $impDoc->created_at->format('d/m/Y H:i') }} · {{ $impDoc->usuario?->name }}</div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        @php
                                            $coloresDoc = ['pendiente'=>'bg-gray-100 text-gray-600','procesando'=>'bg-amber-100 text-amber-700','completado'=>'bg-green-100 text-green-700','fallido'=>'bg-red-100 text-red-700','cancelado'=>'bg-gray-200 text-gray-600'];
                                        @endphp
                                        <span class="badge {{ $coloresDoc[$impDoc->estado] ?? 'bg-gray-100 text-gray-600' }}">{{ ucfirst($impDoc->estado) }}</span>
                                        <span class="text-xs text-gray-500">{{ $impDoc->insertadas }} credenciales</span>
                                        @if(in_array($impDoc->estado, ['pendiente', 'procesando']))
                                            <form method="POST" action="{{ route('importaciones.cancelar', $impDoc) }}" data-confirm="¿Cancelar esta importación de credenciales?">
                                                @csrf
                                                <button type="submit" class="action-danger" title="Cancelar">
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
            @endif

            @if(auth()->user()?->role === 'admin')
            <!-- Confirmación de borrar todos los documentos -->
            <form method="POST" action="{{ route('documentos.vaciar') }}" id="formBorrarDocs" class="card border-red-200 overflow-hidden mb-6 hidden">
                @csrf
                <div class="px-4 py-3 border-b border-red-100 bg-red-50/50 flex items-center justify-between">
                    <h3 class="font-semibold text-red-700">Zona de peligro · Borrar todos los documentos</h3>
                </div>
                <div class="p-4">
                    <p class="text-sm text-gray-500">Esta acción elimina todos los documentos y sus firmas de forma definitiva. Los alumnos, su información y sus firmas almacenadas no se borran. No se puede deshacer.</p>
                    <div class="mt-4 rounded-xl bg-red-50 border border-red-200 p-4">
                        <div class="flex items-center gap-3">
                            <img id="captcha-borrar-docs-imagen" src="{{ $captcha_imagen }}" alt="Captcha" class="h-14 border border-gray-300 rounded-lg bg-gray-50">
                            <button type="button" id="btnCaptchaBorrarDocs" class="text-sm text-brand-600 hover:underline font-medium">Actualizar imagen</button>
                        </div>
                        <div class="mt-3">
                            <x-input-label for="captcha_borrar_docs" value="Resuelve el captcha para confirmar la eliminación" />
                            <x-text-input id="captcha_borrar_docs" class="block mt-1 w-48" type="text" name="captcha" maxlength="4" required autocomplete="off" placeholder="Resultado" />
                            <x-input-error :messages="$errors->get('captcha')" class="mt-2" />
                        </div>
                        <button type="submit" class="btn-danger mt-3">Confirmar eliminación definitiva</button>
                    </div>
                </div>
            </form>
            @endif

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
                                                        <img src="{{ $doc->firma->imagen_src }}" alt="Firma del documento {{ $doc->folio }}"
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
        // Alternar el panel de importación de credenciales
        (function () {
            const btn = document.getElementById('btnToggleImportar');
            const panel = document.getElementById('panelImportar');
            if (btn && panel) {
                btn.addEventListener('click', function () {
                    panel.classList.toggle('hidden');
                });
            }

            // Zona de peligro: borrar todos los documentos + captcha
            const btnBorrarDocs = document.getElementById('btnBorrarDocs');
            const formBorrarDocs = document.getElementById('formBorrarDocs');
            if (btnBorrarDocs && formBorrarDocs) {
                btnBorrarDocs.addEventListener('click', function () {
                    formBorrarDocs.classList.toggle('hidden');
                });
                const btnCaptcha = document.getElementById('btnCaptchaBorrarDocs');
                if (btnCaptcha) {
                    btnCaptcha.addEventListener('click', async function () {
                        const res = await fetch('{{ route('captcha.refresh') }}', {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            }
                        });
                        const datos = await res.json();
                        document.getElementById('captcha-borrar-docs-imagen').src = datos.imagen;
                    });
                }
            }
        })();

        // Progreso en vivo de las importaciones de credenciales
        (function () {
            let hayEnCursoDocs = {{ $procesandoDocs ? 'true' : 'false' }};
            let intervaloDocs = null;

            function etiquetaDocs(imp) {
                return 'credenciales';
            }

            async function consultarProgresoDocs() {
                let res, datos;
                try {
                    res = await fetch('{{ route('importaciones.progreso') }}', { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                    datos = await res.json();
                } catch (e) {
                    return;
                }
                const docs = datos.filter(function (d) { return d.tipo === 'documentos'; });
                if (docs.length === 0) {
                    if (intervaloDocs) { clearInterval(intervaloDocs); intervaloDocs = null; }
                    location.reload();
                    return;
                }
                const contenedor = document.getElementById('progresoImportacionDocs');
                if (!contenedor) return;
                docs.forEach(function (imp) {
                    let fila = document.getElementById('impdoc_' + imp.id);
                    if (!fila) {
                        fila = document.createElement('div');
                        fila.id = 'impdoc_' + imp.id;
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
                            + '<span id="contadordoc_' + imp.id + '" class="font-semibold text-right text-brand-700 text-sm"></span>'
                            + '</div>'
                            + '<div class="mt-2 h-3 w-full bg-gray-200 rounded-full overflow-hidden"><div class="h-full bg-gradient-to-r from-brand-600 to-brand-400 transition-all" id="barradoc_' + imp.id + '" style="width:0%"></div></div>'
                            + '<div class="ins mt-2 flex flex-wrap items-center gap-2"></div>'
                            + '</div>';
                        contenedor.appendChild(fila);
                    }
                    const nom = fila.querySelector('.text-sm.font-medium.text-gray-800');
                    if (nom) nom.textContent = imp.nombre_original || 'archivo.csv';

                    const proc = fila.querySelector('.procs');
                    if (proc) proc.textContent = Number(imp.procesadas).toLocaleString() + ' de ' + Number(imp.total_filas).toLocaleString();

                    const contador = document.getElementById('contadordoc_' + imp.id);
                    const barra = document.getElementById('barradoc_' + imp.id);
                    if (contador && imp.total_filas) {
                        const pct = Math.min(100, Math.round(imp.procesadas / imp.total_filas * 100));
                        contador.innerHTML = '<span class="font-mono">#' + Number(imp.procesadas).toLocaleString()
                            + ' / ' + Number(imp.total_filas).toLocaleString() + ' (' + pct + '%)</span>';
                        if (barra) barra.style.width = pct + '%';
                    }

                    const ins = fila.querySelector('.ins');
                    if (ins) {
                        ins.innerHTML = ''
                            + '<span class="badge bg-green-100 text-green-700">' + Number(imp.insertadas).toLocaleString() + ' ' + etiquetaDocs(imp) + '</span>'
                            + (Number(imp.duplicadas) > 0 ? ' <span class="badge bg-purple-100 text-purple-700">' + Number(imp.duplicadas).toLocaleString() + ' ya existentes (sin duplicar)</span>' : '')
                            + (Number(imp.errores) > 0 ? ' <span class="badge bg-red-100 text-red-700">' + Number(imp.errores).toLocaleString() + ' errores</span>' : '');
                    }
                });
            }

            const formDocs = document.getElementById('formImportarDocs');
            if (formDocs) {
                formDocs.addEventListener('submit', async function (e) {
                    e.preventDefault();
                    const btn = formDocs.querySelector('button[type="submit"]');
                    const estado = document.getElementById('estadoSubidaDocs');
                    const error = document.getElementById('errorSubidaDocs');
                    const archivo = document.getElementById('archivo_docs');
                    if (estado) {
                        estado.classList.remove('hidden');
                        estado.innerHTML = '<strong>Subiendo ' + (archivo && archivo.files[0] ? archivo.files[0].name : 'archivo') + '…</strong>';
                    }
                    if (error) error.classList.add('hidden');
                    if (btn) btn.disabled = true;
                    const formData = new FormData(formDocs);
                    if (archivo && archivo.files[0]) formData.set('archivo', archivo.files[0], archivo.files[0].name);
                    try {
                        const res = await fetch(formDocs.action, {
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
                        hayEnCursoDocs = true;
                        if (estado) estado.innerHTML = '<strong>Archivo recibido.</strong> Procesando en segundo plano…';
                        formDocs.reset();
                        consultarProgresoDocs();
                        if (intervaloDocs) clearInterval(intervaloDocs);
                        intervaloDocs = setInterval(consultarProgresoDocs, 3000);
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

            if (hayEnCursoDocs) {
                consultarProgresoDocs();
                intervaloDocs = setInterval(consultarProgresoDocs, 3000);
            }
        })();

        (function () {
            const input = document.getElementById('buscador');
            const tipo = document.getElementById('filtroTipo');
            const estado = document.getElementById('filtroEstado');
            const resultados = document.getElementById('resultados');
            let timer = null;

            function construirUrl(url) {
                const idx = url ? url.indexOf('?') : -1;
                const base = (idx >= 0 ? url.slice(0, idx) : (url || window.location.pathname));
                const params = new URLSearchParams(idx >= 0 ? url.slice(idx + 1) : window.location.search);
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
