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
