<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_daily', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('widget_id')->nullable()->constrained()->nullOnDelete();
            $table->date('date')->index();

            $table->integer('conversations')->default(0);
            $table->integer('messages')->default(0);
            $table->integer('leads')->default(0);
            $table->integer('unique_visitors')->default(0);
            $table->integer('returning_visitors')->default(0);
            $table->integer('ai_responses')->default(0);
            $table->integer('fallbacks')->default(0);
            $table->integer('human_takeovers')->default(0);
            $table->float('avg_conversation_length')->default(0); // messages
            $table->float('avg_response_time_ms')->default(0);
            $table->float('resolution_rate')->default(0); // percentage
            $table->float('lead_conversion_rate')->default(0);

            $table->json('peak_hours')->nullable(); // hourly distribution
            $table->json('top_topics')->nullable();
            $table->json('language_distribution')->nullable();
            $table->json('device_distribution')->nullable();

            $table->timestamps();
            $table->unique(['user_id', 'widget_id', 'date']);
        });

        Schema::create('ai_insights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('widget_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type'); // general, topic, dropoff, peak_time, etc.
            $table->text('insight'); // AI-generated insight text
            $table->enum('sentiment', ['positive', 'neutral', 'negative', 'actionable'])->default('neutral');
            $table->integer('importance')->default(5); // 1-10
            $table->date('period_start');
            $table->date('period_end');
            $table->json('supporting_data')->nullable();
            $table->boolean('is_read')->default(false);
            $table->boolean('is_pinned')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_insights');
        Schema::dropIfExists('analytics_daily');
    }
};
