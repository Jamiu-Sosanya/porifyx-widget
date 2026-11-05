<?php

namespace App\Services;

use App\Models\KnowledgeChunk;
use App\Models\Widget;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;

class KnowledgeRetrievalService
{
    /**
     * Find relevant chunks from the widget's knowledge base using semantic search.
     */
    public function findRelevantChunks(Widget $widget, string $query, int $maxResults = null): Collection
    {
        $maxResults = $maxResults ?? config('ai.training.max_results', 5);
        $threshold = config('ai.training.similarity_threshold', 0.75);

        $kb = $widget->activeKnowledgeBase;

        if (!$kb || !$kb->isReady()) {
            return collect();
        }

        // Generate embedding for the query
        $queryEmbedding = $this->generateEmbedding($query);

        if (empty($queryEmbedding)) {
            return collect();
        }

        // Get all chunks and compute similarity
        $chunks = $kb->chunks()
            ->whereNotNull('embedding')
            ->get()
            ->map(function (KnowledgeChunk $chunk) use ($queryEmbedding) {
                $chunk->similarity_score = $chunk->cosineSimilarity($queryEmbedding);
                return $chunk;
            })
            ->filter(fn ($chunk) => $chunk->similarity_score >= $threshold)
            ->sortByDesc('similarity_score')
            ->take($maxResults);

        return $chunks;
    }

    /**
     * Generate an embedding vector for a text string.
     */
    public function generateEmbedding(string $text): array
    {
        try {
            $response = OpenAI::embeddings()->create([
                'model' => config('ai.openai.embedding_model', 'text-embedding-3-small'),
                'input' => $text,
            ]);

            return $response->embeddings[0]->embedding;
        } catch (\Exception $e) {
            Log::error('Embedding generation failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Generate embeddings for multiple texts in batch.
     */
    public function generateBatchEmbeddings(array $texts): array
    {
        try {
            $response = OpenAI::embeddings()->create([
                'model' => config('ai.openai.embedding_model', 'text-embedding-3-small'),
                'input' => $texts,
            ]);

            return collect($response->embeddings)
                ->map(fn ($e) => $e->embedding)
                ->toArray();
        } catch (\Exception $e) {
            Log::error('Batch embedding generation failed: ' . $e->getMessage());
            return [];
        }
    }
}
