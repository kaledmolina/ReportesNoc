@php
    $report = $report ?? null; // Aseguramos que la variable exista aunque sea null
    $listaCanales = \App\Helpers\CanalesHelper::getLista();
    $bitacora = collect();

    // Solo ejecutamos la lógica si existe un reporte
    if ($report) {
        // 1. Observaciones Generales
        if($report->observaciones_generales) {
            $bitacora->push([
                'tipo' => 'General',
                'titulo' => 'Observaciones Generales',
                'texto' => $report->observaciones_generales,
                'icono' => 'heroicon-m-pencil-square',
                'color_bg' => 'bg-yellow-100 dark:bg-yellow-900/50',
                'color_text' => 'text-yellow-600 dark:text-yellow-400',
            ]);
        }

        // 2. Proveedores
        if(!empty($report->lista_proveedores)) {
            foreach($report->lista_proveedores as $p) {
                if(!empty($p['detalle'])) {
                    $bitacora->push([
                        'tipo' => 'Proveedor',
                        'titulo' => "Novedad en {$p['nombre']}",
                        'texto' => $p['detalle'],
                        'icono' => 'heroicon-m-globe-alt',
                        'color_bg' => 'bg-blue-100 dark:bg-blue-900/50',
                        'color_text' => 'text-blue-600 dark:text-blue-400',
                    ]);
                }
            }
        }

        // 3. Concentradores
        if(!empty($report->lista_concentradores)) {
            $ignorar = ['bien', 'ok', 'normal', 'sin novedad', 'operativo', 'en linea', 'online'];
            foreach($report->lista_concentradores as $c) {
                if(!empty($c['detalle']) && !in_array(strtolower(trim($c['detalle'])), $ignorar)) {
                    $bitacora->push([
                        'tipo' => 'Concentrador',
                        'titulo' => "Reporte en {$c['nombre']}",
                        'texto' => $c['detalle'],
                        'icono' => 'heroicon-m-server-stack',
                        'color_bg' => 'bg-orange-100 dark:bg-orange-900/50',
                        'color_text' => 'text-orange-600 dark:text-orange-400',
                    ]);
                }
            }
        }

        // 4. Servidores
        if(!empty($report->lista_servidores)) {
            foreach($report->lista_servidores as $s) {
                if(!empty($s['detalle'])) {
                    $bitacora->push([
                        'tipo' => 'Servidor',
                        'titulo' => "Estado de {$s['nombre']}",
                        'texto' => $s['detalle'],
                        'icono' => 'heroicon-m-bolt',
                        'color_bg' => 'bg-indigo-100 dark:bg-indigo-900/50',
                        'color_text' => 'text-indigo-600 dark:text-indigo-400',
                    ]);
                }
            }
        }
        if($report->novedades_servidores) {
            $bitacora->push([
                'tipo' => 'Infraestructura',
                'titulo' => 'Nota de Infraestructura',
                'texto' => $report->novedades_servidores,
                'icono' => 'heroicon-m-server',
                'color_bg' => 'bg-indigo-100 dark:bg-indigo-900/50',
                'color_text' => 'text-indigo-600 dark:text-indigo-400',
            ]);
        }

        // 5. TV
        if($report->tv_observaciones) {
            $bitacora->push([
                'tipo' => 'TV',
                'titulo' => 'Observación de Televisión',
                'texto' => $report->tv_observaciones,
                'icono' => 'heroicon-m-tv',
                'color_bg' => 'bg-purple-100 dark:bg-purple-900/50',
                'color_text' => 'text-purple-600 dark:text-purple-400',
            ]);
        }
    }
@endphp

<x-filament::widget>
    <x-filament::card>
        @if($report)
            {{-- CABECERA --}}
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-4 border-b border-gray-100 dark:border-gray-700 pb-4">
                <div>
                    <h2 class="text-xl font-bold text-gray-800 dark:text-white flex items-center gap-2">
                        <span>📝</span> 
                        <span>Bitácora del Turno – {{ ucfirst($report->turno) }}</span>
                    </h2>
                    <p class="text-sm text-gray-500 mt-1 flex items-center gap-3">
                        <span class="flex items-center gap-1">
                            <x-heroicon-m-calendar class="w-4 h-4"/> 
                            {{ \Carbon\Carbon::parse($report->fecha)->translatedFormat('l d \d\e F') }}
                        </span>
                        <span class="text-gray-300 dark:text-gray-600">|</span>
                        <span class="flex items-center gap-1">
                            <x-heroicon-m-user class="w-4 h-4"/> {{ auth()->user()->name ?? 'Operador' }}
                        </span>
                    </p>
                </div>
            </div>

            {{-- CONTENIDO PRINCIPAL --}}
            <div>
                {{-- Contenedor con fondo oscuro sólido para evitar errores de transparencia --}}
                <div class="h-full bg-gray-50 dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 p-5 flex flex-col">
                    <h3 class="text-sm font-bold text-gray-700 dark:text-gray-200 mb-4 flex items-center gap-2">
                        <x-heroicon-m-chat-bubble-left-right class="w-5 h-5 text-gray-400"/> 
                        Notas y Novedades del Turno
                    </h3>

                    <div class="space-y-4 flex-1 overflow-y-auto max-h-[500px] pr-2 scrollbar-thin scrollbar-thumb-gray-300 dark:scrollbar-thumb-gray-600">
                        
                        @forelse($bitacora as $nota)
                            <div class="flex gap-3 group">
                                <div class="mt-1 flex-shrink-0">
                                    <div class="w-8 h-8 rounded-full {{ $nota['color_bg'] }} flex items-center justify-center {{ $nota['color_text'] }} ring-2 ring-white dark:ring-gray-800 group-hover:scale-110 transition-transform">
                                        @svg($nota['icono'], 'w-4 h-4')
                                    </div>
                                </div>
                                <div class="flex-1 bg-white dark:bg-gray-800 p-3 rounded-lg border border-gray-100 dark:border-gray-700 shadow-sm hover:shadow-md transition-shadow">
                                    <div class="flex justify-between items-start mb-1">
                                        <h4 class="text-xs font-bold text-gray-900 dark:text-white uppercase tracking-wide">
                                            {{ $nota['titulo'] }}
                                        </h4>
                                        <span class="text-[10px] px-1.5 py-0.5 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-500">
                                            {{ $nota['tipo'] }}
                                        </span>
                                    </div>
                                    <p class="text-sm text-gray-600 dark:text-gray-300 leading-relaxed whitespace-pre-line">
                                        {{ $nota['texto'] }}
                                    </p>
                                </div>
                            </div>
                        @empty
                            <div class="flex flex-col items-center justify-center py-12 text-gray-400">
                                <span class="text-sm italic">Sin novedades registradas en ningún ítem.</span>
                            </div>
                        @endforelse

                    </div>
                </div>
            </div>
        @else
            <div class="text-center p-8 text-gray-500">No hay reportes disponibles.</div>
        @endif
    </x-filament::card>
</x-filament::widget>