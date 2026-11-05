<?php

namespace App\Services\Channels;

use App\Models\ChannelConfiguration;
use App\Models\Conversation;
use Illuminate\Support\Facades\Mail;

class EmailChannelService extends BaseChannelService
{
    protected string $channelType = 'email';

    public function sendMessage(
        ChannelConfiguration $channel,
        Conversation $conversation,
        string $message,
        array $options = []
    ): array {
        try {
            if ($channel->hasReachedDailyLimit()) {
                return ['success' => false, 'error' => 'Daily email limit reached'];
            }

            $recipientEmail = $options['recipient_email'] ?? $conversation->visitor?->email;
            if (!$recipientEmail) {
                return ['success' => false, 'error' => 'No email address for recipient'];
            }

            $credentials = $channel->credentials;
            $senderEmail = $credentials['sender_email'] ?? config('mail.from.address');
            $senderName = $credentials['sender_name'] ?? $channel->widget->ai_name ?? 'Support';

            $subject = $options['subject'] ?? "Response from {$senderName}";

            // Send email
            Mail::raw($message, function ($mail) use ($senderEmail, $senderName, $recipientEmail, $subject, $conversation) {
                $mail->from($senderEmail, $senderName)
                    ->to($recipientEmail)
                    ->subject($subject)
                    ->replyTo($senderEmail);
            });

            $externalId = 'email_' . uniqid();

            $channelMsg = $this->createChannelMessage(
                $channel,
                $conversation,
                'outbound',
                $message,
                [
                    'external_message_id' => $externalId,
                    'sender_email' => $senderEmail,
                    'metadata' => ['subject' => $subject],
                ]
            );

            $channel->increment('total_messages_sent');
            $channel->incrementDailyCount();
            $channel->update(['last_message_at' => now()]);

            $this->logActivity('email_sent', $channel, [
                'to' => $recipientEmail,
                'subject' => $subject,
            ]);

            return [
                'success' => true,
                'external_id' => $externalId,
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
            // Email webhook format (Mailgun/SendGrid example)
            $senderEmail = $payload['from'] ?? null;
            $senderName = $payload['sender'] ?? null;
            $content = $payload['text'] ?? $payload['body'] ?? null;
            $subject = $payload['subject'] ?? null;
            $messageId = $payload['message_id'] ?? null;
            $threadId = $payload['thread_id'] ?? null;

            if (!$senderEmail || !$content) return;

            $channel = ChannelConfiguration::where('channel_type', 'email')
                ->whereJsonContains('credentials->receiver_email', $senderEmail)
                ->first();

            if (!$channel) return;

            $contactRecord = $this->getOrCreateContact($channel, $senderEmail, [
                'name' => $senderName,
            ]);

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
                    'sender_email'        => $senderEmail,
                    'sender_name'         => $senderName,
                    'external_message_id' => $messageId,
                    'external_thread_id'  => $threadId,
                    'metadata'            => ['subject' => $subject],
                ]
            );

            $channel->increment('total_messages_received');

            // Generate AI response
            $aiResponse = $this->generateAiResponse($conversation, $content, $channel);

            // Send reply
            $this->sendMessage($channel, $conversation, $aiResponse, [
                'recipient_email' => $senderEmail,
                'subject' => 'Re: ' . ($subject ?? 'Your Message'),
            ]);

            $contactRecord->updateLastMessage();

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Email receive error: ' . $e->getMessage());
        }
    }

    public function verifyWebhookSignature(array $payload, string $signature): bool
    {
        // Email webhook verification depends on provider (Mailgun, SendGrid, etc.)
        $secret = config('services.mailgun.secret') ?? config('services.sendgrid.secret') ?? '';
        return hash_hmac('sha256', json_encode($payload), $secret) === $signature;
    }

    public function testConnection(ChannelConfiguration $channel): bool
    {
        try {
            // Test by trying to send a test email
            $credentials = $channel->credentials;
            $testEmail = $credentials['receiver_email'] ?? null;

            if (!$testEmail) return false;

            Mail::raw('Test email from PoriFyx Widget', function ($mail) use ($testEmail, $credentials) {
                $mail->from($credentials['sender_email'] ?? config('mail.from.address'))
                    ->to($testEmail)
                    ->subject('Email Channel Test');
            });

            return true;
        } catch (\Exception $e) {
            $this->logError('testConnection', $channel, $e);
            return false;
        }
    }

    public function getStatus(ChannelConfiguration $channel): array
    {
        $credentials = $channel->credentials;
        return [
            'channel_type'    => 'email',
            'is_enabled'      => $channel->is_enabled,
            'sender_email'    => $credentials['sender_email'] ?? null,
            'receiver_email'  => $credentials['receiver_email'] ?? null,
            'total_sent'      => $channel->total_messages_sent,
            'total_received'  => $channel->total_messages_received,
            'last_activity'   => $channel->last_message_at?->diffForHumans(),
            'daily_used'      => $channel->messages_today,
            'daily_limit'     => $channel->daily_message_limit,
        ];
    }

    public function syncContact(ChannelConfiguration $channel, string $contactId): void
    {
        // Email contacts are typically synced via webhooks
    }
}
