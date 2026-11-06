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

        // Admin gives credits; bank transfer for a credit pack is added after approval
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->post(route('admin.users.credits', $u), ['credits' => 500])->assertSessionHas('success');
        $this->assertSame(500, $u->fresh()->ai_credits);
        foreach (['bank_transfer_enabled' => '1', 'bank_transfer_details' => 'Acme IBAN'] as $k => $v) {
            \App\Models\SystemSetting::updateOrCreate(['key' => $k], ['label' => $k, 'type' => 'string', 'group' => 'payment', 'value' => $v]);
        }
        \Illuminate\Support\Facades\Cache::flush();
        $this->actingAs($u)->get(route('billing.bank', ['pack' => 's']))->assertOk()->assertSee('1,000 AI reply credits');
        $this->actingAs($u)->post(route('billing.bank.submit'), ['pack' => 's', 'reference' => 'PFX-CR'])->assertSessionHas('success');
        $pay = \App\Models\ManualPayment::firstOrFail();
        $this->actingAs($admin)->post(route('admin.payments.approve', $pay))->assertSessionHas('success');
        $this->assertSame(1500, $u->fresh()->ai_credits);
        $this->assertNull($u->fresh()->subscription);

        $this->get('/')->assertOk()->assertSee('Start free. Pay less as you grow.')->assertSee('$19');
    }
}
