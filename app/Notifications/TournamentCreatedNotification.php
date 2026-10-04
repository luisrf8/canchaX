<?php

namespace App\Notifications;

use App\Models\Tournament;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TournamentCreatedNotification extends Notification
{
    use Queueable;

    public Tournament $tournament;

    public function __construct(Tournament $tournament)
    {
        $this->tournament = $tournament;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'tournament_created',
            'tournament_id' => $this->tournament->id,
            'tournament_name' => $this->tournament->name,
            'sport' => strtoupper($this->tournament->sport),
            'organizer' => $this->tournament->organizer->name ?? 'Staff',
            'message' => '¡Nuevo Torneo Publicado! "' . $this->tournament->name . '" por ' . ($this->tournament->organizer->name ?? 'Staff'),
            'created_at' => now()->toIso8601String(),
        ];
    }
}
