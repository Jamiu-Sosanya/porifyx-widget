<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\User;
use App\Models\Visitor;
use App\Models\Widget;
use App\Services\AiChatService;
use App\Services\MultiProviderAiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class AiToolsTest extends TestCase
{
    use RefreshDatabase;

    public array $replies = [];
    public array $seen = [];

    private function setUpChat(array $plan = []): array
    {
        $u = $this->subscribed(User::factory()->create(), $plan);
        $w = Widget::create(['user_id' => $u->id, 'name' => 'Shop', 'uuid' => (string) Str::uuid(), 'token' => 'secret-token', 'is_active' => true]);
        $v = Visitor::create(['widget_id' => $w->id, 'fingerprint' => 'fp']);
        $c = Conversation::create(['uuid' => (string) Str::uuid(), 'widget_id' => $w->id, 'visitor_id' => $v->id, 'user_id' => $u->id, 'status' => 'ai_active']);
        $t = $this;
        $this->app->instance(MultiProviderAiService::class, new class($t) extends MultiProviderAiService {
            public function __construct(private $t) {}
            public function chat(Widget $widget, array $messages, int $maxTokens = 512): array
            {
                $this->t->seen[] = $messages;
                return ['content' => array_shift($this->t->replies) ?? 'OK', 'provider' => 'fake', 'model' => 'fake'];
            }
        });

        return [$w, $c];
    }

    public function test_woocommerce_order_lookup_runs_signed_and_answers_with_result(): void
    {
        [$w, $c] = $this->setUpChat();
        $this->actingAs($w->user)->put(route('widgets.tools.woo', $w), ['url' => 'https://93.184.216.34', 'orders' => '1'])->assertSessionHasNoErrors();
        Http::fake(['93.184.216.34/wp-json/porifyx/v1/order' => Http::response(['found' => true, 'number' => '991', 'status' => 'Shipped', 'tracking' => 'RM123'])]);
        $this->replies = ['[[TOOL woo_order_status {"order_number": "991", "email": "Jo@x.test"}]]', 'Your order 991 has shipped. Tracking: RM123.'];

        $r = app(AiChatService::class)->generateResponse($c, 'Where is order 991? jo@x.test');

        $this->assertSame('Your order 991 has shipped. Tracking: RM123.', $r['content']);
        $this->assertStringContainsString('woo_order_status', $this->seen[0][0]['content'], 'Tool is described in the system prompt');
        $this->assertStringContainsString('"status":"Shipped"', end($this->seen[1])['content']);
        Http::assertSent(function ($req) {
            $d = $req->data();
            return $d['email'] === 'jo@x.test' && hash_equals(hash_hmac('sha256', 'order|991|jo@x.test|' . $d['ts'], 'secret-token'), $d['sig']);
        });
        $this->assertTrue(DB::table('tool_calls')->where('tool', 'woo_order_status')->where('ok', true)->exists());
    }

    public function test_tools_that_are_not_switched_on_never_run_and_syntax_is_hidden(): void
    {
        [$w, $c] = $this->setUpChat();
        Http::fake();
        $this->replies = ['[[TOOL woo_coupon {"email": "a@b.test"}]]', 'Sorry, I cannot do that.'];
        $r = app(AiChatService::class)->generateResponse($c, 'discount please');
        $this->assertSame('Sorry, I cannot do that.', $r['content']);
        Http::assertNothingSent();
        $this->assertSame(0, DB::table('tool_calls')->where('ok', true)->count());
        $this->assertStringContainsString('Tool not available', end($this->seen[1])['content']);
    }

    public function test_mcp_server_tools_are_discovered_allowed_and_called(): void
    {
        [$w, $c] = $this->setUpChat();
        Http::fake(function ($req) {
            $m = $req->data()['method'] ?? '';
            return match ($m) {
                'initialize' => Http::response(['jsonrpc' => '2.0', 'id' => 1, 'result' => ['protocolVersion' => '2025-03-26']], 200, ['Mcp-Session-Id' => 'S1']),
                'tools/list' => Http::response("event: message\ndata: " . json_encode(['jsonrpc' => '2.0', 'id' => 2, 'result' => ['tools' => [['name' => 'crm_lookup', 'description' => 'Find a customer', 'inputSchema' => ['properties' => ['email' => ['type' => 'string']]]]]]]) . "\n\n", 200, ['Content-Type' => 'text/event-stream']),
                'tools/call' => Http::response(['jsonrpc' => '2.0', 'id' => 2, 'result' => ['content' => [['type' => 'text', 'text' => 'Customer since 2021, plan Gold']]]]),
                default => Http::response('', 202),
            };
        });
        $this->actingAs($w->user)->post(route('widgets.tools.mcp', $w), ['name' => 'CRM', 'url' => 'https://93.184.216.34/mcp', 'auth' => 'Bearer x'])->assertSessionHasNoErrors();
        $srv = \App\Support\AiTools::settings($w->fresh())['mcp'][0];
        $this->assertFalse($srv['tools'][0]['enabled'], 'Discovered tools start switched off');
        $this->actingAs($w->user)->put(route('widgets.tools.mcp.save', [$w, $srv['id']]), ['enabled' => ['crm_lookup']])->assertRedirect();

        $this->replies = ['[[TOOL mcp__' . $srv['id'] . '__crm_lookup {"email": "a@b.test"}]]', 'You have been with us since 2021 on Gold.'];
        $r = app(AiChatService::class)->generateResponse($c->fresh(), 'am I a customer? a@b.test');
        $this->assertSame('You have been with us since 2021 on Gold.', $r['content']);
        Http::assertSent(fn ($req) => ($req->data()['method'] ?? '') === 'tools/call' && $req->header('Mcp-Session-Id')[0] === 'S1' && $req->header('Authorization')[0] === 'Bearer x');
        $this->actingAs($w->user)->get(route('widgets.tools', $w))->assertOk()->assertSee('crm_lookup');
    }

    public function test_mcp_needs_a_plan_with_integrations(): void
    {
        [$w] = $this->setUpChat(['has_api_access' => false]);
        $this->actingAs($w->user)->post(route('widgets.tools.mcp', $w), ['name' => 'X', 'url' => 'https://93.184.216.34/mcp'])->assertForbidden();
    }
}
