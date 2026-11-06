<?php

namespace Tests\Feature;

use App\Mail\AnalyticsReportMail;
use App\Models\Conversation;
use App\Models\Lead;
use App\Models\User;
use App\Models\Visitor;
use App\Models\Widget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class AnalyticsReportTest extends TestCase
{
    use RefreshDatabase;

    private function data(): User
    {
        $u = $this->subscribed(User::factory()->create());
        $w = Widget::create(['user_id' => $u->id, 'name' => 'Shop', 'uuid' => (string) Str::uuid(), 'token' => Str::random(32), 'is_active' => true]);
        $v = Visitor::create(['widget_id' => $w->id, 'fingerprint' => 'f']);
        foreach ([1, 2, 10] as $daysAgo) {
            $c = Conversation::create(['uuid' => (string) Str::uuid(), 'widget_id' => $w->id, 'visitor_id' => $v->id, 'user_id' => $u->id, 'status' => 'ai_active', 'message_count' => 3, 'current_page_url' => 'https://shop.test/pricing']);
            $c->forceFill(['created_at' => now()->subDays($daysAgo)])->save();
        }
        Lead::create(['user_id' => $u->id, 'widget_id' => $w->id, 'email' => 'a@b.test', 'status' => 'new']);

        return $u;
    }

    public function test_period_comparison_page_and_csv(): void
    {
        $u = $this->data();
        $r = \App\Support\AnalyticsReport::build($u, 7);
        $this->assertSame(2, $r['kpis']['conversations']['value']);
        $this->assertSame(1, $r['kpis']['conversations']['previous']);
        $this->assertSame(100, $r['kpis']['conversations']['change']);
        $this->assertSame(50.0, (float) $r['conversion']);

        $this->actingAs($u)->get(route('analytics.index', ['period' => 7]))->assertOk()->assertSee('Top pages')->assertSee('shop.test/pricing')->assertSee('Email reports');
        $csv = $this->actingAs($u)->get(route('analytics.export', ['period' => 7]))->assertOk()->streamedContent();
        $this->assertStringContainsString('date,conversations,messages,leads', $csv);
    }

    public function test_scheduled_email_reports(): void
    {
        Mail::fake();
        $u = $this->data();
        $this->actingAs($u)->put(route('analytics.reports'), ['enabled' => '1', 'days' => 2, 'every' => 1, 'recipients' => 'boss@shop.test, me@shop.test', 'send_now' => '1'])->assertRedirect();
        Mail::assertSent(AnalyticsReportMail::class, fn ($m) => $m->hasTo('boss@shop.test') && $m->report['days'] === 2);
        $this->actingAs($u)->put(route('analytics.reports'), ['days' => 7, 'every' => 7, 'recipients' => 'not-an-email'])->assertSessionHasErrors('recipients');

        // Due report goes out from the scheduler, then waits for the next interval.
        $u->refresh();
        $s = $u->report_settings; $s['next_at'] = now()->subMinute()->toIso8601String(); $u->update(['report_settings' => $s]);
        Mail::fake();
        $this->artisan('schedule:test', ['--name' => 'analytics-reports'])->assertSuccessful();
        Mail::assertSent(AnalyticsReportMail::class, 1);
        $this->assertTrue(\Carbon\Carbon::parse($u->fresh()->report_settings['next_at'])->isFuture());
        $this->assertStringContainsString('Conversations', (new AnalyticsReportMail($u, \App\Support\AnalyticsReport::build($u, 7)))->render());
    }
}
