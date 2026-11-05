<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProactiveMessage extends Model
{
    protected $fillable = [
        'widget_id', 'name', 'message', 'trigger_type', 'trigger_value',
        'target_url_pattern', 'quick_replies', 'is_active', 'shown_count', 'clicked_count',
    ];

    protected $casts = [
        'is_active'    => 'boolean',
        'quick_replies'=> 'array',
        'trigger_value'=> 'integer',
        'shown_count'  => 'integer',
        'clicked_count'=> 'integer',
    ];

    public function widget(): BelongsTo
    {
        return $this->belongsTo(Widget::class);
    }

    public function getClickThroughRate(): float
    {
        if ($this->shown_count === 0) return 0;
        return round($this->clicked_count / $this->shown_count * 100, 1);
    }
}
