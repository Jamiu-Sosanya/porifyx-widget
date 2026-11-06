<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * - Plans get a monthly booking allowance (0 = not included, -1 = unlimited).
 * - The four default plans are re-tiered.
 * - The AI provider becomes a platform (admin) setting instead of a per-widget choice.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('plans', 'bookings_per_month')) {
            Schema::table('plans', function (Blueprint $t) {
                $t->integer('bookings_per_month')->default(0)->after('leads_per_month');
            });
        }

        $tiers = [
            'starter' => ['widgets_limit' => 1, 'messages_per_month' => 1000, 'training_sources_limit' => 10, 'leads_per_month' => 100, 'bookings_per_month' => 50,
                'team_members_limit' => 1, 'workflow_automations_limit' => 0, 'has_live_chat' => false, 'has_ai_insights' => false,
                'has_custom_branding' => false, 'has_api_access' => false, 'has_priority_support' => false, 'has_white_label' => false],
            'pro' => ['widgets_limit' => 3, 'messages_per_month' => 5000, 'training_sources_limit' => 50, 'leads_per_month' => 1000, 'bookings_per_month' => 300,
                'team_members_limit' => 3, 'workflow_automations_limit' => 10, 'has_live_chat' => true, 'has_ai_insights' => true,
                'has_custom_branding' => true, 'has_api_access' => false, 'has_priority_support' => false, 'has_white_label' => false],
            'business' => ['widgets_limit' => 10, 'messages_per_month' => 20000, 'training_sources_limit' => 200, 'leads_per_month' => 5000, 'bookings_per_month' => 1500,
                'team_members_limit' => 10, 'workflow_automations_limit' => 50, 'has_live_chat' => true, 'has_ai_insights' => true,
                'has_custom_branding' => true, 'has_api_access' => true, 'has_priority_support' => true, 'has_white_label' => false],
            'enterprise' => ['widgets_limit' => -1, 'messages_per_month' => -1, 'training_sources_limit' => -1, 'leads_per_month' => -1, 'bookings_per_month' => -1,
                'team_members_limit' => -1, 'workflow_automations_limit' => -1, 'has_live_chat' => true, 'has_ai_insights' => true,
                'has_custom_branding' => true, 'has_api_access' => true, 'has_priority_support' => true, 'has_white_label' => true],
        ];
        foreach ($tiers as $slug => $values) {
            DB::table('plans')->where('slug', $slug)->update($values + ['updated_at' => now()]);
        }

        if (Schema::hasTable('system_settings') && ! DB::table('system_settings')->where('key', 'ai_provider')->exists()) {
            DB::table('system_settings')->insert([
                'key' => 'ai_provider', 'config_key' => 'ai.provider', 'label' => 'AI Provider (all widgets)',
                'description' => 'The provider every widget uses: openai, anthropic, gemini, groq or openrouter. Its default model below is the model used. Customers cannot change this.',
                'type' => 'string', 'group' => 'ai', 'value' => 'openrouter', 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('plans', 'bookings_per_month')) {
            Schema::table('plans', fn (Blueprint $t) => $t->dropColumn('bookings_per_month'));
        }
        DB::table('system_settings')->where('key', 'ai_provider')->delete();
    }
};
