<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Advanced AI Provider settings on widgets ──────────────────────────
        Schema::table('widgets', function (Blueprint $table) {
            // Multi-provider AI
            $table->enum('ai_provider', ['openai', 'anthropic', 'gemini', 'groq'])->default('openai')->after('tone');
            $table->string('ai_model', 80)->nullable()->after('ai_provider');

            // Smart suggested replies
            $table->boolean('smart_suggestions_enabled')->default(false)->after('suggested_replies');
            $table->unsignedTinyInteger('smart_suggestions_count')->default(3)->after('smart_suggestions_enabled');

            // Auto language detection
            $table->boolean('auto_language_detection')->default(false)->after('smart_suggestions_count');

            // Widget themes
            $table->string('theme', 30)->default('dark')->after('font_family');

            // Notification integrations
            $table->string('slack_webhook_url', 2048)->nullable()->after('csat_prompt_enabled');
            $table->string('discord_webhook_url', 2048)->nullable()->after('slack_webhook_url');
            $table->string('notification_email', 255)->nullable()->after('discord_webhook_url');
            $table->boolean('notify_on_lead')->default(true)->after('notification_email');
            $table->boolean('notify_on_handoff')->default(true)->after('notify_on_lead');

            // Proactive messaging
            $table->boolean('proactive_enabled')->default(false)->after('auto_open_delay');
            $table->integer('proactive_trigger_seconds')->default(30)->after('proactive_enabled');
            $table->integer('proactive_trigger_pages')->default(2)->after('proactive_trigger_seconds');
            $table->string('proactive_message', 500)->nullable()->after('proactive_trigger_pages');

            // Offline message collection
            $table->boolean('offline_form_enabled')->default(false)->after('collect_phone');
            $table->string('offline_form_message', 500)->default("Leave us a message and we'll get back to you shortly.")->after('offline_form_enabled');

            // Sound notifications
            $table->boolean('sound_enabled')->default(false)->after('typing_animation');

            // Conversation starter buttons
            $table->json('conversation_starters')->nullable()->after('suggested_replies');
        });

        // ── Sentiment & language on messages ─────────────────────────────────
        Schema::table('messages', function (Blueprint $table) {
            $table->float('sentiment_score')->nullable()->after('confidence_score'); // -1 to 1
            $table->enum('sentiment_label', ['positive', 'neutral', 'negative'])->nullable()->after('sentiment_score');
            $table->string('detected_language', 10)->nullable()->after('sentiment_label');
            $table->json('smart_suggestions')->nullable()->after('metadata'); // AI-generated follow-up suggestions
            $table->json('reactions')->nullable()->after('smart_suggestions'); // emoji reactions
        });

        // ── Conversation enhancements ─────────────────────────────────────────
        Schema::table('conversations', function (Blueprint $table) {
            $table->float('avg_sentiment')->nullable()->after('csat_submitted_at');
            $table->string('detected_language', 10)->nullable()->after('language');
            $table->json('labels')->nullable()->after('context_data'); // conversation labels/tags
            $table->integer('total_tokens_used')->default(0)->after('ai_message_count');
            $table->boolean('offline_message_sent')->default(false)->after('csat_submitted_at');
        });

        // ── Conversation labels ───────────────────────────────────────────────
        Schema::create('conversation_labels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 80);
            $table->string('color', 20)->default('#2f5ee8');
            $table->string('icon', 10)->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'name']);
        });

        Schema::create('conversation_label_pivot', function (Blueprint $table) {
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('conversation_label_id')->constrained()->cascadeOnDelete();
            $table->primary(['conversation_id', 'conversation_label_id']);
        });

        // ── Proactive messages ────────────────────────────────────────────────
        Schema::create('proactive_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('widget_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->text('message');
            $table->enum('trigger_type', ['time_on_page', 'page_count', 'scroll_depth', 'exit_intent', 'manual'])->default('time_on_page');
            $table->integer('trigger_value')->default(30); // seconds / pages / percentage
            $table->string('target_url_pattern', 500)->nullable(); // regex pattern for URL targeting
            $table->json('quick_replies')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('shown_count')->default(0);
            $table->integer('clicked_count')->default(0);
            $table->timestamps();
        });

        // ── Offline messages ──────────────────────────────────────────────────
        Schema::create('offline_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('widget_id')->constrained()->cascadeOnDelete();
            $table->foreignId('conversation_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 255)->nullable();
            $table->string('email', 255);
            $table->text('message');
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->string('page_url', 2048)->nullable();
            $table->timestamps();

            $table->index(['widget_id', 'is_read']);
        });

        // ── AB Tests ──────────────────────────────────────────────────────────
        Schema::create('widget_ab_tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('widget_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->json('variant_a'); // config overrides
            $table->json('variant_b');
            $table->float('split_ratio')->default(0.5); // 0-1, A gets this ratio
            $table->integer('impressions_a')->default(0);
            $table->integer('impressions_b')->default(0);
            $table->integer('leads_a')->default(0);
            $table->integer('leads_b')->default(0);
            $table->integer('conversations_a')->default(0);
            $table->integer('conversations_b')->default(0);
            $table->boolean('is_active')->default(false);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('widget_ab_tests');
        Schema::dropIfExists('offline_messages');
        Schema::dropIfExists('proactive_messages');
        Schema::dropIfExists('conversation_label_pivot');
        Schema::dropIfExists('conversation_labels');

        Schema::table('conversations', function (Blueprint $table) {
            $table->dropColumn(['avg_sentiment', 'detected_language', 'labels', 'total_tokens_used', 'offline_message_sent']);
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn(['sentiment_score', 'sentiment_label', 'detected_language', 'smart_suggestions', 'reactions']);
        });

        Schema::table('widgets', function (Blueprint $table) {
            $table->dropColumn([
                'ai_provider', 'ai_model', 'smart_suggestions_enabled', 'smart_suggestions_count',
                'auto_language_detection', 'theme', 'slack_webhook_url', 'discord_webhook_url',
                'notification_email', 'notify_on_lead', 'notify_on_handoff',
                'proactive_enabled', 'proactive_trigger_seconds', 'proactive_trigger_pages', 'proactive_message',
                'offline_form_enabled', 'offline_form_message', 'sound_enabled', 'conversation_starters',
            ]);
        });
    }
};
