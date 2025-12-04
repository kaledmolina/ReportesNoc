<div>
<div class="min-h-screen bg-gray-900 text-white font-mono p-4 relative overflow-hidden" wire:poll.60s="checkNetworkStatus">
    
    <!-- HEADER -->
    <div class="flex justify-between items-center border-b border-gray-700 pb-4 mb-6 z-10 relative">
        <div>
            <h1 class="text-3xl font-bold tracking-wider flex items-center gap-3">
                <span class="text-red-500">INTALNET NOC</span>
                <span class="text-gray-500 text-lg">| MONITOR PON</span>
            </h1>
            <div class="flex items-center gap-2 mt-1">
                <span class="relative flex h-2 w-2">
                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full {{ $apiError ? 'bg-red-400' : 'bg-green-400' }} opacity-75"></span>
                  <span class="relative inline-flex rounded-full h-2 w-2 {{ $apiError ? 'bg-red-500' : 'bg-green-500' }}"></span>
                </span>
                <p class="text-xs text-gray-400">
                    API Status: 
                    <span class="{{ $apiError ? 'text-red-500 font-bold' : 'text-green-500 font-bold' }}">
                        {{ $apiError ? 'DESCONECTADO' : 'CONECTADO' }}
                    </span>
                    | Sync: <span id="last-update">{{ now()->format('H:i:s') }}</span>
                </p>
            </div>
        </div>
        
        <div class="flex items-center gap-4">
            <!-- ESTADO DEL AUDIO (Con wire:ignore para que no se resetee) -->
            <div id="audio-status-badge" wire:ignore class="hidden px-3 py-1 rounded bg-gray-800 border border-gray-600 text-gray-400 text-xs uppercase">
                🔇 Audio Inactivo
            </div>

            <!-- RELOJ -->
            <div class="text-2xl font-bold tabular-nums" id="clock">{{ now()->format('H:i:s') }}</div>
        </div>
    </div>

    <!-- BOTÓN GIGANTE PARA INICIALIZAR (Con wire:ignore para que no reaparezca) -->
    <div id="init-overlay" wire:ignore onclick="armAudioSystem()" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/90 backdrop-blur-sm cursor-pointer">
        <div class="text-center animate-pulse">
            <div class="inline-flex items-center justify-center w-24 h-24 rounded-full bg-blue-600 mb-4 shadow-lg shadow-blue-500/50">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <h2 class="text-2xl font-bold text-white">HAGA CLIC PARA INICIAR TURNO</h2>
            <p class="text-blue-300 mt-2">Activa el sistema de audio y monitoreo</p>
        </div>
    </div>

    <!-- OVERLAY DE ALARMA (Este SÍ queremos que Livewire lo controle o JS, usamos JS aquí) -->
    <div id="alarm-overlay" wire:ignore.self class="hidden fixed inset-0 z-40 bg-red-900/80 backdrop-blur-md flex flex-col items-center justify-center animate-pulse">
        <h1 class="text-6xl font-black text-white mb-8 drop-shadow-lg tracking-widest" style="text-shadow: 0 0 20px red;">¡ALERTA CRÍTICA!</h1>
        <div class="text-2xl text-white mb-8 font-bold bg-black/30 px-6 py-2 rounded">
            CORTE DE FIBRA DETECTADO
        </div>
        <button onclick="stopAlarm()" class="px-8 py-4 bg-red-600 hover:bg-red-500 text-white font-bold text-2xl rounded-lg shadow-xl border-4 border-white transform hover:scale-105 transition-all">
            🔕 SILENCIAR ALARMA
        </button>
    </div>

    <!-- CONTENIDO PRINCIPAL -->
    <div class="space-y-6">
        
        @if($apiError)
            <div class="bg-red-600 text-white p-4 rounded mb-6 text-center font-bold shadow-lg border-2 border-red-400">
                ⚠️ ERROR API: {{ $apiError }}
            </div>
        @endif

        @if(count($alerts) > 0)
            <!-- TABLA DE CORTES -->
            <div class="bg-red-900/20 border border-red-500/50 rounded-lg p-4">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-bold text-red-500 flex items-center gap-2">
                        CRITICAL ALERT: {{ count($alerts) }} PUERTOS AFECTADOS
                    </h2>
                </div>
                <div class="overflow-x-auto rounded-lg shadow-2xl border border-gray-700 bg-gray-800">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-gray-900 text-gray-400 uppercase text-xs tracking-wider">
                            <tr>
                                <th class="p-4 border-b border-gray-700">OLT</th>
                                <th class="p-4 border-b border-gray-700">Puerto</th>
                                <th class="p-4 border-b border-gray-700 text-center">ONUs</th>
                                <th class="p-4 border-b border-gray-700 text-center text-red-400">LOS</th>
                                <th class="p-4 border-b border-gray-700 text-center text-yellow-400">Pwr</th>
                                <th class="p-4 border-b border-gray-700">Causa</th>
                                <th class="p-4 border-b border-gray-700">Tiempo</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-700 text-sm">
                            @foreach($alerts as $alert)
                                <tr class="hover:bg-gray-700/50 transition-colors {{ $alert['affected'] > 20 ? 'bg-red-900/10' : '' }}">
                                    <td class="p-4 font-bold text-white">{{ $alert['olt_name'] }}</td>
                                    <td class="p-4 font-mono text-blue-400">{{ $alert['pon_port'] }}</td>
                                    <td class="p-4 text-center text-gray-300">{{ $alert['total_onus'] }}</td>
                                    <td class="p-4 text-center font-bold text-red-400">{{ $alert['los'] }}</td>
                                    <td class="p-4 text-center font-bold text-yellow-400">{{ $alert['power_fail'] }}</td>
                                    <td class="p-4"><span class="bg-red-600 px-2 py-1 rounded text-xs text-white">{{ $alert['cause'] }}</span></td>
                                    <td class="p-4 text-gray-400">{{ \Carbon\Carbon::parse($alert['updated_at'])->diffForHumans() }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <!-- ESTADO OK -->
            <div class="flex flex-col items-center justify-center h-48 text-gray-600 border border-dashed border-gray-800 rounded-lg bg-gray-800/30">
                <h2 class="text-xl font-bold text-green-500">RED NOMINAL - SIN CORTES ACTIVOS</h2>
            </div>
        @endif

        <!-- HEARTBEAT GRID -->
        <div class="mt-8 pt-4 border-t border-gray-800">
            <h3 class="text-xs uppercase text-gray-500 font-bold tracking-widest mb-3">Infraestructura (Heartbeat)</h3>
            @if(count($scannedOlts) > 0)
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3">
                    @foreach($scannedOlts as $olt)
                        <div class="p-3 rounded border border-gray-700 flex items-center justify-between {{ $olt['status'] == 'online' ? 'bg-gray-800' : 'bg-red-900/20 border-red-600' }}">
                            <div class="truncate pr-2">
                                <div class="text-xs font-bold text-gray-300 truncate" title="{{ $olt['name'] }}">{{ $olt['name'] }}</div>
                                <div class="text-[10px] text-gray-500">{{ $olt['ip'] }}</div>
                            </div>
                            <div class="flex flex-col items-end">
                                @if($olt['status'] == 'online')
                                    <span class="flex h-2 w-2 rounded-full bg-green-500 mb-1"></span>
                                    <span class="text-[10px] text-green-500">{{ $olt['latency'] }}ms</span>
                                @else
                                    <span class="flex h-2 w-2 rounded-full bg-red-500 mb-1 animate-pulse"></span>
                                    <span class="text-[10px] text-red-500">OFF</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-xs text-gray-500 animate-pulse">Sincronizando infraestructura...</p>
            @endif
        </div>
    </div>

    <!-- El audio también debe tener wire:ignore para no cortarse al recargar -->
    <audio id="siren" wire:ignore src="{{ asset('sounds/ringtone-021-365652.mp3') }}" preload="auto" loop></audio>
</div>

@script
<script>
    const siren = document.getElementById('siren');
    const initOverlay = document.getElementById('init-overlay');
    const audioStatusBadge = document.getElementById('audio-status-badge');
    const alarmOverlay = document.getElementById('alarm-overlay');
    
    let isArmed = false;
    let isRinging = false;

    window.armAudioSystem = () => {
        siren.play().then(() => {
            siren.pause();
            siren.currentTime = 0;
            isArmed = true;
            
            // Ocultamos visualmente
            initOverlay.classList.add('hidden');
            
            // Mostramos badge
            audioStatusBadge.classList.remove('hidden');
            audioStatusBadge.classList.remove('border-gray-600', 'text-gray-400');
            audioStatusBadge.classList.add('border-green-500', 'text-green-400', 'bg-green-900/20');
            audioStatusBadge.innerHTML = "🔊 SISTEMA ARMADO";
            
        }).catch(err => {
            console.error("Error al armar audio:", err);
            alert("Error: El navegador bloqueó el audio. Intente de nuevo.");
        });
    }

    window.stopAlarm = () => {
        siren.pause();
        siren.currentTime = 0;
        isRinging = false;
        alarmOverlay.classList.add('hidden');
    }

    Livewire.on('play-siren', () => {
        // Solo suena si está armado y no está sonando ya
        if (isArmed && !isRinging) {
            isRinging = true;
            siren.currentTime = 0;
            siren.loop = true; 
            siren.play().catch(e => console.log('Error reproducción:', e));
            alarmOverlay.classList.remove('hidden');
        }
    });

    setInterval(() => {
        document.getElementById('clock').innerText = new Date().toLocaleTimeString('es-CO');
    }, 1000);
</script>
@endscript
</div>