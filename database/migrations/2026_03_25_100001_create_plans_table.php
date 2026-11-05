<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('price_monthly', 10, 2)->default(0);
            $table->decimal('price_yearly', 10, 2)->default(0);
            $table->string('stripe_monthly_id')->nullable();
            $table->string('stripe_yearly_id')->nullable();
            $table->string('color', 30)->default('blue');
            $table->boolean('is_popular')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);

            // Feature limits (-1 = unlimited)
            $table->integer('widgets_limit')->default(1);
            $table->integer('messages_per_month')->default(500);
            $table->integer('training_sources_limit')->default(3);
            $table->integer('leads_per_month')->default(50);
            $table->integer('team_members_limit')->default(1);
            $table->boolean('has_live_chat')->default(false);
            $table->integer('workflow_automations_limit')->default(0);
            $table->boolean('has_ai_insights')->default(false);
            $table->boolean('has_custom_branding')->default(false);
            $table->boolean('has_api_access')->default(false);
            $table->boolean('has_priority_support')->default(false);
            $table->boolean('has_white_label')->default(false);

            // Overage pricing
            $table->decimal('overage_message_price', 8, 4)->default(0.01);
            $table->decimal('overage_lead_price', 8, 4)->default(0.05);

            $table->json('features')->nullable(); // Additional features as JSON
            $table->timestamps();
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained()->restrictOnDelete();
            $table->string('stripe_subscription_id')->nullable()->unique();
            $table->string('stripe_customer_id')->nullable();
            $table->enum('billing_cycle', ['monthly', 'yearly'])->default('monthly');
            $table->enum('status', ['trialing', 'active', 'past_due', 'canceled', 'incomplete', 'paused'])->default('trialing');
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('current_period_start')->nullable();
            $table->timestamp('current_period_end')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('usage_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('metric'); // messages, leads, etc.
            $table->integer('count')->default(0);
            $table->decimal('overage_cost', 10, 2)->default(0);
            $table->year('year');
            $table->tinyInteger('month');
            $table->timestamps();
            $table->unique(['user_id', 'metric', 'year', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usage_records');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('plans');
    }
};
