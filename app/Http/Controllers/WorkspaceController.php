<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\TeamMember;
use App\Models\User;
use App\Support\Workspace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/** Switch between workspaces, create a new one, and manage custom team roles. */
class WorkspaceController extends Controller
{
    public function switch(Request $request)
    {
        $real = Workspace::realUser();
        $id = $request->integer('workspace');
        abort_unless(collect(Workspace::for($real))->contains('id', $id), 403);
        $id === $real->id ? session()->forget('workspace_id') : session(['workspace_id' => $id]);

        return redirect()->route('dashboard');
    }

    /** A new, separate workspace (own widgets, plan and team) owned by the person creating it. */
    public function store(Request $request)
    {
        $real = Workspace::realUser();
        $d = $request->validate(['name' => 'required|string|max:80']);
        abort_if(TeamMember::where('user_id', $real->id)->where('role', 'owner')->count() >= 10, 422, 'You can own up to 10 extra workspaces.');

        $ws = DB::transaction(function () use ($real, $d) {
            $ws = User::create([
                'name' => $d['name'], 'company_name' => $d['name'], 'email' => 'ws-' . Str::lower(Str::random(12)) . '@workspaces.invalid',
                'password' => Hash::make(Str::random(40)), 'role' => 'user', 'is_workspace' => true,
            ]);
            $ws->forceFill(['email_verified_at' => now()])->save();
            TeamMember::create(['owner_id' => $ws->id, 'user_id' => $real->id, 'role' => 'owner', 'status' => 'active', 'joined_at' => now()]);
            if ($plan = Plan::where('slug', 'starter')->first()) {
                Subscription::create(['user_id' => $ws->id, 'plan_id' => $plan->id, 'status' => 'trialing', 'billing_cycle' => 'monthly',
                    'trial_ends_at' => now()->addDays(config('plans.trial_days', 14)), 'current_period_start' => now(), 'current_period_end' => now()->addDays(config('plans.trial_days', 14))]);
            }

            return $ws;
        });
        session(['workspace_id' => $ws->id]);

        return redirect()->route('dashboard')->with('success', "Workspace \"{$d['name']}\" created. You are working in it now.");
    }

    public function saveRole(Request $request)
    {
        $d = $request->validate(['id' => 'nullable|integer', 'name' => 'required|string|max:60', 'permissions' => 'array', 'permissions.*' => 'in:' . implode(',', array_keys(Workspace::PERMISSIONS))]);
        $owner = $request->user();
        $values = ['name' => $d['name'], 'permissions' => json_encode(array_values($d['permissions'] ?? [])), 'updated_at' => now()];
        if (filled($d['id'] ?? null)) {
            DB::table('custom_roles')->where('owner_id', $owner->id)->where('id', $d['id'])->update($values);
        } else {
            DB::table('custom_roles')->insert($values + ['owner_id' => $owner->id, 'created_at' => now()]);
        }
        \App\Support\Audit::log('role.saved', null, ['name' => $d['name'], 'permissions' => $d['permissions'] ?? []]);

        return back()->with('success', 'Role saved.');
    }

    public function deleteRole(Request $request, int $id)
    {
        $owner = $request->user();
        if (TeamMember::where('owner_id', $owner->id)->where('role', 'custom:' . $id)->exists()) {
            return back()->with('error', 'Move the people in this role to another role first.');
        }
        DB::table('custom_roles')->where('owner_id', $owner->id)->where('id', $id)->delete();

        return back()->with('success', 'Role deleted.');
    }
}
