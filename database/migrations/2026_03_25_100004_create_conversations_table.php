<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visitors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('widget_id')->constrained()->cascadeOnDelete();
            $table->string('fingerprint', 64)->index(); // browser fingerprint
            $table->string('email')->nullable()->index();
            $table->string('name')->nullable();
            $table->string('phone')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('country', 5)->nullable();
            $table->string('city')->nullable();
            $table->string('browser')->nullable();
            $table->string('os')->nullable();
            $table->string('device_type', 20)->nullable();
            $table->string('timezone')->nullable();
            $table->string('language', 10)->nullable();
            $table->integer('visit_count')->default(1);
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->json('behavior_data')->nullable(); // pages visited, time on site
            $table->json('custom_attributes')->nullable();
            $table->timestamps();

            $table->unique(['widget_id', 'fingerprint']);
        });

        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 36)->unique();
            $table->foreignId('widget_id')->constrained()->cascadeOnDelete();
            $table->foreignId('visitor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // widget owner
            $table->foreignId('assigned_agent_id')->nullable()->constrained('users')->nullOnDelete();

            $table->enum('status', ['open', 'ai_active', 'human_active', 'resolved', 'spam'])->default('ai_active');
            $table->enum('channel', ['widget', 'api'])->default('widget');
            $table->string('subject')->nullable();
            $table->string('language', 10)->default('en');
            $table->string('current_page_url')->nullable();
            $table->string('referrer_url')->nullable();
            $table->json('context_data')->nullable(); // UTM, page meta, etc.
            $table->json('ai_context')->nullable(); // summarized conversation context
            $table->integer('message_count')->default(0);
            $table->integer('ai_message_count')->default(0);
            $table->boolean('is_read')->default(false);
            $table->boolean('is_starred')->default(false);
            $table->timestamp('first_response_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['widget_id', 'status']);
            $table->index(['widget_id', 'created_at']);
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 36)->unique();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->enum('role', ['visitor', 'ai', 'agent', 'system']);
            $table->foreignId('agent_id')->nullable()->constrained('users')->nullOnDelete();
            $table->longText('content');
            $table->enum('content_type', ['text', 'image', 'file', 'audio', 'lead_form', 'system'])->default('text');
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->string('file_mime')->nullable();
            $table->float('confidence_score')->nullable(); // AI confidence
            $table->json('sources_used')->nullable(); // KB chunks used
            $table->boolean('is_fallback')->default(false);
            $table->json('metadata')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['conversation_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
        Schema::dropIfExists('conversations');
        Schema::dropIfExists('visitors');
    }
};
