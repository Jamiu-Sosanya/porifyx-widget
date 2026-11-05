<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KnowledgeChunk extends Model
{
    use HasFactory;

    protected $fillable = [
        'knowledge_base_id', 'training_source_id', 'content', 'source_url',
        'chunk_index', 'token_count', 'embedding', 'embedding_model', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'embedding' => 'array',
            'metadata'  => 'array',
        ];
    }

    public function knowledgeBase(): BelongsTo
    {
        return $this->belongsTo(KnowledgeBase::class);
    }

    public function trainingSource(): BelongsTo
    {
        return $this->belongsTo(TrainingSource::class);
    }

    /**
     * Calculate cosine similarity with a given embedding vector.
     */
    public function cosineSimilarity(array $queryEmbedding): float
    {
        $a = $this->embedding;
        if (!$a || count($a) !== count($queryEmbedding)) return 0.0;

        $dotProduct = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        foreach ($a as $i => $val) {
            $dotProduct += $val * $queryEmbedding[$i];
            $normA += $val * $val;
            $normB += $queryEmbedding[$i] * $queryEmbedding[$i];
        }

        $denominator = sqrt($normA) * sqrt($normB);
        return $denominator > 0 ? $dotProduct / $denominator : 0.0;
    }
}
