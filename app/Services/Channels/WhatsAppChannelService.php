<?php

namespace App\Services\Channels;

use App\Models\ChannelConfiguration;
use App\Models\Conversation;
use App\Models\Visitor;
use Illuminate\Support\Facades\Http;

class WhatsAppChannelService extends BaseChannelService
{
    protected string $channelType = 'whatsapp';

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

            $phoneNumber = $options['recipient_phone'] ?? $conversation->visitor?->phone;
            if (!$phoneNumber) {
                return ['success' => false, 'error' => 'No phone number for recipient'];
            }

            $credentials = $channel->credentials;
            $phoneNumberId = $credentials['phone_number_id'] ?? null;
            $accessToken = $credentials['access_token'] ?? null;

            if (!$phoneNumberId || !$accessToken) {
                return ['success' => false, 'error' => 'WhatsApp credentials not configured'];
            }

            $response = Http::withToken($accessToken)
                ->post("https://graph.instagram.com/v18.0/{$phoneNumberId}/messages", [
                    'messaging_product' => 'whatsapp',
                    'to'                => preg_replace('/\D/', '', $phoneNumber),
                    'type'              => 'text',
                    'text'              => ['body' => $message],
                ]);

            if ($response->successful()) {
                $messageData = $response->json();
                $externalId = $messageData['messages'][0]['id'] ?? null;

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

                $this->logActivity('message_sent', $channel, ['message_id' => $externalId]);

                return [
                    'success' => true,
                    'external_id' => $externalId,
                    'message_id' => $channelMsg->id,
                ];
            }

            $error = $response->json()['error']['message'] ?? 'Unknown error';
            $this->logActivity('send_failed', $channel, ['error' => $error]);

            return ['success' => false, 'error' => $error];

        } catch (\Exception $e) {
            $this->logError('sendMessage', $channel, $e);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function receiveMessage(array $payload): void
    {
        try {
            $entry = $payload['entry'][0] ?? null;
            $change = $entry['changes'][0] ?? null;
            $messageData = $change['value'] ?? null;

            if (!$messageData || !($messageData['messages'] ?? null)) return;

            $message = $messageData['messages'][0];
            $phoneId = $messageData['metadata']['phone_number_id'];
            $contact = $messageData['contacts'][0];

            $channel = ChannelConfiguration::where('channel_type', 'whatsapp')
                ->where('account_id', $phoneId)
                ->first();

            if (!$channel) return;

            $phoneNumber = $contact['wa_id'];
            $displayName = $contact['profile']['name'];

            $contactRecord = $this->getOrCreateContact($channel, $phoneNumber, [
                'name' => $displayName,
            ]);

            $content = $message['text']['body'] ?? '';
            $externalThreadId = $message['from'];

            $conversation = Conversation::firstOrCreate(
                [
                    'widget_id'   => $channel->widget_id,
                    'visitor_id'  => $contactRecord->visitor_id,
                ],
                [
                    'user_id'       => $channel->widget->user_id,
                    'status'        => 'ai_active',
                    'last_message_at' => now(),
                ]
            );

            $this->createChannelMessage(
                $channel,
                $conversation,
                'inbound',
                $content,
                [
                    'sender_channel_id'  => $externalThreadId,
                    'sender_name'        => $displayName,
                    'sender_phone'       => $phoneNumber,
                    'external_message_id' => $message['id'],
                    'external_thread_id' => $externalThreadId,
                ]
            );

            $channel->increment('total_messages_received');

            // Generate AI response
            $aiResponse = $this->generateAiResponse($conversation, $content, $channel);

            // Send back via WhatsApp
            $this->sendMessage($channel, $conversation, $aiResponse, [
                'recipient_phone' => $phoneNumber,
            ]);

            $contactRecord->updateLastMessage();

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('WhatsApp receive error: ' . $e->getMessage());
        }
    }

    public function verifyWebhookSignature(array $payload, string $signature): bool
    {
        $token = $payload['_channel']?->credentials['verification_token'] ?? '';
        return $payload['hub_verify_token'] === $token;
    }

    public function testConnection(ChannelConfiguration $channel): bool
    {
        try {
            $credentials = $channel->credentials;
            $accessToken = $credentials['access_token'] ?? null;

            if (!$accessToken) return false;

            $response = Http::withToken($accessToken)
                ->get('https://graph.instagram.com/v18.0/me/phone_number_quality_rating');

            return $response->successful();
        } catch (\Exception $e) {
            $this->logError('testConnection', $channel, $e);
            return false;
        }
    }

    public function getStatus(ChannelConfiguration $channel): array
    {
        return [
            'channel_type'       => 'whatsapp',
            'is_enabled'         => $channel->is_enabled,
            'account_name'       => $channel->account_name,
            'total_sent'         => $channel->total_messages_sent,
            'total_received'     => $channel->total_messages_received,
            'last_activity'      => $channel->last_message_at?->diffForHumans(),
            'daily_used'         => $channel->messages_today,
            'daily_limit'        => $channel->daily_message_limit,
        ];
    }

    public function syncContact(ChannelConfiguration $channel, string $contactId): void
    {
        // WhatsApp contact sync implementation
        // Usually handled via webhook, but can add manual sync if needed
    }
}
