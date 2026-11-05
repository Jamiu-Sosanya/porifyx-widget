<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('channel_configurations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('widget_id')->constrained()->cascadeOnDelete();
            $table->enum('channel_type', [
                'web',          // Embedded widget
                'email',        // Email notifications & replies
                'whatsapp',     // WhatsApp Business API
                'facebook',     // Facebook Messenger
                'instagram',    // Instagram Direct Messages
                'twitter',      // Twitter/X DMs
                'telegram',     // Telegram Bot
                'slack',        // Slack Workspace
                'discord',      // Discord Server
                'sms',          // SMS via Twilio
            ])->default('web');

            // Connection credentials (encrypted in config)
            $table->json('credentials')->nullable();

            // Status & settings
            $table->boolean('is_enabled')->default(true);
            $table->boolean('is_default')->default(false);
            $table->json('settings')->nullable(); // Channel-specific settings

            // Webhook configurations
            $table->string('webhook_url')->nullable();
            $table->string('webhook_secret')->nullable();
            $table->string('verification_token')->nullable();

            // Rate limiting & quotas
            $table->integer('rate_limit_per_minute')->default(60);
            $table->integer('daily_message_limit')->nullable();
            $table->integer('messages_today')->default(0);

            // Statistics
            $table->integer('total_messages_sent')->default(0);
            $table->integer('total_messages_received')->default(0);
            $table->timestamp('last_message_at')->nullable();
            $table->timestamp('webhook_tested_at')->nullable();

            // Account info
            $table->string('account_id')->nullable();  // Account/page/number ID
            $table->string('account_name')->nullable();
            $table->text('account_description')->nullable();

            // Timestamps
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['widget_id', 'channel_type']);
            $table->index(['widget_id', 'is_enabled']);
        });

        Schema::create('channel_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('widget_id')->constrained()->cascadeOnDelete();
            $table->foreignId('channel_config_id')->constrained('channel_configurations')->cascadeOnDelete();

            // Message routing
            $table->string('external_channel_id')->nullable(); // Platform-specific ID
            $table->string('external_message_id')->nullable()->unique(); // Platform message ID
            $table->string('external_thread_id')->nullable(); // Group/conversation ID on platform

            // Content
            $table->enum('direction', ['inbound', 'outbound'])->default('inbound');
            $table->enum('message_type', ['text', 'image', 'file', 'audio', 'video', 'live_location', 'reaction', 'system'])->default('text');
            $table->longText('content');
            $table->json('metadata')->nullable(); // Channel-specific metadata
            $table->json('attachments')->nullable(); // File attachments

            // Sender/Recipient
            $table->string('sender_channel_id')->nullable();
            $table->string('sender_name')->nullable();
            $table->string('sender_phone')->nullable();
            $table->string('sender_email')->nullable();

            // Delivery status
            $table->enum('status', ['pending', 'sent', 'delivered', 'read', 'failed'])->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();

            // Timestamps
            $table->timestamps();

            $table->index(['conversation_id', 'channel_config_id']);
            $table->index(['widget_id', 'direction']);
            $table->index(['external_channel_id']);
        });

        Schema::create('channel_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('widget_id')->constrained()->cascadeOnDelete();
            $table->foreignId('visitor_id')->nullable()->constrained()->nullOnDelete();

            // Contact details by channel
            $table->string('channel_type'); // e.g., 'whatsapp', 'email', 'facebook'
            $table->string('channel_identifier')->unique(); // Phone, email, username, etc
            $table->string('display_name')->nullable();
            $table->string('avatar_url')->nullable();

            // Sync status
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('last_sync_at')->nullable();

            // Activity
            $table->integer('message_count')->default(0);
            $table->timestamp('last_message_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['widget_id', 'channel_type', 'channel_identifier'], 'channel_contacts_unique');
            $table->index(['visitor_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('channel_contacts');
        Schema::dropIfExists('channel_messages');
        Schema::dropIfExists('channel_configurations');
    }
};
