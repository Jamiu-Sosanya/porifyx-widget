<?php

namespace Tests\Feature;

use App\Mail\TicketMail;
use App\Models\Conversation;
use App\Models\HelpArticle;
use App\Models\SystemSetting;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Visitor;
use App\Models\Widget;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class SupportTest extends TestCase
{
    use RefreshDatabase;

    private function widget(): Widget
    {
        $user = $this->subscribed(User::factory()->create());

        return Widget::create(['user_id' => $user->id, 'name' => 'Shop', 'uuid' => (string) Str::uuid(), 'token' => Str::random(32), 'is_active' => true, 'show_handoff_button' => true]);
    }

    public function test_ticket_lifecycle_from_chat_to_customer_reply_merge_and_escalation(): void
    {
        Mail::fake();
        $w = $this->widget();
        $u = $w->user;
        $v = Visitor::create(['widget_id' => $w->id, 'fingerprint' => 'fp', 'email' => 'cust@x.test', 'name' => 'Cus']);
        $c = Conversation::create(['uuid' => (string) Str::uuid(), 'widget_id' => $w->id, 'visitor_id' => $v->id, 'user_id' => $u->id, 'status' => 'ai_active']);
        $c->messages()->create(['role' => 'visitor', 'content' => 'My order arrived broken']);

        $this->actingAs($u)->get(route('tickets.create', ['conversation' => $c->uuid]))->assertOk()->assertSee('cust@x.test')->assertSee('My order arrived broken');
        $this->actingAs($u)->post(route('tickets.store'), [
            'subject' => 'Broken order', 'requester_email' => 'cust@x.test', 'message' => 'My order arrived broken', 'priority' => 'normal', 'conversation_uuid' => $c->uuid, 'notify' => '1',
        ])->assertRedirect();
        $t = Ticket::sole();
        $this->assertSame([$c->id, $w->id], [$t->conversation_id, $t->widget_id]);
        Mail::assertSent(TicketMail::class, fn ($m) => $m->kind === 'created' && $m->hasTo('cust@x.test'));

        $this->actingAs($u)->post(route('tickets.reply', $t), ['type' => 'note', 'body' => 'Check stock'])->assertRedirect();
        $this->actingAs($u)->post(route('tickets.reply', $t), ['type' => 'reply', 'body' => 'Sorry! A replacement is on the way.'])->assertRedirect();
        Mail::assertSent(TicketMail::class, fn ($m) => $m->kind === 'reply' && $m->body === 'Sorry! A replacement is on the way.');
        $this->assertSame('pending', $t->fresh()->status);

        // Customer page: sees replies, never internal notes; wrong token is 404.
        $this->get($t->publicUrl())->assertOk()->assertSee('A replacement is on the way')->assertDontSee('Check stock');
        $this->get(route('tickets.public', [$t->id, 'bad-token']))->assertNotFound();
        $this->post(route('tickets.public.reply', [$t->id, $t->publicToken()]), ['body' => 'Thanks, when?'])->assertRedirect();
        $this->assertSame('open', $t->fresh()->status);
        Mail::assertSent(TicketMail::class, fn ($m) => $m->kind === 'owner_customer' && $m->hasTo($u->email));

        // Assign + priority, then merge a duplicate.
        $this->actingAs($u)->put(route('tickets.update', $t), ['status' => 'open', 'priority' => 'high', 'assigned_to' => $u->id])->assertRedirect();
        $dup = Ticket::create(['user_id' => $u->id, 'subject' => 'Dup', 'requester_email' => 'cust@x.test', 'status' => 'open', 'priority' => 'normal']);
        $dup->log('customer', 'Same issue again');
        $this->actingAs($u)->post(route('tickets.merge', $dup), ['into' => $t->number])->assertRedirect(route('tickets.show', $t));
        $this->assertSame([$t->id, 'closed'], [$dup->fresh()->merged_into_id, $dup->fresh()->status]);
        $this->assertTrue($t->messages()->where('body', 'Same issue again')->exists());

        // Escalation: customer waiting longer than the owner's setting.
        $u->update(['support_settings' => ['escalate_hours' => 2]]);
        $this->travelTo(CarbonImmutable::now()->addHours(3));
        $this->artisan('tickets:escalate')->expectsOutput('Escalated 1 ticket(s).')->assertSuccessful();
        $this->assertSame('urgent', $t->fresh()->priority);
        $this->assertNotNull($t->fresh()->escalated_at);
        Mail::assertSent(TicketMail::class, fn ($m) => $m->kind === 'owner_escalated');

        // Another user cannot see it.
        $this->actingAs($this->subscribed(User::factory()->create()))->get(route('tickets.show', $t))->assertForbidden();
        $this->actingAs($u)->get(route('tickets.index'))->assertOk()->assertSee($t->number);
    }

    public function test_help_centre_publishes_safe_markdown_with_search_and_categories(): void
    {
        $w = $this->widget();
        $this->actingAs($w->user)->post(route('widgets.help.categories.store', $w), ['name' => 'Billing', 'icon' => '💳'])->assertRedirect();
        $cat = \App\Models\HelpCategory::sole();
        $this->actingAs($w->user)->post(route('widgets.help.articles.save', $w), [
            'title' => 'How refunds work', 'help_category_id' => $cat->id, 'is_published' => '1',
            'body' => "## Refunds\nWithin **30 days**. <script>alert(1)</script> [bad](javascript:alert(1))",
        ])->assertRedirect();
        $this->actingAs($w->user)->post(route('widgets.help.articles.save', $w), ['title' => 'Draft note', 'body' => 'secret', 'help_category_id' => $cat->id])->assertRedirect();
        $a = HelpArticle::where('title', 'How refunds work')->sole();

        $this->get(route('help.index', $w->uuid))->assertOk()->assertSee('Billing')->assertSee('How refunds work');
        $this->get(route('help.index', [$w->uuid, 'q' => 'refunds']))->assertOk()->assertSee('1 result');
        $this->get(route('help.category', [$w->uuid, $cat->slug]))->assertOk()->assertSee('How refunds work')->assertDontSee('Draft note');
        $page = $this->get(route('help.article', [$w->uuid, $a->slug]))->assertOk()->assertSee('<strong>30 days</strong>', false);
        $this->assertStringNotContainsString('<script>alert(1)</script>', $page->getContent());
        $this->assertStringNotContainsString('href="javascript:', $page->getContent());
        $this->get(route('help.article', [$w->uuid, 'draft-note']))->assertNotFound();
        $this->assertSame(1, $a->fresh()->views);
    }

    public function test_tags_business_hours_and_invite_only(): void
    {
        $w = $this->widget();
        $v = Visitor::create(['widget_id' => $w->id, 'fingerprint' => 'fp2']);
        $c = Conversation::create(['uuid' => (string) Str::uuid(), 'widget_id' => $w->id, 'visitor_id' => $v->id, 'user_id' => $w->user_id, 'status' => 'ai_active']);
        $this->actingAs($w->user)->put(route('conversations.tags', $c), ['tags' => 'billing, VIP, billing, '])->assertRedirect();
        $this->assertSame(['billing', 'VIP'], $c->fresh()->tags);
        $this->actingAs($w->user)->get(route('conversations.index', ['tag' => 'VIP']))->assertOk()->assertSee('#VIP');

        // Business hours: Mon-Fri 09-17 London; Saturday = away.
        $w->update(['engagement' => ['hours' => ['enabled' => true, 'timezone' => 'Europe/London', 'days' => ['mon' => ['09:00', '17:00'], 'sat' => null], 'away_text' => 'Back Monday']]]);
        $this->travelTo(CarbonImmutable::parse('2026-11-09 10:00', 'Europe/London')); // Monday
        $this->getJson("/api/widget/{$w->uuid}/config")->assertJsonPath('widget.show_handoff_button', true)->assertJsonPath('widget.team_away_text', null);
        $this->travelTo(CarbonImmutable::parse('2026-11-14 10:00', 'Europe/London')); // Saturday
        $this->getJson("/api/widget/{$w->uuid}/config")->assertJsonPath('widget.show_handoff_button', false)->assertJsonPath('widget.team_away_text', 'Back Monday');

        // Invite-only
        auth()->logout();
        $this->get(route('register'))->assertOk();
        SystemSetting::updateOrCreate(['key' => 'allow_registration'], ['label' => 'x', 'type' => 'boolean', 'group' => 'features', 'value' => '0']);
        \Illuminate\Support\Facades\Cache::flush();
        $this->get(route('register'))->assertForbidden();
        $this->post(route('register'), ['name' => 'X', 'email' => 'x@x.test', 'password' => 'Password123!', 'password_confirmation' => 'Password123!'])->assertForbidden();
    }
}
