<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuthToken extends Model
{
    protected $table = 'auth_tokens';

    public $timestamps = true;

    protected $fillable = [
        'user_id',
        'token',
        'remember',
        'expires_at',
        'user_agent',
        'ip',
        'last_used_at',
    ];

    protected $casts = [
        'remember' => 'boolean',
        'expires_at' => 'datetime',
        'last_used_at' => 'datetime',
    ];

    protected $hidden = [
        'token',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }
}
