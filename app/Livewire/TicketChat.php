<?php

namespace App\Livewire;

use Livewire\Component;

class TicketChat extends Component
{
    public \App\Models\Incident $incident;
    public $content;

    public function mount(\App\Models\Incident $incident)
    {
        $this->incident = $incident;
    }

    public function sendMessage()
    {
        $this->validate([
            'content' => 'required|string|max:1000',
        ]);

        $this->incident->comments()->create([
            'user_id' => auth()->id(),
            'content' => $this->content,
        ]);

        $this->content = '';
    }

    public function render()
    {
        return view('livewire.ticket-chat', [
            'comments' => $this->incident->comments()->with('user')->latest()->get(),
        ]);
    }
}
