<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GameMatch extends Model
{
    protected $table = 'game_matches';

    protected $fillable = [
        'tournament_id',
        'stage',
        'home_team_id',
        'away_team_id',
        'home_score',
        'away_score',
        'home_sets',
        'away_sets',
        'home_penalties',
        'away_penalties',
        'current_period',
        'status',
        'elapsed_seconds',
        'timer_running',
        'tennis_data',
        'stats',
        'winner_id',
        'next_match_id',
        'next_match_slot',
        'scheduled_time',
    ];

    protected $casts = [
        'tennis_data' => 'array',
        'stats' => 'array',
        'timer_running' => 'boolean',
    ];

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function homeTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'home_team_id');
    }

    public function awayTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'away_team_id');
    }

    public function winner(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'winner_id');
    }

    public function nextMatch(): BelongsTo
    {
        return $this->belongsTo(GameMatch::class, 'next_match_id');
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(MatchIncident::class, 'game_match_id')->orderBy('id', 'desc');
    }
}
