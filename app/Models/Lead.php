<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lead extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id', 'widget_id', 'conversation_id', 'visitor_id',
        'name', 'email', 'phone', 'company', 'website',
        'score', 'temperature', 'quality_grade', 'engagement_score',
        'service_interest', 'budget_range', 'timeline', 'pain_point',
        'industry', 'company_size', 'notes', 'tags', 'status', 'priority',
        'qualification_answers', 'form_data', 'qualified_at', 'last_engagement_at', 'follow_up_priority',
    ];

    protected function casts(): array
    {
        return [
            'tags'                   => 'array',
            'qualification_answers'  => 'array',
            'form_data'              => 'array',
            'qualified_at'           => 'datetime',
            'last_engagement_at'     => 'datetime',
            'score'                  => 'integer',
            'engagement_score'       => 'integer',
            'follow_up_priority'     => 'integer',
        ];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function widget(): BelongsTo { return $this->belongsTo(Widget::class); }
    public function conversation(): BelongsTo { return $this->belongsTo(Conversation::class); }
    public function visitor(): BelongsTo { return $this->belongsTo(Visitor::class); }

    public function leadNotes(): HasMany
    {
        return $this->hasMany(LeadNote::class);
    }

    public function getTemperatureColorAttribute(): string
    {
        return match ($this->temperature) {
            'hot'  => 'bg-rose-500/10 text-rose-400',
            'warm' => 'bg-amber-500/10 text-amber-400',
            'cold' => 'bg-sky-500/10 text-sky-400',
            default => 'bg-gray-500/10 text-gray-400',
        };
    }

    public function getQualityGradeColorAttribute(): string
    {
        return match ($this->quality_grade) {
            'A' => 'text-emerald-600 bg-emerald-100 dark:bg-emerald-900/30 dark:text-emerald-400',
            'B' => 'text-blue-600 bg-blue-100 dark:bg-blue-900/30 dark:text-blue-400',
            'C' => 'text-amber-600 bg-amber-100 dark:bg-amber-900/30 dark:text-amber-400',
            'D' => 'text-red-600 bg-red-100 dark:bg-red-900/30 dark:text-red-400',
            default => 'text-gray-600 bg-gray-100 dark:bg-gray-800 dark:text-gray-400',
        };
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'new'       => 'bg-blue-500/10 text-blue-400',
            'contacted' => 'bg-sky-500/10 text-sky-400',
            'qualified' => 'bg-emerald-500/10 text-emerald-400',
            'proposal'  => 'bg-accent-500/10 text-accent-400',
            'won'       => 'bg-green-500/10 text-green-400',
            'lost'      => 'bg-rose-500/10 text-rose-400',
            default     => 'bg-gray-500/10 text-gray-400',
        };
    }

    public function scopeHot($query) { return $query->where('temperature', 'hot'); }
    public function scopeWarm($query) { return $query->where('temperature', 'warm'); }
    public function scopeCold($query) { return $query->where('temperature', 'cold'); }

    public function scopeGradeA($query) { return $query->where('quality_grade', 'A'); }
    public function scopeGradeB($query) { return $query->where('quality_grade', 'B'); }
    public function scopeGradeC($query) { return $query->where('quality_grade', 'C'); }
    public function scopeGradeD($query) { return $query->where('quality_grade', 'D'); }

    public function scopeHighPriority($query) { return $query->where('follow_up_priority', '>=', 70); }
    public function scopeMediumPriority($query) { return $query->whereBetween('follow_up_priority', [40, 69]); }
    public function scopeLowPriority($query) { return $query->where('follow_up_priority', '<', 40); }
}
