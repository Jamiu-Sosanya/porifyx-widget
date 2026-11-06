<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\User;
use App\Models\Visitor;
use App\Models\Widget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminSiteTest extends TestCase
{
    use RefreshDatabase;

    public function test_privacy_page_editor_request_form_and_retention(): void
    {
        Mail::fake();
        $admin = User::factory()->create(['role' => 'admin']);
        $this->get('/privacy')->assertOk()->assertSee('Make a privacy request');
        $this->actingAs($admin)->put(route('admin.privacy.save'), [
            'enabled' => '1', 'title' => 'Our privacy promise', 'effective_date' => '7 May 2026', 'summary' => 'We **never** sell data.',
            'contact_team' => 'Privacy desk', 'contact_email' => 'privacy@shop.test', 'retention_days' => 30, 'request_form' => '1',
        ])->assertRedirect();
        auth()->logout();
        $this->get('/privacy')->assertOk()->assertSee('Our privacy promise')->assertSee('<strong>never</strong>', false)->assertSee('privacy@shop.test')->assertSee('after 30 days');
        $this->post(route('privacy.request'), ['type' => 'delete', 'email' => 'ana@x.test'])->assertSessionHas('privacy_sent');
        $this->assertTrue(DB::table('activity_log')->where('event', 'privacy.request')->exists());

        // Retention job removes old chats only.
        $u = $this->subscribed(User::factory()->create());
        $w = Widget::create(['user_id' => $u->id, 'name' => 'S', 'uuid' => (string) Str::uuid(), 'token' => Str::random(32), 'is_active' => true]);
        $v = Visitor::create(['widget_id' => $w->id, 'fingerprint' => 'f']);
        $old = Conversation::create(['uuid' => (string) Str::uuid(), 'widget_id' => $w->id, 'visitor_id' => $v->id, 'user_id' => $u->id, 'status' => 'resolved', 'last_message_at' => now()->subDays(40)]);
        $new = Conversation::create(['uuid' => (string) Str::uuid(), 'widget_id' => $w->id, 'visitor_id' => $v->id, 'user_id' => $u->id, 'status' => 'ai_active', 'last_message_at' => now()]);
        $this->artisan('schedule:test', ['--name' => 'privacy-retention'])->assertSuccessful();
        $this->assertNull(Conversation::find($old->id));
        $this->assertNotNull(Conversation::find($new->id));
    }

    public function test_theme_picker_preview_and_apply(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get(route('admin.marketing'))->assertOk()->assertSee('Sunset')->assertSee('Preview');
        $this->get('/?preview_theme=prism')->assertOk()->assertSee("var forced = 'light'", false);
        $this->actingAs($admin)->put(route('admin.marketing.save'), ['marketing_theme' => 'midnight', 'hero_title' => 'Hello'])->assertRedirect();
        $this->get('/')->assertOk()->assertSee("var forced = 'dark'", false)->assertSee('Hello');
        $this->actingAs($admin)->put(route('admin.marketing.save'), ['marketing_theme' => 'nope'])->assertSessionHasErrors('marketing_theme');
    }

    public function test_monitor_filters_resolve_and_dashboard_card(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $u = $this->subscribed(User::factory()->create());
        $w = Widget::create(['user_id' => $u->id, 'name' => 'Acme Bot', 'uuid' => (string) Str::uuid(), 'token' => Str::random(32), 'is_active' => true]);
        \App\Support\WidgetEvents::record($w->id, 'provider_down', 'cURL error 28');
        \App\Support\WidgetEvents::record($w->id, 'failover', 'openai failed', 'warning');

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()->assertSee('2 open problems');
        $this->actingAs($admin)->get(route('admin.monitor', ['type' => 'failover']))->assertOk()->assertSee('openai failed')->assertDontSee('cURL error 28');
        $this->actingAs($admin)->post(route('admin.monitor.resolve'), ['all' => 1])->assertRedirect();
        $this->assertSame(0, DB::table('widget_events')->whereNull('resolved_at')->count());
        $this->actingAs($admin)->get(route('admin.dashboard'))->assertSee('All clear');
    }
}
