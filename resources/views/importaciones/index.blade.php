<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Importar alumnos (CSV)') }}</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Subir archivo -->
            <div class="card overflow-hidden mb-6">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="font-semibold text-gray-800">Subir archivo CSV</h3>
                </div>
                <div class="p-6">
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
                            <p class="mt-1 text-xs text-gray-500">Cada importación corresponde a un ciclo/semestre. El listado de trabajo se podrá filtrar por este ciclo.</p>
                        </div>

                        <div class="rounded-xl bg-blue-50 border border-blue-200 p-4 text-sm text-blue-800">
                            <strong>Formato esperado de columnas:</strong>
                            <code class="block mt-1 bg-white/60 border border-blue-100 rounded px-2 py-1">matricula, codigo de alumno, nombre completo, carrera, ciclo de ingreso, status</code>
                            <p class="mt-2">Se detectan encabezados por nombre (matricula, código, nombre completo, etc.). Si no hay encabezados, se asume el orden anterior. Debes capturar el <strong>ciclo (semestre)</strong> de este listado obligatoriamente. Si hay alumnos que ya aparecieron antes, se vinculan a este ciclo sin duplicarse. El procesamiento ocurre en <strong>segundo plano</strong> por lotes, por lo que no congela el navegador aunque el archivo tenga más de 130,000 registros.</p>
                        </div>

                        <div>
                            <x-primary-button>Subir y comenzar a importar</x-primary-button>
                        </div>
                    </form>

                    <!-- Progreso en vivo (aparece justo debajo del formulario de importar) -->
                    <div id="progresoImportacion" class="mt-4 space-y-3"></div>
                </div>
            </div>

            <!-- Historial de importaciones -->
            <div class="card overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="font-semibold text-gray-800">Historial de importaciones</h3>
                </div>
                <div id="lista_importaciones" class="p-6 space-y-3">
                    @include('importaciones._lista')
                </div>
            </div>

            <!-- Zona de peligro: borrar todo -->
            <div class="card border-red-200 overflow-hidden mt-6">
                <div class="px-6 py-4 border-b border-red-100 bg-red-50/50">
                    <h3 class="font-semibold text-red-700">Zona de peligro</h3>
                </div>
                <div class="p-6">
                    <button type="button" id="btnBorrarTodo" class="btn-danger">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                        Borrar todos los alumnos
                    </button>
                    <p class="text-xs text-gray-500 mt-2">Esta acción elimina todos los alumnos, sus documentos, firmas e historial de importaciones de forma definitiva. No se puede deshacer.</p>

                    <!-- Panel de confirmación con captcha (oculto por defecto) -->
                    <form method="POST" action="{{ route('importaciones.vaciar') }}" id="formBorrarTodo" class="mt-4 space-y-3 hidden">
                        @csrf
                        <div class="rounded-xl bg-red-50 border border-red-200 p-4">
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
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        // Consulta el progreso de las importaciones en curso y actualiza los cuadritos en vivo.
        let despuesDeSubir = false;

        async function consultarProgreso() {
            let res, datos;
            try {
                res = await fetch('{{ route('importaciones.progreso') }}', { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                datos = await res.json();
            } catch (e) {
                return;
            }
            if (datos.length === 0) {
                // Terminó la importación en curso -> recargar para ver el historial final.
                location.reload();
                return;
            }
            const contenedor = document.getElementById('progresoImportacion');
            if (!contenedor) return;
            datos.forEach(function (imp) {
                let fila = document.getElementById('imp_' + imp.id);
                if (!fila) {
                    fila = document.createElement('div');
                    fila.id = 'imp_' + imp.id;
                    fila.className = 'border border-amber-200 rounded-xl bg-amber-50/60 p-4';
                    fila.innerHTML = '<div class="flex flex-wrap items-center justify-between gap-2">'
                        + '<div class="flex items-center gap-2">'
                        + '<svg class="h-4 w-4 text-brand-600 animate-spin" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>'
                        + '<div>'
                        + '<div class="text-sm font-medium text-gray-800">' + (imp.nombre_original || 'Importando…') + '</div>'
                        + '<div class="text-xs text-gray-500">Subido justo ahora · importándose en segundo plano</div>'
                        + '</div></div>'
                        + '<span class="badge bg-amber-100 text-amber-700">Procesando</span>'
                        + '</div>'
                        + '<div class="mt-3 bg-white border border-gray-100 rounded-xl p-3">'
                        + '<div class="flex flex-wrap items-center justify-between gap-2 text-sm">'
                        + '<span class="font-medium text-gray-700 flex items-center gap-2">'
                        + '<svg class="h-4 w-4 text-brand-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>'
                        + 'Importando · <span class="text-gray-500 font-normal truncate max-w-[16rem]"></span>'
                        + '</span>'
                        + '<span id="contador_' + imp.id + '" class="font-semibold text-right text-brand-700 text-sm"></span>'
                        + '</div>'
                        + '<div class="mt-2 h-3 w-full bg-gray-200 rounded-full overflow-hidden"><div class="h-full bg-gradient-to-r from-brand-600 to-brand-400 transition-all" id="barra_' + imp.id + '" style="width:0%"></div></div>'
                        + '<div class="procs mt-2 text-xs text-gray-600"></div>'
                        + '<div class="ins mt-2 flex flex-wrap items-center gap-2"></div>'
                        + '<div id="detalle_' + imp.id + '" class="mt-1"></div>'
                        + '</div>';
                    contenedor.appendChild(fila);
                }
                const sqrt = fila.querySelector('.truncate');
                if (sqrt) sqrt.textContent = imp.nombre_original || 'archivo.csv';

                const proc = fila.querySelector('.procs');
                if (proc) proc.textContent = 'Procesadas: ' + Number(imp.procesadas).toLocaleString() + ' de ' + Number(imp.total_filas).toLocaleString();

                const contador = document.getElementById('contador_' + imp.id);
                const barra = document.getElementById('barra_' + imp.id);
                if (contador && imp.total_filas) {
                    const pct = Math.min(100, Math.round(imp.procesadas / imp.total_filas * 100));
                    contador.innerHTML = '<span class="font-mono">#' + Number(imp.procesadas).toLocaleString()
                        + ' / ' + Number(imp.total_filas).toLocaleString() + ' (' + pct + '%)</span>'
                        + '<span class="ml-3 rounded-lg bg-green-100 text-green-700 px-2 py-0.5 font-semibold">' + Number(imp.insertadas).toLocaleString() + ' insertados</span>';
                    if (barra) barra.style.width = pct + '%';
                }

                const ins = fila.querySelector('.ins');
                if (ins) {
                    ins.innerHTML = ''
                        + '<span class="badge bg-green-100 text-green-700">' + Number(imp.insertadas).toLocaleString() + ' alumnos</span>';
                }

                const detalle = document.getElementById('detalle_' + imp.id);
                if (detalle) {
                    detalle.innerHTML = '';
                }
            });
        }

        // Subida por AJAX: al enviar el formulario mostramos el cuadrito de progreso en vivo al instante
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
                formData.set('archivo', archivo.files[0], archivo.files[0].name);
                try {
                    const res = await fetch(formImportar.action, {
                        method: 'POST',
                        body: formData,
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
                    });
                    let datos;
                    try {
                        datos = await res.json();
                    } catch (e) {
                        datos = {};
                    }
                    if (!res.ok) {
                        const motivo = (datos.errors && datos.errors.archivo && datos.errors.archivo[0])
                            || datos.message
                            || ('Error HTTP ' + res.status + ' al subir el archivo.');
                        throw new Error(motivo);
                    }
                    despuesDeSubir = true;
                    if (estado) estado.innerHTML = '<strong>Archivo recibido.</strong> Procesando en segundo plano…';
                    formImportar.reset();
                    consultarProgreso();
                    if (window._intervaloProgreso) clearInterval(window._intervaloProgreso);
                    window._intervaloProgreso = setInterval(consultarProgreso, 3000);
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

        // Arrancar el polling si ya hay una importación en curso (página cargada normalmente)
        const hayEnCurso = {{ $procesando ? 'true' : 'false' }};
        if (hayEnCurso) {
            consultarProgreso();
            if (!window._intervaloProgreso) window._intervaloProgreso = setInterval(consultarProgreso, 3000);
        }

        // Botón "Borrar todos los alumnos" (zona de peligro)
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
    </script>
    @endpush
</x-app-layout>
