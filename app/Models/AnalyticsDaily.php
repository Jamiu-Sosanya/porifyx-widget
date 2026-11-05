<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnalyticsDaily extends Model
{
    protected $table = 'analytics_daily';

    protected $fillable = [
        'user_id', 'widget_id', 'date', 'conversations', 'messages', 'leads',
        'unique_visitors', 'returning_visitors', 'ai_responses', 'fallbacks',
        'human_takeovers', 'avg_conversation_length', 'avg_response_time_ms',
        'resolution_rate', 'lead_conversion_rate', 'peak_hours', 'top_topics',
        'language_distribution', 'device_distribution',
    ];

    protected function casts(): array
    {
        return [
            'date'                  => 'date',
            'peak_hours'            => 'array',
            'top_topics'            => 'array',
            'language_distribution' => 'array',
            'device_distribution'   => 'array',
        ];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function widget(): BelongsTo { return $this->belongsTo(Widget::class); }
}
