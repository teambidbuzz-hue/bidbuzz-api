<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Team extends Model
{
    use HasUuids;

    protected $fillable = [
        'tournament_id',
        'name',
        'logo',
    ];

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function players(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Player::class);
    }
}
