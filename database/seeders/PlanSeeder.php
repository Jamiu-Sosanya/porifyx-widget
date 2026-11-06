<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Starter', 'slug' => 'starter', 'sort_order' => 1, 'color' => 'blue',
                'description' => 'Perfect for small businesses getting started with AI chat.',
                'price_monthly' => 29, 'price_yearly' => 290,
                'stripe_monthly_id' => 'price_starter_monthly', 'stripe_yearly_id' => 'price_starter_yearly',
                'widgets_limit' => 1, 'messages_per_month' => 1000, 'training_sources_limit' => 10,
                'leads_per_month' => 100, 'bookings_per_month' => 50, 'team_members_limit' => 1,
                'has_live_chat' => false, 'workflow_automations_limit' => 0, 'has_ai_insights' => false,
                'has_custom_branding' => false, 'has_api_access' => false, 'has_priority_support' => false,
            ],
            [
                'name' => 'Pro', 'slug' => 'pro', 'sort_order' => 2, 'color' => 'violet', 'is_popular' => true,
                'description' => 'For growing businesses that need advanced features and more volume.',
                'price_monthly' => 79, 'price_yearly' => 790,
                'stripe_monthly_id' => 'price_pro_monthly', 'stripe_yearly_id' => 'price_pro_yearly',
                'widgets_limit' => 3, 'messages_per_month' => 5000, 'training_sources_limit' => 50,
                'leads_per_month' => 1000, 'bookings_per_month' => 300, 'team_members_limit' => 3,
                'has_live_chat' => true, 'workflow_automations_limit' => 10, 'has_ai_insights' => true,
                'has_custom_branding' => true, 'has_api_access' => false, 'has_priority_support' => false,
            ],
            [
                'name' => 'Business', 'slug' => 'business', 'sort_order' => 3, 'color' => 'emerald',
                'description' => 'For established businesses that need full control and scale.',
                'price_monthly' => 199, 'price_yearly' => 1990,
                'stripe_monthly_id' => 'price_business_monthly', 'stripe_yearly_id' => 'price_business_yearly',
                'widgets_limit' => 10, 'messages_per_month' => 20000, 'training_sources_limit' => 200,
                'leads_per_month' => 5000, 'bookings_per_month' => 1500, 'team_members_limit' => 10,
                'has_live_chat' => true, 'workflow_automations_limit' => 50, 'has_ai_insights' => true,
                'has_custom_branding' => true, 'has_api_access' => true, 'has_priority_support' => true,
            ],
            [
                'name' => 'Enterprise', 'slug' => 'enterprise', 'sort_order' => 4, 'color' => 'amber',
                'description' => 'Unlimited everything. Custom branding. Dedicated support.',
                'price_monthly' => 499, 'price_yearly' => 4990,
                'stripe_monthly_id' => 'price_enterprise_monthly', 'stripe_yearly_id' => 'price_enterprise_yearly',
                'widgets_limit' => -1, 'messages_per_month' => -1, 'training_sources_limit' => -1,
                'leads_per_month' => -1, 'bookings_per_month' => -1, 'team_members_limit' => -1,
                'has_live_chat' => true, 'workflow_automations_limit' => -1, 'has_ai_insights' => true,
                'has_custom_branding' => true, 'has_api_access' => true, 'has_priority_support' => true,
                'has_white_label' => true,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(['slug' => $plan['slug']], $plan);
        }
    }
}
