<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChannelContact extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'widget_id',
        'visitor_id',
        'channel_type',
        'channel_identifier',
        'display_name',
        'avatar_url',
        'is_verified',
        'verified_at',
        'last_sync_at',
        'message_count',
        'last_message_at',
    ];

    protected function casts(): array
    {
        return [
            'is_verified'     => 'boolean',
            'verified_at'     => 'datetime',
            'last_sync_at'    => 'datetime',
            'last_message_at' => 'datetime',
        ];
    }

    public function widget(): BelongsTo
    {
        return $this->belongsTo(Widget::class);
    }

    public function visitor(): BelongsTo
    {
        return $this->belongsTo(Visitor::class);
    }

    /**
     * Mark contact as verified
     */
    public function markVerified(): void
    {
        if (!$this->is_verified) {
            $this->update([
                'is_verified' => true,
                'verified_at' => now(),
            ]);
        }
    }

    /**
     * Update last message timestamp
     */
    public function updateLastMessage(): void
    {
        $this->update([
            'last_message_at' => now(),
            'message_count' => $this->message_count + 1,
        ]);
    }
}
