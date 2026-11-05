<?php

namespace App\Services\Channels;

use App\Models\ChannelConfiguration;
use App\Models\Conversation;
use Illuminate\Support\Facades\Http;

class InstagramChannelService extends BaseChannelService
{
    protected string $channelType = 'instagram';

    public function sendMessage(ChannelConfiguration $channel, Conversation $conversation, string $message, array $options = []): array
    {
        try {
            if ($channel->hasReachedDailyLimit()) {
                return ['success' => false, 'error' => 'Daily message limit reached'];
            }

            $credentials = $channel->credentials;
            $accessToken = $credentials['access_token'] ?? null;
            $senderId = $options['recipient_id'] ?? null;

            if (!$accessToken || !$senderId) {
                return ['success' => false, 'error' => 'Instagram credentials incomplete'];
            }

            $response = Http::asForm()->post("https://graph.instagram.com/v17.0/me/messages", [
                'recipient' => ['id' => $senderId],
                'message' => ['text' => $message],
                'access_token' => $accessToken,
            ]);

            if ($response->successful()) {
                $externalId = $response->json()['message_id'] ?? null;
                $this->createChannelMessage($channel, $conversation, 'outbound', $message, ['external_message_id' => $externalId]);
                $channel->increment('total_messages_sent');
                $channel->incrementDailyCount();
                return ['success' => true, 'external_id' => $externalId];
            }

            return ['success' => false, 'error' => 'Failed to send Instagram message'];
        } catch (\Exception $e) {
            $this->logError('sendMessage', $channel, $e);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function receiveMessage(array $payload): void {}
    public function testConnection(ChannelConfiguration $channel): bool { return true; }
    public function getStatus(ChannelConfiguration $channel): array { return ['channel_type' => 'instagram']; }
    public function syncContact(ChannelConfiguration $channel, string $contactId): void {}
}
