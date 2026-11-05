<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('automations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('widget_id')->nullable()->constrained()->nullOnDelete(); // null = all widgets
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);

            // Trigger
            $table->enum('trigger', [
                'new_lead',
                'chat_started',
                'chat_completed',
                'ai_fallback',
                'hot_lead',
                'keyword_detected',
                'time_based',
            ]);
            $table->json('trigger_conditions')->nullable(); // extra conditions

            // Action
            $table->enum('action', [
                'send_email',
                'send_telegram',
                'tag_lead',
                'assign_priority',
                'assign_agent',
                'webhook',
                'add_to_crm',
            ]);
            $table->json('action_config'); // email body, webhook URL, tag name, etc.

            // Stats
            $table->integer('trigger_count')->default(0);
            $table->integer('success_count')->default(0);
            $table->timestamp('last_triggered_at')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('automation_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('automation_id')->constrained()->cascadeOnDelete();
            $table->morphs('triggerable'); // conversation, lead, etc.
            $table->enum('status', ['success', 'failed'])->default('success');
            $table->text('error_message')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['automation_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('automation_logs');
        Schema::dropIfExists('automations');
    }
};
