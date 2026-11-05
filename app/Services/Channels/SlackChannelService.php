<?php

namespace App\Services\Channels;

use App\Models\ChannelConfiguration;
use App\Models\Conversation;
use Illuminate\Support\Facades\Http;

class SlackChannelService extends BaseChannelService
{
    protected string $channelType = 'slack';

    public function sendMessage(ChannelConfiguration $channel, Conversation $conversation, string $message, array $options = []): array
    {
        try {
            $credentials = $channel->credentials;
            $botToken = $credentials['bot_token'] ?? null;
            $channelId = $options['recipient_id'] ?? $credentials['channel_id'] ?? null;

            if (!$botToken || !$channelId) {
                return ['success' => false, 'error' => 'Slack credentials incomplete'];
            }

            $response = Http::withToken($botToken)
                ->post('https://slack.com/api/chat.postMessage', [
                    'channel' => $channelId,
                    'text' => $message,
                ]);

            if ($response->successful() && $response->json()['ok']) {
                $msgData = $response->json();
                $externalId = $msgData['ts'] ?? null;
                $this->createChannelMessage($channel, $conversation, 'outbound', $message, ['external_message_id' => $externalId]);
                $channel->increment('total_messages_sent');
                return ['success' => true, 'external_id' => $externalId];
            }

            return ['success' => false, 'error' => 'Failed to send Slack message'];
        } catch (\Exception $e) {
            $this->logError('sendMessage', $channel, $e);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function receiveMessage(array $payload): void
    {
        try {
            if (($payload['type'] ?? null) === 'url_verification') {
                return; // Slack verification challenge
            }

            $event = $payload['event'] ?? null;
            if (!$event || ($event['bot_id'] ?? null)) return;

            $userId = $event['user'] ?? null;
            $channelId = $event['channel'] ?? null;
            $content = $event['text'] ?? '';
            $messageId = $event['ts'] ?? null;

            if (!$userId || !$channelId || !$content) return;

            $channel = ChannelConfiguration::where('channel_type', 'slack')
                ->whereJsonContains('credentials->channel_id', $channelId)
                ->first();

            if (!$channel) return;

            $contactRecord = $this->getOrCreateContact($channel, $userId);

            $conversation = Conversation::firstOrCreate(
                ['widget_id' => $channel->widget_id, 'visitor_id' => $contactRecord->visitor_id],
                ['user_id' => $channel->widget->user_id, 'status' => 'ai_active']
            );

            $this->createChannelMessage($channel, $conversation, 'inbound', $content, ['external_message_id' => $messageId]);
            $channel->increment('total_messages_received');

            $aiResponse = $this->generateAiResponse($conversation, $content, $channel);
            $this->sendMessage($channel, $conversation, $aiResponse, ['recipient_id' => $channelId]);
            $contactRecord->updateLastMessage();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Slack error: ' . $e->getMessage());
        }
    }

    public function verifyWebhookSignature(array $payload, string $signature): bool
    {
        $signingSecret = config('services.slack.signing_secret') ?? '';
        $timestamp = $payload['headers']['X-Slack-Request-Timestamp'] ?? '';
        $body = $payload['body'] ?? '';

        $baseString = "v0:{$timestamp}:{$body}";
        $computed = 'v0=' . hash_hmac('sha256', $baseString, $signingSecret);

        return hash_equals($computed, $signature);
    }

    public function testConnection(ChannelConfiguration $channel): bool
    {
        try {
            $credentials = $channel->credentials;
            $botToken = $credentials['bot_token'] ?? null;

            if (!$botToken) return false;

            $response = Http::withToken($botToken)->post('https://slack.com/api/auth.test');
            return $response->successful() && $response->json()['ok'];
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getStatus(ChannelConfiguration $channel): array
    {
        return ['channel_type' => 'slack', 'is_enabled' => $channel->is_enabled];
    }

    public function syncContact(ChannelConfiguration $channel, string $contactId): void {}
}
