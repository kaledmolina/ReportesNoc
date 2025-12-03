<x-filament::widget>
    <x-filament::card>
    <div x-data="{ 
    text: @js($reportText),
    copied: false,
    copyToClipboard() {
        // Lógica unificada con el reporte general
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(this.text).then(() => {
                this.showSuccess();
            }).catch(() => {
                this.fallbackCopy();
            });
        } else {
            this.fallbackCopy();
        }
    },
    fallbackCopy() {
        const textArea = document.createElement('textarea');
        textArea.value = this.text;
        
        // Aseguramos que el textarea no sea visible pero sea parte del DOM
        textArea.style.top = '0';
        textArea.style.left = '0';
        textArea.style.position = 'fixed';

        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();

        try {
            var successful = document.execCommand('copy');
            if(successful) {
                this.showSuccess();
            } else {
                console.error('Fallo al copiar texto');
            }
        } catch (err) {
            console.error('Error al copiar', err);
        }

        document.body.removeChild(textArea);
    },
    showSuccess() {
        this.copied = true;
        new FilamentNotification()
            .title('¡Reporte PL copiado!')
            .success()
            .send();
        setTimeout(() => this.copied = false, 3000);
    }
}" 
class="flex flex-col md:flex-row items-center justify-between gap-4"
        >
            {{-- Texto Informativo --}}
            <div class="flex-1">
                <div class="flex items-center gap-2 mb-1">
                    {{-- Se cambió text-info-600 a text-primary-600 para consistencia --}}
                    <x-heroicon-o-clipboard-document-list class="w-6 h-6 text-primary-600 dark:text-primary-400"/>
                    <h2 class="text-lg font-bold text-gray-800 dark:text-white">Reporte Puerto Libertador (WhatsApp)</h2>
                </div>
                <p class="text-sm text-gray-500">
                    @if($lastUpdate)
                        Último reporte: hace {{ $lastUpdate }}
                    @else
                        No hay reportes recientes.
                    @endif
                </p>
            </div>

            {{-- Botón de Acción - Estilos unificados con Primary --}}
            <button 
                @click="copyToClipboard()"
                :disabled="!text || text.includes('No hay reportes')"
                :class="copied ? 'bg-green-600 hover:bg-green-700' : 'bg-primary-600 hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed'"
                class="w-full md:w-auto inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-all duration-200"
            >
                <span x-show="!copied" class="flex items-center">
                    <x-heroicon-m-document-duplicate class="w-5 h-5 mr-2 -ml-1"/>
                    Copiar
                </span>
                <span x-show="copied" class="flex items-center" style="display: none;">
                    <x-heroicon-m-check class="w-5 h-5 mr-2 -ml-1"/>
                    ¡Copiado!
                </span>
            </button>
        </div>

        {{-- Previsualización --}}
        <div x-data="{ open: false }" class="mt-4 border-t border-gray-200 dark:border-gray-700 pt-4">
            <button @click="open = !open" class="text-xs text-gray-500 hover:text-primary-600 flex items-center">
                <span x-text="open ? 'Ocultar vista previa' : 'Ver qué se va a copiar'"></span>
                <x-heroicon-m-chevron-down x-show="!open" class="w-3 h-3 ml-1"/>
                <x-heroicon-m-chevron-up x-show="open" class="w-3 h-3 ml-1" style="display: none;"/>
            </button>
            
            <div x-show="open" style="display: none;" class="mt-2 p-3 bg-gray-50 dark:bg-gray-900 rounded border border-gray-200 dark:border-gray-700">
                <pre class="whitespace-pre-wrap text-xs font-mono text-gray-600 dark:text-gray-300">{{ $reportText }}</pre>
            </div>
        </div>

    </x-filament::card>
</x-filament::widget>
