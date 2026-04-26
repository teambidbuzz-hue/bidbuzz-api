<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Player extends Model
{
    use HasUuids;

    protected $fillable = [
        'tournament_id',
        'photo',
        'full_name',
        'age',
        'phone_number',
        'batting_hand',
        'player_role',
        'bowling_arm',
        'status',
        'team_id',
        'sold_price',
        'sold_at',
        'label',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'age' => 'integer',
            'sold_price' => 'integer',
            'sold_at' => 'datetime',
            'sort_order' => 'integer',
        ];
    }

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
