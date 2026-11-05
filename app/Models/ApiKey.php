<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ApiKey extends Model
{
    protected $fillable = [
        'user_id', 'name', 'key', 'permissions', 'allowed_ips',
        'requests_count', 'rate_limit', 'last_used_at', 'expires_at', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'permissions'  => 'array',
            'allowed_ips'  => 'array',
            'is_active'    => 'boolean',
            'last_used_at' => 'datetime',
            'expires_at'   => 'datetime',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($apiKey) {
            $apiKey->key = $apiKey->key ?: 'pfx_' . Str::random(48);
        });
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isValid(): bool
    {
        return $this->is_active && !$this->isExpired();
    }
}
