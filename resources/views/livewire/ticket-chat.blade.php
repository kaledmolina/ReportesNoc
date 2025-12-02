<div class="flex flex-col h-[500px]"> 
    
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
    // Script simple para bajar el scroll automáticamente al cargar o recibir mensajes
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