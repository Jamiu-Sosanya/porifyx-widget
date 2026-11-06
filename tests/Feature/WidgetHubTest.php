<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Widget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class WidgetHubTest extends TestCase
{
    use RefreshDatabase;

    public function test_workspace_hub_and_answer_settings(): void
    {
        $u = $this->subscribed(User::factory()->create());
        $w = Widget::create(['user_id' => $u->id, 'name' => 'Shop', 'uuid' => (string) Str::uuid(), 'token' => Str::random(32), 'is_active' => true]);
        $this->actingAs($u)->get(route('widgets.show', $w))->assertOk()->assertSee('Setup progress')->assertSee('1/3')->assertSee('Optimise and grow')->assertSee($w->uuid);
        $this->actingAs($u)->get(route('widgets.playground', $w))->assertOk();
        $this->actingAs($u)->get(route('widgets.show', $w))->assertSee('2/3');

        $this->actingAs($u)->put(route('widgets.update', $w), ['name' => 'Shop', 'min_confidence' => '0.8', 'fallback_message' => 'Please email us for that.'])->assertSessionHasNoErrors();
        $this->assertEqualsWithDelta(0.8, (float) $w->fresh()->min_confidence, 0.001);
        $this->assertSame('Please email us for that.', $w->fresh()->fallback_message);
        $this->actingAs($u)->put(route('widgets.update', $w), ['name' => 'Shop', 'min_confidence' => '2'])->assertSessionHasErrors('min_confidence');
    }
}
