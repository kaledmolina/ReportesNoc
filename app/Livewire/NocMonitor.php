<?php

namespace App\Livewire;

use Livewire\Component;
use App\Services\SmartOltMonitorService;

class NocMonitor extends Component
{
    public $alerts = [];
    public $scannedOlts = []; // Nueva lista de OLTs
    public $apiError = false; // Estado de la conexión global
    public $lastAlertCount = 0;

    public function render()
    {
        return view('livewire.noc-monitor');
    }

    public function checkNetworkStatus()
    {
        $service = new SmartOltMonitorService();
        $health = $service->getSystemHealth(); // Usamos el nuevo método

        $this->alerts = $health['alerts'];
        $this->scannedOlts = $health['olts'];
        $this->apiError = $health['error'];

        // Lógica de Sonido
        $currentCount = count($this->alerts);
        if ($currentCount > 0 && $currentCount > $this->lastAlertCount) {
            $this->dispatch('play-siren');
        }
        $this->lastAlertCount = $currentCount;
    }
}