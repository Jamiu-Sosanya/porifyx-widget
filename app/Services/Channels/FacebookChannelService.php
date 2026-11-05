<?php

namespace App\Services\Channels;

use App\Models\ChannelConfiguration;
use App\Models\Conversation;
use Illuminate\Support\Facades\Http;

class FacebookChannelService extends BaseChannelService
{
    protected string $channelType = 'facebook';

    public function sendMessage(
        ChannelConfiguration $channel,
        Conversation $conversation,
        string $message,
        array $options = []
    ): array {
        try {
            if ($channel->hasReachedDailyLimit()) {
                return ['success' => false, 'error' => 'Daily message limit reached'];
            }

            $credentials = $channel->credentials;
            $pageAccessToken = $credentials['page_access_token'] ?? null;
            $pageId = $credentials['page_id'] ?? null;
            $senderId = $options['recipient_id'] ?? $options['sender_id'] ?? null;

            if (!$pageAccessToken || !$pageId || !$senderId) {
                return ['success' => false, 'error' => 'Facebook credentials incomplete'];
            }

            $response = Http::post("https://graph.facebook.com/v17.0/me/messages", [
                'recipient' => ['id' => $senderId],
                'messaging_type' => 'RESPONSE',
                'message' => ['text' => $message],
                'access_token' => $pageAccessToken,
            ]);

            if ($response->successful()) {
                $msgData = $response->json();
                $externalId = $msgData['message_id'] ?? null;

                $channelMsg = $this->createChannelMessage(
                    $channel,
                    $conversation,
                    'outbound',
                    $message,
                    ['external_message_id' => $externalId]
                );

                $channel->increment('total_messages_sent');
                $channel->incrementDailyCount();
                $channel->update(['last_message_at' => now()]);

                return ['success' => true, 'external_id' => $externalId, 'message_id' => $channelMsg->id];
            }

            return ['success' => false, 'error' => 'Failed to send Facebook message'];
        } catch (\Exception $e) {
            $this->logError('sendMessage', $channel, $e);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function receiveMessage(array $payload): void
    {
        try {
            $entry = $payload['entry'][0] ?? null;
            $messaging = $entry['messaging'][0] ?? null;

            if (!$messaging || !($messaging['message'] ?? null)) return;

            $senderId = $messaging['sender']['id'];
            $recipientId = $messaging['recipient']['id'];
            $message = $messaging['message'];
            $messageId = $message['mid'];
            $content = $message['text'] ?? '';

            $channel = ChannelConfiguration::where('channel_type', 'facebook')
                ->where('account_id', $recipientId)
                ->first();

            if (!$channel) return;

            $contactRecord = $this->getOrCreateContact($channel, $senderId);

            $conversation = Conversation::firstOrCreate(
                ['widget_id' => $channel->widget_id, 'visitor_id' => $contactRecord->visitor_id],
                ['user_id' => $channel->widget->user_id, 'status' => 'ai_active', 'last_message_at' => now()]
            );

            $this->createChannelMessage(
                $channel,
                $conversation,
                'inbound',
                $content,
                ['sender_channel_id' => $senderId, 'external_message_id' => $messageId]
            );

            $channel->increment('total_messages_received');

            $aiResponse = $this->generateAiResponse($conversation, $content, $channel);
            $this->sendMessage($channel, $conversation, $aiResponse, ['recipient_id' => $senderId]);

            $contactRecord->updateLastMessage();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Facebook receive error: ' . $e->getMessage());
        }
    }

    public function verifyWebhookSignature(array $payload, string $signature): bool
    {
        $token = config('services.facebook.webhook_token');
        return hash_hmac('sha256', json_encode($payload), $token) === $signature;
    }

    public function testConnection(ChannelConfiguration $channel): bool
    {
        try {
            $credentials = $channel->credentials;
            $pageAccessToken = $credentials['page_access_token'] ?? null;

            if (!$pageAccessToken) return false;

            $response = Http::get("https://graph.facebook.com/v17.0/me", [
                'access_token' => $pageAccessToken,
                'fields' => 'id,name',
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            $this->logError('testConnection', $channel, $e);
            return false;
        }
    }

    public function getStatus(ChannelConfiguration $channel): array
    {
        return [
            'channel_type' => 'facebook',
            'is_enabled' => $channel->is_enabled,
            'page_name' => $channel->account_name,
            'total_sent' => $channel->total_messages_sent,
            'total_received' => $channel->total_messages_received,
            'last_activity' => $channel->last_message_at?->diffForHumans(),
            'daily_used' => $channel->messages_today,
            'daily_limit' => $channel->daily_message_limit,
        ];
    }

    public function syncContact(ChannelConfiguration $channel, string $contactId): void
    {
        // Facebook contact sync
    }
}
