<?php

namespace Tests\Feature;

use App\Models\ProactiveMessage;
use App\Models\User;
use App\Models\Widget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class EngagementTest extends TestCase
{
    use RefreshDatabase;

    private function widget(): Widget
    {
        $user = $this->subscribed(User::factory()->create());

        return Widget::create(['user_id' => $user->id, 'name' => 'Shop', 'uuid' => (string) Str::uuid(), 'token' => Str::random(32), 'is_active' => true,
            'show_handoff_button' => true, 'message_streaming' => true]);
    }

    public function test_saving_the_editor_keeps_widget_live_and_saves_every_setting(): void
    {
        $w = $this->widget();
        $this->actingAs($w->user)->put(route('widgets.update', $w), [
            'name' => 'Shop', 'theme' => 'light', 'notification_email' => 'team@shop.test',
            'smart_suggestions_enabled' => '1', 'smart_suggestions_count' => 4, 'proactive_enabled' => '1', 'proactive_message' => 'Need help?',
            'offline_form_enabled' => '1', 'conversation_starters_input' => "Pricing?\nDelivery?",
        ])->assertRedirect()->assertSessionHasNoErrors();

        $w->refresh();
        $this->assertTrue($w->is_active, 'Saving the editor must not take the widget offline');
        $this->assertSame('light', $w->theme);
        $this->assertSame('team@shop.test', $w->notification_email);
        $this->assertTrue($w->smart_suggestions_enabled);
        $this->assertSame(4, (int) $w->smart_suggestions_count);
        $this->assertTrue($w->proactive_enabled);
        $this->assertSame(['Pricing?', 'Delivery?'], $w->conversation_starters);
        $this->assertTrue($w->offline_form_enabled);

        // Unticking now really switches off
        $this->actingAs($w->user)->put(route('widgets.update', $w), ['name' => 'Shop'])->assertRedirect();
        $this->assertFalse($w->fresh()->offline_form_enabled);
        $this->assertTrue($w->fresh()->is_active);
    }

    public function test_engagement_settings_reach_the_widget(): void
    {
        $w = $this->widget();
        $this->actingAs($w->user)->put(route('widgets.engagement.save', $w), [
            'voice_input_enabled' => '1',
            'ai_notice' => ['enabled' => '1', 'title' => 'AI here', 'text' => 'We use AI.', 'privacy_url' => 'https://shop.test/privacy'],
            'hidden_paths' => "/checkout*\n/account/*",
            'cards' => [['title' => 'See pricing', 'keywords' => 'price, cost', 'button' => 'Pricing', 'url' => 'https://shop.test/pricing'], ['title' => '', 'keywords' => '']],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $cfg = fn ($url) => $this->getJson("/api/widget/{$w->uuid}/config?page_url=" . urlencode($url))->json('widget');
        $c = $cfg('https://shop.test/products');
        $this->assertFalse($c['hidden']);
        $this->assertTrue($c['voice_input_enabled']);
        $this->assertSame('AI here', $c['ai_notice']['title']);
        $this->assertNotEmpty($c['ai_notice']['version']);
        $this->assertTrue($cfg('https://shop.test/checkout/step-2')['hidden']);
        $this->assertTrue($cfg('https://shop.test/account/orders')['hidden']);

        $this->assertSame([['title' => 'See pricing', 'text' => '', 'button' => 'Pricing', 'url' => 'https://shop.test/pricing']],
            \App\Support\Engagement::cardsFor($w->fresh(), 'What does it cost per month?'));
        $this->assertSame([], \App\Support\Engagement::cardsFor($w->fresh(), 'Do you ship abroad?'));

        // Unsafe card links are rejected
        $this->actingAs($w->user)->put(route('widgets.engagement.save', $w), ['cards' => [['title' => 'x', 'keywords' => 'x', 'url' => 'javascript:alert(1)']]])
            ->assertSessionHasErrors('cards.0.url');
    }

    public function test_behaviour_triggers_crud_and_page_targeting(): void
    {
        $w = $this->widget();
        $this->actingAs($w->user)->post(route('widgets.triggers.store', $w), [
            'trigger_type' => 'idle', 'trigger_value' => 20, 'message' => 'Still there?', 'target_url_pattern' => '/pricing*', 'quick_replies' => 'Yes, No',
        ])->assertRedirect();
        $t = ProactiveMessage::sole();
        $this->assertSame(['Yes', 'No'], $t->quick_replies);
        $this->assertTrue($w->fresh()->proactive_enabled);

        $msgs = fn ($url) => $this->getJson("/api/widget/{$w->uuid}/config?page_url=" . urlencode($url))->json('widget.proactive.messages');
        $this->assertCount(1, $msgs('https://shop.test/pricing/annual'));
        $this->assertCount(0, $msgs('https://shop.test/blog'));

        $this->actingAs($w->user)->get(route('widgets.engagement', $w))->assertOk()->assertSee('Still there?');
        $this->actingAs($w->user)->delete(route('widgets.triggers.destroy', [$w, $t]))->assertRedirect();
        $this->assertSame(0, ProactiveMessage::count());
        $this->actingAs($this->subscribed(User::factory()->create()))->get(route('widgets.engagement', $w))->assertForbidden();
    }
}
