<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Organizer extends Authenticatable
{
    use HasApiTokens, Notifiable;

    /**
     * The table associated with the model.
     */
    protected $table = 'organizers';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'email',
        'name',
        'email_verified_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
        ];
    }
}
