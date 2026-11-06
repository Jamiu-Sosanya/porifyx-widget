<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\TeamMember;
use App\Models\User;
use App\Models\Widget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class WorkspaceRolesTest extends TestCase
{
    use RefreshDatabase;

    private function setUpTeam(string $role): array
    {
        $owner = $this->subscribed(User::factory()->create(['company_name' => 'Acme Ltd']));
        $w = Widget::create(['user_id' => $owner->id, 'name' => 'Acme Bot', 'uuid' => (string) Str::uuid(), 'token' => Str::random(32), 'is_active' => true]);
        $lead = Lead::create(['user_id' => $owner->id, 'widget_id' => $w->id, 'email' => 'lead@x.test', 'status' => 'new']);
        $me = $this->subscribed(User::factory()->create(['company_name' => 'My Shop']));
        TeamMember::create(['owner_id' => $owner->id, 'user_id' => $me->id, 'role' => $role, 'status' => 'active', 'joined_at' => now()]);

        return [$owner, $w, $lead, $me];
    }

    public function test_member_switches_in_and_sees_the_workspace_data(): void
    {
        [$owner, $w, $lead, $me] = $this->setUpTeam('agent');
        $this->actingAs($me)->get(route('leads.index'))->assertOk()->assertDontSee('lead@x.test'); // own account first

        $this->actingAs($me)->post(route('workspaces.switch'), ['workspace' => $owner->id])->assertRedirect(route('dashboard'));
        $this->actingAs($me)->get(route('leads.index'))->assertOk()->assertSee('lead@x.test')->assertSee('Acme Ltd');
        $this->actingAs($me)->get(route('widgets.edit', $w))->assertOk();

        // Agent: no widget editing, no billing, no team, no personal settings of the owner.
        $this->actingAs($me)->put(route('widgets.update', $w), ['name' => 'Hacked'])->assertForbidden();
        $this->actingAs($me)->get(route('settings.billing'))->assertForbidden();
        $this->actingAs($me)->get(route('settings.team'))->assertForbidden();
        $this->actingAs($me)->get(route('settings.profile'))->assertForbidden();
        $this->assertSame('Acme Bot', $w->fresh()->name);
        // Allowed: work on leads? agent has leads.view only -> moving a lead is forbidden.
        $this->actingAs($me)->putJson(route('leads.update', $lead), ['status' => 'won'])->assertForbidden();

        // Cannot switch into a workspace you are not a member of.
        $stranger = $this->subscribed(User::factory()->create());
        $this->actingAs($me)->post(route('workspaces.switch'), ['workspace' => $stranger->id])->assertForbidden();

        // Back to own account.
        $this->actingAs($me)->post(route('workspaces.switch'), ['workspace' => $me->id]);
        $this->actingAs($me)->get(route('leads.index'))->assertDontSee('lead@x.test');
    }

    public function test_custom_role_permissions_and_removed_membership(): void
    {
        [$owner, $w, $lead, $me] = $this->setUpTeam('viewer');
        $this->actingAs($owner)->put(route('settings.roles.save'), ['name' => 'Sales', 'permissions' => ['leads.view', 'leads.manage']])->assertRedirect();
        $roleId = DB::table('custom_roles')->value('id');
        $member = TeamMember::where('user_id', $me->id)->first();
        $this->actingAs($owner)->patch(route('settings.team.updateRole', $member), ['role' => 'custom:' . $roleId])->assertRedirect();
        $this->assertSame('custom:' . $roleId, $member->fresh()->role);

        $this->actingAs($me)->post(route('workspaces.switch'), ['workspace' => $owner->id]);
        $this->actingAs($me)->putJson(route('leads.update', $lead), ['status' => 'won'])->assertOk();
        $this->actingAs($me)->put(route('widgets.update', $w), ['name' => 'X'])->assertForbidden();
        $this->assertTrue(DB::table('activity_log')->exists());

        // Membership removed: back in own account automatically.
        $member->delete();
        $this->actingAs($me)->get(route('leads.index'))->assertOk()->assertDontSee('lead@x.test');
    }

    public function test_person_creates_a_new_workspace_with_its_own_trial(): void
    {
        \App\Models\Plan::create(['name' => 'Starter', 'slug' => 'starter', 'price_monthly' => 29, 'price_yearly' => 290, 'is_active' => true, 'widgets_limit' => 1, 'messages_per_month' => 100, 'training_sources_limit' => 1, 'leads_per_month' => 10, 'team_members_limit' => 1]);
        $me = $this->subscribed(User::factory()->create());
        $this->actingAs($me)->post(route('workspaces.store'), ['name' => 'Second Brand'])->assertRedirect(route('dashboard'));
        $ws = User::where('is_workspace', true)->sole();
        $this->assertSame('trialing', $ws->subscription->status);
        $this->actingAs($me)->get(route('widgets.index'))->assertOk();
        $this->actingAs($me)->get(route('dashboard'))->assertSee('Second Brand');
        $this->post(route('logout'));
        $this->post(route('login'), ['email' => $ws->email, 'password' => 'anything'])->assertSessionHasErrors();
    }
}
