<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Widget;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PlanGatingTest extends TestCase
{
    use RefreshDatabase;

    private function widget(?array $plan = [], bool $active = true): Widget
    {
        $user = User::factory()->create();
        if ($plan !== null) $this->subscribed($user, $plan);

        return Widget::create([
            'user_id' => $user->id, 'name' => 'Shop', 'uuid' => (string) Str::uuid(), 'token' => Str::random(32), 'is_active' => $active,
            'show_handoff_button' => true, 'show_branding' => false,
            'booking' => ['enabled' => true, 'timezone' => 'UTC', 'min_notice' => 0, 'slot_step' => 60, 'buffer' => 0,
                'services' => [['id' => 'a', 'name' => 'A', 'duration' => 60, 'price' => '', 'description' => '']],
                'hours' => array_fill_keys(['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'], [['09:00', '12:00']])],
        ]);
    }

    public function test_full_plan_gets_everything(): void
    {
        $w = $this->widget();
        $this->getJson("/api/widget/{$w->uuid}/config")->assertOk()
            ->assertJsonPath('widget.service_available', true)
            ->assertJsonPath('widget.show_handoff_button', true)
            ->assertJsonPath('widget.show_branding', false)
            ->assertJsonPath('widget.booking.label', fn ($v) => is_string($v));
    }

    public function test_no_subscription_shows_message_form_and_hides_paid_features(): void
    {
        $w = $this->widget(null);
        $this->getJson("/api/widget/{$w->uuid}/config")->assertOk()
            ->assertJsonPath('widget.service_available', false)
            ->assertJsonPath('widget.offline_form_enabled', true)
            ->assertJsonPath('widget.show_handoff_button', false)
            ->assertJsonPath('widget.show_branding', true)
            ->assertJsonPath('widget.booking', null);

        $conv = $this->postJson("/api/widget/{$w->uuid}/conversations", ['fingerprint' => 'fp1'])->json('conversation_uuid');
        $this->postJson("/api/widget/{$w->uuid}/conversations/{$conv}/messages", ['content' => 'hi'])
            ->assertStatus(503)->assertJsonPath('code', 'service_unavailable');
        // Visitors can still leave a message.
        $this->postJson("/api/widget/{$w->uuid}/offline", ['email' => 'a@b.co', 'message' => 'Call me'])->assertSuccessful();
    }

    public function test_expired_trial_counts_as_inactive(): void
    {
        $w = $this->widget();
        $w->user->subscription->update(['status' => 'trialing', 'trial_ends_at' => now()->subDay()]);
        $this->getJson("/api/widget/{$w->uuid}/config")->assertJsonPath('widget.service_available', false);
    }

    public function test_monthly_limits_close_each_feature(): void
    {
        $w = $this->widget(['messages_per_month' => 2, 'bookings_per_month' => 1, 'has_live_chat' => false]);
        $w->user->incrementUsage('messages', 2);
        $this->getJson("/api/widget/{$w->uuid}/config")->assertJsonPath('widget.service_available', false)
            ->assertJsonPath('widget.show_handoff_button', false);

        $this->travelTo(CarbonImmutable::parse('2026-11-10 06:00:00', 'UTC'));
        \Illuminate\Support\Facades\Mail::fake();
        $book = fn () => $this->postJson("/api/widget/{$w->uuid}/booking", ['service_id' => 'a', 'start' => '2026-11-10T09:00:00Z', 'name' => 'A', 'email' => 'a@b.co']);
        $book()->assertSuccessful();
        $this->postJson("/api/widget/{$w->uuid}/booking", ['service_id' => 'a', 'start' => '2026-11-10T10:00:00Z', 'name' => 'B', 'email' => 'b@b.co'])
            ->assertStatus(422)->assertJsonPath('errors.service_id.0', fn ($m) => str_contains($m, 'not available'));
    }

    public function test_playground_runs_inactive_widget_only_with_valid_token(): void
    {
        $w = $this->widget([], false);
        $this->getJson("/api/widget/{$w->uuid}/config")->assertNotFound();
        $this->getJson("/api/widget/{$w->uuid}/config", ['X-PFX-Playground' => 'nope'])->assertNotFound();
        $this->getJson("/api/widget/{$w->uuid}/config", ['X-PFX-Playground' => $w->playgroundToken()])->assertOk()
            ->assertJsonPath('widget.playground', true);

        $this->actingAs($w->user)->get(route('widgets.playground', $w))->assertOk()->assertSee($w->playgroundToken());
        $this->actingAs($this->widget()->user)->get(route('widgets.playground', $w))->assertForbidden();
    }
}
