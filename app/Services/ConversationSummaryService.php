<?php

namespace App\Services;

use App\Models\Conversation;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;

class ConversationSummaryService
{
    /**
     * Generate AI summary and action items for a conversation.
     */
    public function generateSummary(Conversation $conversation): bool
    {
        $messages = $conversation->messages()
            ->whereIn('role', ['visitor', 'ai'])
            ->orderBy('created_at')
            ->get();

        if ($messages->count() < 2) {
            return false; // Not enough conversation for meaningful summary
        }

        $transcript = $messages->map(fn ($m) => "{$m->role}: {$m->content}")->implode("\n");

        try {
            $response = \App\Support\Ai::create([
                'model'    => 'gpt-4o-mini',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a conversation summarizer. Analyze the chat conversation and provide a concise summary and extract any action items mentioned. Return ONLY valid JSON with these fields: summary (string, 2-3 sentences), action_items (array of strings, each action item as a clear actionable statement). Focus on key points discussed, decisions made, and follow-up actions needed. Keep summary brief but comprehensive.'
                    ],
                    ['role' => 'user', 'content' => "Summarize this conversation and extract action items:\n\n{$transcript}"],
                ],
                'max_tokens'  => 1000,
                'temperature' => 0.3,
            ]);

            $content = $response->choices[0]->message->content;

            // Extract JSON from potential markdown code blocks
            if (preg_match('/```(?:json)?\s*([\s\S]*?)```/', $content, $matches)) {
                $content = $matches[1];
            }

            $data = json_decode(trim($content), true);

            if (!$data || !isset($data['summary'])) {
                Log::warning('Invalid AI response for conversation summary', [
                    'conversation_id' => $conversation->id,
                    'response' => $content
                ]);
                return false;
            }

            // Update conversation with summary data
            $conversation->update([
                'summary' => $data['summary'],
                'action_items' => $data['action_items'] ?? [],
                'summary_generated_at' => now(),
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to generate conversation summary', [
                'conversation_id' => $conversation->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Check if a conversation should have a summary generated.
     */
    public function shouldGenerateSummary(Conversation $conversation): bool
    {
        // Only generate for completed conversations
        if (!$conversation->resolved_at) {
            return false;
        }

        // Don't regenerate if already has summary
        if ($conversation->summary_generated_at) {
            return false;
        }

        // Check if conversation has enough messages
        $messageCount = $conversation->messages()
            ->whereIn('role', ['visitor', 'ai'])
            ->count();

        return $messageCount >= 4; // Minimum for meaningful summary
    }
}