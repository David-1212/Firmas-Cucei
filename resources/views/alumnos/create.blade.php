<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Registrar alumno') }}</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="card overflow-hidden">
                <div class="p-6">
                    <form method="POST" action="{{ route('alumnos.store') }}" class="space-y-4">
                        @csrf

                        <div>
                            <x-input-label for="codigo" value="Código escolar" />
                            <p class="text-xs text-gray-500 mb-1">Escribe el código; si ya existe te mostrará el alumno registrado.</p>
                            <div class="relative">
                                <x-text-input id="codigo" class="block mt-1 w-full" type="text" name="codigo" :value="old('codigo')" required autofocus placeholder="ej. 123456789" />
                                <div id="resultados_alumnos" class="absolute z-50 mt-1 left-0 right-0 max-h-60 overflow-y-auto bg-white border border-gray-300 rounded-md shadow-lg divide-y divide-gray-100 hidden"></div>
                            </div>
                            <div id="aviso_existente" class="mt-1 text-xs text-amber-600 hidden">
                                Este código corresponde a: <span class="font-medium" id="nombre_existente"></span>
                            </div>
                            <div id="sin_resultados_codigo" class="mt-1 text-xs text-red-600 hidden"></div>
                            <x-input-error :messages="$errors->get('codigo')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="matricula" value="Matrícula" />
                            <x-text-input id="matricula" class="block mt-1 w-full" type="text" name="matricula" :value="old('matricula')" />
                            <x-input-error :messages="$errors->get('matricula')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="nombre_completo" value="Nombre completo" />
                            <x-text-input id="nombre_completo" class="block mt-1 w-full" type="text" name="nombre_completo" :value="old('nombre_completo')" required />
                            <x-input-error :messages="$errors->get('nombre_completo')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="carrera" value="Carrera" />
                            <x-text-input id="carrera" class="block mt-1 w-full" type="text" name="carrera" :value="old('carrera')" />
                            <x-input-error :messages="$errors->get('carrera')" class="mt-2" />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="ciclo_ingreso" value="Ciclo de ingreso" />
                                <x-text-input id="ciclo_ingreso" class="block mt-1 w-full" type="text" name="ciclo_ingreso" :value="old('ciclo_ingreso')" placeholder="ej. 2026-A" />
                                <x-input-error :messages="$errors->get('ciclo_ingreso')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="status" value="Status" />
                                <x-text-input id="status" class="block mt-1 w-full" type="text" name="status" :value="old('status')" />
                                <x-input-error :messages="$errors->get('status')" class="mt-2" />
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100">
                            <a href="{{ route('alumnos.index') }}" class="btn-secondary">Cancelar</a>
                            <x-primary-button>Guardar alumno</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        (function () {
            const input = document.getElementById('codigo');
            const resultados = document.getElementById('resultados_alumnos');
            const aviso = document.getElementById('aviso_existente');
            const nombreExistente = document.getElementById('nombre_existente');
            const sinResultados = document.getElementById('sin_resultados_codigo');
            let timer = null;
            let valorActivo = '';

            const urlBase = '{{ route('documentos.buscarAlumnos') }}';

            async function buscar(termino) {
                try {
                    const res = await fetch(urlBase + '?q=' + encodeURIComponent(termino), {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    const datos = await res.json();

                    if (termino !== input.value.trim()) return;

                    resultados.innerHTML = '';
                    resultados.classList.add('hidden');
                    sinResultados.classList.add('hidden');

                    if (datos.length === 0) {
                        sinResultados.textContent = 'No se encontró ningún alumno con ese código.';
                        sinResultados.classList.remove('hidden');
                        return;
                    }

                    resultados.classList.remove('hidden');
                    datos.forEach(function (a) {
                        const opcion = document.createElement('button');
                        opcion.type = 'button';
                        opcion.className = 'w-full text-left px-3 py-2 hover:bg-brand-50 text-sm';
                        opcion.innerHTML = '<span class="font-medium text-brand-700 font-mono">' + a.codigo + '</span> — ' +
                            a.nombre_completo + (a.carrera ? ' <span class="text-gray-400">(' + a.carrera + ')</span>' : '');
                        opcion.addEventListener('mousedown', function (e) {
                            e.preventDefault();
                            elegir(a);
                        });
                        resultados.appendChild(opcion);
                    });
                } catch (e) {}
            }

            function elegir(a) {
                input.value = a.codigo;
                valorActivo = a.codigo;
                resultados.classList.add('hidden');
                sinResultados.classList.add('hidden');
                aviso.classList.remove('hidden');
                nombreExistente.textContent = a.nombre_completo;
            }

            function ocultar() {
                resultados.classList.add('hidden');
                sinResultados.classList.add('hidden');
            }

            input.addEventListener('input', function () {
                clearTimeout(timer);
                const termino = this.value.trim();
                aviso.classList.add('hidden');

                if (termino.length < 2 || termino === valorActivo) {
                    ocultar();
                    return;
                }
                timer = setTimeout(() => buscar(termino), 350);
            });

            document.addEventListener('click', function (e) {
                if (!e.target.closest('#codigo') && !e.target.closest('#resultados_alumnos')) {
                    ocultar();
                }
            });
        })();
    </script>
    @endpush
</x-app-layout>
