<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'city',
        'bio',
        'avatar_color',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function tournaments(): HasMany
    {
        return $this->hasMany(Tournament::class);
    }

    public function collaboratingTournaments(): BelongsToMany
    {
        return $this->belongsToMany(Tournament::class, 'tournament_collaborators', 'user_id', 'tournament_id');
    }

    public function subscribedTournaments(): BelongsToMany
    {
        return $this->belongsToMany(Tournament::class, 'tournament_subscriptions', 'user_id', 'tournament_id');
    }

    public function isSubscribedTo($tournamentId): bool
    {
        return $this->subscribedTournaments()->where('tournament_id', $tournamentId)->exists();
    }
}
