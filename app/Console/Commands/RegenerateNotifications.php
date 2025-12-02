<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class RegenerateNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:regenerate-notifications';
    protected $description = 'Regenerate notifications for all pending assigned tickets';

    public function handle()
    {
        $incidents = \App\Models\Incident::whereIn('estado', ['pendiente', 'en_proceso'])->get();
        $count = 0;

        foreach ($incidents as $incident) {
            foreach ($incident->responsibles as $user) {
                // Check if notification exists
                $exists = $user->notifications()
                    ->where('data', 'like', '%ticket_number":"' . $incident->ticket_number . '"%')
                    ->exists();

                if (!$exists) {
                    \Filament\Notifications\Notification::make()
                        ->title('Ticket Asignado (Pendiente)')
                        ->body("Tienes asignado el ticket #{$incident->ticket_number}")
                        ->warning()
                        ->actions([
                            \Filament\Notifications\Actions\Action::make('ver')
                                ->button()
                                ->url(\App\Filament\Resources\IncidentResource::getUrl('edit', ['record' => $incident])),
                        ])
                        ->sendToDatabase($user);
                    $count++;
                }
            }
        }

        $this->info("Regenerated {$count} notifications.");
    }
}
