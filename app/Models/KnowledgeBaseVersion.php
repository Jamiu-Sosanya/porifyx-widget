<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KnowledgeBaseVersion extends Model
{
    protected $fillable = [
        'knowledge_base_id', 'version', 'name', 'description',
        'total_chunks', 'is_active', 'snapshot_metadata',
    ];

    protected function casts(): array
    {
        return [
            'is_active'         => 'boolean',
            'snapshot_metadata' => 'array',
        ];
    }

    public function knowledgeBase(): BelongsTo
    {
        return $this->belongsTo(KnowledgeBase::class);
    }
}
