<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmartOltMonitorService
{
    protected $baseUrl;
    protected $token;

    public function __construct()
    {
        $this->baseUrl = config('services.smartolt.api_url');
        $this->token = config('services.smartolt.key');

        if (empty($this->baseUrl) || empty($this->token)) {
            throw new \Exception("Configuración SmartOLT incompleta en .env");
        }
    }

    public function getSystemHealth()
    {
        // AUMENTADO: Damos más tiempo total al script (3 minutos)
        set_time_limit(180);

        $alerts = [];
        $scannedOlts = [];

        try {
            // 1. Obtener lista de OLTs
            $olts = $this->makeRequest('/system/get_olts');

            if ($olts === null) {
                throw new \Exception("Error al conectar con API SmartOLT (Lista vacía)");
            }

            foreach ($olts as $olt) {
                $oltStatus = [
                    'name' => $olt['name'],
                    'ip' => $olt['ip'] ?? 'N/A',
                    'status' => 'offline',
                    'latency' => 0,
                    'last_check' => now()->format('H:i:s')
                ];

                // FIX ID: Usamos 'id' o 'unique_id'
                $oltId = $olt['id'] ?? $olt['unique_id'] ?? null;
                if (!$oltId) continue;

                $start = microtime(true);

                // 2. Consultar cortes (Paso Crítico)
                $ponPorts = $this->makeRequest("/system/get_outage_pons/{$oltId}");

                if ($ponPorts !== null) {
                    $oltStatus['status'] = 'online';
                    $oltStatus['latency'] = round((microtime(true) - $start) * 1000);

                    foreach ($ponPorts as $port) {
                        // DETECTIVE DE VARIABLES:
                        // La API puede cambiar nombres, buscamos en todas las variantes posibles
                        $totalOnus = $port['total_onus'] ?? $port['onus_count'] ?? 0;
                        if ($totalOnus == 0) continue;

                        $losOnus = $port['total_los_onus'] 
                                   ?? $port['los_count'] 
                                   ?? $port['los_onus'] 
                                   ?? $port['offline_onus'] // A veces offline incluye LOS
                                   ?? 0;

                        $powerFail = $port['total_power_fail_onus'] 
                                     ?? $port['power_fail_count'] 
                                     ?? $port['power_fail'] 
                                     ?? 0;
                        
                        // FIX SLOT/BOARD: A veces viene como 'slot', a veces como 'board'
                        $slot = $port['slot'] ?? $port['board'] ?? '?';
                        $portNum = $port['port'] ?? '?';

                        // DEBUG: Si hay ALGO caído, guardamos en LOG para ver la estructura real
                        if ($losOnus > 0 || $powerFail > 0) {
                             // Esto guardará la respuesta exacta en storage/logs/laravel.log
                             Log::info("DATA RAW OLT {$olt['name']} Puerto $slot/$portNum:", $port);
                        }

                        // UMBRAL DE ALERTA: (Más de 5 caídos)
                        if ($losOnus > 5 || $powerFail > 5) {
                            $cause = $port['outage_cause'] ?? 'Desconocido';
                            
                            // Forzamos causa si la API no la dice clara
                            if ($cause == 'OK' || empty($cause) || $cause == 'Desconocido') {
                                if ($losOnus > $powerFail) $cause = 'CORTE FIBRA (LOS)';
                                else $cause = 'CORTE ENERGÍA';
                            }

                            $alerts[] = [
                                'olt_name' => $olt['name'],
                                'pon_port' => "Board $slot / Port $portNum",
                                'affected' => $losOnus + $powerFail,
                                'total_onus' => $totalOnus,
                                'los' => $losOnus,
                                'power_fail' => $powerFail,
                                'cause' => $cause,
                                'updated_at' => $port['latest_status_change'] ?? now()
                            ];
                        }
                    }
                }
                
                $scannedOlts[] = $oltStatus;
            }

        } catch (\Exception $e) {
            return [
                'alerts' => [],
                'olts' => [],
                'error' => $e->getMessage()
            ];
        }

        return [
            'alerts' => collect($alerts)->sortByDesc('affected')->values()->toArray(),
            'olts' => $scannedOlts,
            'error' => false
        ];
    }

    private function makeRequest($endpoint)
    {
        try {
            $baseUrl = rtrim($this->baseUrl, '/'); 
            $url = $baseUrl . $endpoint;

            // FIX CRÍTICO: Timeout subido a 20 segundos.
            // OLT Montería tiene 1.4s de latencia base + tiempo de proceso de la OLT.
            $response = Http::timeout(20)->withHeaders([
                'X-Token' => $this->token
            ])->get($url);

            if ($response->failed()) {
                if (str_contains($endpoint, 'get_olts')) {
                    throw new \Exception("HTTP Error " . $response->status());
                }
                // Si falla una OLT específica, retornamos null para marcarla offline pero seguir con las otras
                return null;
            }

            $json = $response->json();

            if (isset($json['status']) && $json['status'] === false) {
                $errorMsg = is_string($json['response']) ? $json['response'] : 'Error API desconocido';
                // Solo detenemos todo si es error de autenticación
                if (str_contains(strtolower($errorMsg), 'token')) {
                    throw new \Exception("API Auth: " . $errorMsg);
                }
                Log::warning("Error API en $url: $errorMsg");
                return null;
            }

            return $json['response'] ?? [];

        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'API Auth')) throw $e;
            return null; 
        }
    }
}