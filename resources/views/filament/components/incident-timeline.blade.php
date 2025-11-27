<div class="px-4 py-6">
    <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-4">Cronograma del Incidente</h3>
    
    <ol class="relative border-l border-gray-200 dark:border-gray-700 ml-3">                  
        {{-- 1. CREACIÓN --}}
        <li class="mb-10 ml-6">            
            <span class="absolute flex items-center justify-center w-6 h-6 bg-blue-100 rounded-full -left-3 ring-8 ring-white dark:ring-gray-900 dark:bg-blue-900">
                <x-heroicon-s-plus class="w-3 h-3 text-blue-800 dark:text-blue-300"/>
            </span>
            <h3 class="flex items-center mb-1 text-lg font-semibold text-gray-900 dark:text-white">
                Ticket Creado
                @if($record->created_by)
                    <span class="bg-blue-100 text-blue-800 text-sm font-medium mr-2 px-2.5 py-0.5 rounded dark:bg-blue-900 dark:text-blue-300 ml-3">
                        {{ $record->createdBy->name ?? 'Usuario' }}
                    </span>
                @endif
            </h3>
            <time class="block mb-2 text-sm font-normal leading-none text-gray-400 dark:text-gray-500">
                {{ $record->created_at->format('d/m/Y h:i A') }}
            </time>
            <p class="mb-4 text-base font-normal text-gray-500 dark:text-gray-400">
                El incidente fue reportado inicialmente con estado: <strong>{{ ucfirst($record->estado) }}</strong>.
            </p>
        </li>

        {{-- 2. HISTORIAL DE RESPONSABLES --}}
        @foreach($record->responsibles()->orderBy('pivot_created_at')->get() as $responsible)
            <li class="mb-10 ml-6">
                <span class="absolute flex items-center justify-center w-6 h-6 bg-gray-100 rounded-full -left-3 ring-8 ring-white dark:ring-gray-900 dark:bg-gray-700">
                    <x-heroicon-s-user class="w-3 h-3 text-gray-800 dark:text-gray-300"/>
                </span>
                
                <div class="p-4 bg-white border border-gray-200 rounded-lg shadow-sm dark:bg-gray-700 dark:border-gray-600">
                    <div class="items-center justify-between mb-3 sm:flex">
                        <time class="mb-1 text-xs font-normal text-gray-400 sm:order-last sm:mb-0">
                            {{ \Carbon\Carbon::parse($responsible->pivot->assigned_at)->format('d/m/Y h:i A') }}
                        </time>
                        <div class="text-sm font-normal text-gray-500 lex dark:text-gray-300">
                            Asignado a <span class="font-semibold text-gray-900 dark:text-white">{{ $responsible->name }}</span>
                        </div>
                    </div>

                    {{-- ESTADOS DEL RESPONSABLE --}}
                    <div class="space-y-2">
                        {{-- ACEPTADO --}}
                        @if($responsible->pivot->accepted_at)
                            <div class="flex items-center text-sm text-green-600 dark:text-green-400">
                                <x-heroicon-s-check-circle class="w-4 h-4 mr-1"/>
                                Aceptado / En Proceso: {{ \Carbon\Carbon::parse($responsible->pivot->accepted_at)->format('d/m/Y h:i A') }}
                            </div>
                        @endif

                        {{-- ESCALADO --}}
                        @if($responsible->pivot->status === 'escalated')
                            <div class="flex items-center text-sm text-orange-600 dark:text-orange-400">
                                <x-heroicon-s-arrow-right-circle class="w-4 h-4 mr-1"/>
                                Escalado: {{ $responsible->pivot->notes }}
                            </div>
                        @endif
                        
                        {{-- PENDIENTE --}}
                        @if($responsible->pivot->status === 'pending')
                            <div class="flex items-center text-sm text-yellow-600 dark:text-yellow-400">
                                <x-heroicon-s-clock class="w-4 h-4 mr-1"/>
                                Pendiente de aceptación
                            </div>
                        @endif
                    </div>
                </div>
            </li>
        @endforeach

        {{-- 3. RESOLUCIÓN (Si aplica) --}}
        @if($record->estado === 'resuelto')
            <li class="ml-6">
                <span class="absolute flex items-center justify-center w-6 h-6 bg-green-100 rounded-full -left-3 ring-8 ring-white dark:ring-gray-900 dark:bg-green-900">
                    <x-heroicon-s-check class="w-3 h-3 text-green-800 dark:text-green-300"/>
                </span>
                <h3 class="font-medium leading-tight text-gray-900 dark:text-white">Incidente Resuelto</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    El ticket ha sido marcado como resuelto.
                </p>
            </li>
        @endif
    </ol>
</div>
