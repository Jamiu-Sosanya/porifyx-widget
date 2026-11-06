<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ticket extends Model
{
    public const STATUSES = ['open' => 'Open', 'pending' => 'Waiting on customer', 'solved' => 'Solved', 'closed' => 'Closed'];
    public const PRIORITIES = ['low' => 'Low', 'normal' => 'Normal', 'high' => 'High', 'urgent' => 'Urgent'];

    protected $fillable = [
        'user_id', 'widget_id', 'conversation_id', 'assigned_to', 'merged_into_id', 'subject', 'requester_name', 'requester_email',
        'status', 'priority', 'last_customer_reply_at', 'last_agent_reply_at', 'escalated_at',
    ];

    protected function casts(): array
    {
        return ['last_customer_reply_at' => 'datetime', 'last_agent_reply_at' => 'datetime', 'escalated_at' => 'datetime'];
    }

    public function owner(): BelongsTo { return $this->belongsTo(User::class, 'user_id'); }
    public function widget(): BelongsTo { return $this->belongsTo(Widget::class); }
    public function conversation(): BelongsTo { return $this->belongsTo(Conversation::class); }
    public function assignee(): BelongsTo { return $this->belongsTo(User::class, 'assigned_to'); }
    public function mergedInto(): BelongsTo { return $this->belongsTo(Ticket::class, 'merged_into_id'); }
    public function messages(): HasMany { return $this->hasMany(TicketMessage::class)->oldest('id'); }

    public function getNumberAttribute(): string
    {
        return 'TK-' . (1000 + $this->id);
    }

    /** Signed link the customer uses to read and reply (no account needed). */
    public function publicToken(): string
    {
        return substr(hash_hmac('sha256', 'ticket|' . $this->id . '|' . $this->requester_email, (string) config('app.key')), 0, 32);
    }

    public function publicUrl(): string
    {
        return route('tickets.public', [$this->id, $this->publicToken()]);
    }

    public function log(string $type, string $body, ?int $userId = null): TicketMessage
    {
        return $this->messages()->create(['type' => $type, 'body' => $body, 'user_id' => $userId]);
    }

    public function canBeSeenBy(User $u): bool
    {
        return $this->user_id === $u->id || $this->assigned_to === $u->id;
    }
}
