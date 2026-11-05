<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('widgets', function (Blueprint $table) {
            $table->boolean('webhook_enabled')->default(false)->after('handoff_acknowledgment');
            $table->string('webhook_url', 2048)->nullable();
            $table->string('webhook_secret', 128)->nullable();
            $table->boolean('csat_prompt_enabled')->default(false);
        });

        Schema::table('conversations', function (Blueprint $table) {
            $table->unsignedTinyInteger('csat_score')->nullable()->after('last_message_at');
            $table->string('csat_comment', 500)->nullable()->after('csat_score');
            $table->timestamp('csat_submitted_at')->nullable()->after('csat_comment');
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropColumn(['csat_score', 'csat_comment', 'csat_submitted_at']);
        });

        Schema::table('widgets', function (Blueprint $table) {
            $table->dropColumn(['webhook_enabled', 'webhook_url', 'webhook_secret', 'csat_prompt_enabled']);
        });
    }
};
