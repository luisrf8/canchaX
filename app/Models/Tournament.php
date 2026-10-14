<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tournament extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'sport',
        'format',
        'status',
        'is_hidden',
        'location',
        'description',
        'is_current',
        'settings',
    ];

    protected $casts = [
        'settings' => 'array',
        'is_current' => 'boolean',
        'is_hidden' => 'boolean',
    ];

    public function isPast(): bool
    {
        if ($this->status === 'completed' || $this->status === 'archived') {
            return true;
        }

        if ($this->relationLoaded('matches')) {
            $total = $this->matches->count();
            if ($total === 0) {
                return false;
            }
            $unfinished = $this->matches->filter(function ($m) {
                return $m->status !== 'finished' && $m->current_period !== 'Finalizado';
            })->count();
            return $unfinished === 0;
        }

        $totalMatches = $this->matches()->count();
        if ($totalMatches === 0) {
            return false;
        }

        $unfinished = $this->matches()
            ->where('status', '!=', 'finished')
            ->where('current_period', '!=', 'Finalizado')
            ->count();

        return $unfinished === 0;
    }

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function collaborators(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'tournament_collaborators', 'tournament_id', 'user_id');
    }

    public function subscribers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'tournament_subscriptions', 'tournament_id', 'user_id');
    }

    public function teams(): HasMany
    {
        return $this->hasMany(Team::class);
    }

    public function matches(): HasMany
    {
        return $this->hasMany(GameMatch::class);
    }

    public function canManage(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        if ($this->user_id === $user->id) {
            return true;
        }

        return $this->collaborators()->where('user_id', $user->id)->exists();
    }
}
