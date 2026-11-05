<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TrainingSource extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'knowledge_base_id', 'user_id', 'type', 'name', 'content',
        'source_url', 'file_path', 'pages_crawled', 'chunks_generated',
        'tokens_used', 'status', 'error_message', 'version', 'last_processed_at',
    ];

    protected function casts(): array
    {
        return [
            'last_processed_at' => 'datetime',
        ];
    }

    public function knowledgeBase(): BelongsTo
    {
        return $this->belongsTo(KnowledgeBase::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function chunks(): HasMany
    {
        return $this->hasMany(KnowledgeChunk::class);
    }

    public function getTypeIconAttribute(): string
    {
        return match ($this->type) {
            'url'  => 'globe-alt',
            'pdf'  => 'document',
            'text' => 'pencil',
            'faq'  => 'question-mark-circle',
            'sitemap' => 'map',
            default => 'document',
        };
    }
}
