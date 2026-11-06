<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /** AI is included in every plan: no per-reply credits, no automatic upgrades. Extra AI replies come from top-up packs. */
    public const PLANS = [
        [
            'name' => 'Free', 'slug' => 'free', 'sort_order' => 0, 'color' => 'slate', 'is_active' => true,
            'description' => 'Free forever. A real AI assistant for one website, no card needed.',
            'price_monthly' => 0, 'price_yearly' => 0,
            'widgets_limit' => 1, 'messages_per_month' => 100, 'training_sources_limit' => 3,
            'leads_per_month' => 50, 'bookings_per_month' => 10, 'team_members_limit' => 1,
            'has_live_chat' => true, 'workflow_automations_limit' => 1, 'has_ai_insights' => false,
            'has_custom_branding' => false, 'has_api_access' => false, 'has_priority_support' => false,
        ],
        [
            'name' => 'Starter', 'slug' => 'starter', 'sort_order' => 1, 'color' => 'blue', 'is_active' => true,
            'description' => 'For small businesses. AI, live chat, leads and bookings included.',
            'price_monthly' => 19, 'price_yearly' => 190,
            'stripe_monthly_id' => 'price_starter_monthly', 'stripe_yearly_id' => 'price_starter_yearly',
            'widgets_limit' => 1, 'messages_per_month' => 2000, 'training_sources_limit' => 25,
            'leads_per_month' => 300, 'bookings_per_month' => 100, 'team_members_limit' => 2,
            'has_live_chat' => true, 'workflow_automations_limit' => 3, 'has_ai_insights' => false,
            'has_custom_branding' => false, 'has_api_access' => false, 'has_priority_support' => false,
        ],
        [
            'name' => 'Pro', 'slug' => 'pro', 'sort_order' => 2, 'color' => 'violet', 'is_popular' => true, 'is_active' => true,
            'description' => 'For growing teams: your branding, automations and AI insights.',
            'price_monthly' => 49, 'price_yearly' => 490,
            'stripe_monthly_id' => 'price_pro_monthly', 'stripe_yearly_id' => 'price_pro_yearly',
            'widgets_limit' => 3, 'messages_per_month' => 8000, 'training_sources_limit' => 100,
            'leads_per_month' => 2000, 'bookings_per_month' => 500, 'team_members_limit' => 5,
            'has_live_chat' => true, 'workflow_automations_limit' => 25, 'has_ai_insights' => true,
            'has_custom_branding' => true, 'has_api_access' => false, 'has_priority_support' => false,
        ],
        [
            'name' => 'Business', 'slug' => 'business', 'sort_order' => 3, 'color' => 'emerald', 'is_active' => true,
            'description' => 'For busy sites and agencies: more widgets, API and priority support.',
            'price_monthly' => 129, 'price_yearly' => 1290,
            'stripe_monthly_id' => 'price_business_monthly', 'stripe_yearly_id' => 'price_business_yearly',
            'widgets_limit' => 10, 'messages_per_month' => 30000, 'training_sources_limit' => 500,
            'leads_per_month' => -1, 'bookings_per_month' => 3000, 'team_members_limit' => 15,
            'has_live_chat' => true, 'workflow_automations_limit' => -1, 'has_ai_insights' => true,
            'has_custom_branding' => true, 'has_api_access' => true, 'has_priority_support' => true,
        ],
        [
            'name' => 'Enterprise', 'slug' => 'enterprise', 'sort_order' => 4, 'color' => 'amber', 'is_active' => true,
            'description' => 'Unlimited everything, white label and dedicated support.',
            'price_monthly' => 299, 'price_yearly' => 2990,
            'stripe_monthly_id' => 'price_enterprise_monthly', 'stripe_yearly_id' => 'price_enterprise_yearly',
            'widgets_limit' => -1, 'messages_per_month' => -1, 'training_sources_limit' => -1,
            'leads_per_month' => -1, 'bookings_per_month' => -1, 'team_members_limit' => -1,
            'has_live_chat' => true, 'workflow_automations_limit' => -1, 'has_ai_insights' => true,
            'has_custom_branding' => true, 'has_api_access' => true, 'has_priority_support' => true,
            'has_white_label' => true,
        ],
    ];

    public function run(): void
    {
        foreach (self::PLANS as $plan) {
            Plan::updateOrCreate(['slug' => $plan['slug']], $plan);
        }
    }
}
