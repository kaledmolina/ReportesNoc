<x-filament::widget>
    <x-filament::card>
        @if($report)
            {{-- CABECERA DEL REPORTE --}}
            <div class="border-b border-gray-200 dark:border-gray-700 pb-4 mb-4">
                <h2 class="text-xl font-bold text-gray-800 dark:text-white">
                    📡 Centro de Operaciones de Red – Informe {{ ucfirst($report->turno) }}
                </h2>
                <p class="text-sm text-gray-500 mt-1">
                    📅 Fecha: {{ \Carbon\Carbon::parse($report->fecha)->translatedFormat('d \d\e F') }} | 
                    📍 Ciudad: {{ $report->ciudad }}
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- COLUMNA IZQUIERDA: ESTADO GENERAL --}}
                <div class="space-y-4 text-sm">
                    {{-- 1 & 2: Concentradores y Proveedores --}}
                    <div class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <span class="font-medium">1️⃣ Concentradores</span>
                        <span class="px-2 py-1 rounded text-xs font-bold {{ $report->concentradores_ok ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                            {{ $report->concentradores_ok ? 'Óptimo ✅' : 'Falla ❌' }}
                        </span>
                    </div>
                    
                    <div class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <span class="font-medium">2️⃣ Proveedores</span>
                        <span class="px-2 py-1 rounded text-xs font-bold {{ $report->proveedores_ok ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                            {{ $report->proveedores_ok ? 'Enlazados ✅' : 'Sin Enlace ❌' }}
                        </span>
                    </div>

                    {{-- 3: OLTs --}}
                    <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-100 dark:border-blue-800">
                        <h3 class="font-bold text-blue-800 dark:text-blue-300 mb-2">3️⃣ Temperaturas OLT</h3>
                        <div class="flex justify-between items-center">
                            <span>Monteria: <strong>{{ $report->temp_olt_monteria }}°</strong></span>
                            <span>Backup: <strong>{{ $report->temp_olt_backup }}°</strong></span>
                        </div>
                    </div>

                    {{-- 4: Televisión y Servidores --}}
                    <div class="p-3 bg-purple-50 dark:bg-purple-900/20 rounded-lg border border-purple-100 dark:border-purple-800">
                        <h3 class="font-bold text-purple-800 dark:text-purple-300 mb-2">4️⃣ Televisión y Servicios</h3>
                        
                        {{-- Canales Offline --}}
                        @if(!empty($report->tv_canales_offline))
                            <div class="mb-2">
                                <span class="text-red-600 font-semibold text-xs uppercase">Sin Señal:</span>
                                <ul class="list-disc list-inside pl-1 text-gray-600 dark:text-gray-400">
                                    @foreach($report->tv_canales_offline as $canal)
                                        <li>{{ $canal }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="flex justify-between items-center mt-2 border-t border-purple-200 pt-2">
                            <span>Disponibilidad: <strong>{{ $report->tv_canales_activos }}/{{ $report->tv_canales_total }}</strong> ✅</span>
                        </div>

                        <div class="mt-2 pt-2 border-t border-purple-200 flex justify-between">
                            <span>Intalflix:</span>
                            <span class="{{ $report->intalflix_online ? 'text-green-600' : 'text-red-600 font-bold' }}">
                                {{ $report->intalflix_online ? 'En línea ✅' : 'CAÍDO ❌' }}
                            </span>
                        </div>
                    </div>
                </div>

                {{-- COLUMNA DERECHA: NOVEDADES E INCIDENTES --}}
                <div class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                    <h3 class="font-bold text-gray-800 dark:text-gray-200 mb-3 uppercase tracking-wide border-b pb-2">
                        📢 Novedades e Incidentes
                    </h3>

                    @if($report->observaciones_generales)
                        <div class="mb-4 text-sm italic text-gray-600 dark:text-gray-400 bg-yellow-50 dark:bg-yellow-900/10 p-2 rounded">
                            "{{ $report->observaciones_generales }}"
                        </div>
                    @endif

                    @forelse($report->incidents as $incidente)
                        <div class="mb-4 last:mb-0 p-3 rounded-lg border-l-4 {{ match($incidente->estado) {
                            'resuelto' => 'border-green-500 bg-green-50 dark:bg-green-900/10',
                            'pendiente' => 'border-red-500 bg-red-50 dark:bg-red-900/10',
                            default => 'border-orange-500 bg-orange-50 dark:bg-orange-900/10',
                        } }}">
                            <div class="flex justify-between items-start">
                                <h4 class="font-bold text-sm text-gray-900 dark:text-white">
                                    {{ $incidente->identificador }}
                                    <span class="text-xs font-normal text-gray-500">({{ ucfirst($incidente->tipo_falla) }})</span>
                                </h4>
                                <span class="text-xs font-bold px-2 py-0.5 rounded-full uppercase {{ match($incidente->estado) {
                                    'resuelto' => 'bg-green-200 text-green-800',
                                    'pendiente' => 'bg-red-200 text-red-800',
                                    default => 'bg-orange-200 text-orange-800',
                                } }}">
                                    {{ $incidente->estado }}
                                </span>
                            </div>
                            
                            <p class="text-sm mt-1 text-gray-700 dark:text-gray-300">
                                {{ $incidente->barrios }}
                            </p>
                            
                            @if($incidente->descripcion)
                                <p class="text-xs mt-2 text-gray-500 dark:text-gray-400 border-t pt-1 border-gray-200 dark:border-gray-700">
                                    {{ $incidente->descripcion }}
                                </p>
                            @endif

                            @if($incidente->usuarios_afectados)
                                <div class="mt-2 text-xs font-semibold text-gray-600">
                                    👥 {{ $incidente->usuarios_afectados }} usuarios afectados
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="text-center py-4 text-gray-400">
                            <span class="block text-2xl mb-2">✨</span>
                            Sin novedades reportadas. Todo opera con normalidad.
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Footer con hora de generación --}}
            <div class="mt-4 text-right text-xs text-gray-400">
                Generado automáticamente: {{ now()->format('H:i A') }}
            </div>

        @else
            <div class="text-center p-6 text-gray-500">
                No hay reportes registrados aún.
            </div>
        @endif
    </x-filament::card>
</x-filament::widget>