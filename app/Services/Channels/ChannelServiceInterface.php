<?php

namespace App\Services\Channels;

use App\Models\ChannelConfiguration;
use App\Models\Conversation;

interface ChannelServiceInterface
{
    /**
     * Send a message via this channel
     */
    public function sendMessage(
        ChannelConfiguration $channel,
        Conversation $conversation,
        string $message,
        array $options = []
    ): array;

    /**
     * Receive and process an inbound message from the channel
     */
    public function receiveMessage(array $payload): void;

    /**
     * Verify webhook signature/token
     */
    public function verifyWebhookSignature(array $payload, string $signature): bool;

    /**
     * Sync contact information
     */
    public function syncContact(ChannelConfiguration $channel, string $contactId): void;

    /**
     * Test channel connectivity
     */
    public function testConnection(ChannelConfiguration $channel): bool;

    /**
     * Get channel status
     */
    public function getStatus(ChannelConfiguration $channel): array;

    /**
     * Disconnect/deactivate channel
     */
    public function disconnect(ChannelConfiguration $channel): bool;
}
