<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // push_subscriptions is created by 2026_04_21_000001_create_push_subscriptions_table.php

        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // null = global template
            $table->string('event'); // new_lead, chat_started, etc.
            $table->enum('channel', ['email', 'telegram', 'browser']);
            $table->string('subject')->nullable(); // for email
            $table->text('body');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_templates');
    }
};
