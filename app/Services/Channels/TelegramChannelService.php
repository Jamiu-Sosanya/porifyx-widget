<?php

namespace App\Services\Channels;

use App\Models\ChannelConfiguration;
use App\Models\Conversation;
use Illuminate\Support\Facades\Http;

class TelegramChannelService extends BaseChannelService
{
    protected string $channelType = 'telegram';

    public function sendMessage(ChannelConfiguration $channel, Conversation $conversation, string $message, array $options = []): array
    {
        try {
            $credentials = $channel->credentials;
            $botToken = $credentials['bot_token'] ?? null;
            $chatId = $options['recipient_id'] ?? null;

            if (!$botToken || !$chatId) {
                return ['success' => false, 'error' => 'Telegram credentials incomplete'];
            }

            $response = Http::post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'HTML',
            ]);

            if ($response->successful()) {
                $msgData = $response->json();
                $externalId = $msgData['result']['message_id'] ?? null;
                $this->createChannelMessage($channel, $conversation, 'outbound', $message, ['external_message_id' => $externalId]);
                $channel->increment('total_messages_sent');
                $channel->incrementDailyCount();
                return ['success' => true, 'external_id' => $externalId];
            }

            return ['success' => false, 'error' => 'Failed to send Telegram message'];
        } catch (\Exception $e) {
            $this->logError('sendMessage', $channel, $e);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function receiveMessage(array $payload): void
    {
        try {
            $message = $payload['message'] ?? null;
            if (!$message) return;

            $chatId = $message['chat']['id'];
            $userId = $message['from']['id'];
            $content = $message['text'] ?? '';
            $messageId = $message['message_id'];

            $channel = ChannelConfiguration::where('channel_type', 'telegram')
                ->whereJsonContains('credentials->chat_id', $chatId)
                ->first();

            if (!$channel) return;

            $contactRecord = $this->getOrCreateContact($channel, (string)$userId);

            $conversation = Conversation::firstOrCreate(
                ['widget_id' => $channel->widget_id, 'visitor_id' => $contactRecord->visitor_id],
                ['user_id' => $channel->widget->user_id, 'status' => 'ai_active']
            );

            $this->createChannelMessage($channel, $conversation, 'inbound', $content, ['external_message_id' => $messageId]);
            $channel->increment('total_messages_received');

            $aiResponse = $this->generateAiResponse($conversation, $content, $channel);
            $this->sendMessage($channel, $conversation, $aiResponse, ['recipient_id' => $chatId]);
            $contactRecord->updateLastMessage();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Telegram error: ' . $e->getMessage());
        }
    }

    public function testConnection(ChannelConfiguration $channel): bool
    {
        try {
            $credentials = $channel->credentials;
            $botToken = $credentials['bot_token'] ?? null;

            if (!$botToken) return false;

            $response = Http::get("https://api.telegram.org/bot{$botToken}/getMe");
            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getStatus(ChannelConfiguration $channel): array
    {
        return ['channel_type' => 'telegram', 'is_enabled' => $channel->is_enabled];
    }

    public function syncContact(ChannelConfiguration $channel, string $contactId): void {}
}
