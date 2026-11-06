<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Widget;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;

/**
 * Generates AI-powered contextual follow-up reply suggestions.
 */
class SmartSuggestionsService
{
    /**
     * Generate smart follow-up suggestions for the visitor based on the AI's last response.
     *
     * @return string[]  Array of short suggestion strings (2-4 words each)
     */
    public function generate(Conversation $conversation, string $aiResponse, string $language = 'en'): array
    {
        $widget = $conversation->widget;
        $count  = $widget->smart_suggestions_count ?? 3;

        if ($count < 1) return [];

        // Get last visitor message for context
        $lastVisitorMsg = $conversation->messages()
            ->where('role', 'visitor')
            ->latest()
            ->value('content') ?? '';

        $langInstruction = $language !== 'en'
            ? "IMPORTANT: Generate suggestions in the same language as the conversation (language code: {$language})."
            : '';

        try {
            $response = \App\Support\Ai::create([
                'model'       => 'gpt-4o-mini',
                'max_tokens'  => 120,
                'temperature' => 0.8,
                'messages'    => [
                    [
                        'role'    => 'system',
                        'content' => "You are generating quick-reply button suggestions for a chat widget. Generate exactly {$count} short, natural follow-up questions or responses (3-6 words each) that the VISITOR might want to say next, based on the AI's last response. Return ONLY a JSON array of strings. No explanations. {$langInstruction}",
                    ],
                    [
                        'role'    => 'user',
                        'content' => "Visitor asked: \"{$lastVisitorMsg}\"\n\nAI responded: \"{$aiResponse}\"\n\nGenerate {$count} smart follow-up suggestions the visitor might click:",
                    ],
                ],
            ]);

            $raw  = trim($response->choices[0]->message->content ?? '');
            // Strip markdown code fences if present
            $raw  = preg_replace('/^```(?:json)?\s*/i', '', $raw);
            $raw  = preg_replace('/\s*```$/', '', $raw);
            $data = json_decode($raw, true);

            if (is_array($data)) {
                return array_values(
                    array_filter(
                        array_map(fn ($s) => is_string($s) ? trim(strip_tags($s)) : '', $data),
                        fn ($s) => strlen($s) > 1 && strlen($s) < 80
                    )
                );
            }
        } catch (\Exception $e) {
            Log::debug('Smart suggestions generation failed: ' . $e->getMessage());
        }

        return [];
    }

    /**
     * Generate topic-based conversation starter suggestions for widget initialization.
     */
    public function generateConversationStarters(Widget $widget): array
    {
        $systemPrompt = $widget->system_prompt ?? '';
        $aiName       = $widget->ai_name ?? 'AI Assistant';

        try {
            $response = \App\Support\Ai::create([
                'model'       => 'gpt-4o-mini',
                'max_tokens'  => 150,
                'temperature' => 0.7,
                'messages'    => [
                    [
                        'role'    => 'system',
                        'content' => 'Generate 4 conversation starter button suggestions for a chat widget. These are short questions or topics visitors commonly ask about. Return ONLY a JSON array of strings.',
                    ],
                    [
                        'role'    => 'user',
                        'content' => "AI name: {$aiName}\nInstructions: {$systemPrompt}\n\nGenerate 4 starter suggestions:",
                    ],
                ],
            ]);

            $raw  = trim($response->choices[0]->message->content ?? '');
            $raw  = preg_replace('/^```(?:json)?\s*/i', '', $raw);
            $raw  = preg_replace('/\s*```$/', '', $raw);
            $data = json_decode($raw, true);

            if (is_array($data)) {
                return array_slice(
                    array_values(array_filter(
                        array_map(fn ($s) => is_string($s) ? trim($s) : '', $data),
                        fn ($s) => strlen($s) > 1
                    )),
                    0, 4
                );
            }
        } catch (\Exception $e) {
            Log::debug('Conversation starters generation failed: ' . $e->getMessage());
        }

        return [];
    }
}
