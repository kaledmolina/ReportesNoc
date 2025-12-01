<div class="px-4 py-6">
    <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-4">Historial Detallado del Ticket</h3>
    
    <ol class="relative border-l border-gray-200 dark:border-gray-700 ml-3">                  
        {{-- 1. CREACIÓN (Siempre Pendiente al inicio) --}}
        <li class="mb-10 ml-6">            
            <span class="absolute flex items-center justify-center w-6 h-6 bg-blue-100 rounded-full -left-3 ring-8 ring-white dark:ring-gray-900 dark:bg-blue-900">
                <x-heroicon-s-plus class="w-3 h-3 text-blue-800 dark:text-blue-300"/>
            </span>
            <h3 class="flex items-center mb-1 text-lg font-semibold text-gray-900 dark:text-white">
                Ticket Creado (Pendiente)
                @if($record->created_by)
                    <span class="bg-blue-100 text-blue-800 text-sm font-medium mr-2 px-2.5 py-0.5 rounded dark:bg-blue-900 dark:text-blue-300 ml-3">
                        Por: {{ $record->createdBy->name ?? 'Usuario' }}
                    </span>
                @endif
            </h3>
            <time class="block mb-2 text-sm font-normal leading-none text-gray-400 dark:text-gray-500">
                {{ $record->created_at->format('d/m/Y h:i A') }}
            </time>
            <p class="mb-4 text-base font-normal text-gray-500 dark:text-gray-400">
                El incidente ingresó al sistema en estado <strong>Pendiente</strong>.
            </p>
        </li>

        {{-- 2. HISTORIAL DE RESPONSABLES Y CAMBIOS DE ESTADO --}}
        @foreach($record->responsibles()->orderBy('pivot_created_at')->get() as $responsible)
            @php
                $assignedAt = \Carbon\Carbon::parse($responsible->pivot->assigned_at);
                $acceptedAt = $responsible->pivot->accepted_at ? \Carbon\Carbon::parse($responsible->pivot->accepted_at) : null;
                $escalatedAt = $responsible->pivot->escalated_at ? \Carbon\Carbon::parse($responsible->pivot->escalated_at) : null;
                $resolvedAt = $responsible->pivot->resolved_at ? \Carbon\Carbon::parse($responsible->pivot->resolved_at) : null;
                
                // Calcular tiempo en pendiente (desde asignación hasta aceptación)
                $timePending = $acceptedAt ? $assignedAt->diffForHumans($acceptedAt, ['parts' => 2, 'syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE]) : null;
                
                // Calcular tiempo en proceso (desde aceptación hasta resolución o escalado)
                $endTime = $resolvedAt ?? $escalatedAt;
                $timeProcessing = ($acceptedAt && $endTime) ? $acceptedAt->diffForHumans($endTime, ['parts' => 2, 'syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE]) : null;
            @endphp

            <li class="mb-10 ml-6">
                <span class="absolute flex items-center justify-center w-6 h-6 bg-gray-100 rounded-full -left-3 ring-8 ring-white dark:ring-gray-900 dark:bg-gray-700">
                    <x-heroicon-s-user class="w-3 h-3 text-gray-800 dark:text-gray-300"/>
                </span>
                
                <div class="p-4 bg-white border border-gray-200 rounded-lg shadow-sm dark:bg-gray-700 dark:border-gray-600">
                    <div class="items-center justify-between mb-3 sm:flex">
                        <time class="mb-1 text-xs font-normal text-gray-400 sm:order-last sm:mb-0">
                            Asignado: {{ $assignedAt->format('d/m/Y h:i A') }}
                        </time>
                        <div class="text-sm font-normal text-gray-500 lex dark:text-gray-300">
                            Responsable: <span class="font-semibold text-gray-900 dark:text-white">{{ $responsible->name }}</span>
                        </div>
                    </div>

                    <div class="space-y-3">
                        {{-- ACEPTACIÓN (CAMBIO A EN PROCESO) --}}
                        @if($acceptedAt)
                            <div class="flex flex-col p-2 bg-green-50 rounded dark:bg-green-900/30 border border-green-100 dark:border-green-800">
                                <div class="flex items-center text-sm text-green-700 dark:text-green-400 font-medium">
                                    <x-heroicon-s-play class="w-4 h-4 mr-2"/>
                                    Inició atención (En Proceso)
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 ml-6 mt-1">
                                    Fecha: {{ $acceptedAt->format('d/m/Y h:i A') }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 ml-6">
                                    Tiempo de espera (Pendiente): <strong>{{ $timePending }}</strong>
                                </div>
                            </div>
                        @else
                            <div class="flex items-center text-sm text-yellow-600 dark:text-yellow-400">
                                <x-heroicon-s-clock class="w-4 h-4 mr-1"/>
                                Esperando atención...
                            </div>
                        @endif

                        {{-- ESCALADO --}}
                        @if($escalatedAt)
                            <div class="flex flex-col p-2 bg-orange-50 rounded dark:bg-orange-900/30 border border-orange-100 dark:border-orange-800">
                                <div class="flex items-center text-sm text-orange-700 dark:text-orange-400 font-medium">
                                    <x-heroicon-s-arrow-right-circle class="w-4 h-4 mr-2"/>
                                    Escalado / Reasignado
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 ml-6 mt-1">
                                    Fecha: {{ $escalatedAt->format('d/m/Y h:i A') }}
                                </div>
                                @if($timeProcessing)
                                    <div class="text-xs text-gray-500 dark:text-gray-400 ml-6">
                                        Tiempo trabajado: <strong>{{ $timeProcessing }}</strong>
                                    </div>
                                @endif
                                <div class="text-xs text-gray-600 dark:text-gray-300 ml-6 mt-1 italic">
                                    "{{ $responsible->pivot->notes }}"
                                </div>
                            </div>
                        @endif

                        {{-- RESOLUCIÓN --}}
                        @if($resolvedAt)
                            <div class="flex flex-col p-2 bg-blue-50 rounded dark:bg-blue-900/30 border border-blue-100 dark:border-blue-800">
                                <div class="flex items-center text-sm text-blue-700 dark:text-blue-400 font-medium">
                                    <x-heroicon-s-check-circle class="w-4 h-4 mr-2"/>
                                    Resuelto
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 ml-6 mt-1">
                                    Fecha: {{ $resolvedAt->format('d/m/Y h:i A') }}
                                </div>
                                @if($timeProcessing)
                                    <div class="text-xs text-gray-500 dark:text-gray-400 ml-6">
                                        Tiempo de atención: <strong>{{ $timeProcessing }}</strong>
                                    </div>
                                @endif
                                <div class="text-xs text-gray-600 dark:text-gray-300 ml-6 mt-1 italic">
                                    Notas: "{{ $responsible->pivot->notes }}"
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </li>
        @endforeach

        {{-- 3. CIERRE FINAL (Resumen) --}}
        @if($record->estado === 'resuelto')
            <li class="ml-6">
                <span class="absolute flex items-center justify-center w-6 h-6 bg-green-100 rounded-full -left-3 ring-8 ring-white dark:ring-gray-900 dark:bg-green-900">
                    <x-heroicon-s-check class="w-3 h-3 text-green-800 dark:text-green-300"/>
                </span>
                <h3 class="font-medium leading-tight text-gray-900 dark:text-white">Incidente Cerrado</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Tiempo total desde reporte: <strong>{{ $record->created_at->diffForHumans($record->updated_at, ['parts' => 2, 'syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE]) }}</strong>
                </p>
            </li>
        @endif
    </ol>
</div>
