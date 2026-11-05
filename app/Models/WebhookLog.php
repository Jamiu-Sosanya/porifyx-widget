<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebhookLog extends Model
{
    protected $fillable = [
        'widget_id',
        'event',
        'url',
        'payload',
        'response_status',
        'response_body',
        'response_time_ms',
        'status',
        'error_message',
        'attempt',
        'delivered_at',
    ];

    protected $casts = [
        'payload'      => 'array',
        'delivered_at' => 'datetime',
    ];

    public function widget(): BelongsTo
    {
        return $this->belongsTo(Widget::class);
    }

    public function isSuccess(): bool
    {
        return $this->status === 'success';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function getStatusBadge(): string
    {
        return match ($this->status) {
            'success' => 'success',
            'failed'  => 'danger',
            default   => 'warning',
        };
    }

    public function getFormattedDuration(): string
    {
        if ($this->response_time_ms === null) {
            return '—';
        }

        return $this->response_time_ms >= 1000
            ? round($this->response_time_ms / 1000, 2) . 's'
            : $this->response_time_ms . 'ms';
    }

    public function scopeForWidget($query, int $widgetId)
    {
        return $query->where('widget_id', $widgetId);
    }

    public function scopeRecent($query)
    {
        return $query->orderByDesc('created_at');
    }
}
