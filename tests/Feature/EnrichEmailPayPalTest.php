<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\InboundEmailController;
use App\Mail\TicketMail;
use App\Models\Lead;
use App\Models\Plan;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Widget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class EnrichEmailPayPalTest extends TestCase
{
    use RefreshDatabase;

    public function test_business_email_leads_get_company_details(): void
    {
        $html = '<html><head><title>Acme Rockets | Reusable launch</title><meta name="description" content="We build reusable rockets."><meta property="og:site_name" content="Acme Rockets"></head><body><a href="https://www.linkedin.com/company/acme-rockets">in</a></body></html>';
        $parsed = (new \App\Services\LeadEnrichmentService)->parse($html, 'acme-rockets.test');
        $this->assertSame('Acme Rockets', $parsed['company']);
        $this->assertSame(['https://www.linkedin.com/company/acme-rockets'], $parsed['socials']);

        $svc = $this->partialMock(\App\Services\LeadEnrichmentService::class);
        $svc->shouldReceive('company')->with('acme-rockets.test')->andReturn($parsed);
        $u = $this->subscribed(User::factory()->create());
        $w = Widget::create(['user_id' => $u->id, 'name' => 'S', 'uuid' => (string) Str::uuid(), 'token' => Str::random(32), 'is_active' => true]);
        $lead = Lead::create(['user_id' => $u->id, 'widget_id' => $w->id, 'email' => 'ceo@acme-rockets.test', 'status' => 'new']);
        $svc->enrich($lead->fresh());
        $lead->refresh();
        $this->assertSame(['business', 'Acme Rockets', 'https://acme-rockets.test'], [$lead->enrichment['type'], $lead->company, $lead->website]);

        $p = Lead::create(['user_id' => $u->id, 'widget_id' => $w->id, 'email' => 'ana@gmail.com', 'status' => 'new']);
        (new \App\Services\LeadEnrichmentService)->enrich($p);
        $this->assertSame('personal', $p->fresh()->enrichment['type']);
        $this->assertNull($p->fresh()->company);
        $this->actingAs($u)->get(route('leads.show', $lead))->assertOk()->assertSee('Business email');
    }

    public function test_inbound_email_opens_and_threads_tickets(): void
    {
        Mail::fake();
        $u = $this->subscribed(User::factory()->create(['email' => 'owner@shop.test']));
        $url = InboundEmailController::url($u);

        $this->post(str_replace(url('/'), '', $url) . 'x', ['sender' => 'a@b.test', 'subject' => 'Hi', 'body-plain' => 'x'])->assertNotFound();

        // Postmark JSON
        $this->postJson(str_replace(url('/'), '', $url), ['FromFull' => ['Email' => 'Jo@Cust.test', 'Name' => 'Jo'], 'Subject' => 'Where is my order?', 'TextBody' => 'Order 991 not here', 'StrippedTextReply' => ''])
            ->assertOk()->assertJsonPath('ticket', 'TK-' . (1000 + Ticket::max('id')));
        $t = Ticket::sole();
        $this->assertSame(['jo@cust.test', 'Jo', 'Where is my order?'], [$t->requester_email, $t->requester_name, $t->subject]);
        Mail::assertSent(TicketMail::class, fn ($m) => $m->kind === 'created' && $m->hasTo('jo@cust.test'));

        // Mailgun reply with the ticket number in the subject threads into the same ticket.
        $t->update(['status' => 'pending']);
        $this->post(str_replace(url('/'), '', $url), ['sender' => 'jo@cust.test', 'subject' => 'Re: Where is my order? [' . $t->number . ']', 'stripped-text' => 'Any update?'])->assertOk();
        $this->assertSame(1, Ticket::count());
        $this->assertSame('open', $t->fresh()->status);
        $this->assertTrue($t->messages()->where('body', 'Any update?')->exists());

        // Owner's own address is ignored (no loops).
        $this->post(str_replace(url('/'), '', $url), ['sender' => 'owner@shop.test', 'subject' => 'test', 'body-plain' => 'x'])->assertJsonPath('ignored', true);
    }

    public function test_paypal_activates_only_after_paypal_confirms(): void
    {
        config(['services.paypal.client_id' => 'cid', 'services.paypal.secret' => 'sec', 'services.paypal.mode' => 'sandbox', 'services.paypal.webhook_id' => 'WH-1']);
        $u = User::factory()->create();
        $plan = Plan::create(['name' => 'Pro', 'slug' => 'pro-pp', 'price_monthly' => 79, 'price_yearly' => 790, 'is_active' => true, 'paypal_monthly_id' => 'P-MONTH', 'widgets_limit' => 1, 'messages_per_month' => 100, 'training_sources_limit' => 1, 'leads_per_month' => 1, 'team_members_limit' => 1]);
        Http::fake([
            '*/v1/oauth2/token' => Http::response(['access_token' => 'tok']),
            '*/v1/billing/subscriptions/I-OK' => Http::response(['id' => 'I-OK', 'status' => 'ACTIVE', 'custom_id' => (string) $u->id]),
            '*/v1/billing/subscriptions/I-OTHER' => Http::response(['id' => 'I-OTHER', 'status' => 'ACTIVE', 'custom_id' => '999']),
            '*/v1/billing/subscriptions' => Http::response(['id' => 'I-NEW', 'links' => [['rel' => 'approve', 'href' => 'https://www.sandbox.paypal.com/approve/I-NEW']]]),
            '*/v1/notifications/verify-webhook-signature' => Http::sequence()->push(['verification_status' => 'FAILURE'])->push(['verification_status' => 'SUCCESS']),
        ]);

        $this->actingAs($u)->post(route('billing.paypal'), ['plan_id' => $plan->id, 'billing_cycle' => 'monthly'])->assertRedirect('https://www.sandbox.paypal.com/approve/I-NEW');
        $this->actingAs($u)->get(route('billing.paypal.return', ['plan' => $plan->id, 'cycle' => 'monthly', 'subscription_id' => 'I-OTHER']))->assertSessionHas('error');
        $this->assertFalse($u->fresh()->hasActivePlan(), 'Someone else\'s PayPal subscription must not activate this account');
        $this->actingAs($u)->get(route('billing.paypal.return', ['plan' => $plan->id, 'cycle' => 'monthly', 'subscription_id' => 'I-OK']))->assertRedirect();
        $this->assertSame([$plan->id, 'I-OK'], [$u->fresh()->currentPlan()->id, $u->fresh()->subscription->paypal_subscription_id]);

        $evt = ['event_type' => 'BILLING.SUBSCRIPTION.CANCELLED', 'resource' => ['id' => 'I-OK']];
        $this->postJson('/api/paypal/webhook', $evt)->assertStatus(400); // signature not verified
        $this->postJson('/api/paypal/webhook', $evt)->assertOk();
        $this->assertSame('canceled', $u->fresh()->subscription->status);
    }
}
