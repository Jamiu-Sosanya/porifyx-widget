<?php

namespace Tests\Feature;

use App\Models\Automation;
use App\Models\AutomationLog;
use App\Models\Conversation;
use App\Models\Lead;
use App\Models\User;
use App\Models\Widget;
use App\Services\AutomationRunner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class WorkflowsAndInboxTest extends TestCase
{
    use RefreshDatabase;

    private function widget(User $u): Widget
    {
        return Widget::create(['user_id' => $u->id, 'name' => 'Shop', 'uuid' => (string) Str::uuid(), 'token' => Str::random(32), 'is_active' => true]);
    }

    public function test_automations_fire_and_can_be_tested_and_duplicated(): void
    {
        Mail::fake();
        $u = $this->subscribed(User::factory()->create());
        $w = $this->widget($u);
        $lead = Lead::create(['user_id' => $u->id, 'widget_id' => $w->id, 'name' => 'Ann', 'email' => 'ann@example.com']);
        $tag = Automation::create(['user_id' => $u->id, 'name' => 'Tag', 'is_active' => true, 'trigger' => 'new_lead', 'action' => 'tag_lead', 'action_config' => ['tags' => 'vip, new']]);
        $kw = Automation::create(['user_id' => $u->id, 'name' => 'Angry', 'is_active' => true, 'trigger' => 'keyword_detected', 'trigger_conditions' => ['keywords' => 'refund, angry'], 'action' => 'send_email', 'action_config' => ['email' => 'boss@example.com']]);

        AutomationRunner::fire('new_lead', $w, ['lead' => $lead]);
        $this->assertSame(['vip', 'new'], $lead->fresh()->tags);
        AutomationRunner::fire('keyword_detected', $w, ['message' => 'hello there']);
        $this->assertSame(0, AutomationLog::where('automation_id', $kw->id)->count());
        AutomationRunner::fire('keyword_detected', $w, ['message' => 'I want a REFUND now']);
        $this->assertSame(1, AutomationLog::where('automation_id', $kw->id)->where('status', 'success')->count());

        $this->actingAs($u)->post(route('automations.test', $tag))->assertSessionHas('success');
        $this->actingAs($u)->post(route('automations.duplicate', $tag))->assertRedirect();
        $this->assertFalse(Automation::where('name', 'Tag (copy)')->firstOrFail()->is_active);
        $this->actingAs($u)->get(route('automations.index'))->assertOk()->assertSee('Start from a template')->assertSee('Recent runs');
        $this->actingAs($u)->get(route('automations.create', ['trigger' => 'hot_lead', 'name' => 'Hot lead → email sales']))->assertOk()->assertSee('Hot lead → email sales');
    }

    public function test_inbox_quick_views_cleanup_and_dashboard(): void
    {
        $u = $this->subscribed(User::factory()->create());
        $w = $this->widget($u);
        $v = \App\Models\Visitor::create(['widget_id' => $w->id, 'fingerprint' => 'f']);
        $mk = fn ($s, $n, $age = 0) => Conversation::forceCreate(['uuid' => (string) Str::uuid(), 'widget_id' => $w->id, 'user_id' => $u->id, 'visitor_id' => $v->id, 'status' => $s, 'message_count' => $n, 'last_message_at' => now()->subMinutes($age), 'created_at' => now()->subHours($age ? 2 : 0)]);
        $mk('open', 4); $mk('ai_active', 6); $empty = $mk('ai_active', 1, 180);

        $this->actingAs($u)->get(route('conversations.index'))->assertOk()->assertSee('Needs human')->assertSee('Delete 1 empty');
        $this->actingAs($u)->get(route('conversations.index', ['view' => 'needs_human']))->assertOk();
        $this->actingAs($u)->post(route('conversations.cleanup'))->assertSessionHas('success');
        $this->assertNull(Conversation::find($empty->id));
        $this->assertSame(2, Conversation::count());

        $this->actingAs($u)->get(route('dashboard'))->assertOk()->assertSee('Lead pipeline')->assertSee('Knowledge health');
    }
}
