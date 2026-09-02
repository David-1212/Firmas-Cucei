@forelse($importaciones as $importacion)
    <div id="imp_{{ $importacion->id }}" class="border border-gray-100 rounded-xl bg-gray-50/50 p-4">
        <div class="flex items-center justify-between">
            <div>
                <div class="text-sm font-medium text-gray-800">{{ $importacion->nombre_original }}</div>
                <div class="text-xs text-gray-500">{{ $importacion->created_at->format('d/m/Y H:i') }} · {{ $importacion->usuario?->name }}</div>
            </div>
            <div class="flex items-center gap-3">
                @if($importacion->ciclo)
                    <span class="badge bg-purple-100 text-purple-700">Ciclo {{ $importacion->ciclo }}</span>
                @endif
                @php
                    $colores = ['pendiente'=>'bg-gray-100 text-gray-600','procesando'=>'bg-amber-100 text-amber-700','completado'=>'bg-green-100 text-green-700','fallido'=>'bg-red-100 text-red-700','cancelado'=>'bg-gray-200 text-gray-600'];
                @endphp
                <span class="badge {{ $colores[$importacion->estado] ?? 'bg-gray-100 text-gray-600' }}">{{ ucfirst($importacion->estado) }}</span>
                @if(in_array($importacion->estado, ['pendiente', 'procesando']))
                    <form method="POST" action="{{ route('importaciones.cancelar', $importacion) }}" data-confirm="¿Cancelar esta importación?">
                        @csrf
                        <button type="submit" class="action-danger" title="Cancelar">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            Cancelar
                        </button>
                    </form>
                @endif
            </div>
        </div>
        <div class="procs mt-2 text-xs text-gray-600">
            @if($importacion->total_filas > 0)
                Procesadas: {{ $importacion->procesadas }}/{{ $importacion->total_filas }}
            @else
                Procesadas: {{ $importacion->procesadas }}
            @endif
        </div>
        <div class="ins mt-2 flex flex-wrap items-center gap-2">
            <span class="badge bg-green-100 text-green-700">{{ $importacion->insertadas }} alumnos</span>
        </div>
        @if($importacion->estado === 'completado')
            <div class="mt-2 text-xs text-gray-500 bg-white border border-gray-100 p-2 rounded-lg">
                Los alumnos de este archivo quedaron en el listado bajo el ciclo
                @if($importacion->ciclo)<strong class="text-purple-700">{{ $importacion->ciclo }}</strong>@endif.
            </div>
        @endif
        @if($importacion->estado === 'procesando')
            @php $pct = $importacion->total_filas > 0 ? min(100, round($importacion->procesadas / $importacion->total_filas * 100)) : 0; @endphp
            <div class="mt-3 bg-white border border-gray-100 rounded-xl p-3">
                <div class="flex items-center justify-between text-sm">
                    <span class="font-medium text-gray-700 flex items-center gap-2">
                        <svg class="h-4 w-4 text-brand-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Importando: <span class="text-gray-500 font-normal truncate max-w-[16rem]">{{ $importacion->nombre_original }}</span>
                    </span>
                    <span id="contador_{{ $importacion->id }}" class="font-semibold text-right text-brand-700 text-sm">
                        <span class="font-mono">#{{ number_format($importacion->procesadas) }} / {{ number_format($importacion->total_filas) }} ({{ $pct }}%)</span>
                        <span class="ml-2 rounded-lg bg-green-100 text-green-700 px-2 py-0.5 font-semibold">{{ number_format($importacion->insertadas) }} insertados</span>
                    </span>
                </div>
                <div class="mt-2 h-3 w-full bg-gray-200 rounded-full overflow-hidden">
                    <div class="h-full bg-gradient-to-r from-brand-600 to-brand-400 transition-all" id="barra_{{ $importacion->id }}" style="width: {{ $pct }}%"></div>
                </div>
                <div class="cuadro-alumnos mt-3">
                    <div class="text-xs text-gray-500 mb-1">Últimos alumn@s importados:</div>
                    <div class="max-h-40 overflow-y-auto border rounded-lg divide-y divide-gray-100 bg-white">
                        @forelse($importacion->ultimos_codigos ?? [] as $a)
                            <div class="px-3 py-1 text-xs font-mono">{{ $a['codigo'] }} <span class="text-gray-400">·</span> {{ $a['nombre'] }}</div>
                        @empty
                            <div class="px-3 py-2 text-xs text-gray-400">Aún no se registran alumnos…</div>
                        @endforelse
                    </div>
                </div>
            </div>
        @endif
        @if($importacion->mensaje_error)
            <div class="mt-2 text-xs text-red-700 bg-red-50 border border-red-100 p-2 rounded-lg">{{ $importacion->mensaje_error }}</div>
        @endif
    </div>
@empty
    <p class="text-sm text-gray-500">No hay importaciones registradas.</p>
@endforelse
