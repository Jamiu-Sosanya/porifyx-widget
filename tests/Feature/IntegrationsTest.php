<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Widget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class IntegrationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_integrations_hub_shows_status_per_widget(): void
    {
        $u = $this->subscribed(User::factory()->create());
        $this->actingAs($u)->get(route('integrations.index'))->assertRedirect(route('widgets.create'));
        $w = Widget::create(['user_id' => $u->id, 'name' => 'Shop', 'uuid' => (string) Str::uuid(), 'token' => Str::random(32), 'is_active' => true, 'slack_webhook_url' => 'https://hooks.slack.com/x']);
        $this->actingAs($u)->get(route('integrations.index'))->assertOk()
            ->assertSee('HubSpot')->assertSee('Google Calendar')->assertSee('WooCommerce')->assertSee('MCP servers')->assertSee('1 connected');
    }
}
