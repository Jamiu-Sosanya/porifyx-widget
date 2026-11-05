<?php

namespace App\Services;

use App\Models\KnowledgeBase;
use App\Models\KnowledgeChunk;
use App\Models\TrainingSource;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TrainingService
{
    public function __construct(
        protected KnowledgeRetrievalService $retrievalService,
    ) {}

    /**
     * Process a training source and generate chunks + embeddings.
     */
    public function processSource(TrainingSource $source): void
    {
        $source->update(['status' => 'processing']);

        try {
            // Extract raw text based on source type
            $rawText = match ($source->type) {
                'url'     => $this->extractFromUrl($source->source_url),
                'pdf'     => $this->extractFromPdf($source->file_path),
                'text'    => $source->content,
                'faq'     => $this->formatFaq($source->content),
                'sitemap' => $this->extractFromSitemap($source->source_url),
                default   => throw new \Exception("Unknown source type: {$source->type}"),
            };

            if (empty($rawText)) {
                throw new \Exception('No text content could be extracted.');
            }

            // Store raw content
            $source->update(['content' => $rawText]);

            // Chunk the text
            $chunks = $this->chunkText($rawText);

            // Generate embeddings in batches
            $batchSize = 20;
            $chunkBatches = array_chunk($chunks, $batchSize);
            $totalChunks = 0;
            $totalTokens = 0;

            foreach ($chunkBatches as $batch) {
                $embeddings = $this->retrievalService->generateBatchEmbeddings($batch);

                if (empty($embeddings)) {
                    throw new \Exception(
                        'Embedding generation failed — check that your OpenAI API key is set in ' .
                        '.env (OPENAI_API_KEY). Embeddings always use OpenAI regardless of which ' .
                        'AI provider you chose for chat.'
                    );
                }

                foreach ($batch as $index => $chunkText) {
                    $tokenCount = (int) ceil(Str::length($chunkText) / 4);

                    KnowledgeChunk::create([
                        'knowledge_base_id'  => $source->knowledge_base_id,
                        'training_source_id' => $source->id,
                        'content'            => $chunkText,
                        'source_url'         => $source->source_url,
                        'chunk_index'        => $totalChunks,
                        'token_count'        => $tokenCount,
                        'embedding'          => $embeddings[$index] ?? null,
                        'embedding_model'    => config('ai.openai.embedding_model'),
                        'metadata'           => [
                            'source_name' => $source->name,
                            'source_type' => $source->type,
                        ],
                    ]);

                    $totalChunks++;
                    $totalTokens += $tokenCount;
                }
            }

            $source->update([
                'status'            => 'completed',
                'chunks_generated'  => $totalChunks,
                'tokens_used'       => $totalTokens,
                'last_processed_at' => now(),
            ]);

            // Update knowledge base stats
            $kb = $source->knowledgeBase;
            $kb->update([
                'total_chunks'   => $kb->chunks()->count(),
                'total_tokens'   => $kb->chunks()->sum('token_count'),
                'status'         => 'ready',
                'last_trained_at'=> now(),
            ]);

        } catch (\Exception $e) {
            Log::error("Training source processing failed: {$e->getMessage()}", [
                'source_id' => $source->id,
            ]);

            $source->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Extract text from a URL.
     */
    protected function extractFromUrl(string $url): string
    {
        $response = Http::timeout(30)->get($url);

        if (!$response->successful()) {
            throw new \Exception("Failed to fetch URL: {$url}");
        }

        $html = $response->body();

        // Strip HTML, scripts, styles
        $text = strip_tags(
            preg_replace([
                '/<script[^>]*>.*?<\/script>/is',
                '/<style[^>]*>.*?<\/style>/is',
                '/<nav[^>]*>.*?<\/nav>/is',
                '/<footer[^>]*>.*?<\/footer>/is',
                '/<header[^>]*>.*?<\/header>/is',
            ], '', $html)
        );

        // Clean up whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);

        return $text;
    }

    /**
     * Extract text from a PDF file.
     */
    protected function extractFromPdf(string $filePath): string
    {
        if (!Storage::disk('local')->exists($filePath)) {
            throw new \Exception("PDF file not found: {$filePath}");
        }

        $fullPath = Storage::disk('local')->path($filePath);

        $which = shell_exec('which pdftotext 2>/dev/null');
        if (empty(trim($which ?? ''))) {
            throw new \Exception(
                'pdftotext is not installed on this server. ' .
                'Please install poppler-utils (e.g. "sudo apt-get install poppler-utils") ' .
                'or contact your hosting provider.'
            );
        }

        $content = shell_exec('pdftotext ' . escapeshellarg($fullPath) . ' -');

        if (empty(trim($content ?? ''))) {
            throw new \Exception('PDF text extraction returned empty content. The PDF may be image-based or encrypted.');
        }

        return trim($content);
    }

    /**
     * Format FAQ content for better chunking.
     */
    protected function formatFaq(string $content): string
    {
        // Assume FAQ is in Q/A format
        return $content;
    }

    /**
     * Extract URLs from a sitemap and crawl them.
     */
    protected function extractFromSitemap(string $sitemapUrl): string
    {
        $response = Http::timeout(30)->get($sitemapUrl);

        if (!$response->successful()) {
            throw new \Exception("Failed to fetch sitemap: {$sitemapUrl}");
        }

        $xml = simplexml_load_string($response->body());
        $urls = [];
        $maxPages = config('ai.training.crawler_max_pages', 50);

        foreach ($xml->url as $urlEntry) {
            if (count($urls) >= $maxPages) break;
            $urls[] = (string) $urlEntry->loc;
        }

        $allText = '';
        foreach ($urls as $url) {
            try {
                $allText .= "\n\n--- Page: {$url} ---\n";
                $allText .= $this->extractFromUrl($url);
            } catch (\Exception $e) {
                Log::warning("Failed to crawl {$url}: {$e->getMessage()}");
            }
        }

        return $allText;
    }

    /**
     * Split text into overlapping chunks.
     */
    public function chunkText(string $text): array
    {
        $chunkSize = config('ai.training.chunk_size', 1000);
        $overlap = config('ai.training.chunk_overlap', 200);

        $chunks = [];
        $length = Str::length($text);
        $offset = 0;

        while ($offset < $length) {
            $chunk = Str::substr($text, $offset, $chunkSize);

            // Try to break at sentence boundary
            $lastPeriod = strrpos($chunk, '. ');
            if ($lastPeriod !== false && $lastPeriod > $chunkSize * 0.5) {
                $chunk = substr($chunk, 0, $lastPeriod + 1);
            }

            $chunks[] = trim($chunk);
            $offset += Str::length($chunk) - $overlap;

            if ($offset >= $length) break;
        }

        return array_filter($chunks, fn ($c) => Str::length($c) > 50);
    }

    /**
     * Re-train an entire knowledge base.
     */
    public function retrainKnowledgeBase(KnowledgeBase $kb): void
    {
        $kb->update(['status' => 'processing']);

        // Delete old chunks
        $kb->chunks()->delete();

        // Re-process all sources
        foreach ($kb->trainingSources as $source) {
            $this->processSource($source);
        }

        $kb->update([
            'status'  => 'ready',
            'version' => $kb->version + 1,
        ]);
    }
}
