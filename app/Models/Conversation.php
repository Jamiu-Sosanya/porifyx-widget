<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Conversation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid', 'widget_id', 'visitor_id', 'user_id', 'assigned_agent_id',
        'status', 'channel', 'subject', 'language', 'detected_language',
        'current_page_url', 'referrer_url',
        'context_data', 'tags', 'ai_context', 'message_count', 'ai_message_count',
        'total_tokens_used', 'avg_sentiment', 'offline_message_sent',
        'is_read', 'is_starred', 'first_response_at', 'resolved_at', 'last_message_at',
        'csat_score', 'csat_comment', 'csat_submitted_at',
        'labels',
        'summary', 'action_items', 'summary_generated_at',
    ];

    protected function casts(): array
    {
        return [
            'context_data'         => 'array',
            'tags'                 => 'array',
            'ai_context'           => 'array',
            'action_items'         => 'array',
            'labels'               => 'array',
            'is_read'              => 'boolean',
            'is_starred'           => 'boolean',
            'offline_message_sent' => 'boolean',
            'avg_sentiment'        => 'float',
            'first_response_at'    => 'datetime',
            'resolved_at'          => 'datetime',
            'last_message_at'      => 'datetime',
            'csat_submitted_at'    => 'datetime',
            'summary_generated_at' => 'datetime',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();
        static::creating(fn ($c) => $c->uuid = $c->uuid ?: (string) Str::uuid());
    }

    public function widget(): BelongsTo
    {
        return $this->belongsTo(Widget::class);
    }

    public function visitor(): BelongsTo
    {
        return $this->belongsTo(Visitor::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignedAgent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_agent_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function lastMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    public function lead(): HasOne
    {
        return $this->hasOne(Lead::class);
    }

    public function isAiActive(): bool
    {
        return $this->status === 'ai_active';
    }

    public function isHumanActive(): bool
    {
        return $this->status === 'human_active';
    }

    public function switchToHuman(User $agent): void
    {
        $this->update([
            'status'            => 'human_active',
            'assigned_agent_id' => $agent->id,
        ]);
    }

    public function switchToAi(): void
    {
        $this->update([
            'status'            => 'ai_active',
            'assigned_agent_id' => null,
        ]);
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'ai_active'    => 'bg-brand-500/10 text-brand-400',
            'human_active' => 'bg-emerald-500/10 text-emerald-400',
            'resolved'     => 'bg-gray-500/10 text-gray-400',
            'open'         => 'bg-blue-500/10 text-blue-400',
            'spam'         => 'bg-rose-500/10 text-rose-400',
            default        => 'bg-gray-500/10 text-gray-400',
        };
    }
}
