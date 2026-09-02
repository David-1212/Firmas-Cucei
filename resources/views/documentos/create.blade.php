<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Generar documento') }}</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="card overflow-hidden">
                <div class="p-6">
                    <form method="POST" action="{{ route('documentos.store') }}" class="space-y-4">
                        @csrf

                        <!-- Búsqueda de alumno -->
                        <div>
                            <x-input-label value="Buscar alumno" />
                            <p class="text-xs text-gray-500 mb-1">Escribe el código o nombre del alumno y selecciónalo de la lista.</p>
                            <div class="relative">
                                <input type="text" id="buscar_alumno"
                                       class="input block mt-1 w-full border-gray-300"
                                       placeholder="Código o nombre del alumno..."
                                       autocomplete="off"
                                       value="{{ $alumno ? $alumno->codigo . ' — ' . $alumno->nombre_completo : '' }}">
                                <input type="hidden" name="alumno_id" id="alumno_id" value="{{ $alumno?->id }}">
                                <div id="resultados_alumnos" class="absolute z-50 mt-1 left-0 right-0 max-h-60 overflow-y-auto bg-white border border-gray-300 rounded-md shadow-lg divide-y divide-gray-100 hidden"></div>
                            </div>
                            <div id="alumno_seleccionado" class="mt-1 text-xs {{ $alumno ? '' : 'hidden' }}">
                                <span class="text-emerald-600 font-medium" id="alumno_nombre">{{ $alumno?->nombre_completo }}</span>
                                <span class="text-gray-500" id="alumno_codigo">{{ $alumno?->codigo }}</span>
                            </div>
                            <div id="folio_preview" class="mt-2 rounded-lg border border-brand-200 bg-brand-50 px-3 py-2 text-sm text-brand-800 {{ $alumno ? '' : 'hidden' }}">
                                <span class="text-gray-500">Folio que se asignará:</span>
                                <span class="font-mono font-semibold" id="folio_valor">{{ $alumno ? $alumno->codigo . '-' . $alumno->id . '-1' : '' }}</span>
                            </div>
                            <div id="sin_resultados" class="mt-1 text-xs text-red-600 hidden">No se encontró ningún alumno. Verifica el código o nombre.</div>
                            <x-input-error :messages="$errors->get('alumno_id')" class="mt-2" />
                        </div>

                        <!-- Select box de tipo de documento -->
                        <div>
                            <x-input-label for="tipo_documento_id" value="Documento a generar" />
                            <select name="tipo_documento_id" id="tipo_documento_id" required class="input block mt-1 w-full border-gray-300">
                                <option value="">-- Selecciona el documento --</option>
                                @foreach($tipos as $tipo)
                                    <option value="{{ $tipo->id }}" {{ old('tipo_documento_id') == $tipo->id ? 'selected' : '' }}>{{ $tipo->nombre }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('tipo_documento_id')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="observaciones" value="Observaciones (opcional)" />
                            <textarea name="observaciones" id="observaciones" rows="3" class="input block mt-1 w-full border-gray-300">{{ old('observaciones') }}</textarea>
                            <x-input-error :messages="$errors->get('observaciones')" class="mt-2" />
                        </div>

                        <div class="rounded-md bg-gray-50 border border-gray-200 p-4 text-sm text-gray-600 space-y-1">
                            <div>El <strong>folio</strong> se asignará automáticamente con el código del alumno + su id + un número secuencial (siempre único), ej. <span class="font-mono">222333444-15-1</span>.</div>
                            <div>La <strong>fecha y hora</strong> se registrarán automáticamente al guardar.</div>
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100">
                            <a href="{{ route('documentos.index') }}" class="btn-secondary">Cancelar</a>
                            <x-primary-button>Generar documento</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        (function () {
            const input = document.getElementById('buscar_alumno');
            const idHidden = document.getElementById('alumno_id');
            const resultados = document.getElementById('resultados_alumnos');
            const seleccionado = document.getElementById('alumno_seleccionado');
            const sinResultados = document.getElementById('sin_resultados');
            const alumnoNombre = document.getElementById('alumno_nombre');
            const alumnoCodigo = document.getElementById('alumno_codigo');
            const folioPreview = document.getElementById('folio_preview');
            const folioValor = document.getElementById('folio_valor');
            let timer = null;

            const urlBase = '{{ route('documentos.buscarAlumnos') }}';
            const urlFolio = '{{ route('documentos.siguienteFolio') }}';

            async function cargarFolio(id) {
                try {
                    const res = await fetch(urlFolio + '?alumno_id=' + encodeURIComponent(id), {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    const datos = await res.json();
                    if (datos.folio) {
                        folioValor.textContent = datos.folio;
                        folioPreview.classList.remove('hidden');
                    } else {
                        folioPreview.classList.add('hidden');
                    }
                } catch (e) {
                    folioPreview.classList.add('hidden');
                }
            }

            async function buscar(termino) {
                const res = await fetch(urlBase + '?q=' + encodeURIComponent(termino), { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                const datos = await res.json();
                resultados.innerHTML = '';
                resultados.classList.remove('hidden');
                sinResultados.classList.add('hidden');

                if (datos.length === 0) {
                    sinResultados.classList.remove('hidden');
                    return;
                }

                datos.forEach(function (a) {
                    const opcion = document.createElement('button');
                    opcion.type = 'button';
                    opcion.className = 'w-full text-left px-3 py-2 hover:bg-brand-50 text-sm';
                    opcion.innerHTML = '<span class="font-medium text-brand-700 font-mono">' + a.codigo + '</span> — ' + a.nombre_completo + (a.carrera ? ' <span class="text-gray-400">(' + a.carrera + ')</span>' : '');
                    opcion.addEventListener('click', function () {
                        idHidden.value = a.id;
                        input.value = a.etiqueta;
                        alumnoNombre.textContent = a.nombre_completo;
                        alumnoCodigo.textContent = a.codigo;
                        seleccionado.classList.remove('hidden');
                        resultados.classList.add('hidden');
                        cargarFolio(a.id);
                    });
                    resultados.appendChild(opcion);
                });
            }

            input.addEventListener('input', function () {
                clearTimeout(timer);
                const termino = this.value.trim();
                if (termino.length < 2) {
                    resultados.classList.add('hidden');
                    sinResultados.classList.add('hidden');
                    idHidden.value = '';
                    folioPreview.classList.add('hidden');
                    return;
                }
                timer = setTimeout(() => buscar(termino), 350);
            });

            document.addEventListener('click', function (e) {
                if (!e.target.closest('#buscar_alumno') && !e.target.closest('#resultados_alumnos')) {
                    resultados.classList.add('hidden');
                    sinResultados.classList.add('hidden');
                }
            });

            // Si ya hay un alumno preseleccionado (viene de la ficha del alumno)
            @if($alumno)
                idHidden.value = '{{ $alumno->id }}';
                cargarFolio('{{ $alumno->id }}');
            @endif
        })();
    </script>
    @endpush
</x-app-layout>
