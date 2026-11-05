<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    protected $fillable = [
        'user_id', 'plan_id', 'stripe_subscription_id', 'stripe_customer_id',
        'billing_cycle', 'status', 'trial_ends_at', 'current_period_start',
        'current_period_end', 'canceled_at', 'ends_at', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'trial_ends_at'       => 'datetime',
            'current_period_start'=> 'datetime',
            'current_period_end'  => 'datetime',
            'canceled_at'         => 'datetime',
            'ends_at'             => 'datetime',
            'metadata'            => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function isActive(): bool
    {
        return in_array($this->status, ['active', 'trialing']);
    }

    public function isTrialing(): bool
    {
        return $this->status === 'trialing' && $this->trial_ends_at?->isFuture();
    }

    public function isCanceled(): bool
    {
        return $this->status === 'canceled';
    }

    public function isPastDue(): bool
    {
        return $this->status === 'past_due';
    }

    public function daysLeftOnTrial(): int
    {
        if (!$this->isTrialing()) return 0;
        return (int) now()->diffInDays($this->trial_ends_at, false);
    }
}
