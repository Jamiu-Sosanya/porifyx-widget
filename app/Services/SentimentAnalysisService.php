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
        try {
            $response = OpenAI::chat()->create([
                'model'       => 'gpt-4o-mini',
                'max_tokens'  => 60,
                'temperature' => 0,
                'messages'    => [
                    [
                        'role'    => 'system',
                        'content' => 'Analyze the sentiment of the following message. Respond with JSON only: {"score": <float between -1.0 and 1.0>, "label": "positive"|"neutral"|"negative"}. Score: -1.0=very negative, 0=neutral, 1.0=very positive.',
                    ],
                    ['role' => 'user', 'content' => substr($text, 0, 500)],
                ],
            ]);

            $raw  = $response->choices[0]->message->content;
            $data = json_decode(trim($raw), true);

            if (isset($data['score'], $data['label'])) {
                return [
                    'score' => max(-1.0, min(1.0, (float) $data['score'])),
                    'label' => in_array($data['label'], ['positive', 'neutral', 'negative']) ? $data['label'] : 'neutral',
                ];
            }
        } catch (\Exception $e) {
            Log::debug('Sentiment analysis failed: ' . $e->getMessage());
        }

        return ['score' => 0.0, 'label' => 'neutral'];
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
            $response = OpenAI::chat()->create([
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
            $response = OpenAI::chat()->create([
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
            $response = OpenAI::chat()->create([
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
