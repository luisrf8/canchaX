<?php

namespace App\Notifications;

use App\Models\GameMatch;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class MatchFinishedNotification extends Notification
{
    use Queueable;

    public GameMatch $match;
    public string $message;

    public function __construct(GameMatch $match, string $message)
    {
        $this->match = $match;
        $this->message = $message;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'match_finished',
            'match_id' => $this->match->id,
            'tournament_id' => $this->match->tournament_id,
            'tournament_name' => $this->match->tournament->name ?? 'Torneo',
            'home_team' => $this->match->homeTeam->name ?? 'Local',
            'away_team' => $this->match->awayTeam->name ?? 'Visita',
            'winner' => $this->match->winner->name ?? 'Empate',
            'home_score' => $this->match->home_score,
            'away_score' => $this->match->away_score,
            'message' => $this->message,
            'created_at' => now()->toIso8601String(),
        ];
    }
}
