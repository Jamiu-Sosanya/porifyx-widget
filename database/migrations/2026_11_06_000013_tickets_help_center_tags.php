<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();             // business owner
            $t->foreignId('widget_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('conversation_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('merged_into_id')->nullable()->constrained('tickets')->nullOnDelete();
            $t->string('subject');
            $t->string('requester_name')->nullable();
            $t->string('requester_email');
            $t->string('status', 20)->default('open');     // open | pending | solved | closed
            $t->string('priority', 20)->default('normal'); // low | normal | high | urgent
            $t->timestamp('last_customer_reply_at')->nullable();
            $t->timestamp('last_agent_reply_at')->nullable();
            $t->timestamp('escalated_at')->nullable();
            $t->timestamps();
            $t->index(['user_id', 'status']);
        });

        Schema::create('ticket_messages', function (Blueprint $t) {
            $t->id();
            $t->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('type', 20); // customer | reply | note | event
            $t->text('body');
            $t->timestamps();
        });

        Schema::create('help_categories', function (Blueprint $t) {
            $t->id();
            $t->foreignId('widget_id')->constrained()->cascadeOnDelete();
            $t->string('name');
            $t->string('slug');
            $t->string('icon', 16)->nullable();
            $t->string('description', 300)->nullable();
            $t->unsignedInteger('sort')->default(0);
            $t->timestamps();
            $t->unique(['widget_id', 'slug']);
        });

        Schema::create('help_articles', function (Blueprint $t) {
            $t->id();
            $t->foreignId('widget_id')->constrained()->cascadeOnDelete();
            $t->foreignId('help_category_id')->nullable()->constrained()->nullOnDelete();
            $t->string('title');
            $t->string('slug');
            $t->text('body');
            $t->boolean('is_published')->default(true);
            $t->unsignedInteger('views')->default(0);
            $t->timestamps();
            $t->unique(['widget_id', 'slug']);
        });

        Schema::table('conversations', function (Blueprint $t) {
            if (! Schema::hasColumn('conversations', 'tags')) $t->json('tags')->nullable();
        });
        Schema::table('users', function (Blueprint $t) {
            if (! Schema::hasColumn('users', 'support_settings')) $t->json('support_settings')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('help_articles');
        Schema::dropIfExists('help_categories');
        Schema::dropIfExists('ticket_messages');
        Schema::dropIfExists('tickets');
        if (Schema::hasColumn('conversations', 'tags')) Schema::table('conversations', fn (Blueprint $t) => $t->dropColumn('tags'));
        if (Schema::hasColumn('users', 'support_settings')) Schema::table('users', fn (Blueprint $t) => $t->dropColumn('support_settings'));
    }
};
