<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\Widget;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;

class AiChatService
{
    public function __construct(
        protected KnowledgeRetrievalService  $retrievalService,
        protected LeadQualificationService   $leadService,
        protected MultiProviderAiService     $aiProvider,
        protected SentimentAnalysisService   $sentimentService,
        protected SmartSuggestionsService    $suggestionsService,
    ) {}

    /**
     * Generate an AI response for a visitor message.
     */
    public function generateResponse(Conversation $conversation, string $userMessage): array
    {
        $widget = $conversation->widget;

        // 1. Detect language if enabled
        $detectedLanguage = 'en';
        if ($widget->auto_language_detection) {
            $detectedLanguage = $this->sentimentService->detectLanguage($userMessage);
            if ($detectedLanguage !== 'en' && !$conversation->detected_language) {
                $conversation->update(['detected_language' => $detectedLanguage]);
            }
        }
        $lang = $conversation->detected_language ?? $detectedLanguage;

        // 2. Retrieve relevant context from knowledge base
        $relevantChunks = $this->retrievalService->findRelevantChunks($widget, $userMessage);

        // 3. Build the system prompt
        $systemPrompt = $this->buildSystemPrompt($widget, $relevantChunks, $lang);

        // 4. Build conversation history
        $messages = $this->buildMessages($conversation, $systemPrompt, $userMessage);

        // 5. Call AI provider
        try {
            $result  = $this->aiProvider->chat($widget, $messages, $this->getMaxTokens($widget));
            $content = $result['content'] ?? '';

            $confidence = $this->calculateConfidence($relevantChunks);
            $isFallback = $confidence < config('ai.response.confidence_threshold', 0.6);

            if ($isFallback && $widget->strict_mode) {
                $content = config('ai.response.fallback_message');
            }

            // 6. Translate response if needed
            if ($lang !== 'en' && $widget->auto_language_detection) {
                $content = $this->sentimentService->translateText($content, $lang, 'en');
            }

            // 7. Generate smart follow-up suggestions — isolated so a failure here
            //    does not swallow the entire AI response.
            $suggestions = [];
            if ($widget->smart_suggestions_enabled && !$isFallback) {
                try {
                    $suggestions = $this->suggestionsService->generate($conversation, $content, $lang);
                } catch (\Exception $e) {
                    Log::warning('Smart suggestions failed (non-fatal): ' . $e->getMessage());
                }
            }

            return [
                'content'           => $content,
                'confidence_score'  => $confidence,
                'is_fallback'       => $isFallback,
                'sources_used'      => $relevantChunks->pluck('id')->toArray(),
                'tokens_used'       => $result['tokens_used'] ?? 0,
                'provider'          => $result['provider'] ?? 'openai',
                'model_used'        => $result['model'] ?? null,
                'smart_suggestions' => $suggestions,
                'detected_language' => $lang,
            ];
        } catch (\Exception $e) {
            Log::error('AI API error: ' . $e->getMessage(), [
                'conversation_id' => $conversation->id,
                'widget_id'       => $widget->id,
                'provider'        => \App\Services\MultiProviderAiService::platformProvider(),
            ]);

            return [
                'content'           => 'I\'m experiencing some technical difficulties. Please try again in a moment, or I can connect you with our team.',
                'confidence_score'  => 0,
                'is_fallback'       => true,
                'sources_used'      => [],
                'tokens_used'       => 0,
                'provider'          => \App\Services\MultiProviderAiService::platformProvider(),
                'model_used'        => null,
                'smart_suggestions' => [],
                'detected_language' => $lang,
            ];
        }
    }

    /**
     * Stream an AI response (for real-time typing effect).
     */
    public function streamResponse(Conversation $conversation, string $userMessage): \Generator
    {
        $widget = $conversation->widget;

        // Language detection
        $lang = $conversation->detected_language ?? 'en';
        if ($widget->auto_language_detection && !$lang) {
            $lang = $this->sentimentService->detectLanguage($userMessage);
        }

        $relevantChunks = $this->retrievalService->findRelevantChunks($widget, $userMessage);
        $systemPrompt   = $this->buildSystemPrompt($widget, $relevantChunks, $lang);
        $messages       = $this->buildMessages($conversation, $systemPrompt, $userMessage);

        try {
            yield from $this->aiProvider->chatStreamed($widget, $messages, $this->getMaxTokens($widget));
        } catch (\Exception $e) {
            Log::error('AI stream error: ' . $e->getMessage(), [
                'provider' => \App\Services\MultiProviderAiService::platformProvider(),
            ]);
            yield 'I\'m experiencing technical difficulties. Please try again.';
        }
    }

    /**
     * Generate smart suggestions for an already-completed AI response.
     * Used when streaming finishes and we want to append suggestions.
     */
    public function generatePostStreamSuggestions(Conversation $conversation, string $aiContent): array
    {
        $widget = $conversation->widget;
        if (!$widget->smart_suggestions_enabled) return [];

        $lang = $conversation->detected_language ?? 'en';
        return $this->suggestionsService->generate($conversation, $aiContent, $lang);
    }

    protected function buildSystemPrompt(Widget $widget, $chunks, string $lang = 'en'): string
    {
        $tone         = config("ai.response.tones.{$widget->tone}", config('ai.response.tones.friendly'));
        $customPrompt = $widget->system_prompt ?? '';

        $contextBlock = '';
        if ($chunks->isNotEmpty()) {
            $contextBlock = "\n\n--- KNOWLEDGE BASE CONTEXT ---\n";
            foreach ($chunks as $chunk) {
                $contextBlock .= $chunk->content . "\n---\n";
            }
            $contextBlock .= "--- END CONTEXT ---\n";
            $contextBlock .= "\nUse the above context to answer the user's questions accurately. If the answer isn't in the context, say so honestly.";
        }

        $prompt = "{$tone}\n\nYour name is: {$widget->ai_name}\n";

        if ($customPrompt) {
            $prompt .= "\nAdditional instructions: {$customPrompt}\n";
        }

        $prompt .= $contextBlock;

        if ($lang !== 'en') {
            $prompt .= "\n\nIMPORTANT: The visitor is communicating in language code '{$lang}'. You MUST respond in the same language as the visitor. Do not switch to English unless the visitor does.";
        }

        if ($widget->lead_qualification_enabled) {
            $prompt .= "\n\nYou should naturally try to understand the visitor's needs. When appropriate, ask about their budget, timeline, company size, and main challenges. Be conversational, not interrogative.";
        }

        $booking = \App\Support\Booking::settings($widget);
        if ($booking['enabled'] && $booking['ai_booking']) {
            $services = collect($booking['services'])->map(fn ($x) => $x['name'] . ' (' . $x['duration'] . ' min' . ($x['price'] ? ', ' . $x['price'] : '') . ')')->implode('; ');
            $prompt .= "\n\nAppointments: visitors can book online with " . $booking['business_name'] . '. Services: ' . $services . '. '
                . 'When the visitor wants to book, reschedule or check availability, answer briefly and end your reply with the exact token [[BOOKING]] on its own line. '
                . 'The chat shows a booking button in its place. Never invent available times; the button shows real availability. '
                . 'Every booking has a booking number like BK-7F3K9Q sent by email. When the visitor wants to change, move, check or cancel an existing booking, '
                . 'answer briefly and end your reply with the exact token [[MANAGE_BOOKING]] on its own line; the chat then asks for their booking number and email. '
                . 'Never confirm, change or cancel a booking yourself.';
        }
        $prompt .= "\n\nRules:\n";
        $prompt .= "- Keep responses concise and helpful\n";
        $prompt .= "- Use markdown formatting when appropriate (bold, bullets, code blocks)\n";
        $prompt .= "- If you don't know the answer, suggest they contact the team\n";
        $prompt .= "- Never make up information about products or pricing\n";
        $prompt .= "- Be warm and engaging to build rapport\n";

        return $prompt;
    }

    protected function buildMessages(Conversation $conversation, string $systemPrompt, string $currentMessage): array
    {
        $messages = [['role' => 'system', 'content' => $systemPrompt]];

        $maxHistory = config('ai.response.max_history_messages', 20);
        $history = $conversation->messages()
            ->whereIn('role', ['visitor', 'ai', 'agent'])
            ->orderBy('created_at', 'desc')
            ->take($maxHistory)
            ->get()
            ->reverse();

        foreach ($history as $msg) {
            $role = match ($msg->role) {
                'visitor' => 'user',
                'ai'      => 'assistant',
                'agent'   => 'assistant',
                default   => 'user',
            };
            $messages[] = ['role' => $role, 'content' => $msg->content];
        }

        $last = $history->last();
        $alreadyHasUserTurn = $last && $last->role === 'visitor' && $last->content === $currentMessage;
        if (!$alreadyHasUserTurn) {
            $messages[] = ['role' => 'user', 'content' => $currentMessage];
        }

        return $messages;
    }

    protected function calculateConfidence($chunks): float
    {
        if ($chunks->isEmpty()) return 0.0;
        return round($chunks->max('similarity_score') ?? 0.5, 2);
    }

    protected function getMaxTokens(Widget $widget): int
    {
        return match ($widget->response_length ?? 'medium') {
            'short'  => 256,
            'medium' => 512,
            'long'   => 1024,
            default  => 512,
        };
    }

    /**
     * Summarize a conversation using AI.
     */
    public function summarizeConversation(Conversation $conversation): string
    {
        $messages = $conversation->messages()
            ->orderBy('created_at')
            ->take(50)
            ->get()
            ->map(fn ($m) => "{$m->role}: {$m->content}")
            ->implode("\n");

        try {
            $response = OpenAI::chat()->create([
                'model'      => 'gpt-4o-mini',
                'messages'   => [
                    ['role' => 'system', 'content' => 'Summarize this conversation in 2-3 sentences. Focus on the main topic, visitor needs, and any key information gathered.'],
                    ['role' => 'user', 'content' => $messages],
                ],
                'max_tokens' => 150,
            ]);

            return $response->choices[0]->message->content;
        } catch (\Exception $e) {
            Log::error('Summarization error: ' . $e->getMessage());
            return 'Unable to generate summary.';
        }
    }

    /**
     * Generate an agent assist suggestion — AI suggests what a human agent could reply.
     */
    public function agentAssist(Conversation $conversation): string
    {
        $history = $conversation->messages()
            ->orderBy('created_at')
            ->take(30)
            ->get()
            ->map(fn ($m) => "{$m->role}: {$m->content}")
            ->implode("\n");

        try {
            $response = OpenAI::chat()->create([
                'model'      => 'gpt-4o-mini',
                'max_tokens' => 200,
                'messages'   => [
                    [
                        'role'    => 'system',
                        'content' => 'You are an assistant helping a human support agent craft the best reply. Based on the conversation history, suggest a helpful, professional response the agent could send. Be concise.',
                    ],
                    ['role' => 'user', 'content' => $history . "\n\nSuggest a reply for the agent:"],
                ],
            ]);

            return $response->choices[0]->message->content ?? '';
        } catch (\Exception $e) {
            Log::error('Agent assist error: ' . $e->getMessage());
            return '';
        }
    }

    /**
     * Classify the intent/topic of a conversation.
     */
    public function classifyIntent(string $message): array
    {
        try {
            $response = OpenAI::chat()->create([
                'model'       => 'gpt-4o-mini',
                'max_tokens'  => 80,
                'temperature' => 0,
                'messages'    => [
                    [
                        'role'    => 'system',
                        'content' => 'Classify the intent of this message. Return JSON: {"intent": "question|complaint|purchase|support|feedback|greeting|other", "topic": "<short topic label 1-3 words>", "urgency": "low|medium|high"}',
                    ],
                    ['role' => 'user', 'content' => substr($message, 0, 400)],
                ],
            ]);

            $raw  = trim($response->choices[0]->message->content ?? '');
            $raw  = preg_replace('/^```(?:json)?\s*/i', '', $raw);
            $raw  = preg_replace('/\s*```$/', '', $raw);
            $data = json_decode($raw, true);

            return is_array($data) ? $data : ['intent' => 'other', 'topic' => 'general', 'urgency' => 'low'];
        } catch (\Exception $e) {
            return ['intent' => 'other', 'topic' => 'general', 'urgency' => 'low'];
        }
    }
}
