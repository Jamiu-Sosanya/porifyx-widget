<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManualPayment extends Model
{
    /** What was bought, for lists and emails. */
    public function label(): string
    {
        return $this->credits ? number_format($this->credits) . ' AI credits' : ($this->plan?->name . ' (' . $this->billing_cycle . ')');
    }

    protected $fillable = ['user_id', 'plan_id', 'billing_cycle', 'amount', 'reference', 'status', 'note', 'reviewed_by', 'reviewed_at', 'credits'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'reviewed_at' => 'datetime'];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function plan(): BelongsTo { return $this->belongsTo(Plan::class); }

    /** Approve: start (or extend) the customer's subscription for one billing period. */
    public function approve(User $admin): void
    {
        if ($this->credits) {
            $this->user->increment('ai_credits', $this->credits);
            $this->update(['status' => 'approved', 'reviewed_by' => $admin->id, 'reviewed_at' => now()]);

            return;
        }
        $sub = $this->user->subscription;
        $from = $sub && $sub->plan_id === $this->plan_id && $sub->current_period_end?->isFuture() ? $sub->current_period_end : now();
        $end = $this->billing_cycle === 'yearly' ? $from->copy()->addYear() : $from->copy()->addMonth();

        Subscription::updateOrCreate(['user_id' => $this->user_id], [
            'plan_id' => $this->plan_id, 'billing_cycle' => $this->billing_cycle, 'status' => 'active',
            'current_period_start' => now(), 'current_period_end' => $end, 'canceled_at' => null, 'ends_at' => null, 'trial_ends_at' => null,
        ]);
        $this->update(['status' => 'approved', 'reviewed_by' => $admin->id, 'reviewed_at' => now()]);
    }
}
