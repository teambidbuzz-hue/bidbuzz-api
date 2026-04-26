<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tournament extends Model
{
    use HasUuids;
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'organizer_id',
        'name',
        'season',
        'club_name',
        'team_budget',
        'max_players_per_team',
        'player_base_price',
        'registration_closing_date',
        'logo',
        'storage_key',
    ];

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        static::creating(function (Tournament $tournament) {
            if (!$tournament->storage_key) {
                $max = static::max('storage_key');
                $tournament->storage_key = $max ? $max + 1 : 1000;
            }
        });
    }

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'team_budget' => 'integer',
            'max_players_per_team' => 'integer',
            'player_base_price' => 'integer',
            'registration_closing_date' => 'datetime',
        ];
    }

    /**
     * Get the organizer that owns the tournament.
     */
    public function organizer(): BelongsTo
    {
        return $this->belongsTo(Organizer::class);
    }

    /**
     * Get the players registered in this tournament.
     */
    public function players(): HasMany
    {
        return $this->hasMany(Player::class);
    }

    /**
     * Get the teams in this tournament.
     */
    public function teams(): HasMany
    {
        return $this->hasMany(Team::class);
    }
}
