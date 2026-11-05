<?php

namespace App\Services\Channels;

use App\Models\ChannelConfiguration;
use App\Models\Conversation;
use Twilio\Rest\Client;

class SmsChannelService extends BaseChannelService
{
    protected string $channelType = 'sms';

    public function sendMessage(
        ChannelConfiguration $channel,
        Conversation $conversation,
        string $message,
        array $options = []
    ): array {
        try {
            if ($channel->hasReachedDailyLimit()) {
                return ['success' => false, 'error' => 'Daily SMS limit reached'];
            }

            $phoneNumber = $options['recipient_phone'] ?? $conversation->visitor?->phone;
            if (!$phoneNumber) {
                return ['success' => false, 'error' => 'No phone number for recipient'];
            }

            $credentials = $channel->credentials;
            $accountSid = $credentials['account_sid'] ?? null;
            $authToken = $credentials['auth_token'] ?? null;
            $fromNumber = $credentials['from_number'] ?? null;

            if (!$accountSid || !$authToken || !$fromNumber) {
                return ['success' => false, 'error' => 'SMS credentials not configured'];
            }

            $client = new Client($accountSid, $authToken);
            $smsMessage = $client->messages->create($phoneNumber, [
                'from' => $fromNumber,
                'body' => substr($message, 0, 160), // SMS character limit
            ]);

            $channelMsg = $this->createChannelMessage(
                $channel,
                $conversation,
                'outbound',
                $message,
                ['external_message_id' => $smsMessage->sid]
            );

            $channel->increment('total_messages_sent');
            $channel->incrementDailyCount();
            $channel->update(['last_message_at' => now()]);

            return [
                'success' => true,
                'external_id' => $smsMessage->sid,
                'message_id' => $channelMsg->id,
            ];

        } catch (\Exception $e) {
            $this->logError('sendMessage', $channel, $e);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function receiveMessage(array $payload): void
    {
        try {
            $phoneNumber = $payload['From'] ?? null;
            $content = $payload['Body'] ?? null;
            $messageId = $payload['MessageSid'] ?? null;

            if (!$phoneNumber || !$content) return;

            $channel = ChannelConfiguration::where('channel_type', 'sms')
                ->whereJsonContains('credentials->from_number', ltrim($phoneNumber, '+'))
                ->first();

            if (!$channel) return;

            $contactRecord = $this->getOrCreateContact($channel, $phoneNumber, ['name' => $phoneNumber]);

            $conversation = Conversation::firstOrCreate(
                ['widget_id' => $channel->widget_id, 'visitor_id' => $contactRecord->visitor_id],
                ['user_id' => $channel->widget->user_id, 'status' => 'ai_active', 'last_message_at' => now()]
            );

            $this->createChannelMessage(
                $channel,
                $conversation,
                'inbound',
                $content,
                [
                    'sender_phone' => $phoneNumber,
                    'external_message_id' => $messageId,
                ]
            );

            $channel->increment('total_messages_received');

            $aiResponse = $this->generateAiResponse($conversation, $content, $channel);
            $this->sendMessage($channel, $conversation, $aiResponse, ['recipient_phone' => $phoneNumber]);

            $contactRecord->updateLastMessage();

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('SMS receive error: ' . $e->getMessage());
        }
    }

    public function verifyWebhookSignature(array $payload, string $signature): bool
    {
        $token = config('services.twilio.auth_token') ?? '';
        $baseUrl = config('app.url') . '/api/webhooks/sms';
        $computed = hash_hmac('sha1', $baseUrl . http_build_query($payload), $token, true);
        return base64_encode($computed) === $signature;
    }

    public function testConnection(ChannelConfiguration $channel): bool
    {
        try {
            $credentials = $channel->credentials;
            $accountSid = $credentials['account_sid'] ?? null;
            $authToken = $credentials['auth_token'] ?? null;

            if (!$accountSid || !$authToken) return false;

            $client = new Client($accountSid, $authToken);
            $account = $client->api->accounts->fetch();

            return $account->status === 'active';
        } catch (\Exception $e) {
            $this->logError('testConnection', $channel, $e);
            return false;
        }
    }

    public function getStatus(ChannelConfiguration $channel): array
    {
        $credentials = $channel->credentials;
        return [
            'channel_type' => 'sms',
            'is_enabled' => $channel->is_enabled,
            'from_number' => $credentials['from_number'] ?? null,
            'total_sent' => $channel->total_messages_sent,
            'total_received' => $channel->total_messages_received,
            'last_activity' => $channel->last_message_at?->diffForHumans(),
            'daily_used' => $channel->messages_today,
            'daily_limit' => $channel->daily_message_limit,
        ];
    }

    public function syncContact(ChannelConfiguration $channel, string $contactId): void
    {
        // SMS contacts are synced via webhooks
    }
}
