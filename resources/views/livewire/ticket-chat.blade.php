<div class="flex flex-col h-[600px]">
    
    {{-- HEADER: ESTADO Y ACCIONES --}}
    <div class="mb-4 p-4 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm">
        <div class="flex justify-between items-center mb-4">
            <div>
                <h3 class="text-lg font-bold text-gray-800 dark:text-white">
                    Ticket #{{ $incident->ticket_number }}
                </h3>
                <div class="flex gap-2 mt-1">
                    <x-filament::badge color="{{ match($incident->estado) {
                        'pendiente' => 'danger',
                        'en_proceso' => 'warning',
                        'resuelto' => 'success',
                        default => 'gray'
                    } }}">
                        {{ ucfirst(str_replace('_', ' ', $incident->estado)) }}
                    </x-filament::badge>
                    
                    @if($pivot)
                        <x-filament::badge color="gray" icon="heroicon-m-user">
                            Mi Estado: {{ ucfirst($pivot->status) }}
                        </x-filament::badge>
                    @endif
                </div>
            </div>
        </div>

        {{-- BOTONES DE ACCIÓN --}}
        <div class="flex flex-wrap gap-2">
            @if($pivot && $pivot->status === 'pending' && $incident->estado !== 'resuelto')
                <x-filament::button wire:click="accept" color="success" icon="heroicon-m-check">
                    Aceptar
                </x-filament::button>
                
                <x-filament::button wire:click="$set('showRejectForm', true)" color="danger" icon="heroicon-m-x-mark">
                    Rechazar
                </x-filament::button>
            @endif

            @if($pivot && $pivot->status === 'accepted')
                @if($incident->estado === 'pendiente')
                    <x-filament::button wire:click="attend" color="primary" icon="heroicon-m-play">
                        Atender
                    </x-filament::button>

                    <x-filament::button wire:click="$set('showEscalateForm', true)" color="warning" icon="heroicon-m-arrow-right-circle">
                        Escalar
                    </x-filament::button>
                @endif

                @if($incident->estado === 'en_proceso')
                    <x-filament::button wire:click="$set('showResolveForm', true)" color="success" icon="heroicon-m-check-circle">
                        Resolver
                    </x-filament::button>
                @endif
            @endif
        </div>

        {{-- FORMULARIOS --}}
        @if($showRejectForm)
            <div class="mt-4 p-4 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">Motivo del rechazo</label>
                <textarea wire:model="rejectReason" class="w-full rounded-lg border-gray-300 dark:bg-gray-900 dark:border-gray-600" rows="3"></textarea>
                <div class="flex justify-end gap-2 mt-2">
                    <x-filament::button wire:click="$set('showRejectForm', false)" color="gray" size="sm">Cancelar</x-filament::button>
                    <x-filament::button wire:click="reject" color="danger" size="sm">Confirmar Rechazo</x-filament::button>
                </div>
            </div>
        @endif

        @if($showEscalateForm)
            <div class="mt-4 p-4 bg-orange-50 dark:bg-orange-900/20 rounded-lg border border-orange-200 dark:border-orange-800">
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Escalar a:</label>
                        <select wire:model="escalateTo" class="w-full rounded-lg border-gray-300 dark:bg-gray-900 dark:border-gray-600">
                            <option value="">Seleccionar usuario...</option>
                            @foreach($users as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Motivo</label>
                        <textarea wire:model="escalateReason" class="w-full rounded-lg border-gray-300 dark:bg-gray-900 dark:border-gray-600" rows="2"></textarea>
                    </div>
                    <div class="flex justify-end gap-2">
                        <x-filament::button wire:click="$set('showEscalateForm', false)" color="gray" size="sm">Cancelar</x-filament::button>
                        <x-filament::button wire:click="escalate" color="warning" size="sm">Confirmar Escalamiento</x-filament::button>
                    </div>
                </div>
            </div>
        @endif

        @if($showResolveForm)
            <div class="mt-4 p-4 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Notas de Resolución</label>
                        <textarea wire:model="resolveNotes" class="w-full rounded-lg border-gray-300 dark:bg-gray-900 dark:border-gray-600" rows="3"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Evidencias (Fotos)</label>
                        <input type="file" wire:model="resolvePhotos" multiple class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-green-50 file:text-green-700 hover:file:bg-green-100 dark:file:bg-gray-700 dark:file:text-gray-200">
                    </div>
                    <div class="flex justify-end gap-2">
                        <x-filament::button wire:click="$set('showResolveForm', false)" color="gray" size="sm">Cancelar</x-filament::button>
                        <x-filament::button wire:click="resolve" color="success" size="sm">Confirmar Solución</x-filament::button>
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- CHAT AREA --}}
    <div class="flex-1 overflow-y-auto p-4 space-y-4 rounded-lg border border-gray-200 bg-gray-50 dark:bg-gray-900 dark:border-gray-700" 
         id="chat-scroll-area"
         wire:poll.5s>

        @forelse ($comments->reverse() as $comment)
            @php
                $isMe = $comment->user_id === auth()->id();
            @endphp

            <div class="flex w-full {{ $isMe ? 'justify-end' : 'justify-start' }}">
                
                <div class="flex flex-col max-w-[75%] {{ $isMe ? 'items-end' : 'items-start' }}">
                    
                    <div class="flex items-center gap-2 mb-1 px-1">
                        <span class="text-xs font-medium {{ $isMe ? 'text-primary-600 dark:text-primary-400' : 'text-gray-600 dark:text-gray-400' }}">
                            {{ $comment->user->name }}
                        </span>
                        <span class="text-[10px] text-gray-400">
                            {{ $comment->created_at->format('h:i A') }}
                        </span>
                    </div>

                    <div class="px-4 py-2 text-sm shadow-sm
                        {{ $isMe 
                            ? 'bg-primary-600 text-white rounded-l-2xl rounded-tr-2xl rounded-br-none' 
                            : 'bg-white text-gray-800 border border-gray-200 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-200 rounded-r-2xl rounded-tl-2xl rounded-bl-none' 
                        }}">
                        
                        {{ $comment->content }}
                        
                    </div>
                </div>
            </div>
        @empty
            <div class="flex flex-col items-center justify-center h-full text-gray-400 dark:text-gray-500 opacity-50">
                <x-heroicon-o-chat-bubble-left-right class="w-10 h-10 mb-2"/>
                <p class="text-sm">No hay mensajes aún.</p>
            </div>
        @endforelse
    </div>

    <div class="mt-4 pt-2">
        <form wire:submit.prevent="sendMessage" class="flex gap-2 items-center">
            
            <div class="flex-1">
                <input 
                    wire:model="content" 
                    type="text" 
                    placeholder="Escribe un mensaje..." 
                    class="block w-full rounded-lg shadow-sm border-gray-300 transition duration-75 
                           focus:border-primary-500 focus:ring-1 focus:ring-inset focus:ring-primary-500 
                           dark:bg-gray-900 dark:border-gray-600 dark:text-white dark:placeholder-gray-400"
                >
            </div>

            <x-filament::button 
                type="submit" 
                color="primary"
                icon="heroicon-m-paper-airplane"
                wire:loading.attr="disabled"
            >
                Enviar
            </x-filament::button>

        </form>
    </div>
</div>

<script>
    document.addEventListener('livewire:initialized', () => {
        const scrollArea = document.getElementById('chat-scroll-area');
        if(scrollArea) {
            scrollArea.scrollTop = scrollArea.scrollHeight;
            
            Livewire.hook('morph.updated', ({ el, component }) => {
                if(el.id === 'chat-scroll-area') {
                   el.scrollTop = el.scrollHeight;
                }
            });
        }
    });
</script>