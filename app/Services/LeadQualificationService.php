<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Lead;
use App\Models\Widget;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;

class LeadQualificationService
{
    /**
     * Analyze conversation and qualify/score a lead.
     */
    public function qualifyFromConversation(Conversation $conversation): ?Lead
    {
        $messages = $conversation->messages()
            ->whereIn('role', ['visitor', 'ai'])
            ->orderBy('created_at')
            ->get();

        if ($messages->count() < 3) {
            return null; // Not enough conversation
        }

        $transcript = $messages->map(fn ($m) => "{$m->role}: {$m->content}")->implode("\n");

        try {
            $response = AppSupportAi::create([
                'model'    => 'gpt-4o-mini',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a lead qualification analyst. Analyze the conversation and extract lead information. Return ONLY valid JSON with these fields: name (string|null), email (string|null), phone (string|null), company (string|null), service_interest (string|null), budget_range (string|null), timeline (string|null), pain_point (string|null), score (int 0-100), temperature (hot|warm|cold). Score based on: purchase intent, budget mention, timeline urgency, decision-maker status. Do not include any text outside the JSON.'
                    ],
                    ['role' => 'user', 'content' => "Analyze this conversation:\n\n{$transcript}"],
                ],
                'max_tokens'  => 500,
                'temperature' => 0.1,
            ]);

            $content = $response->choices[0]->message->content;
            // Extract JSON from potential markdown code blocks
            if (preg_match('/```(?:json)?\s*([\s\S]*?)```/', $content, $matches)) {
                $content = $matches[1];
            }
            $data = json_decode(trim($content), true);

            if (!$data || !isset($data['score'])) {
                Log::warning('Lead qualification returned invalid data', ['response' => $content]);
                return null;
            }

            $visitor = $conversation->visitor;

            $leadKey = [
                'conversation_id' => $conversation->id,
                'user_id'         => $conversation->user_id,
            ];

            $existingLead = Lead::query()->where($leadKey)->first();
            $previousScore = $existingLead?->score;
            // Plan gate: no new leads once the monthly allowance is used (existing leads still update).
            $owner = $conversation->user ?? $conversation->widget?->user;
            if (! $existingLead && $owner && ! $owner->canUse('leads')) {
                return null;
            }
            $previousTemp = $existingLead?->temperature;

            $lead = Lead::updateOrCreate(
                $leadKey,
                [
                    'widget_id'        => $conversation->widget_id,
                    'visitor_id'       => $conversation->visitor_id,
                    'name'             => $data['name'] ?? $visitor?->name,
                    'email'            => $data['email'] ?? $visitor?->email,
                    'phone'            => $data['phone'] ?? $visitor?->phone,
                    'company'          => $data['company'] ?? null,
                    'service_interest' => $data['service_interest'] ?? null,
                    'budget_range'     => $data['budget_range'] ?? null,
                    'timeline'         => $data['timeline'] ?? null,
                    'pain_point'       => $data['pain_point'] ?? null,
                    'score'            => $data['score'],
                    'temperature'      => $data['temperature'] ?? $this->scoreToTemperature($data['score']),
                    'qualification_answers' => $data,
                    'qualified_at'     => $data['score'] >= config('ai.lead_qualification.warm_threshold', 50) ? now() : null,
                    'engagement_score' => $this->calculateEngagementScore($conversation),
                    'quality_grade'    => $this->calculateQualityGrade($data, $conversation),
                    'industry'         => $this->detectIndustry($data),
                    'company_size'     => $this->detectCompanySize($data),
                    'last_engagement_at' => now(),
                    'follow_up_priority' => $this->calculateFollowUpPriority($data, $conversation),
                ]
            );

            // Record A/B test lead if this is a new lead
            if ($lead->wasRecentlyCreated && $owner) {
                $owner->incrementUsage('leads');
            }
            if ($lead->wasRecentlyCreated && $visitor) {
                $this->recordAbTestLead($conversation->widget, $visitor->fingerprint);
            }

            if ($visitor && ($data['email'] ?? null)) {
                $visitor->update(['email' => $data['email'], 'name' => $data['name'] ?? $visitor->name]);
            }

            $widgetModel = Widget::query()->find($conversation->widget_id);
            if ($widgetModel && $widgetModel->webhook_enabled && $widgetModel->webhook_url) {
                $threshold = max(1, (int) config('webhooks.lead_score_jump_threshold', 10));
                $isNewLead = $lead->wasRecentlyCreated;
                $scoreJump = $existingLead !== null
                    && abs((int) $lead->score - (int) ($previousScore ?? 0)) >= $threshold;
                $temperatureChanged = $existingLead !== null
                    && (string) ($previousTemp ?? '') !== (string) $lead->temperature;

                $notifyLead = $widgetModel->webhook_lead_always
                    || $isNewLead
                    || $scoreJump
                    || $temperatureChanged;

                if ($notifyLead) {
                    app(WidgetWebhookService::class)->dispatch($widgetModel, 'lead.qualified', [
                        'conversation_uuid' => $conversation->uuid,
                        'lead_id'           => $lead->id,
                        'score'             => $lead->score,
                        'temperature'       => $lead->temperature,
                        'email'             => $lead->email,
                        'name'              => $lead->name,
                        'meta'              => [
                            'is_new_record'       => $isNewLead,
                            'score_jump'          => $scoreJump,
                            'temperature_changed' => $temperatureChanged,
                        ],
                    ]);
                }
            }

            return $lead;

        } catch (\Exception $e) {
            Log::error('Lead qualification error: ' . $e->getMessage());
            return null;
        }
    }

    protected function scoreToTemperature(int $score): string
    {
        if ($score >= config('ai.lead_qualification.hot_threshold', 80)) return 'hot';
        if ($score >= config('ai.lead_qualification.warm_threshold', 50)) return 'warm';
        return 'cold';
    }

    /**
     * Record A/B test lead event.
     */
    private function recordAbTestLead(Widget $widget, string $fingerprint): void
    {
        $abTest = $widget->abTests()->where('is_active', true)->first();
        if (!$abTest) {
            return;
        }

        $variant = $abTest->assignVariant($fingerprint);
        $abTest->increment("leads_{$variant}");
    }

    /**
     * Calculate engagement score based on conversation metrics.
     */
    protected function calculateEngagementScore(Conversation $conversation): int
    {
        $score = 0;

        // Message count (max 30 points)
        $messageCount = $conversation->messages()->whereIn('role', ['visitor', 'ai'])->count();
        $score += min($messageCount * 2, 30);

        // Conversation duration (max 20 points)
        $duration = $conversation->created_at->diffInMinutes($conversation->last_message_at ?? now());
        $score += min($duration, 20);

        // Visitor messages ratio (max 20 points)
        $visitorMessages = $conversation->messages()->where('role', 'visitor')->count();
        $totalMessages = $conversation->messages()->whereIn('role', ['visitor', 'ai'])->count();
        if ($totalMessages > 0) {
            $ratio = $visitorMessages / $totalMessages;
            $score += min($ratio * 40, 20); // Max 20 points for high engagement
        }

        // Has contact info (10 points)
        if ($conversation->visitor && ($conversation->visitor->email || $conversation->visitor->phone)) {
            $score += 10;
        }

        // Has company info (10 points)
        if ($conversation->visitor && $conversation->visitor->company) {
            $score += 10;
        }

        // Has pain point mentioned (10 points)
        if (str_contains(strtolower($conversation->messages()->where('role', 'visitor')->pluck('content')->implode(' ')), 'problem') ||
            str_contains(strtolower($conversation->messages()->where('role', 'visitor')->pluck('content')->implode(' ')), 'issue') ||
            str_contains(strtolower($conversation->messages()->where('role', 'visitor')->pluck('content')->implode(' ')), 'need')) {
            $score += 10;
        }

        return min($score, 100);
    }

    /**
     * Calculate quality grade based on multiple factors.
     */
    protected function calculateQualityGrade(array $data, Conversation $conversation): string
    {
        $score = $data['score'] ?? 0;

        // Boost for high engagement
        $engagementScore = $this->calculateEngagementScore($conversation);
        $score += $engagementScore * 0.3; // 30% weight on engagement

        // Boost for contact completeness
        $contactCompleteness = 0;
        if (!empty($data['email'])) $contactCompleteness += 25;
        if (!empty($data['phone'])) $contactCompleteness += 25;
        if (!empty($data['company'])) $contactCompleteness += 25;
        if (!empty($data['name'])) $contactCompleteness += 25;
        $score += $contactCompleteness * 0.2; // 20% weight on contact completeness

        // Boost for specific business signals
        if (!empty($data['budget_range'])) $score += 10;
        if (!empty($data['timeline']) && str_contains(strtolower($data['timeline']), 'soon')) $score += 10;
        if (!empty($data['service_interest'])) $score += 5;

        $finalScore = min($score, 100);

        if ($finalScore >= 85) return 'A';
        if ($finalScore >= 70) return 'B';
        if ($finalScore >= 50) return 'C';
        return 'D';
    }

    /**
     * Detect industry from conversation data.
     */
    protected function detectIndustry(array $data): ?string
    {
        $text = strtolower(($data['company'] ?? '') . ' ' . ($data['service_interest'] ?? '') . ' ' . ($data['pain_point'] ?? ''));

        $industries = [
            'technology' => ['tech', 'software', 'app', 'digital', 'it', 'web'],
            'healthcare' => ['health', 'medical', 'clinic', 'hospital', 'patient'],
            'finance' => ['bank', 'financial', 'investment', 'insurance', 'accounting'],
            'retail' => ['store', 'shop', 'ecommerce', 'retail', 'commerce'],
            'education' => ['school', 'university', 'education', 'training', 'learning'],
            'manufacturing' => ['manufacture', 'production', 'factory', 'industrial'],
            'real estate' => ['real estate', 'property', 'housing', 'construction'],
            'marketing' => ['marketing', 'advertising', 'brand', 'agency'],
        ];

        foreach ($industries as $industry => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($text, $keyword)) {
                    return $industry;
                }
            }
        }

        return null;
    }

    /**
     * Detect company size from conversation data.
     */
    protected function detectCompanySize(array $data): ?string
    {
        $text = strtolower(($data['company'] ?? '') . ' ' . ($data['service_interest'] ?? ''));

        if (str_contains($text, 'enterprise') || str_contains($text, 'large') || str_contains($text, 'corporation')) {
            return 'enterprise';
        }
        if (str_contains($text, 'startup') || str_contains($text, 'small')) {
            return 'small';
        }
        if (str_contains($text, 'medium') || str_contains($text, 'mid-size')) {
            return 'medium';
        }

        return null;
    }

    /**
     * Calculate follow-up priority score.
     */
    protected function calculateFollowUpPriority(array $data, Conversation $conversation): int
    {
        $priority = 0;

        // Base priority from temperature
        $temperature = $data['temperature'] ?? $this->scoreToTemperature($data['score'] ?? 0);
        switch ($temperature) {
            case 'hot': $priority += 50; break;
            case 'warm': $priority += 30; break;
            case 'cold': $priority += 10; break;
        }

        // Quality grade bonus
        $grade = $this->calculateQualityGrade($data, $conversation);
        switch ($grade) {
            case 'A': $priority += 30; break;
            case 'B': $priority += 20; break;
            case 'C': $priority += 10; break;
        }

        // Urgency signals
        if (!empty($data['timeline']) && str_contains(strtolower($data['timeline']), 'urgent')) {
            $priority += 20;
        }
        if (!empty($data['timeline']) && str_contains(strtolower($data['timeline']), 'asap')) {
            $priority += 20;
        }

        // High budget signals
        if (!empty($data['budget_range']) && (str_contains(strtolower($data['budget_range']), 'high') || str_contains($data['budget_range'], '$'))) {
            $priority += 15;
        }

        return min($priority, 100);
    }
}
