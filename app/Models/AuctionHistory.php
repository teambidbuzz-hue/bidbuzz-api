<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuctionHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'tournament_id',
        'player_id',
        'action',
        'team_id',
        'sold_price',
    ];

    public function player()
    {
        return $this->belongsTo(Player::class);
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }
}
