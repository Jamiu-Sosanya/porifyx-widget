<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('widgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('uuid', 36)->unique();
            $table->string('token', 64)->unique();

            // Identity
            $table->string('ai_name')->default('AI Assistant');
            $table->string('ai_avatar')->nullable();
            $table->enum('tone', ['friendly', 'professional', 'sales', 'support'])->default('friendly');

            // Appearance
            $table->string('primary_color', 20)->default('#2f5ee8');
            $table->string('secondary_color', 20)->default('#ffffff');
            $table->string('text_color', 20)->default('#1f2937');
            $table->string('bg_color', 20)->default('#ffffff');
            $table->enum('position', ['bottom-right', 'bottom-left', 'top-right', 'top-left'])->default('bottom-right');
            $table->enum('launcher_shape', ['circle', 'rounded', 'square'])->default('circle');
            $table->string('launcher_icon')->nullable();
            $table->string('font_family')->default('Inter, sans-serif');

            // Behavior
            $table->string('welcome_message')->default('Hi! How can I help you today?');
            $table->string('placeholder_text')->default('Type a message...');
            $table->boolean('show_branding')->default(true);
            $table->boolean('auto_open')->default(false);
            $table->integer('auto_open_delay')->default(5); // seconds
            $table->boolean('show_on_mobile')->default(true);
            $table->boolean('collect_email')->default(true);
            $table->boolean('collect_phone')->default(false);
            $table->boolean('file_upload_enabled')->default(false);
            $table->boolean('voice_input_enabled')->default(false);
            $table->boolean('emoji_enabled')->default(true);
            $table->boolean('typing_animation')->default(true);
            $table->boolean('message_streaming')->default(true);

            // AI Settings
            $table->float('temperature')->default(0.7);
            $table->enum('response_length', ['short', 'medium', 'long'])->default('medium');
            $table->boolean('strict_mode')->default(false); // only answer from KB
            $table->text('system_prompt')->nullable();
            $table->json('lead_qualification_flow')->nullable();
            $table->boolean('lead_qualification_enabled')->default(true);

            // Security
            $table->json('allowed_domains')->nullable();
            $table->boolean('domain_restriction')->default(false);
            $table->integer('rate_limit')->default(30); // messages per minute

            // Status
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_deployed_at')->nullable();

            // Stats (cached)
            $table->integer('total_conversations')->default(0);
            $table->integer('total_messages')->default(0);
            $table->integer('total_leads')->default(0);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('widgets');
    }
};
