<?php

namespace App\Services\Channels;

use App\Models\ChannelConfiguration;
use App\Models\ChannelContact;
use App\Models\ChannelMessage;
use App\Models\Conversation;
use Illuminate\Support\Facades\Log;

abstract class BaseChannelService implements ChannelServiceInterface
{
    protected string $channelType;

    /**
     * Create a channel message record
     */
    protected function createChannelMessage(
        ChannelConfiguration $channel,
        ?Conversation $conversation,
        string $direction,
        string $content,
        array $options = []
    ): ChannelMessage {
        return ChannelMessage::create([
            'conversation_id'    => $conversation?->id,
            'widget_id'          => $channel->widget_id,
            'channel_config_id'  => $channel->id,
            'direction'          => $direction,
            'message_type'       => $options['message_type'] ?? 'text',
            'content'            => $content,
            'metadata'           => $options['metadata'] ?? null,
            'attachments'        => $options['attachments'] ?? null,
            'sender_channel_id'  => $options['sender_channel_id'] ?? null,
            'sender_name'        => $options['sender_name'] ?? null,
            'sender_phone'       => $options['sender_phone'] ?? null,
            'sender_email'       => $options['sender_email'] ?? null,
            'external_message_id' => $options['external_message_id'] ?? null,
            'external_thread_id' => $options['external_thread_id'] ?? null,
            'status'             => $direction === 'outbound' ? 'pending' : 'delivered',
        ]);
    }

    /**
     * Get or create channel contact
     */
    protected function getOrCreateContact(
        ChannelConfiguration $channel,
        string $identifier,
        array $contactData = []
    ): ChannelContact {
        return ChannelContact::updateOrCreate(
            [
                'widget_id'       => $channel->widget_id,
                'channel_type'    => $this->channelType,
                'channel_identifier' => $identifier,
            ],
            [
                'display_name' => $contactData['name'] ?? null,
                'avatar_url'   => $contactData['avatar'] ?? null,
            ]
        );
    }

    /**
     * Log channel activity
     */
    protected function logActivity(string $action, ChannelConfiguration $channel, array $details = []): void
    {
        Log::info("Channel {$this->channelType} activity", [
            'action'        => $action,
            'widget_id'     => $channel->widget_id,
            'channel_id'    => $channel->id,
            'timestamp'     => now()->toIso8601String(),
            ...$details,
        ]);
    }

    /**
     * Log channel error
     */
    protected function logError(string $action, ChannelConfiguration $channel, \Throwable $exception): void
    {
        Log::error("Channel {$this->channelType} error", [
            'action'       => $action,
            'widget_id'    => $channel->widget_id,
            'channel_id'   => $channel->id,
            'error'        => $exception->getMessage(),
            'trace'        => $exception->getTraceAsString(),
        ]);
    }

    /**
     * Send response via AI chat service
     */
    protected function generateAiResponse(Conversation $conversation, string $userMessage, ChannelConfiguration $channel): string
    {
        try {
            $chatService = app(\App\Services\AiChatService::class);
            $result = $chatService->generateResponse($conversation, $userMessage);
            return $result['content'] ?? 'I am currently unavailable. Please try again later.';
        } catch (\Exception $e) {
            $this->logError('generate_ai_response', $channel, $e);
            return 'I am currently unavailable. Please try again later.';
        }
    }

    /**
     * Default webhook verification (override in child classes)
     */
    public function verifyWebhookSignature(array $payload, string $signature): bool
    {
        return true; // Override in child classes
    }

    /**
     * Default disconnect implementation
     */
    public function disconnect(ChannelConfiguration $channel): bool
    {
        return $channel->update(['is_enabled' => false]);
    }
}
