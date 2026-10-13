<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

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
        'timer_started_at',
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
        'timer_started_at' => 'datetime',
    ];

    /**
     * Calcula los segundos reales transcurridos en vivo basados en la base de datos
     * y la marca de tiempo de inicio del cronometro.
     */
    public function getLiveElapsedSeconds(): int
    {
        $base = (int) ($this->elapsed_seconds ?? 0);

        if ($this->status === 'finished' || $this->current_period === 'Finalizado') {
            return max(0, $base);
        }

        if ($this->timer_running) {
            if (!$this->timer_started_at) {
                $this->timer_started_at = Carbon::now();
                $this->save();
            }
            $extra = Carbon::now()->diffInSeconds($this->timer_started_at, true);
            return max(0, $base + (int) $extra);
        }

        return max(0, $base);
    }

    /**
     * Retorna el minuto de juego en formato oficial (1' - 90'+).
     */
    public function getCurrentMinute(): int
    {
        $secs = $this->getLiveElapsedSeconds();
        return max(1, (int) ceil($secs / 60));
    }

    /**
     * Inicia o reanuda el cronometro guardando el timestamp en BD.
     */
    public function startTimer(?int $fromSeconds = null): void
    {
        if ($fromSeconds !== null && $fromSeconds >= 0) {
            $this->elapsed_seconds = $fromSeconds;
        }
        $this->timer_running = true;
        $this->timer_started_at = Carbon::now();
        $this->save();
    }

    /**
     * Pausa el cronometro consolidando los segundos transcurridos en la BD.
     */
    public function pauseTimer(?int $reportedElapsedSeconds = null): void
    {
        if ($this->timer_running) {
            if ($reportedElapsedSeconds !== null && $reportedElapsedSeconds >= 0) {
                $this->elapsed_seconds = $reportedElapsedSeconds;
            } else {
                $this->elapsed_seconds = $this->getLiveElapsedSeconds();
            }
            $this->timer_running = false;
            $this->timer_started_at = null;
            $this->save();
        }
    }

    /**
     * Ajusta el tiempo (+1m, +5m, etc.) manteniendo la sincronizacion.
     */
    public function adjustTimer(int $secondsToAdd): void
    {
        $current = $this->getLiveElapsedSeconds();
        $this->elapsed_seconds = max(0, $current + $secondsToAdd);
        if ($this->timer_running) {
            $this->timer_started_at = Carbon::now();
        }
        $this->save();
    }

    /**
     * Fija un valor exacto de segundos transcurridos.
     */
    public function setExactSeconds(int $seconds): void
    {
        $this->elapsed_seconds = max(0, $seconds);
        if ($this->timer_running) {
            $this->timer_started_at = Carbon::now();
        }
        $this->save();
    }

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