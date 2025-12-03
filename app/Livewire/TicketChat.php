<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use Filament\Notifications\Notification;
use App\Models\User;

class TicketChat extends Component
{
    use WithFileUploads;

    public \App\Models\Incident $incident;
    public $content;
    
    // Propiedades para formularios de acciones
    public $rejectReason;
    public $escalateTo;
    public $escalateReason;
    public $resolveNotes;
    public $resolvePhotos = [];

    // Estados de UI para mostrar formularios
    public $showRejectForm = false;
    public $showEscalateForm = false;
    public $showResolveForm = false;

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

    public function accept()
    {
        $this->incident->responsibles()->updateExistingPivot(auth()->id(), [
            'status' => 'accepted',
            'accepted_at' => now(),
        ]);

        Notification::make()
            ->title('Ticket Aceptado')
            ->body('Ahora puedes atender o escalar el ticket.')
            ->success()
            ->send();
            
        $this->dispatch('ticket-updated'); // Para actualizar el widget padre si es necesario
    }

    public function attend()
    {
        $this->incident->update(['estado' => 'en_proceso']);
        
        Notification::make()
            ->title('Atendiendo Ticket')
            ->body('El ticket ahora está en proceso.')
            ->success()
            ->send();

        $this->dispatch('ticket-updated');
    }

    public function reject()
    {
        $this->validate([
            'rejectReason' => 'required|string|min:5',
        ]);

        $this->incident->responsibles()->updateExistingPivot(auth()->id(), [
            'status' => 'rejected',
            'rejected_at' => now(),
            'notes' => $this->rejectReason,
        ]);

        // Borrar notificación
        auth()->user()->notifications()
            ->where('data', 'like', '%ticket_number":"' . $this->incident->ticket_number . '"%')
            ->orWhere('data', 'like', '%tickets/' . $this->incident->id . '/edit%')
            ->delete();

        Notification::make()->title('Ticket Rechazado')->warning()->send();
        
        $this->showRejectForm = false;
        $this->rejectReason = '';
        $this->dispatch('ticket-updated');
        $this->dispatch('close-modal'); // Cerrar el modal
    }

    public function escalate()
    {
        $this->validate([
            'escalateTo' => 'required|exists:users,id',
            'escalateReason' => 'required|string|min:5',
        ]);

        $this->incident->responsibles()->updateExistingPivot(auth()->id(), [
            'status' => 'escalated',
            'notes' => "Escalado por " . auth()->user()->name . ": " . $this->escalateReason,
            'escalated_at' => now(),
        ]);

        $this->incident->responsibles()->attach($this->escalateTo, [
            'status' => 'pending',
            'assigned_by' => auth()->id(),
            'assigned_at' => now(),
        ]);

        // Borrar notificación del usuario actual
        auth()->user()->notifications()
            ->where('data', 'like', '%ticket_number":"' . $this->incident->ticket_number . '"%')
            ->orWhere('data', 'like', '%tickets/' . $this->incident->id . '/edit%')
            ->delete();

        // Crear notificación para el nuevo responsable
        $newResponsible = User::find($this->escalateTo);
        if ($newResponsible) {
            \Filament\Notifications\Notification::make()
                ->title('Nuevo Ticket Asignado')
                ->body("Se te ha asignado el ticket #{$this->incident->ticket_number}")
                ->warning()
                ->actions([
                    \Filament\Notifications\Actions\Action::make('ver')
                        ->button()
                        ->url(\Filament\Facades\Filament::getPanel('admin')->getUrl()),
                ])
                ->sendToDatabase($newResponsible);
        }

        Notification::make()
            ->title('Ticket Escalado')
            ->body('El ticket ha sido reasignado correctamente.')
            ->success()
            ->send();

        $this->showEscalateForm = false;
        $this->escalateTo = null;
        $this->escalateReason = '';
        $this->dispatch('ticket-updated');
        $this->dispatch('close-modal');
    }

    public function resolve()
    {
        $this->validate([
            'resolveNotes' => 'required|string|min:5',
            'resolvePhotos.*' => 'image|max:10240', // 10MB max
        ]);

        $photoPaths = [];
        foreach ($this->resolvePhotos as $photo) {
            $photoPaths[] = $photo->store('incident-resolution-photos', 'public');
        }

        $this->incident->update([
            'estado' => 'resuelto',
            'photos_resolution' => $photoPaths,
        ]);

        $this->incident->responsibles()->updateExistingPivot(auth()->id(), [
            'notes' => "Resuelto: " . $this->resolveNotes,
            'resolved_at' => now(),
        ]);

        // Borrar notificación
        auth()->user()->notifications()
            ->where('data', 'like', '%ticket_number":"' . $this->incident->ticket_number . '"%')
            ->orWhere('data', 'like', '%tickets/' . $this->incident->id . '/edit%')
            ->delete();

        Notification::make()
            ->title('Ticket Resuelto')
            ->body('El incidente ha sido marcado como resuelto.')
            ->success()
            ->send();

        $this->showResolveForm = false;
        $this->resolveNotes = '';
        $this->resolvePhotos = [];
        $this->dispatch('ticket-updated');
        $this->dispatch('close-modal');
    }

    public function render()
    {
        return view('livewire.ticket-chat', [
            'comments' => $this->incident->comments()->with('user')->latest()->get(),
            'users' => User::where('id', '!=', auth()->id())->pluck('name', 'id'),
            'pivot' => $this->incident->responsibles()
                        ->where('user_id', auth()->id())
                        ->first()
                        ?->pivot
        ]);
    }
}
