<?php

namespace App\Services\Channels;

use App\Models\ChannelConfiguration;
use App\Models\Conversation;
use Illuminate\Support\Facades\Http;

class TwitterChannelService extends BaseChannelService
{
    protected string $channelType = 'twitter';

    public function sendMessage(ChannelConfiguration $channel, Conversation $conversation, string $message, array $options = []): array
    {
        try {
            $credentials = $channel->credentials;
            $apiKey = $credentials['api_key'] ?? null;
            $apiSecret = $credentials['api_secret'] ?? null;
            $recursiveId = $options['recipient_id'] ?? null;

            if (!$apiKey || !$apiSecret || !$recursiveId) {
                return ['success' => false, 'error' => 'Twitter credentials incomplete'];
            }

            // Twitter API v2 implementation
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->getBearerToken($channel),
                'Content-Type' => 'application/json',
            ])->post('https://api.twitter.com/2/dm_conversations/with/:participant_id/dm_events', [
                'text' => substr($message, 0, 10000),
            ]);

            if ($response->successful()) {
                $externalId = $response->json()['data']['id'] ?? null;
                $this->createChannelMessage($channel, $conversation, 'outbound', $message, ['external_message_id' => $externalId]);
                $channel->increment('total_messages_sent');
                return ['success' => true, 'external_id' => $externalId];
            }

            return ['success' => false, 'error' => 'Failed to send Twitter DM'];
        } catch (\Exception $e) {
            $this->logError('sendMessage', $channel, $e);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    protected function getBearerToken(ChannelConfiguration $channel): string
    {
        return $channel->credentials['bearer_token'] ?? '';
    }

    public function receiveMessage(array $payload): void {}
    public function testConnection(ChannelConfiguration $channel): bool { return true; }
    public function getStatus(ChannelConfiguration $channel): array { return ['channel_type' => 'twitter']; }
    public function syncContact(ChannelConfiguration $channel, string $contactId): void {}
}
