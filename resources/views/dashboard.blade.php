<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Panel de control') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            @if($listo)
                <div class="mb-6 rounded-xl bg-amber-50 border border-amber-200 p-4 text-sm text-amber-800">
                    Hay una importación de alumnos en proceso en segundo plano. Puedes ver su progreso en la sección <a href="{{ route('importaciones.index') }}" class="underline font-medium">Importar CSV</a>.
                </div>
            @endif

            <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                <div class="card p-5 flex items-start gap-4">
                    <span class="inline-flex items-center justify-center h-11 w-11 rounded-lg bg-brand-100 text-brand-700"><svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg></span>
                    <div>
                        <div class="text-sm font-medium text-gray-500">Alumnos</div>
                        <div class="mt-1 text-3xl font-bold text-brand-700">{{ number_format($stats['alumnos']) }}</div>
                    </div>
                </div>
                <div class="card p-5 flex items-start gap-4">
                    <span class="inline-flex items-center justify-center h-11 w-11 rounded-lg bg-blue-100 text-blue-700"><svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg></span>
                    <div>
                        <div class="text-sm font-medium text-gray-500">Documentos</div>
                        <div class="mt-1 text-3xl font-bold text-blue-700">{{ number_format($stats['documentos']) }}</div>
                    </div>
                </div>
                <div class="card p-5 flex items-start gap-4">
                    <span class="inline-flex items-center justify-center h-11 w-11 rounded-lg bg-emerald-100 text-emerald-700"><svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487zm0 0L19.5 7.125"/></svg></span>
                    <div>
                        <div class="text-sm font-medium text-gray-500">Firmas registradas</div>
                        <div class="mt-1 text-3xl font-bold text-emerald-700">{{ number_format($stats['firmas']) }}</div>
                    </div>
                </div>
                <div class="card p-5 flex items-start gap-4">
                    <span class="inline-flex items-center justify-center h-11 w-11 rounded-lg bg-gray-200 text-gray-700"><svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zM3 19.235v-.11a6.375 6.375 0 0112.75 0v.109A12.318 12.318 0 019.374 21c-2.331 0-4.512-.645-6.374-1.766z"/></svg></span>
                    <div>
                        <div class="text-sm font-medium text-gray-500">Usuarios</div>
                        <div class="mt-1 text-3xl font-bold text-gray-800">{{ $stats['usuarios'] }}</div>
                    </div>
                </div>
                <div class="card p-5 flex items-start gap-4">
                    <span class="inline-flex items-center justify-center h-11 w-11 rounded-lg bg-purple-100 text-purple-700"><svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 6.878V6a2.25 2.25 0 012.25-2.25h7.5A2.25 2.25 0 0118 6v.878m-12 0c.235-.083.487-.128.75-.128h10.5c.263 0 .515.045.75.128m-12 0A2.25 2.25 0 004.5 9v.878m13.5-3A2.25 2.25 0 0119.5 9v.878m0 0a2.246 2.246 0 00-.75-.128H5.25c-.263 0-.515.045-.75.128m15 0A2.25 2.25 0 0121 12v6a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 18v-6c0-.98.626-1.813 1.5-2.122"/></svg></span>
                    <div>
                        <div class="text-sm font-medium text-gray-500">Tipos de documento</div>
                        <div class="mt-1 text-3xl font-bold text-purple-700">{{ $stats['tiposDocumento'] }}</div>
                    </div>
                </div>
            </div>

            <div class="mt-6 grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="card overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                        <h3 class="font-semibold text-gray-800">Últimas importaciones</h3>
                        @if(auth()->user()->role === 'admin')
                            <a href="{{ route('importaciones.index') }}" class="text-sm font-medium text-brand-600 hover:text-brand-800 hover:underline">Ver todas</a>
                        @endif
                    </div>
                    <div class="px-6 py-4">
                        @forelse($ultimasImportaciones as $imp)
                            <div class="flex items-center justify-between py-2 border-b border-gray-100 last:border-0">
                                <div>
                                    <div class="text-sm text-gray-800 font-medium">{{ $imp->nombre_original }}</div>
                                    <div class="text-xs text-gray-500">{{ $imp->created_at->format('d/m/Y H:i') }}</div>
                                </div>
                                <div class="flex items-center gap-3 text-xs">
                                    <span class="text-gray-600">{{ $imp->procesadas }}/{{ $imp->total_filas }}</span>
                                    @php
                                        $colores = ['pendiente'=>'bg-gray-100 text-gray-600','procesando'=>'bg-amber-100 text-amber-700','completado'=>'bg-green-100 text-green-700','fallido'=>'bg-red-100 text-red-700','cancelado'=>'bg-gray-200 text-gray-700'];
                                    @endphp
                                    <span class="px-2 py-0.5 rounded-full font-medium {{ $colores[$imp->estado] ?? 'bg-gray-100 text-gray-600' }}">
                                        {{ ucfirst($imp->estado) }}
                                    </span>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-gray-500">No hay importaciones registradas.</p>
                        @endforelse
                    </div>
                </div>

                <div class="card overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100">
                        <h3 class="font-semibold text-gray-800">Últimos documentos</h3>
                    </div>
                    <div class="px-6 py-4">
                        @forelse($ultimosDocumentos as $doc)
                            <a href="{{ route('documentos.show', $doc) }}" class="flex items-center justify-between py-2 border-b border-gray-100 last:border-0 hover:bg-gray-50">
                                <div>
                                    <div class="text-sm text-gray-800 font-medium">{{ $doc->alumno->nombre_completo }}</div>
                                    <div class="text-xs text-gray-500">{{ $doc->tipoDocumento->nombre }} • {{ $doc->fecha?->format('d/m/Y H:i') }}</div>
                                </div>
                                @if($doc->firma)
                                    <span class="text-xs px-2 py-0.5 rounded-full bg-green-100 text-green-700 font-medium">Firmado</span>
                                @else
                                    <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-600 font-medium">{{ ucfirst($doc->estado) }}</span>
                                @endif
                            </a>
                        @empty
                            <p class="text-sm text-gray-500">No hay documentos registrados.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
