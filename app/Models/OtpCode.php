<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OtpCode extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'otp_codes';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'email',
        'code',
        'expires_at',
        'is_used',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'is_used' => 'boolean',
        ];
    }

    /**
     * Scope to find valid (unused, unexpired) OTPs for an email.
     */
    public function scopeValid($query, string $email)
    {
        return $query->where('email', $email)
            ->where('is_used', false)
            ->where('expires_at', '>', now())
            ->latest();
    }
}
