<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'description', 'price_monthly', 'price_yearly',
        'stripe_monthly_id', 'stripe_yearly_id', 'color', 'is_popular',
        'is_active', 'sort_order', 'widgets_limit', 'messages_per_month',
        'training_sources_limit', 'leads_per_month', 'team_members_limit',
        'has_live_chat', 'workflow_automations_limit', 'has_ai_insights',
        'has_custom_branding', 'has_api_access', 'has_priority_support',
        'has_white_label', 'overage_message_price', 'overage_lead_price',
        'features',
    ];

    protected function casts(): array
    {
        return [
            'price_monthly'      => 'decimal:2',
            'price_yearly'       => 'decimal:2',
            'is_popular'         => 'boolean',
            'is_active'          => 'boolean',
            'has_live_chat'      => 'boolean',
            'has_ai_insights'    => 'boolean',
            'has_custom_branding'=> 'boolean',
            'has_api_access'     => 'boolean',
            'has_priority_support' => 'boolean',
            'has_white_label'    => 'boolean',
            'features'           => 'array',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function isUnlimited(string $feature): bool
    {
        return ($this->{$feature} ?? 0) === -1;
    }

    public function getFormattedPrice(string $cycle = 'monthly'): string
    {
        $price = $cycle === 'yearly' ? $this->price_yearly : $this->price_monthly;
        return '$' . number_format($price, 0);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }
}
