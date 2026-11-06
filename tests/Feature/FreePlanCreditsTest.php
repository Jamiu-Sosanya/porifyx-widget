<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FreePlanCreditsTest extends TestCase
{
    use RefreshDatabase;

    public function test_free_plan_keeps_widgets_answering_and_credits_extend_the_allowance(): void
    {
        $this->seed(PlanSeeder::class);
        $u = User::factory()->create(['created_at' => now()->subDays(30)]);

        $this->assertFalse($u->hasActivePlan());
        $this->assertSame('free', $u->currentPlan()->slug);
        $this->assertTrue($u->canUse('messages'));
        $this->assertFalse($u->hasFeature('custom_branding'));
        $this->actingAs($u)->get(route('dashboard'))->assertOk();
        $this->actingAs($u)->get(route('settings.billing'))->assertOk()->assertSee('AI reply credits');

        $u->incrementUsage('messages', 100);
        $this->assertFalse($u->canUse('messages'));
        $u->update(['ai_credits' => 2]);
        $this->assertTrue($u->canUse('messages'));
        $u->incrementUsage('messages');
        $u->incrementUsage('messages');
        $this->assertSame(0, $u->fresh()->ai_credits);
        $this->assertFalse($u->fresh()->canUse('messages'));

        $this->get('/')->assertOk()->assertSee('Start free. Pay less as you grow.')->assertSee('$19');
    }
}
