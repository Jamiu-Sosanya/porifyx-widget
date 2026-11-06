<?php

namespace Tests;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function createApplication(): Application
    {
        $base = dirname(__DIR__);
        $_ENV['APP_BASE_PATH'] = $base;
        $_SERVER['APP_BASE_PATH'] = $base;

        return parent::createApplication();
    }

    /** Give a user an active subscription. Default plan: everything unlimited. */
    protected function subscribed(User $user, array $plan = []): User
    {
        $p = Plan::create($plan + [
            'name' => 'Test', 'slug' => 'test-' . uniqid(), 'price_monthly' => 10, 'price_yearly' => 100,
            'widgets_limit' => -1, 'messages_per_month' => -1, 'training_sources_limit' => -1, 'leads_per_month' => -1,
            'bookings_per_month' => -1, 'team_members_limit' => -1, 'workflow_automations_limit' => -1,
            'has_live_chat' => true, 'has_ai_insights' => true, 'has_custom_branding' => true, 'has_api_access' => true,
        ]);
        Subscription::create(['user_id' => $user->id, 'plan_id' => $p->id, 'billing_cycle' => 'monthly', 'status' => 'active']);

        return $user->fresh();
    }
}
