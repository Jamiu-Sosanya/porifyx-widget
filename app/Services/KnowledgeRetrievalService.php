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

        // Query embedding. Without one (no OpenAI key for embeddings, or provider down) fall back
        // to keyword search so the assistant still answers from the knowledge base.
        $queryEmbedding = blank(config('ai.openai.key')) ? [] : $this->generateEmbedding($query);
        if (empty($queryEmbedding)) {
            return $this->keywordSearch($kb, $query, $maxResults);
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

        // Chunks stored without vectors (or nothing similar enough): try keywords.
        return $chunks->isNotEmpty() ? $chunks : $this->keywordSearch($kb, $query, $maxResults);
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

    /**
     * Keyword relevance search (no embeddings needed). Scores chunks by how many distinct
     * query terms they contain, weighted by term rarity. Score is scaled to 0..1.
     * ponytail: scans every chunk of the knowledge base; fine for SMB sizes, add a full-text index if KBs grow past ~20k chunks.
     */
    public function keywordSearch($kb, string $query, int $maxResults): Collection
    {
        $stop = array_flip(explode(' ', 'a an the and or but is are was were be to of in on at for with by from as it this that these those i you we they he she my your our their me us do does did can could would should will what when where which who how why please hi hello hey thanks thank there here about any some have has had not no yes so if than then also just more most'));
        $terms = array_values(array_unique(array_filter(
            preg_split('/\s+/', \App\Models\KnowledgeGap::normalize($query)),
            fn ($t) => mb_strlen($t) > 2 && ! isset($stop[$t])
        )));
        if (! $terms) return collect();

        $chunks = $kb->chunks()->get(['id', 'content', 'source_url', 'training_source_id', 'metadata']);
        $total = max(1, $chunks->count());
        $df = [];
        $texts = [];
        foreach ($chunks as $c) {
            $texts[$c->id] = mb_strtolower($c->content);
            foreach ($terms as $t) {
                if (str_contains($texts[$c->id], $t)) $df[$t] = ($df[$t] ?? 0) + 1;
            }
        }
        $maxScore = array_sum(array_map(fn ($t) => log(1 + $total / max(1, $df[$t] ?? 1)), $terms));

        return $chunks->map(function ($c) use ($terms, $texts, $df, $total, $maxScore) {
            $score = 0;
            foreach ($terms as $t) {
                if (str_contains($texts[$c->id], $t)) $score += log(1 + $total / max(1, $df[$t]));
            }
            $c->similarity_score = $maxScore > 0 ? round(min(1, $score / $maxScore), 3) : 0;
            return $c;
        })->filter(fn ($c) => $c->similarity_score >= 0.34)
          // Anything that passes the keyword filter is a usable match: map onto the 0.6..1 confidence band.
          ->each(fn ($c) => $c->similarity_score = round(0.6 + 0.4 * $c->similarity_score, 3))
          ->sortByDesc('similarity_score')
          ->take($maxResults)
          ->values();
    }
}