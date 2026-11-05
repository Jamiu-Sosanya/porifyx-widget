<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class KnowledgeBase extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id', 'widget_id', 'name', 'description', 'version',
        'status', 'total_chunks', 'total_tokens', 'last_trained_at',
        'auto_detect_topics', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active'          => 'boolean',
            'last_trained_at'    => 'datetime',
            'auto_detect_topics' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function widget(): BelongsTo
    {
        return $this->belongsTo(Widget::class);
    }

    public function trainingSources(): HasMany
    {
        return $this->hasMany(TrainingSource::class);
    }

    public function chunks(): HasMany
    {
        return $this->hasMany(KnowledgeChunk::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(KnowledgeBaseVersion::class);
    }

    public function isReady(): bool
    {
        return $this->status === 'ready';
    }
}
