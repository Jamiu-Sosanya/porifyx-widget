<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\OfflineMessage;
use App\Models\ProactiveMessage;
use App\Models\Visitor;
use App\Models\Widget;
use App\Services\AiChatService;
use App\Services\LeadQualificationService;
use App\Services\NotificationChannelService;
use App\Services\ProactiveMessagingService;
use App\Services\SentimentAnalysisService;
use App\Services\SmartSuggestionsService;
use App\Services\WidgetWebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WidgetApiController extends Controller
{
    public function __construct(
        protected AiChatService              $chatService,
        protected LeadQualificationService   $leadService,
        protected WidgetWebhookService       $webhooks,
        protected SentimentAnalysisService   $sentimentService,
        protected SmartSuggestionsService    $suggestionsService,
        protected ProactiveMessagingService  $proactiveService,
        protected NotificationChannelService $notificationService,
    ) {}

    /**
     * Get widget configuration for the embed script.
     */
    public function config(string $widgetUuid)
    {
        $widget = Widget::forPublicApi($widgetUuid, false);

        if (!$widget) {
            return response()->json(['error' => 'Widget not found or inactive.'], 404);
        }

        $origin = request()->header('Origin') ?? request()->header('Referer');
        if ($origin && $widget->domain_restriction && ! $widget->isPlaygroundRequest()) {
            $domain = parse_url($origin, PHP_URL_HOST);
            if (!$widget->isDomainAllowed($domain)) {
                return response()->json(['error' => 'Domain not authorized.'], 403);
            }
        }

        $plan = $widget->planState();

        // Detect user language
        $userLanguage = $this->detectUserLanguage(request());

        $suggested = $widget->suggested_replies;
        if (!is_array($suggested)) $suggested = [];

        $starters = $widget->conversation_starters;
        if (!is_array($starters)) $starters = [];

        $pageUrl = request('page_url', '');

        return response()->json([
            'widget' => [
                // Identity
                'uuid'             => $widget->uuid,
                'ai_name'          => $widget->ai_name,
                'ai_avatar'        => $widget->ai_avatar ? asset('storage/' . $widget->ai_avatar) : null,

                // Appearance
                'primary_color'    => $widget->primary_color,
                'secondary_color'  => $widget->secondary_color,
                'text_color'       => $widget->text_color,
                'bg_color'         => $widget->bg_color,
                'position'         => $widget->position,
                'launcher_shape'   => $widget->launcher_shape,
                'launcher_icon'    => \App\Support\LauncherIcons::forWidget($widget),
                'launcher_style'   => \App\Support\LauncherIcons::style($widget),
                'font_family'      => $widget->font_family,
                'theme'            => $widget->theme ?? 'dark',

                // Behavior (localized)
                'welcome_message'     => $this->getLocalizedText($widget->welcome_messages, $userLanguage, $widget->welcome_message),
                'placeholder_text'    => $this->getLocalizedText($widget->placeholder_texts, $userLanguage, $widget->placeholder_text),
                'show_branding'       => $widget->show_branding || $plan['branding'],
                'auto_open'           => $widget->auto_open,
                'auto_open_delay'     => $widget->auto_open_delay,
                'show_on_mobile'      => $widget->show_on_mobile,
                'collect_email'       => $widget->collect_email,
                'collect_phone'       => $widget->collect_phone,
                'emoji_enabled'       => $widget->emoji_enabled,
                'typing_animation'    => $widget->typing_animation,
                'message_streaming'   => $widget->message_streaming,
                'sound_enabled'       => $widget->sound_enabled ?? false,

                // Replies & starters (localized)
                'suggested_replies'      => array_values(array_filter($suggested, fn ($r) => is_string($r) && $r !== '')),
                'conversation_starters'  => $this->getLocalizedStarters($widget->conversation_starters_multi, $userLanguage, $starters),
                'smart_suggestions_enabled' => (bool) $widget->smart_suggestions_enabled,

                // Handoff
                'show_handoff_button'  => (bool) $widget->show_handoff_button && $plan['handoff'],
                'handoff_button_label' => $widget->handoff_button_label,

                // CSAT
                'csat_prompt_enabled'  => (bool) $widget->csat_prompt_enabled,

                // Offline form (localized)
                'offline_form_enabled' => (bool) $widget->offline_form_enabled || ! $plan['chat'],
                // Plan limit reached or subscription inactive: the widget shows a friendly notice and the message form.
                'service_available'    => $plan['chat'],
                'playground'           => $widget->isPlaygroundRequest(),
                // Engagement: hidden pages, AI notice, voice input
                'hidden'               => ! $widget->isPlaygroundRequest() && \App\Support\Engagement::hiddenOn($widget, (string) request('page_url', '')),
                'ai_notice'            => ($n = \App\Support\Engagement::settings($widget)['ai_notice'])['enabled'] ? $n + ['version' => substr(md5($n['title'] . $n['text']), 0, 8)] : null,
                'voice_input_enabled'  => (bool) $widget->voice_input_enabled,
                'lead_form'            => ($lf = \App\Support\LeadForm::settings($widget))['mode'] !== 'off' ? $lf : null,
                'offline_form_message' => $this->getLocalizedText($widget->offline_form_messages, $userLanguage, $widget->offline_form_message),

                // Proactive
                'proactive' => $this->proactiveService->buildWidgetConfig($widget, $pageUrl),

                // A/B Testing
                'ab_test' => $this->getActiveAbTest($widget, (string) request()->get('fingerprint', '')),

                // Bookings
                'booking' => ($b = \App\Support\Booking::settings($widget))['enabled'] && $plan['bookings'] ? ['label' => $b['button_label']] : null,

                // Language info
                'detected_language' => $userLanguage,
            ],
        ]);
    }

    /**
     * Detect the user's preferred language from the request.
     */
    private function detectUserLanguage(Request $request): string
    {
        // Check for explicit language parameter
        $explicitLang = $request->get('lang');
        if ($explicitLang && preg_match('/^[a-z]{2,3}$/', $explicitLang)) {
            return strtolower($explicitLang);
        }

        // Use Laravel's preferred language detection
        $preferred = $request->getPreferredLanguage();
        if ($preferred) {
            // Extract language code (e.g., 'en-US' -> 'en')
            $langCode = explode('-', $preferred)[0];
            if (preg_match('/^[a-z]{2,3}$/', $langCode)) {
                return strtolower($langCode);
            }
        }

        return 'en'; // Default fallback
    }

    /**
     * Get localized text from multi-language array, with fallback.
     */
    private function getLocalizedText(?array $localizedTexts, string $language, string $fallback): string
    {
        if (!$localizedTexts || !is_array($localizedTexts)) {
            return $fallback;
        }

        return $localizedTexts[$language] ?? $localizedTexts['en'] ?? $fallback;
    }

    /**
     * Get localized conversation starters, with fallback.
     */
    private function getLocalizedStarters(?array $localizedStarters, string $language, array $fallback): array
    {
        if (!$localizedStarters || !is_array($localizedStarters)) {
            return array_values(array_filter($fallback, fn ($s) => is_string($s) && $s !== ''));
        }

        $localized = $localizedStarters[$language] ?? $localizedStarters['en'] ?? null;
        if ($localized && is_array($localized)) {
            return array_values(array_filter($localized, fn ($s) => is_string($s) && $s !== ''));
        }

        return array_values(array_filter($fallback, fn ($s) => is_string($s) && $s !== ''));
    }

    /**
     * Get active A/B test configuration for a visitor.
     */
    private function getActiveAbTest(Widget $widget, string $fingerprint): ?array
    {
        $abTest = $widget->abTests()->where('is_active', true)->first();
        if (!$abTest) {
            return null;
        }

        $variant = $abTest->assignVariant($fingerprint);
        return [
            'id'      => $abTest->id,
            'variant' => $variant,
            'config'  => $abTest->{"variant_{$variant}"},
        ];
    }

    /**
     * Record A/B test event (conversation or lead).
     */
    private function recordAbTestEvent(Widget $widget, string $fingerprint, string $event): void
    {
        $abTest = $widget->abTests()->where('is_active', true)->first();
        if (!$abTest) {
            return;
        }

        $variant = $abTest->assignVariant($fingerprint);
        $abTest->increment("{$event}s_{$variant}");
    }

    /**
     * Start or resume a conversation.
     */
    public function startConversation(Request $request, string $widgetUuid)
    {
        $widget = Widget::forPublicApi($widgetUuid);

        $validated = $request->validate([
            'fingerprint' => 'required|string|max:64',
            'page_url'    => 'nullable|url',
            'referrer'    => 'nullable|url',
            'name'        => 'nullable|string|max:255',
            'email'       => 'nullable|email',
            'timezone'    => 'nullable|string|max:60',
            'device_type' => 'nullable|string|max:20',
        ]);

        $visitor = Visitor::firstOrCreate(
            ['widget_id' => $widget->id, 'fingerprint' => $validated['fingerprint']],
            [
                'ip_address'    => $request->ip(),
                'browser'       => $request->header('User-Agent'),
                'language'      => $request->getPreferredLanguage(),
                'timezone'      => $validated['timezone'] ?? null,
                'device_type'   => $validated['device_type'] ?? null,
                'first_seen_at' => now(),
                'last_seen_at'  => now(),
                'name'          => $validated['name'] ?? null,
                'email'         => $validated['email'] ?? null,
            ]
        );

        $visitor->update([
            'last_seen_at' => now(),
            'visit_count'  => $visitor->visit_count + 1,
        ]);

        if (isset($validated['name']))  $visitor->update(['name'  => $validated['name']]);
        if (isset($validated['email'])) $visitor->update(['email' => $validated['email']]);

        $conversation = Conversation::where('widget_id', $widget->id)
            ->where('visitor_id', $visitor->id)
            ->whereIn('status', ['ai_active', 'human_active', 'open'])
            ->latest()
            ->first();

        if (!$conversation) {
            $conversation = Conversation::create([
                'widget_id'        => $widget->id,
                'visitor_id'       => $visitor->id,
                'user_id'          => $widget->user_id,
                'status'           => 'ai_active',
                'current_page_url' => $validated['page_url'] ?? null,
                'referrer_url'     => $validated['referrer'] ?? null,
                'last_message_at'  => now(),
            ]);

            $widget->increment('total_conversations');

            // Record A/B test conversation
            $this->recordAbTestEvent($widget, $validated['fingerprint'], 'conversation');

            // Detect user language for localized welcome message
            $userLanguage = $this->detectUserLanguage($request);
            $welcomeMessage = $this->getLocalizedText($widget->welcome_messages, $userLanguage, $widget->welcome_message);

            Message::create([
                'conversation_id' => $conversation->id,
                'role'            => 'ai',
                'content'         => $welcomeMessage,
            ]);
        }

        $messages = $conversation->messages()->orderBy('created_at')->get()->map(fn ($m) => [
            'uuid'              => $m->uuid,
            'role'              => $m->role,
            'content'           => $m->content,
            'smart_suggestions' => $m->smart_suggestions ?? [],
            'created_at'        => $m->created_at->toISOString(),
        ]);

        return response()->json([
            'conversation_uuid'   => $conversation->uuid,
            'conversation_status' => $conversation->status,
            'messages'            => $messages,
            'visitor'             => [
                'name'  => $visitor->name,
                'email' => $visitor->email,
            ],
        ]);
    }

    /**
     * Send a message and get AI response.
     */
    public function sendMessage(Request $request, string $widgetUuid, string $conversationUuid)
    {
        $widget       = Widget::forPublicApi($widgetUuid);
        $conversation = $this->conversationForWidget($widget, $conversationUuid);

        $validated = $request->validate([
            'content' => 'required|string|max:' . config('ai.widget.max_message_length', 2000),
        ]);

        $user = $widget->user;
        if (!$user->canUse('messages')) {
            return response()->json(['error' => 'unavailable', 'code' => 'service_unavailable'], 503);
        }

        // Sentiment on visitor message
        $sentiment = $this->sentimentService->analyzeMessage($validated['content']);

        $visitorMessage = Message::create([
            'conversation_id' => $conversation->id,
            'role'            => 'visitor',
            'content'         => $validated['content'],
            'sentiment_score' => $sentiment['score'],
            'sentiment_label' => $sentiment['label'],
        ]);

        $aiResponse = null;
        if ($conversation->isAiActive()) {
            $result = $this->chatService->generateResponse($conversation, $validated['content']);

            $aiMessage = Message::create([
                'conversation_id' => $conversation->id,
                'role'            => 'ai',
                'content'         => $result['content'],
                'confidence_score'=> $result['confidence_score'],
                'is_fallback'     => $result['is_fallback'],
                'sources_used'    => $result['sources_used'],
                'detected_language' => $result['detected_language'] ?? null,
                'smart_suggestions' => $result['smart_suggestions'] ?? [],
                'metadata'        => [
                    'provider'   => $result['provider'] ?? null,
                    'model_used' => $result['model_used'] ?? null,
                    'sources'    => $result['sources'] ?? [],
                ],
            ]);

            $aiResponse = [
                'uuid'              => $aiMessage->uuid,
                'role'              => 'ai',
                'content'           => $result['content'],
                'confidence_score'  => $result['confidence_score'],
                'is_fallback'       => $result['is_fallback'],
                'smart_suggestions' => $result['smart_suggestions'] ?? [],
                'sources'           => $result['sources'] ?? [],
                'cards'             => \App\Support\Engagement::cardsFor($widget, $validated['content'] . ' ' . $result['content']),
                'created_at'        => $aiMessage->created_at->toISOString(),
            ];

            $conversation->increment('ai_message_count');

            // Update detected language on conversation
            if (!empty($result['detected_language'])) {
                $conversation->update(['detected_language' => $result['detected_language']]);
            }
        }

        $conversation->update([
            'message_count'   => $conversation->messages()->count(),
            'last_message_at' => now(),
            'is_read'         => false,
        ]);

        if (! $widget->isPlaygroundRequest()) $user->incrementUsage('messages');
        $widget->increment('total_messages');

        // Update conversation sentiment
        $this->sentimentService->updateConversationSentiment($conversation);

        // Lead qualification every 5 messages
        if ($conversation->message_count % 5 === 0 && $widget->lead_qualification_enabled) {
            try {
                $lead = $this->leadService->qualifyFromConversation($conversation);
                if ($lead) {
                    $this->notificationService->notifyLeadQualified($widget, $lead);
                }
            } catch (\Exception $e) {
                Log::warning('Lead qualification failed: ' . $e->getMessage());
            }
        }

        return response()->json([
            'visitor_message' => [
                'uuid'            => $visitorMessage->uuid,
                'role'            => 'visitor',
                'content'         => $validated['content'],
                'sentiment_score' => $sentiment['score'],
                'sentiment_label' => $sentiment['label'],
                'created_at'      => $visitorMessage->created_at->toISOString(),
            ],
            'ai_response' => $aiResponse,
        ]);
    }

    /**
     * Stream AI response using Server-Sent Events.
     */
    public function streamMessage(Request $request, string $widgetUuid, string $conversationUuid): StreamedResponse|JsonResponse
    {
        $widget       = Widget::forPublicApi($widgetUuid);
        $conversation = $this->conversationForWidget($widget, $conversationUuid);

        $maxLen    = (int) config('ai.widget.max_message_length', 2000);
        $validated = $request->validate([
            'content' => 'required|string|max:' . $maxLen,
        ]);

        $user = $widget->user;
        if (!$user->canUse('messages')) {
            return response()->json(['error' => 'unavailable', 'code' => 'service_unavailable'], 503);
        }

        if (!$conversation->isAiActive()) {
            return response()->json(['error' => 'AI chat is not active for this conversation.'], 409);
        }

        // Save visitor message with sentiment
        $sentiment = $this->sentimentService->analyzeMessage($validated['content']);
        Message::create([
            'conversation_id' => $conversation->id,
            'role'            => 'visitor',
            'content'         => $validated['content'],
            'sentiment_score' => $sentiment['score'],
            'sentiment_label' => $sentiment['label'],
        ]);

        $chatService        = $this->chatService;
        $sentimentSvc       = $this->sentimentService;
        $notificationSvc    = $this->notificationService;
        $leadSvc            = $this->leadService;

        return response()->stream(function () use (
            $conversation, $validated, $widget, $user,
            $chatService, $sentimentSvc, $notificationSvc, $leadSvc
        ) {
            $fullContent = '';

            foreach ($chatService->streamResponse($conversation, $validated['content']) as $chunk) {
                $fullContent .= $chunk;
                echo 'data: ' . json_encode(['chunk' => $chunk]) . "\n\n";
                if (ob_get_level() > 0) ob_flush();
                flush();
            }

            // Generate post-stream smart suggestions
            $suggestions = $chatService->generatePostStreamSuggestions($conversation, $fullContent);

            $aiMessage = Message::create([
                'conversation_id'  => $conversation->id,
                'role'             => 'ai',
                'content'          => $fullContent,
                'smart_suggestions'=> $suggestions,
                'metadata'         => ['sources' => $chatService->lastSources],
            ]);

            $conversation->refresh();
            $conversation->update([
                'message_count'   => $conversation->messages()->count(),
                'last_message_at' => now(),
                'is_read'         => false,
            ]);
            $conversation->increment('ai_message_count');

            if (! $widget->isPlaygroundRequest()) $user->incrementUsage('messages');
            $widget->increment('total_messages');

            // Update sentiment
            $sentimentSvc->updateConversationSentiment($conversation);

            // Lead qualification
            if ($conversation->message_count % 5 === 0 && $widget->lead_qualification_enabled) {
                try {
                    $lead = $leadSvc->qualifyFromConversation($conversation);
                    if ($lead) {
                        $notificationSvc->notifyLeadQualified($widget, $lead);
                    }
                } catch (\Exception $e) {
                    Log::warning('Lead qualification failed in stream: ' . $e->getMessage());
                }
            }

            echo 'data: ' . json_encode([
                'done'              => true,
                'message_uuid'      => $aiMessage->uuid,
                'smart_suggestions' => $suggestions,
                'sources'           => $chatService->lastSources,
                'cards'             => \App\Support\Engagement::cardsFor($widget, $validated['content'] . ' ' . $fullContent),
            ]) . "\n\n";

            if (ob_get_level() > 0) ob_flush();
            flush();
        }, 200, [
            'Content-Type'      => 'text/event-stream',
            'Cache-Control'     => 'no-cache',
            'Connection'        => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    /**
     * Poll for new agent/system messages since a given message UUID.
     * Used by the widget to receive agent replies without WebSockets.
     */
    public function pollMessages(Request $request, string $widgetUuid, string $conversationUuid): JsonResponse
    {
        $widget       = Widget::forPublicApi($widgetUuid);
        $conversation = $this->conversationForWidget($widget, $conversationUuid);

        $afterUuid = $request->query('after');

        $query = $conversation->messages()
            ->whereIn('role', ['agent', 'system'])
            ->orderBy('id');

        if ($afterUuid) {
            $afterId = Message::where('uuid', $afterUuid)
                ->where('conversation_id', $conversation->id)
                ->value('id');
            if ($afterId) {
                $query->where('id', '>', $afterId);
            }
        }

        $messages = $query->get()->map(fn ($m) => [
            'uuid'              => $m->uuid,
            'role'              => $m->role,
            'content'           => $m->content,
            'smart_suggestions' => $m->smart_suggestions ?? [],
            'created_at'        => $m->created_at->toISOString(),
        ]);

        return response()->json([
            'messages'            => $messages,
            'conversation_status' => $conversation->status,
        ]);
    }

    /**
     * Visitor requests a human agent.
     */
    public function requestHumanHandoff(Request $request, string $widgetUuid, string $conversationUuid)
    {
        $widget = Widget::forPublicApi($widgetUuid);

        if (!$widget->show_handoff_button || ! $widget->user->hasFeature('live_chat')) {
            return response()->json(['error' => 'Handoff is disabled for this widget.'], 403);
        }

        $conversation = $this->conversationForWidget($widget, $conversationUuid);

        if (in_array($conversation->status, ['resolved', 'spam'], true)) {
            return response()->json(['error' => 'This conversation cannot be updated.'], 409);
        }

        Message::create([
            'conversation_id' => $conversation->id,
            'role'            => 'system',
            'content'         => 'Visitor requested to speak with a human agent.',
            'content_type'    => 'system',
        ]);

        $ack = Message::create([
            'conversation_id' => $conversation->id,
            'role'            => 'ai',
            'content'         => $widget->handoff_acknowledgment,
        ]);

        $conversation->refresh();
        $conversation->update([
            'status'          => 'open',
            'message_count'   => $conversation->messages()->count(),
            'last_message_at' => now(),
            'is_read'         => false,
        ]);

        $conversation->load('visitor');

        // Notify via Slack/Discord/Email
        $this->notificationService->notifyHandoffRequested($widget, $conversation);

        $this->webhooks->dispatch($widget, 'conversation.handoff', [
            'conversation_uuid' => $conversation->uuid,
            'visitor'           => [
                'email' => $conversation->visitor?->email,
                'name'  => $conversation->visitor?->name,
            ],
            'page_url' => $conversation->current_page_url,
        ]);

        return response()->json([
            'status'  => 'open',
            'message' => [
                'uuid'       => $ack->uuid,
                'role'       => 'ai',
                'content'    => $ack->content,
                'created_at' => $ack->created_at->toISOString(),
            ],
        ]);
    }

    /**
     * Submit CSAT feedback.
     */
    public function submitFeedback(Request $request, string $widgetUuid, string $conversationUuid)
    {
        $widget = Widget::forPublicApi($widgetUuid);

        if (!$widget->csat_prompt_enabled) {
            return response()->json(['error' => 'Feedback is disabled for this widget.'], 403);
        }

        $conversation = $this->conversationForWidget($widget, $conversationUuid);

        if ($conversation->csat_score !== null) {
            return response()->json(['error' => 'Feedback already submitted.'], 409);
        }

        $validated = $request->validate([
            'rating'  => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:500',
        ]);

        $conversation->update([
            'csat_score'        => $validated['rating'],
            'csat_comment'      => $validated['comment'] ?? null,
            'csat_submitted_at' => now(),
        ]);

        $this->webhooks->dispatch($widget, 'conversation.feedback', [
            'conversation_uuid' => $conversation->uuid,
            'rating'            => $validated['rating'],
            'comment'           => $validated['comment'] ?? null,
        ]);

        return response()->json(['ok' => true]);
    }

    /**
     * Submit an offline message (visitor email + message when no live support).
     */
    public function submitOfflineMessage(Request $request, string $widgetUuid)
    {
        $widget = Widget::forPublicApi($widgetUuid);

        // Always open when the AI is unavailable (plan limit or inactive subscription), so no visitor is lost.
        if (!$widget->offline_form_enabled && $widget->user->canUse('messages')) {
            return response()->json(['error' => 'Offline messages are disabled.'], 403);
        }

        $validated = $request->validate([
            'name'        => 'nullable|string|max:255',
            'email'       => 'required|email|max:255',
            'message'     => 'required|string|max:2000',
            'page_url'    => 'nullable|url',
            'conversation_uuid' => 'nullable|string|max:36',
        ]);

        $conversationId = null;
        if (!empty($validated['conversation_uuid'])) {
            $conv = Conversation::where('uuid', $validated['conversation_uuid'])
                ->where('widget_id', $widget->id)
                ->first();
            $conversationId = $conv?->id;
        }

        $offlineMessage = OfflineMessage::create([
            'widget_id'       => $widget->id,
            'conversation_id' => $conversationId,
            'name'            => $validated['name'] ?? null,
            'email'           => $validated['email'],
            'message'         => $validated['message'],
            'page_url'        => $validated['page_url'] ?? null,
        ]);

        // Notify widget owner
        $this->notificationService->notifyOfflineMessage($widget, $offlineMessage);

        return response()->json(['ok' => true, 'message' => "We've received your message and will get back to you soon!"]);
    }

    /**
     * Record a proactive message impression or click.
     */
    public function trackProactive(Request $request, string $widgetUuid)
    {
        $widget    = Widget::forPublicApi($widgetUuid);
        $validated = $request->validate([
            'proactive_id' => 'required|integer',
            'event'        => 'required|in:shown,clicked',
        ]);

        $pm = ProactiveMessage::where('id', $validated['proactive_id'])
            ->where('widget_id', $widget->id)
            ->first();

        if ($pm) {
            if ($validated['event'] === 'shown') {
                $this->proactiveService->recordShown($pm->id);
            } else {
                $this->proactiveService->recordClicked($pm->id);
            }
        }

        return response()->json(['ok' => true]);
    }

    /**
     * Add emoji reaction to a message.
     */
    public function reactToMessage(Request $request, string $widgetUuid, string $conversationUuid, string $messageUuid)
    {
        $widget       = Widget::forPublicApi($widgetUuid);
        $conversation = $this->conversationForWidget($widget, $conversationUuid);
        $validated    = $request->validate(['emoji' => 'required|string|max:8']);

        $message = Message::where('uuid', $messageUuid)
            ->where('conversation_id', $conversation->id)
            ->firstOrFail();

        $reactions   = $message->reactions ?? [];
        $emoji       = $validated['emoji'];
        $reactions[$emoji] = ($reactions[$emoji] ?? 0) + 1;

        $message->update(['reactions' => $reactions]);

        return response()->json(['ok' => true, 'reactions' => $reactions]);
    }

    protected function conversationForWidget(Widget $widget, string $conversationUuid): Conversation
    {
        return Conversation::query()
            ->where('uuid', $conversationUuid)
            ->where('widget_id', $widget->id)
            ->firstOrFail();
    }
}
