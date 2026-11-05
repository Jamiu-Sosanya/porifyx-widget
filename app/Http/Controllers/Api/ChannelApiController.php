<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChannelConfiguration;
use App\Models\ChannelContact;
use App\Models\Widget;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class ChannelApiController extends Controller
{
    public function getChannelTypes(): JsonResponse
    {
        $channelTypes = [
            'whatsapp'  => ['name' => 'WhatsApp',          'description' => 'WhatsApp Business API',        'required_credentials' => ['access_token', 'phone_number_id', 'business_account_id'], 'webhook_required' => true],
            'telegram'  => ['name' => 'Telegram',           'description' => 'Telegram Bot',                 'required_credentials' => ['bot_token'],                                             'webhook_required' => true],
            'facebook'  => ['name' => 'Facebook Messenger', 'description' => 'Facebook Messenger',           'required_credentials' => ['page_access_token', 'page_id', 'app_secret'],            'webhook_required' => true],
            'instagram' => ['name' => 'Instagram',          'description' => 'Instagram Direct Messages',    'required_credentials' => ['access_token', 'instagram_account_id'],                  'webhook_required' => true],
            'slack'     => ['name' => 'Slack',              'description' => 'Slack Workspace',              'required_credentials' => ['bot_token', 'channel_id'],                               'webhook_required' => true],
            'discord'   => ['name' => 'Discord',            'description' => 'Discord Server',               'required_credentials' => ['bot_token', 'guild_id', 'channel_id'],                  'webhook_required' => true],
            'sms'       => ['name' => 'SMS (Twilio)',        'description' => 'SMS via Twilio',               'required_credentials' => ['account_sid', 'auth_token', 'from_number'],             'webhook_required' => true],
            'twitter'   => ['name' => 'Twitter / X',        'description' => 'Twitter Direct Messages',      'required_credentials' => ['api_key', 'api_secret', 'access_token', 'access_token_secret'], 'webhook_required' => true],
            'email'     => ['name' => 'Email',              'description' => 'Email integration',            'required_credentials' => ['sender_email', 'sender_name'],                          'webhook_required' => false],
        ];

        return response()->json(['success' => true, 'data' => $channelTypes]);
    }

    public function listChannels(int $widgetId): JsonResponse
    {
        $widget = Widget::findOrFail($widgetId);

        if ($widget->user_id !== auth()->user()?->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $channels = $widget->channelConfigurations()
            ->withCount(['messages'])
            ->orderBy('created_at')
            ->get()
            ->map(fn ($ch) => $this->formatChannel($ch));

        return response()->json(['success' => true, 'data' => $channels]);
    }

    public function configureChannel(Request $request, int $widgetId): JsonResponse
    {
        $request->validate([
            'channel_type' => ['required', 'string', Rule::in(['email', 'whatsapp', 'facebook', 'sms', 'instagram', 'twitter', 'telegram', 'discord', 'slack'])],
            'account_name' => 'required|string|max:255',
            'credentials'  => 'required|array',
            'is_enabled'   => 'boolean',
            'settings'     => 'array',
        ]);

        $widget = Widget::with('user.subscription.plan')->findOrFail($widgetId);

        if ($widget->user_id !== auth()->user()?->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        // Plan channel limit check
        $plan = $widget->user->currentPlan();
        $channelLimit = $plan ? (($plan->features['channels_per_widget'] ?? null) ?? -1) : 1;
        $currentCount = $widget->channelConfigurations()->count();

        if ($channelLimit !== -1 && $currentCount >= $channelLimit) {
            return response()->json(['success' => false, 'message' => "Channel limit ({$channelLimit}) reached for your plan. Please upgrade."], 403);
        }

        // Prevent duplicate channel type per widget
        if ($widget->channelConfigurations()->where('channel_type', $request->channel_type)->exists()) {
            return response()->json(['success' => false, 'message' => 'This channel type is already configured for this widget.'], 422);
        }

        try {
            $channel = ChannelConfiguration::create([
                'widget_id'    => $widgetId,
                'channel_type' => $request->channel_type,
                'account_name' => $request->account_name,
                'credentials'  => $request->credentials, // model cast handles encryption
                'is_enabled'   => $request->boolean('is_enabled', true),
                'settings'     => $request->input('settings', []),
            ]);

            return response()->json(['success' => true, 'data' => $this->formatChannel($channel), 'message' => 'Channel connected successfully.'], 201);
        } catch (\Throwable $e) {
            Log::error('Channel configuration failed', ['widget_id' => $widgetId, 'type' => $request->channel_type, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed to configure channel.'], 500);
        }
    }

    public function updateChannel(Request $request, int $channelId): JsonResponse
    {
        $channel = ChannelConfiguration::findOrFail($channelId);

        if ($channel->widget->user_id !== auth()->user()?->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'account_name' => 'sometimes|string|max:255',
            'credentials'  => 'sometimes|array',
            'is_enabled'   => 'sometimes|boolean',
            'settings'     => 'sometimes|array',
        ]);

        try {
            $data = array_filter([
                'account_name' => $request->input('account_name'),
                'settings'     => $request->input('settings'),
            ], fn ($v) => $v !== null);

            if ($request->has('is_enabled')) {
                $data['is_enabled'] = $request->boolean('is_enabled');
            }
            if ($request->has('credentials')) {
                $data['credentials'] = $request->input('credentials');
            }

            $channel->update($data);

            return response()->json(['success' => true, 'data' => $this->formatChannel($channel->fresh()), 'message' => 'Channel updated.']);
        } catch (\Throwable $e) {
            Log::error('Channel update failed', ['channel_id' => $channelId, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed to update channel.'], 500);
        }
    }

    public function deleteChannel(int $channelId): JsonResponse
    {
        $channel = ChannelConfiguration::findOrFail($channelId);

        if ($channel->widget->user_id !== auth()->user()?->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        try {
            $channel->delete();
            return response()->json(['success' => true, 'message' => 'Channel disconnected.']);
        } catch (\Throwable $e) {
            Log::error('Channel deletion failed', ['channel_id' => $channelId, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed to disconnect channel.'], 500);
        }
    }

    public function testChannel(int $channelId): JsonResponse
    {
        $channel = ChannelConfiguration::findOrFail($channelId);

        if ($channel->widget->user_id !== auth()->user()?->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        try {
            $service = $channel->getChannelService();
            $result  = $service->testConnection($channel);

            return response()->json(['success' => $result['success'] ?? false, 'message' => $result['message'] ?? 'Test complete.', 'data' => $result]);
        } catch (\Throwable $e) {
            Log::error('Channel test failed', ['channel_id' => $channelId, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Connection test failed: ' . $e->getMessage()], 500);
        }
    }

    public function getChannelStatus(int $channelId): JsonResponse
    {
        $channel = ChannelConfiguration::findOrFail($channelId);

        if ($channel->widget->user_id !== auth()->user()?->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        return response()->json(['success' => true, 'data' => $this->formatChannel($channel)]);
    }

    public function getChannelMessages(Request $request, int $channelId): JsonResponse
    {
        $channel = ChannelConfiguration::findOrFail($channelId);

        if ($channel->widget->user_id !== auth()->user()?->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $messages = $channel->messages()
            ->with('conversation')
            ->orderBy('created_at', 'desc')
            ->paginate($request->integer('per_page', 50));

        return response()->json(['success' => true, 'data' => $messages]);
    }

    public function sendChannelMessage(Request $request, int $channelId): JsonResponse
    {
        $request->validate([
            'contact_id'   => 'required|exists:channel_contacts,id',
            'message'      => 'required|string',
            'message_type' => 'string|in:text,image,file',
        ]);

        $channel = ChannelConfiguration::findOrFail($channelId);

        if ($channel->widget->user_id !== auth()->user()?->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $contact = ChannelContact::findOrFail($request->contact_id);

        try {
            $service = $channel->getChannelService();
            $result  = $service->sendMessage($channel, $contact, $request->message);

            return response()->json(['success' => true, 'data' => $result, 'message' => 'Message sent.']);
        } catch (\Throwable $e) {
            Log::error('Channel message send failed', ['channel_id' => $channelId, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed to send message.'], 500);
        }
    }

    public function getChannelContacts(int $channelId): JsonResponse
    {
        $channel = ChannelConfiguration::findOrFail($channelId);

        if ($channel->widget->user_id !== auth()->user()?->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $contacts = $channel->contacts()->orderBy('last_message_at', 'desc')->get();

        return response()->json(['success' => true, 'data' => $contacts]);
    }

    // ── Webhook Handlers ──────────────────────────────────────────────────────

    public function handleWhatsAppWebhook(Request $request): JsonResponse
    {
        // WhatsApp GET verification challenge
        if ($request->isMethod('GET')) {
            $mode      = $request->query('hub_mode');
            $challenge = $request->query('hub_challenge');
            $token     = $request->query('hub_verify_token');

            $channel = ChannelConfiguration::where('channel_type', 'whatsapp')
                ->where('verification_token', $token)->first();

            if ($mode === 'subscribe' && $channel) {
                return response()->json((int) $challenge);
            }
            return response()->json(['error' => 'Verification failed'], 403);
        }

        return $this->handleWebhook($request, 'whatsapp');
    }

    public function handleFacebookWebhook(Request $request): JsonResponse
    {
        if ($request->isMethod('GET')) {
            $token   = $request->query('hub_verify_token');
            $channel = ChannelConfiguration::where('channel_type', 'facebook')
                ->where('verification_token', $token)->first();

            if ($channel) {
                return response($request->query('hub_challenge'));
            }
            return response()->json(['error' => 'Verification failed'], 403);
        }

        return $this->handleWebhook($request, 'facebook');
    }

    public function handleInstagramWebhook(Request $r): JsonResponse { return $this->handleWebhook($r, 'instagram'); }
    public function handleTelegramWebhook(Request $r): JsonResponse  { return $this->handleWebhook($r, 'telegram'); }
    public function handleDiscordWebhook(Request $r): JsonResponse   { return $this->handleWebhook($r, 'discord'); }
    public function handleSlackWebhook(Request $r): JsonResponse     { return $this->handleWebhook($r, 'slack'); }
    public function handleTwitterWebhook(Request $r): JsonResponse   { return $this->handleWebhook($r, 'twitter'); }
    public function handleEmailWebhook(Request $r): JsonResponse     { return $this->handleWebhook($r, 'email'); }
    public function handleSmsWebhook(Request $r): JsonResponse       { return $this->handleWebhook($r, 'sms'); }

    private function handleWebhook(Request $request, string $channelType): JsonResponse
    {
        try {
            $channel = $this->resolveChannelFromPayload($request, $channelType);

            if (! $channel) {
                Log::warning("No channel found for {$channelType} webhook", ['payload' => $request->all()]);
                return response()->json(['status' => 'ok']); // Always 200 to avoid retries
            }

            $service = $channel->getChannelService();

            if (! $service->verifyWebhookSignature($request)) {
                Log::warning("Invalid webhook signature for {$channelType}", ['channel_id' => $channel->id]);
                return response()->json(['error' => 'Invalid signature'], 401);
            }

            $service->receiveMessage($channel, $request->all());

            return response()->json(['status' => 'ok']);
        } catch (\Throwable $e) {
            Log::error("{$channelType} webhook failed", ['error' => $e->getMessage()]);
            return response()->json(['status' => 'ok']); // 200 to prevent retry storms
        }
    }

    private function resolveChannelFromPayload(Request $request, string $channelType): ?ChannelConfiguration
    {
        return match ($channelType) {
            'whatsapp' => $this->findByPhoneNumberId($request),
            'facebook', 'instagram' => $this->findByPageId($request, $channelType),
            'telegram' => $this->findByTelegramBotToken($request),
            'slack'    => $this->findBySlackTeamId($request),
            'discord'  => $this->findByDiscordGuildId($request),
            'sms'      => $this->findBySmsToNumber($request),
            default    => ChannelConfiguration::where('channel_type', $channelType)->where('is_enabled', true)->first(),
        };
    }

    private function findByPhoneNumberId(Request $request): ?ChannelConfiguration
    {
        $phoneId = data_get($request->all(), 'entry.0.changes.0.value.metadata.phone_number_id');
        if (! $phoneId) return null;

        return ChannelConfiguration::where('channel_type', 'whatsapp')->where('is_enabled', true)->get()
            ->first(fn ($ch) => ($ch->credentials['phone_number_id'] ?? null) === (string) $phoneId);
    }

    private function findByPageId(Request $request, string $type): ?ChannelConfiguration
    {
        $pageId = data_get($request->all(), 'entry.0.id');
        if (! $pageId) return null;

        return ChannelConfiguration::where('channel_type', $type)->where('is_enabled', true)->get()
            ->first(fn ($ch) => ($ch->credentials['page_id'] ?? $ch->credentials['instagram_account_id'] ?? null) === (string) $pageId);
    }

    private function findByTelegramBotToken(Request $request): ?ChannelConfiguration
    {
        // Telegram uses per-bot webhook URLs, so we match by bot_token in credentials
        $botId = data_get($request->all(), 'message.via_bot.id')
            ?? data_get($request->all(), 'callback_query.from.id');

        return ChannelConfiguration::where('channel_type', 'telegram')->where('is_enabled', true)
            ->get()->first(); // Single bot scenario; expand with unique webhook paths if needed
    }

    private function findBySlackTeamId(Request $request): ?ChannelConfiguration
    {
        $teamId = $request->input('team_id') ?? data_get($request->all(), 'event.team');
        if (! $teamId) return null;

        return ChannelConfiguration::where('channel_type', 'slack')->where('is_enabled', true)->get()
            ->first(fn ($ch) => ($ch->credentials['team_id'] ?? null) === (string) $teamId);
    }

    private function findByDiscordGuildId(Request $request): ?ChannelConfiguration
    {
        $guildId = $request->input('guild_id');
        if (! $guildId) return null;

        return ChannelConfiguration::where('channel_type', 'discord')->where('is_enabled', true)->get()
            ->first(fn ($ch) => ($ch->credentials['guild_id'] ?? null) === (string) $guildId);
    }

    private function findBySmsToNumber(Request $request): ?ChannelConfiguration
    {
        $to = $request->input('To');
        if (! $to) return null;

        return ChannelConfiguration::where('channel_type', 'sms')->where('is_enabled', true)->get()
            ->first(fn ($ch) => ($ch->credentials['from_number'] ?? null) === (string) $to);
    }

    private function formatChannel(ChannelConfiguration $ch): array
    {
        return [
            'id'                    => $ch->id,
            'channel_type'          => $ch->channel_type,
            'account_name'          => $ch->account_name,
            'is_enabled'            => $ch->is_enabled,
            'is_default'            => $ch->is_default,
            'settings'              => $ch->settings,
            'rate_limit_per_minute' => $ch->rate_limit_per_minute,
            'daily_message_limit'   => $ch->daily_message_limit,
            'messages_today'        => $ch->messages_today,
            'total_messages_sent'   => $ch->total_messages_sent,
            'total_messages_received' => $ch->total_messages_received,
            'last_message_at'       => $ch->last_message_at?->toIso8601String(),
            'webhook_tested_at'     => $ch->webhook_tested_at?->toIso8601String(),
            'account_id'            => $ch->account_id,
            'account_description'   => $ch->account_description,
            'created_at'            => $ch->created_at->toIso8601String(),
        ];
    }
}
