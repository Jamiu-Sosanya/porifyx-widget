<?php

namespace Tests\Feature;

use App\Models\ChatFlow;
use App\Models\Conversation;
use App\Models\TrainingSource;
use App\Models\User;
use App\Models\Widget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class FlowsAndSyncTest extends TestCase
{
    use RefreshDatabase;

    private function widget(array $plan = []): Widget
    {
        $user = $this->subscribed(User::factory()->create(), $plan);

        return Widget::create(['user_id' => $user->id, 'name' => 'Shop', 'uuid' => (string) Str::uuid(), 'token' => Str::random(32), 'is_active' => true, 'message_streaming' => false]);
    }

    private function quoteFlow(Widget $w): ChatFlow
    {
        $this->actingAs($w->user)->post(route('widgets.flows.save', $w), [
            'name' => 'Quote', 'trigger_keywords' => 'quote, estimate', 'match_mode' => 'any', 'is_active' => '1',
            'steps' => [
                ['type' => 'question', 'text' => 'What is your name?', 'var' => 'name'],
                ['type' => 'question', 'text' => 'Thanks {{name}}! Budget?', 'var' => 'budget', 'choices' => 'Small, Large'],
                ['type' => 'branch', 'var' => 'budget', 'op' => 'equals', 'value' => 'large', 'goto' => '6'],
                ['type' => 'message', 'text' => 'Great, here is our starter pack.'],
                ['type' => 'tag', 'tag' => 'small-budget'],
                ['type' => 'tag', 'tag' => 'quote'],
                ['type' => 'escalate', 'text' => 'Connecting you to sales, {{name}}.'],
            ],
        ])->assertRedirect()->assertSessionHasNoErrors();

        return ChatFlow::sole();
    }

    public function test_flow_runs_inside_the_chat_without_the_ai(): void
    {
        $w = $this->widget();
        $this->quoteFlow($w);
        $conv = $this->postJson("/api/widget/{$w->uuid}/conversations", ['fingerprint' => 'fp1'])->json('conversation_uuid');
        $say = fn ($t) => $this->postJson("/api/widget/{$w->uuid}/conversations/{$conv}/messages", ['content' => $t])->assertOk()->json('ai_response');

        $this->assertSame('What is your name?', $say('Can I get a quote?')['content']);
        $r = $say('Ada');
        $this->assertSame('Thanks Ada! Budget?', $r['content']);
        $this->assertSame(['Small', 'Large'], $r['smart_suggestions']);
        $this->assertSame('Connecting you to sales, Ada.', $say('Large')['content']); // branch jumps over the small-budget steps

        $c = Conversation::where('uuid', $conv)->first();
        $this->assertSame(['quote'], $c->tags);
        $this->assertSame('open', $c->status);
        $this->assertNull($c->context_data['flow']);
        $this->assertSame(1, ChatFlow::sole()->runs);
        $this->assertSame(0, $w->user->getUsageThisMonth('messages'), 'Scripted replies do not use AI messages');
    }

    public function test_builder_validation_dry_run_and_loop_guard(): void
    {
        $w = $this->widget();
        $this->actingAs($w->user)->post(route('widgets.flows.save', $w), [
            'name' => 'Bad', 'trigger_keywords' => 'x', 'match_mode' => 'any',
            'steps' => [['type' => 'question', 'text' => 'Q?'], ['type' => 'branch', 'var' => 'a', 'op' => 'equals', 'goto' => '9'], ['type' => 'webhook', 'url' => 'https://127.0.0.1/hook']],
        ])->assertSessionHasErrors(['steps.0.var', 'steps.1.goto', 'steps.2.url']);

        $this->quoteFlow($w);
        $r = $this->actingAs($w->user)->postJson(route('widgets.flows.test', $w), ['message' => 'estimate please'])->assertOk()->json();
        $this->assertSame('What is your name?', $r['reply']);
        $this->assertSame(0, ChatFlow::sole()->runs, 'Dry runs are not counted');
        $this->actingAs($w->user)->postJson(route('widgets.flows.test', $w), ['message' => 'hello'])->assertJsonPath('reply', null);

        // A branch that loops onto itself stops after the guard.
        $loop = ChatFlow::create(['widget_id' => $w->id, 'name' => 'Loop', 'trigger_keywords' => 'loop', 'steps' => [['type' => 'branch', 'var' => 'v', 'op' => 'is_empty', 'goto' => 1]]]);
        $out = app(\App\Services\FlowEngine::class)->run($loop, 0, []);
        $this->assertSame('', $out['reply']);
    }

    public function test_wordpress_sync_api_and_plugin_download(): void
    {
        Queue::fake();
        $w = $this->widget(['training_sources_limit' => 1]);
        $h = ['X-Widget-Token' => $w->token];
        $page = ['url' => 'https://shop.test/delivery', 'title' => 'Delivery', 'content' => 'We ship in 2 days.'];

        $this->getJson("/api/sync/{$w->uuid}/ping", ['X-Widget-Token' => 'wrong'])->assertStatus(401);
        $this->getJson("/api/sync/{$w->uuid}/ping", $h)->assertOk()->assertJsonPath('widget', 'Shop');
        $this->postJson("/api/sync/{$w->uuid}/pages", $page, $h)->assertOk();
        $this->postJson("/api/sync/{$w->uuid}/pages", ['content' => 'Now 1 day.'] + $page, $h)->assertOk()->assertJsonPath('updated', true);
        $this->assertSame('Now 1 day.', TrainingSource::sole()->content);
        Queue::assertPushed(\App\Jobs\ProcessTrainingSource::class, 2);

        $this->postJson("/api/sync/{$w->uuid}/pages", ['url' => 'https://shop.test/other'] + $page, $h)->assertStatus(402); // plan limit
        $this->deleteJson("/api/sync/{$w->uuid}/pages", ['url' => $page['url']], $h)->assertOk();
        $this->assertSame(0, TrainingSource::count());

        $res = $this->actingAs($w->user)->get(route('widgets.wordpress', $w))->assertOk();
        $this->assertStringContainsString('porifyx-widget-wordpress.zip', $res->headers->get('content-disposition'));
    }
}
