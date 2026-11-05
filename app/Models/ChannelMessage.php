<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChannelMessage extends Model
{
    protected $fillable = [
        'conversation_id',
        'widget_id',
        'channel_config_id',
        'external_channel_id',
        'external_message_id',
        'external_thread_id',
        'direction',
        'message_type',
        'content',
        'metadata',
        'attachments',
        'sender_channel_id',
        'sender_name',
        'sender_phone',
        'sender_email',
        'status',
        'error_message',
        'sent_at',
        'delivered_at',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata'      => 'array',
            'attachments'   => 'array',
            'sent_at'       => 'datetime',
            'delivered_at'  => 'datetime',
            'read_at'       => 'datetime',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function widget(): BelongsTo
    {
        return $this->belongsTo(Widget::class);
    }

    public function channelConfiguration(): BelongsTo
    {
        return $this->belongsTo(ChannelConfiguration::class, 'channel_config_id');
    }

    /**
     * Mark message as delivered
     */
    public function markDelivered(): void
    {
        if (!$this->delivered_at) {
            $this->update(['delivered_at' => now(), 'status' => 'delivered']);
        }
    }

    /**
     * Mark message as read
     */
    public function markRead(): void
    {
        if (!$this->read_at) {
            $this->update(['read_at' => now(), 'status' => 'read']);
        }
    }

    /**
     * Mark message as failed
     */
    public function markFailed(string $error): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $error,
        ]);
    }
}
