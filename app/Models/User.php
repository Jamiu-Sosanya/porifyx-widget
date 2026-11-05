<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable, HasRoles, SoftDeletes, LogsActivity;

    protected $fillable = [
        'name', 'email', 'password', 'avatar', 'role', 'timezone', 'locale',
        'two_factor_enabled', 'two_factor_secret', 'two_factor_recovery_codes',
        'two_factor_confirmed_at', 'is_active', 'company_name', 'company_website',
        'phone', 'notification_preferences', 'telegram_chat_id',
        'last_login_at', 'last_login_ip',
        'pwa_installed_at', 'pwa_platform',
    ];

    protected $hidden = [
        'password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at'          => 'datetime',
            'two_factor_confirmed_at'    => 'datetime',
            'last_login_at'              => 'datetime',
            'pwa_installed_at'           => 'datetime',
            'password'                   => 'hashed',
            'two_factor_enabled'         => 'boolean',
            'is_active'                  => 'boolean',
            'notification_preferences'   => 'array',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

    // ─────────────────────────────────────────────────────────
    // Relationships
    // ─────────────────────────────────────────────────────────

    public function widgets(): HasMany
    {
        return $this->hasMany(Widget::class);
    }

    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class)->latestOfMany();
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    public function automations(): HasMany
    {
        return $this->hasMany(Automation::class);
    }

    public function apiKeys(): HasMany
    {
        return $this->hasMany(ApiKey::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function usageRecords(): HasMany
    {
        return $this->hasMany(UsageRecord::class);
    }

    public function teamMembers(): HasMany
    {
        return $this->hasMany(TeamMember::class, 'owner_id');
    }

    public function pushSubscriptions(): HasMany
    {
        return $this->hasMany(PushSubscription::class);
    }

    public function cannedResponses(): HasMany
    {
        return $this->hasMany(CannedResponse::class)->orderBy('sort_order')->orderBy('title');
    }

    // ─────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function hasActivePlan(): bool
    {
        $sub = $this->subscription;
        return $sub && in_array($sub->status, ['active', 'trialing']);
    }

    public function currentPlan(): ?Plan
    {
        return $this->subscription?->plan;
    }

    public function getUsageThisMonth(string $metric): int
    {
        return $this->usageRecords()
            ->where('metric', $metric)
            ->where('year', now()->year)
            ->where('month', now()->month)
            ->value('count') ?? 0;
    }

    public function canUse(string $metric): bool
    {
        // Platform admin (installer owner) always has unlimited access.
        // The plan gate is for end-users of the SaaS platform, not the owner.
        if ($this->isAdmin()) return true;

        $plan = $this->currentPlan();
        if (!$plan) return false;
        $limit = match ($metric) {
            'messages' => $plan->messages_per_month,
            'leads'    => $plan->leads_per_month,
            'widgets'  => $plan->widgets_limit,
            default    => -1,
        };
        if ($limit === -1) return true;
        return $this->getUsageThisMonth($metric) < $limit;
    }

    public function incrementUsage(string $metric, int $amount = 1): void
    {
        UsageRecord::updateOrCreate(
            [
                'user_id' => $this->id,
                'metric'  => $metric,
                'year'    => now()->year,
                'month'   => now()->month,
            ],
            []
        )->increment('count', $amount);
    }

    public function avatarUrl(): string
    {
        if ($this->avatar) {
            return asset('storage/' . $this->avatar);
        }
        return 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&background=6366f1&color=fff&size=128';
    }
}
