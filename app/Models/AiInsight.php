<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiInsight extends Model
{
    protected $fillable = [
        'user_id', 'widget_id', 'type', 'insight', 'sentiment',
        'importance', 'period_start', 'period_end', 'supporting_data',
        'is_read', 'is_pinned',
    ];

    protected function casts(): array
    {
        return [
            'period_start'   => 'date',
            'period_end'     => 'date',
            'supporting_data'=> 'array',
            'is_read'        => 'boolean',
            'is_pinned'      => 'boolean',
        ];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function widget(): BelongsTo { return $this->belongsTo(Widget::class); }

    public function getSentimentColorAttribute(): string
    {
        return match ($this->sentiment) {
            'positive'   => 'text-emerald-500',
            'negative'   => 'text-rose-500',
            'actionable' => 'text-amber-500',
            default      => 'text-gray-500',
        };
    }
}
