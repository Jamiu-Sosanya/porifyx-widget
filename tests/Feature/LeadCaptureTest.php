<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\OfflineMessage;
use App\Models\User;
use App\Models\Widget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class LeadCaptureTest extends TestCase
{
    use RefreshDatabase;

    private function widget(array $plan = []): Widget
    {
        $user = $this->subscribed(User::factory()->create(), $plan);

        return Widget::create(['user_id' => $user->id, 'name' => 'Shop', 'uuid' => (string) Str::uuid(), 'token' => Str::random(32), 'is_active' => true, 'collect_email' => true]);
    }

    public function test_owner_builds_form_and_visitor_submission_creates_one_lead_per_email(): void
    {
        $w = $this->widget();
        $this->actingAs($w->user)->put(route('widgets.leadform.save', $w), [
            'mode' => 'pre_chat', 'title' => 'Before we start', 'button' => 'Start',
            'fields' => [
                ['label' => 'Your name', 'type' => 'text', 'required' => '1'],
                ['label' => 'Work email', 'type' => 'email', 'required' => '1'],
                ['label' => 'Company', 'type' => 'text'],
                ['label' => 'Team size', 'type' => 'select', 'options' => '1-10, 11-50'],
                ['label' => 'I agree to be contacted', 'type' => 'checkbox', 'required' => '1'],
            ],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $cfg = $this->getJson("/api/widget/{$w->uuid}/config")->json('widget.lead_form');
        $this->assertSame('pre_chat', $cfg['mode']);
        $this->assertSame(['name', 'email', 'company', 'team_size', 'i_agree_to_be_contacted'], array_column($cfg['fields'], 'key'));

        $post = fn ($fields) => $this->postJson("/api/widget/{$w->uuid}/lead", ['fields' => $fields]);
        $post(['name' => 'Ada', 'email' => 'bad'])->assertStatus(422)->assertJsonValidationErrors(['fields.email', 'fields.i_agree_to_be_contacted']);
        $post(['name' => 'Ada', 'email' => 'not-a-mail@'])->assertStatus(422);
        $post(['name' => 'Ada', 'email' => 'Ada@Corp.test', 'company' => 'Corp', 'team_size' => '11-50', 'i_agree_to_be_contacted' => true])->assertOk();
        $post(['name' => 'Ada L', 'email' => 'ada@corp.test', 'team_size' => '1-10', 'i_agree_to_be_contacted' => true])->assertOk();

        $lead = Lead::sole();
        $this->assertSame(['ada@corp.test', 'Ada L', 'Corp', '1-10'], [$lead->email, $lead->name, $lead->company, $lead->form_data['team_size']]);
        $this->assertSame(1, $w->user->getUsageThisMonth('leads'));
        $this->actingAs($w->user)->get(route('leads.show', $lead))->assertOk()->assertSee('Team size');
    }

    public function test_over_limit_contact_is_kept_as_message_not_lost(): void
    {
        $w = $this->widget(['leads_per_month' => 1]);
        $this->postJson("/api/widget/{$w->uuid}/lead", ['fields' => ['email' => 'a@x.test']])->assertOk();
        $this->postJson("/api/widget/{$w->uuid}/lead", ['fields' => ['email' => 'b@x.test']])->assertOk();
        $this->assertSame(1, Lead::count());
        $this->assertSame('b@x.test', OfflineMessage::sole()->email);
    }

    public function test_pipeline_columns_board_and_safe_sorting(): void
    {
        $w = $this->widget();
        $u = $w->user;
        $lead = Lead::create(['user_id' => $u->id, 'widget_id' => $w->id, 'email' => 'a@x.test', 'status' => 'contacted', 'score' => 70]);

        $this->actingAs($u)->put(route('leads.pipeline'), [
            'statuses' => [['label' => 'Inbox', 'color' => '#112233'], ['label' => 'Demo booked', 'color' => '#445566'], ['key' => 'won', 'label' => 'Won', 'color' => '#10b981']],
            'warm' => 40, 'hot' => 60,
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('inbox', $lead->fresh()->status, 'Leads in removed columns move to the first column');
        $this->assertSame('hot', \App\Support\LeadPipeline::temperature($u->fresh(), 65));

        $this->actingAs($u)->putJson(route('leads.update', $lead), ['status' => 'demo_booked'])->assertOk()->assertJsonPath('status', 'demo_booked');
        $this->actingAs($u)->putJson(route('leads.update', $lead), ['status' => 'contacted'])->assertStatus(422);

        $this->actingAs($u)->get(route('leads.index', ['sort' => 'email desc; drop table leads', 'dir' => 'sideways']))->assertOk()->assertSee('Demo booked');
        $this->assertSame(1, Lead::count());
    }
}
