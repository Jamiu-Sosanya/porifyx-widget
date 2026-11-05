<?php

namespace App\Services\Channels;

use App\Models\ChannelConfiguration;
use App\Models\Conversation;
use Illuminate\Support\Facades\Http;

class DiscordChannelService extends BaseChannelService
{
    protected string $channelType = 'discord';

    public function sendMessage(ChannelConfiguration $channel, Conversation $conversation, string $message, array $options = []): array
    {
        try {
            $credentials = $channel->credentials;
            $botToken = $credentials['bot_token'] ?? null;
            $channelId = $options['recipient_id'] ?? $credentials['channel_id'] ?? null;

            if (!$botToken || !$channelId) {
                return ['success' => false, 'error' => 'Discord credentials incomplete'];
            }

            $response = Http::withHeaders(['Authorization' => "Bot {$botToken}"])
                ->post("https://discord.com/api/v10/channels/{$channelId}/messages", [
                    'content' => substr($message, 0, 2000),
                ]);

            if ($response->successful()) {
                $msgData = $response->json();
                $externalId = $msgData['id'] ?? null;
                $this->createChannelMessage($channel, $conversation, 'outbound', $message, ['external_message_id' => $externalId]);
                $channel->increment('total_messages_sent');
                return ['success' => true, 'external_id' => $externalId];
            }

            return ['success' => false, 'error' => 'Failed to send Discord message'];
        } catch (\Exception $e) {
            $this->logError('sendMessage', $channel, $e);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function receiveMessage(array $payload): void
    {
        try {
            if ($payload['type'] === 1) return; // Ping

            $author = $payload['author'] ?? null;
            if ($author['bot'] ?? false) return;

            $channelId = $payload['channel_id'];
            $content = $payload['content'] ?? '';
            $messageId = $payload['id'];
            $userId = $author['id'] ?? null;

            $channel = ChannelConfiguration::where('channel_type', 'discord')
                ->whereJsonContains('credentials->channel_id', $channelId)
                ->first();

            if (!$channel) return;

            $contactRecord = $this->getOrCreateContact($channel, $userId, ['name' => $author['username'] ?? 'Unknown']);

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
            \Illuminate\Support\Facades\Log::error('Discord error: ' . $e->getMessage());
        }
    }

    public function testConnection(ChannelConfiguration $channel): bool
    {
        try {
            $credentials = $channel->credentials;
            $botToken = $credentials['bot_token'] ?? null;

            if (!$botToken) return false;

            $response = Http::withHeaders(['Authorization' => "Bot {$botToken}"])
                ->get('https://discord.com/api/v10/users/@me');

            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getStatus(ChannelConfiguration $channel): array
    {
        return ['channel_type' => 'discord', 'is_enabled' => $channel->is_enabled];
    }

    public function syncContact(ChannelConfiguration $channel, string $contactId): void {}
}
