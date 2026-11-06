<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Lead;
use App\Models\ManualPayment;
use App\Models\Plan;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\Visitor;
use App\Models\Widget;
use App\Support\SafeUrl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class BillingAdminPrivacyTest extends TestCase
{
    use RefreshDatabase;

    private function plan(float $price = 79): Plan
    {
        return Plan::create(['name' => 'Pro', 'slug' => 'pro-' . uniqid(), 'price_monthly' => $price, 'price_yearly' => $price * 10, 'is_active' => true,
            'stripe_monthly_id' => 'price_x', 'stripe_yearly_id' => 'price_y', 'widgets_limit' => 3, 'messages_per_month' => 5000, 'training_sources_limit' => 10, 'leads_per_month' => 100, 'team_members_limit' => 1]);
    }

    private function setting(string $key, string $value): void
    {
        SystemSetting::updateOrCreate(['key' => $key], ['label' => $key, 'type' => 'string', 'group' => 'payment', 'value' => $value]);
        \Illuminate\Support\Facades\Cache::flush();
    }

    public function test_paid_plans_need_payment_and_bank_transfers_need_admin_approval(): void
    {
        config(['services.stripe.secret' => null]);
        $u = User::factory()->create(['role' => 'user']);
        $plan = $this->plan();

        // No payment method configured: nothing is activated for free.
        $this->actingAs($u)->post(route('settings.billing.changePlan'), ['plan_id' => $plan->id, 'billing_cycle' => 'monthly'])->assertSessionHas('error');
        $this->assertFalse($u->fresh()->hasActivePlan());

        // Free plan switches immediately.
        $free = $this->plan(0);
        $this->actingAs($u)->post(route('settings.billing.changePlan'), ['plan_id' => $free->id, 'billing_cycle' => 'monthly'])->assertSessionHas('success');
        $this->assertSame($free->id, $u->fresh()->currentPlan()->id);

        // Bank transfer: pending until the admin approves.
        $this->setting('bank_transfer_enabled', '1');
        $this->setting('bank_transfer_details', "Acme Ltd\nIBAN GB00 TEST");
        $this->actingAs($u)->post(route('settings.billing.changePlan'), ['plan_id' => $plan->id, 'billing_cycle' => 'monthly'])->assertRedirect(route('billing.bank', ['plan' => $plan->id, 'cycle' => 'monthly']));
        $this->actingAs($u)->get(route('billing.bank', ['plan' => $plan->id, 'cycle' => 'monthly']))->assertOk()->assertSee('IBAN GB00 TEST');
        $this->actingAs($u)->post(route('billing.bank.submit'), ['plan_id' => $plan->id, 'billing_cycle' => 'monthly', 'reference' => 'PFX-1'])->assertRedirect(route('settings.billing'));
        $this->assertSame($free->id, $u->fresh()->currentPlan()->id, 'Not upgraded before approval');

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get(route('admin.payments'))->assertOk()->assertSee('PFX-1');
        $this->actingAs($admin)->post(route('admin.payments.approve', ManualPayment::sole()))->assertRedirect();
        $this->assertSame($plan->id, $u->fresh()->currentPlan()->id);
        $this->assertTrue($u->fresh()->subscription->current_period_end->isAfter(now()->addDays(27)));
        $this->assertTrue(DB::table('activity_log')->where('event', 'payment.approved')->exists());

        // Customers cannot approve payments.
        $this->actingAs($u)->post(route('admin.payments.approve', ManualPayment::sole()))->assertForbidden();
    }

    public function test_stripe_webhook_rejects_unsigned_events_and_activates_signed_checkout(): void
    {
        $u = User::factory()->create();
        $plan = $this->plan();
        $payload = json_encode(['id' => 'evt_1', 'object' => 'event', 'type' => 'checkout.session.completed', 'data' => ['object' => [
            'object' => 'checkout.session', 'mode' => 'subscription', 'payment_status' => 'paid', 'client_reference_id' => (string) $u->id,
            'customer' => 'cus_1', 'subscription' => 'sub_1', 'metadata' => ['plan_id' => $plan->id, 'billing_cycle' => 'monthly'],
        ]]]);

        config(['services.stripe.webhook_secret' => null]);
        $this->call('POST', '/api/stripe/webhook', [], [], [], ['CONTENT_TYPE' => 'application/json'], $payload)->assertStatus(400);
        $this->assertFalse($u->fresh()->hasActivePlan());

        config(['services.stripe.webhook_secret' => 'whsec_test']);
        $t = time();
        $sig = 't=' . $t . ',v1=' . hash_hmac('sha256', $t . '.' . $payload, 'whsec_test');
        $this->call('POST', '/api/stripe/webhook', [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_STRIPE_SIGNATURE' => 'bad'], $payload)->assertStatus(400);
        $this->call('POST', '/api/stripe/webhook', [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_STRIPE_SIGNATURE' => $sig], $payload)->assertOk();
        $this->assertSame([$plan->id, 'cus_1'], [$u->fresh()->currentPlan()?->id, $u->fresh()->subscription->stripe_customer_id]);
    }

    public function test_admin_tools_health_monitor_search_and_changelog(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = $this->subscribed(User::factory()->create(['email' => 'owner@shop.test']));
        $w = Widget::create(['user_id' => $owner->id, 'name' => 'Shop', 'uuid' => (string) Str::uuid(), 'token' => Str::random(32), 'is_active' => true]);

        $this->call('POST', "/api/widget/{$w->uuid}/event", [], [], [], ['CONTENT_TYPE' => 'text/plain'], json_encode(['type' => 'reply_failed', 'message' => 'stream error', 'page_url' => 'https://shop.test']))->assertNoContent();
        $this->actingAs($admin)->get(route('admin.monitor'))->assertOk()->assertSee('reply_failed')->assertSee('stream error');
        $this->actingAs($admin)->get(route('admin.system'))->assertOk()->assertSee('Site health')->assertSee('Scheduler (cron)');
        $this->actingAs($admin)->get(route('admin.search', ['q' => 'owner@shop']))->assertOk()->assertSee('owner@shop.test');

        $this->actingAs($admin)->post(route('admin.changelog.store'), ['title' => 'Chat flows are here', 'tag' => 'new', 'body' => 'Build scripted chats.', 'publish' => '1'])->assertRedirect();
        $this->get(route('changelog'))->assertOk()->assertSee('Chat flows are here');
        $this->actingAs($owner)->get(route('dashboard'))->assertOk()->assertSee('Chat flows are here');
        $this->actingAs($owner)->post(route('changelog.seen'))->assertNoContent();
        $this->actingAs($owner)->get(route('dashboard'))->assertDontSee('Chat flows are here');

        $this->actingAs($owner)->get(route('admin.monitor'))->assertForbidden();
    }

    public function test_gdpr_export_and_erase_and_crawler_ssrf_guard(): void
    {
        $owner = $this->subscribed(User::factory()->create());
        $w = Widget::create(['user_id' => $owner->id, 'name' => 'Shop', 'uuid' => (string) Str::uuid(), 'token' => Str::random(32), 'is_active' => true]);
        $v = Visitor::create(['widget_id' => $w->id, 'fingerprint' => 'fp', 'email' => 'ana@x.test', 'name' => 'Ana']);
        $c = Conversation::create(['uuid' => (string) Str::uuid(), 'widget_id' => $w->id, 'visitor_id' => $v->id, 'user_id' => $owner->id, 'status' => 'ai_active']);
        $c->messages()->create(['role' => 'visitor', 'content' => 'My phone is 0700']);
        Lead::create(['user_id' => $owner->id, 'widget_id' => $w->id, 'email' => 'ana@x.test', 'status' => 'new']);

        $this->actingAs($this->subscribed(User::factory()->create()))->get(route('visitors.export', $v))->assertForbidden();
        $json = $this->actingAs($owner)->get(route('visitors.export', $v))->assertOk()->streamedContent();
        $this->assertStringContainsString('My phone is 0700', $json);
        $this->assertStringContainsString('ana@x.test', $json);

        $this->actingAs($owner)->delete(route('visitors.erase', $v))->assertRedirect(route('conversations.index'));
        $this->assertSame([0, 0, 0], [Visitor::count(), Conversation::count(), Lead::count()]);

        foreach (['http://127.0.0.1/x', 'http://localhost/', 'http://169.254.169.254/latest/meta-data', 'http://10.0.0.5/', 'file:///etc/passwd', 'https://[::1]/'] as $bad) {
            $this->assertFalse(SafeUrl::isPublic($bad), $bad);
        }
        $this->assertTrue(SafeUrl::isPublic('https://93.184.216.34/'));
    }
}
