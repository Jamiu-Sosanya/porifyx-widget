<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;

/**
 * Analyzes sentiment for chat messages and tracks conversation mood.
 */
class SentimentAnalysisService
{
    /**
     * Analyze sentiment for a single message text.
     * Returns ['score' => float(-1..1), 'label' => 'positive|neutral|negative'].
     */
    public function analyzeMessage(string $text): array
    {
        // Instant word-list scoring: runs on every visitor message, so no AI round-trip here.
        // ponytail: English-centric lexicon; swap for an AI call in a queued job if multilingual accuracy matters.
        static $pos = ['great', 'good', 'love', 'thanks', 'thank', 'awesome', 'excellent', 'perfect', 'happy', 'amazing', 'helpful', 'nice', 'cool', 'brilliant', 'fantastic', 'glad', 'appreciate', 'wonderful', 'best', 'yes', 'interested', 'easy'];
        static $neg = ['bad', 'terrible', 'awful', 'hate', 'angry', 'worst', 'useless', 'broken', 'problem', 'issue', 'refund', 'cancel', 'disappointed', 'annoyed', 'slow', 'wrong', 'error', 'fail', 'failed', 'complaint', 'poor', 'frustrated', 'scam', 'never', 'not working', "doesn't work", 'unhappy', 'rude'];
        $t = ' ' . mb_strtolower($text) . ' ';
        $p = 0; $n = 0;
        foreach ($pos as $w) $p += substr_count($t, " $w");
        foreach ($neg as $w) $n += substr_count($t, " $w");
        if ($p + $n === 0) return ['score' => 0.0, 'label' => 'neutral'];
        $score = round(($p - $n) / ($p + $n), 2);

        return ['score' => $score, 'label' => $score > 0.2 ? 'positive' : ($score < -0.2 ? 'negative' : 'neutral')];
    }

    /**
     * Batch-analyze multiple messages at once (more efficient).
     * Returns array of ['score', 'label'] indexed by input order.
     */
    public function analyzeBatch(array $texts): array
    {
        if (empty($texts)) return [];

        $numbered = array_map(
            fn ($i, $t) => ($i + 1) . '. ' . substr($t, 0, 300),
            array_keys($texts),
            $texts
        );

        try {
            $response = \App\Support\Ai::create([
                'model'       => 'gpt-4o-mini',
                'max_tokens'  => 200,
                'temperature' => 0,
                'messages'    => [
                    [
                        'role'    => 'system',
                        'content' => 'Analyze sentiment of each numbered message. Respond with JSON array of objects, one per message, in order: [{"score": <-1 to 1>, "label": "positive"|"neutral"|"negative"}, ...]. No explanation.',
                    ],
                    ['role' => 'user', 'content' => implode("\n", $numbered)],
                ],
            ]);

            $raw    = $response->choices[0]->message->content;
            $parsed = json_decode(trim($raw), true);

            if (is_array($parsed) && count($parsed) === count($texts)) {
                return array_map(function ($item) {
                    return [
                        'score' => max(-1.0, min(1.0, (float) ($item['score'] ?? 0))),
                        'label' => in_array($item['label'] ?? '', ['positive', 'neutral', 'negative'])
                            ? $item['label'] : 'neutral',
                    ];
                }, $parsed);
            }
        } catch (\Exception $e) {
            Log::debug('Batch sentiment analysis failed: ' . $e->getMessage());
        }

        return array_fill(0, count($texts), ['score' => 0.0, 'label' => 'neutral']);
    }

    /**
     * Update the average sentiment score for a conversation.
     */
    public function updateConversationSentiment(Conversation $conversation): void
    {
        try {
            $avgScore = $conversation->messages()
                ->where('role', 'visitor')
                ->whereNotNull('sentiment_score')
                ->avg('sentiment_score');

            if ($avgScore !== null) {
                $conversation->update(['avg_sentiment' => round($avgScore, 3)]);
            }
        } catch (\Exception $e) {
            Log::debug('Failed to update conversation sentiment: ' . $e->getMessage());
        }
    }

    /**
     * Detect the language of a given text.
     * Returns ISO 639-1 language code (e.g., 'en', 'es', 'fr').
     */
    public function detectLanguage(string $text): string
    {
        if (empty(trim($text))) {
            return 'en'; // Default to English for empty text
        }

        try {
            $response = \App\Support\Ai::create([
                'model'       => 'gpt-4o-mini',
                'max_tokens'  => 10,
                'temperature' => 0,
                'messages'    => [
                    [
                        'role'    => 'system',
                        'content' => 'Detect the language of the following text. Respond with only the ISO 639-1 language code (e.g., "en", "es", "fr", "de"). If uncertain, default to "en".',
                    ],
                    ['role' => 'user', 'content' => substr($text, 0, 500)],
                ],
            ]);

            $detected = trim(strtolower($response->choices[0]->message->content));

            // Validate it's a reasonable language code (2-3 characters)
            if (preg_match('/^[a-z]{2,3}$/', $detected)) {
                return $detected;
            }
        } catch (\Exception $e) {
            Log::debug('Language detection failed: ' . $e->getMessage());
        }

        return 'en'; // Default fallback
    }

    /**
     * Translate text to a target language.
     */
    public function translateText(string $text, string $targetLanguage, string $sourceLanguage = 'en'): string
    {
        if ($targetLanguage === $sourceLanguage || empty(trim($text))) {
            return $text;
        }

        try {
            $response = \App\Support\Ai::create([
                'model'       => 'gpt-4o-mini',
                'max_tokens'  => 1000,
                'temperature' => 0.3,
                'messages'    => [
                    [
                        'role'    => 'system',
                        'content' => "Translate the following text from {$sourceLanguage} to {$targetLanguage}. Maintain the original meaning and tone. Only return the translated text, nothing else.",
                    ],
                    ['role' => 'user', 'content' => $text],
                ],
            ]);

            return trim($response->choices[0]->message->content);
        } catch (\Exception $e) {
            Log::debug('Translation failed: ' . $e->getMessage());
            return $text; // Return original text if translation fails
        }
    }

    /**
     * Return a human-readable sentiment emoji for display.
     */
    public function getSentimentEmoji(float $score): string
    {
        if ($score >= 0.5) return '😊';
        if ($score >= 0.1) return '🙂';
        if ($score >= -0.1) return '😐';
        if ($score >= -0.5) return '😕';
        return '😠';
    }

    /**
     * Return color hex for sentiment label.
     */
    public function getSentimentColor(string $label): string
    {
        return match ($label) {
            'positive' => '#22c55e',
            'negative' => '#ef4444',
            default    => '#f59e0b',
        };
    }
}
