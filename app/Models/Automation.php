<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Automation extends Model
{
    use HasFactory, SoftDeletes;

    /** Ready-made recipes: [name, trigger, action, keywords]. Shown under "Automations" in the sidebar. */
    public const TEMPLATES = [
        ['Hot lead: email sales', 'hot_lead', 'send_email', ''],
        ['Hot lead: Telegram alert', 'hot_lead', 'send_telegram', ''],
        ['Hot lead: high priority', 'hot_lead', 'assign_priority', ''],
        ['Hot lead: assign agent', 'hot_lead', 'assign_agent', ''],
        ['Hot lead: send to CRM', 'hot_lead', 'add_to_crm', ''],
        ['New lead: send to CRM', 'new_lead', 'add_to_crm', ''],
        ['New lead: email team', 'new_lead', 'send_email', ''],
        ['New lead: add tag', 'new_lead', 'tag_lead', ''],
        ['New lead: webhook', 'new_lead', 'webhook', ''],
        ['New chat: Telegram alert', 'chat_started', 'send_telegram', ''],
        ['New chat: email team', 'chat_started', 'send_email', ''],
        ['New chat: webhook', 'chat_started', 'webhook', ''],
        ['Angry words: escalate', 'keyword_detected', 'assign_priority', 'angry, terrible, complaint, manager, worst'],
        ['Refund request: email support', 'keyword_detected', 'send_email', 'refund, money back, cancel order, chargeback'],
        ['Pricing question: tag lead', 'keyword_detected', 'tag_lead', 'price, pricing, cost, quote, how much'],
        ['Urgent words: Telegram alert', 'keyword_detected', 'send_telegram', 'urgent, asap, emergency, right now'],
        ['AI unsure: Telegram alert', 'ai_fallback', 'send_telegram', ''],
        ['AI unsure: email team', 'ai_fallback', 'send_email', ''],
        ['AI unsure: assign agent', 'ai_fallback', 'assign_agent', ''],
        ['Chat resolved: webhook', 'chat_completed', 'webhook', ''],
        ['Chat resolved: send to CRM', 'chat_completed', 'add_to_crm', ''],
    ];

    protected $fillable = [
        'user_id', 'widget_id', 'name', 'description', 'is_active',
        'trigger', 'trigger_conditions', 'action', 'action_config',
        'trigger_count', 'success_count', 'last_triggered_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active'          => 'boolean',
            'trigger_conditions' => 'array',
            'action_config'      => 'array',
            'last_triggered_at'  => 'datetime',
            'trigger_count'      => 'integer',
            'success_count'      => 'integer',
        ];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function widget(): BelongsTo { return $this->belongsTo(Widget::class); }

    public function logs(): HasMany
    {
        return $this->hasMany(AutomationLog::class);
    }

    public function getSuccessRateAttribute(): float
    {
        if ($this->trigger_count === 0) return 0;
        return round(($this->success_count / $this->trigger_count) * 100, 1);
    }

    public function scopeActive($query) { return $query->where('is_active', true); }
}
