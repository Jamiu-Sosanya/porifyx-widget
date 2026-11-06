<?php

namespace App\Http\Controllers;

use App\Mail\TeamInvitation;
use App\Mail\TeamMemberRemoved;
use App\Models\ApiKey;
use App\Models\CannedResponse;
use App\Models\Plan;
use App\Models\TeamMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use PragmaRX\Google2FA\Google2FA;

class SettingsController extends Controller
{
    public function profile(Request $request)
    {
        return view('settings.profile', ['user' => $request->user()]);
    }

    public function updateProfile(Request $request)
    {
        $validated = $request->validate([
            'name'            => 'required|string|max:255',
            'email'           => 'required|email|unique:users,email,' . $request->user()->id,
            'company_name'    => 'nullable|string|max:255',
            'company_website' => 'nullable|url|max:255',
            'phone'           => 'nullable|string|max:20',
            'timezone'        => 'nullable|string|max:100',
        ]);

        $request->user()->update($validated);

        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('avatars', 'public');
            $request->user()->update(['avatar' => $path]);
        }

        return back()->with('success', 'Profile updated.');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|current_password',
            'password'         => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $request->user()->update(['password' => Hash::make($request->password)]);

        return back()->with('success', 'Password changed.');
    }

    public function notifications(Request $request)
    {
        return view('settings.notifications', ['user' => $request->user()]);
    }

    public function updateNotifications(Request $request)
    {
        $prefs = $request->validate([
            'email_new_lead'       => 'nullable|boolean',
            'email_new_chat'       => 'nullable|boolean',
            'email_ai_fallback'    => 'nullable|boolean',
            'email_weekly_report'  => 'nullable|boolean',
            'telegram_new_lead'    => 'nullable|boolean',
            'telegram_hot_lead'    => 'nullable|boolean',
            'browser_new_message'  => 'nullable|boolean',
        ]);

        $request->user()->update(['notification_preferences' => $prefs]);

        return back()->with('success', 'Notification preferences updated.');
    }

    // Team Management
    public function team(Request $request)
    {
        $members = TeamMember::where('owner_id', $request->user()->id)
            ->with('user')
            ->get();

        return view('settings.team', compact('members'));
    }

    public function inviteTeamMember(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'role'  => ['required', \Illuminate\Validation\Rule::in(array_merge(['admin', 'agent', 'viewer'], \Illuminate\Support\Facades\DB::table('custom_roles')->where('owner_id', $request->user()->id)->pluck('id')->map(fn ($i) => 'custom:' . $i)->all()))],
        ]);

        $user = $request->user();
        $plan = $user->currentPlan();
        $currentCount = TeamMember::where('owner_id', $user->id)->count();
        $limit = $plan?->team_members_limit ?? 1;

        if ($limit !== -1 && $currentCount >= $limit) {
            return back()->with('error', 'Team member limit reached. Please upgrade your plan.');
        }

        $invitedUser = \App\Models\User::where('email', $validated['email'])->first();

        if (!$invitedUser) {
            // Create pending user
            $invitedUser = \App\Models\User::create([
                'name'     => explode('@', $validated['email'])[0],
                'email'    => $validated['email'],
                'password' => Hash::make(Str::random(32)),
                'is_active'=> true,
            ]);
        }

        $token = Str::random(64);
        $expiresAt = now()->addDays(7);

        TeamMember::create([
            'owner_id'          => $user->id,
            'user_id'           => $invitedUser->id,
            'role'              => $validated['role'],
            'status'            => 'pending',
            'invite_token'      => $token,
            'invite_expires_at' => $expiresAt,
        ]);

        $acceptUrl = route('team.accept', ['token' => $token]);
        Mail::to($invitedUser->email)->queue(
            new TeamInvitation($user, $acceptUrl, $validated['role'], $invitedUser->name, $expiresAt)
        );

        return back()->with('success', 'Team member invited!');
    }

    public function removeTeamMember(TeamMember $member)
    {
        abort_unless((int) $member->owner_id === (int) request()->user()->id, 403);

        $removedUser  = $member->user;
        $ownerName    = request()->user()->name;
        $member->delete();

        if ($removedUser) {
            Mail::to($removedUser->email)->queue(new TeamMemberRemoved($removedUser->name, $ownerName));
        }

        return back()->with('success', 'Team member removed.');
    }

    public function resendInvite(TeamMember $member)
    {
        abort_unless((int) $member->owner_id === (int) request()->user()->id, 403);
        abort_unless($member->status === 'pending', 403);

        $token     = Str::random(64);
        $expiresAt = now()->addDays(7);

        $member->update([
            'invite_token'      => $token,
            'invite_expires_at' => $expiresAt,
        ]);

        $acceptUrl = route('team.accept', ['token' => $token]);
        Mail::to($member->user->email)->queue(
            new TeamInvitation(request()->user(), $acceptUrl, $member->role, $member->user->name, $expiresAt)
        );

        return back()->with('success', 'Invite resent — link valid for 7 days.');
    }

    public function acceptInvite(string $token)
    {
        $member = TeamMember::where('invite_token', $token)
            ->where('status', 'pending')
            ->where('invite_expires_at', '>', now())
            ->with(['user', 'owner'])
            ->first();

        if (! $member) {
            return redirect()->route('login')
                ->with('error', 'This invitation link is invalid or has expired. Please ask the team owner to resend it.');
        }

        $member->update([
            'status'       => 'active',
            'invite_token' => null,
            'joined_at'    => now(),
        ]);

        $user = $member->user;
        $user->markEmailAsVerified();

        \Illuminate\Support\Facades\Auth::login($user);
        request()->session()->regenerate();

        return redirect()->route('dashboard')
            ->with('status', 'Welcome! You have joined ' . ($member->owner->name ?? 'the') . "'s team.");
    }

    public function updateTeamRole(Request $request, TeamMember $member)
    {
        abort_unless((int) $member->owner_id === (int) $request->user()->id, 403);

        $request->validate(['role' => ['required', \Illuminate\Validation\Rule::in(array_merge(['admin', 'agent', 'viewer'], \Illuminate\Support\Facades\DB::table('custom_roles')->where('owner_id', $request->user()->id)->pluck('id')->map(fn ($i) => 'custom:' . $i)->all()))]]);

        $member->update(['role' => $request->role]);

        return back()->with('success', 'Role updated.');
    }

    // Billing
    public function billing(Request $request)
    {
        $user = $request->user();
        $subscription = $user->subscription;
        $plan = $subscription?->plan;
        $plans = Plan::active()->get();
        $invoices = $user->invoices()->orderBy('created_at', 'desc')->take(20)->get();

        $usage = [
            'messages' => [
                'used'  => $user->getUsageThisMonth('messages'),
                'limit' => $plan?->messages_per_month ?? 0,
            ],
            'leads' => [
                'used'  => $user->getUsageThisMonth('leads'),
                'limit' => $plan?->leads_per_month ?? 0,
            ],
            'widgets' => [
                'used'  => $user->widgets()->count(),
                'limit' => $plan?->widgets_limit ?? 0,
            ],
        ];

        return view('settings.billing', compact('subscription', 'plan', 'plans', 'invoices', 'usage'));
    }

    public function changePlan(Request $request)
    {
        $validated = $request->validate([
            'plan_id'       => 'required|exists:plans,id',
            'billing_cycle' => 'required|in:monthly,yearly',
        ]);

        $user = $request->user();
        $newPlan = Plan::findOrFail($validated['plan_id']);

        // Update or create subscription
        $user->subscription?->update([
            'plan_id'              => $newPlan->id,
            'billing_cycle'        => $validated['billing_cycle'],
            'status'               => 'active',
            'current_period_start' => now(),
            'current_period_end'   => $validated['billing_cycle'] === 'yearly' ? now()->addYear() : now()->addMonth(),
        ]) ?? \App\Models\Subscription::create([
            'user_id'              => $user->id,
            'plan_id'              => $newPlan->id,
            'billing_cycle'        => $validated['billing_cycle'],
            'status'               => 'active',
            'current_period_start' => now(),
            'current_period_end'   => $validated['billing_cycle'] === 'yearly' ? now()->addYear() : now()->addMonth(),
        ]);

        return back()->with('success', 'Plan updated!');
    }

    public function cancelSubscription(Request $request)
    {
        $user = $request->user();
        $subscription = $user->subscription;

        if (!$subscription || !in_array($subscription->status, ['active', 'trialing'])) {
            return back()->with('error', 'No active subscription to cancel.');
        }

        if ($subscription->paypal_subscription_id && \App\Services\PayPalService::ready()) {
            try { app(\App\Services\PayPalService::class)->cancel($subscription->paypal_subscription_id); } catch (\Throwable $e) { report($e); }
        }

        $subscription->update([
            'status'      => 'canceled',
            'ends_at'     => $subscription->current_period_end,
            'canceled_at' => now(),
        ]);

        return back()->with('success', 'Subscription cancelled. You have access until ' . $subscription->current_period_end->format('M d, Y') . '.');
    }

    public function developer(Request $request)
    {
        $appUrl = rtrim(config('app.url'), '/');
        $sampleWidgetUuid = $request->user()->widgets()->value('uuid') ?? '00000000-0000-0000-0000-000000000000';
        $scoreThreshold = (int) config('webhooks.lead_score_jump_threshold', 10);

        return view('settings.developer', compact('appUrl', 'sampleWidgetUuid', 'scoreThreshold'));
    }

    // API Keys
    public function apiKeys(Request $request)
    {
        $keys = $request->user()->apiKeys()->latest()->get();

        return view('settings.api-keys', compact('keys'));
    }

    public function createApiKey(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'permissions' => 'nullable|array',
        ]);

        $key = $request->user()->apiKeys()->create([
            'name'        => $validated['name'],
            'permissions' => $validated['permissions'] ?? ['read'],
        ]);

        return back()->with('success', 'API key created: ' . $key->key)->with('new_key', $key->key);
    }

    public function deleteApiKey(ApiKey $apiKey)
    {
        abort_unless($apiKey->user_id === request()->user()->id, 403);

        $apiKey->delete();

        return back()->with('success', 'API key deleted.');
    }

    public function twoFactor(Request $request)
    {
        $user   = $request->user();
        $g2fa   = new Google2FA();
        $qrUrl  = null;
        $secret = null;

        if (! $user->two_factor_enabled && ! session('2fa_setup_secret')) {
            $secret = $g2fa->generateSecretKey();
            session(['2fa_setup_secret' => $secret]);
        } elseif (! $user->two_factor_enabled) {
            $secret = session('2fa_setup_secret');
        }

        if ($secret) {
            $qrUrl = $g2fa->getQRCodeUrl(
                config('app.name'),
                $user->email,
                $secret
            );
        }

        return view('settings.two-factor', compact('user', 'secret', 'qrUrl'));
    }

    public function enableTwoFactor(Request $request)
    {
        $user   = $request->user();
        $secret = session('2fa_setup_secret');

        if (! $secret) {
            return back()->withErrors(['code' => 'Session expired. Please start the setup again.']);
        }

        $request->validate(['code' => 'required|digits:6']);

        $g2fa = new Google2FA();

        if (! $g2fa->verifyKey($secret, $request->code)) {
            return back()->withErrors(['code' => 'Invalid authentication code. Please try again.']);
        }

        $recoveryCodes = collect(range(1, 8))->map(fn () => Str::upper(Str::random(4) . '-' . Str::random(4)))->toArray();

        $user->update([
            'two_factor_enabled'       => true,
            'two_factor_secret'        => encrypt($secret),
            'two_factor_recovery_codes'=> encrypt(json_encode($recoveryCodes)),
            'two_factor_confirmed_at'  => now(),
        ]);

        session()->forget('2fa_setup_secret');

        return redirect()->route('settings.twoFactor')
            ->with('success', 'Two-factor authentication enabled successfully.')
            ->with('recovery_codes', $recoveryCodes);
    }

    public function disableTwoFactor(Request $request)
    {
        $request->validate(['password' => 'required']);

        $user = $request->user();

        if (! Hash::check($request->password, $user->password)) {
            return back()->withErrors(['password' => 'Incorrect password.']);
        }

        $user->update([
            'two_factor_enabled'        => false,
            'two_factor_secret'         => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at'   => null,
        ]);

        session()->forget('2fa_setup_secret');

        return back()->with('success', 'Two-factor authentication has been disabled.');
    }

    public function regenerateRecoveryCodes(Request $request)
    {
        $user = $request->user();

        abort_unless($user->two_factor_enabled, 403);

        $recoveryCodes = collect(range(1, 8))->map(fn () => Str::upper(Str::random(4) . '-' . Str::random(4)))->toArray();

        $user->update([
            'two_factor_recovery_codes' => encrypt(json_encode($recoveryCodes)),
        ]);

        return back()->with('recovery_codes', $recoveryCodes)->with('success', 'Recovery codes regenerated. Save them somewhere safe.');
    }

    // ─── Canned Responses ─────────────────────────────────────────────────────

    public function cannedResponses(Request $request)
    {
        $user      = $request->user();
        $responses = $user->cannedResponses()->get();
        $categories = $responses->pluck('category')->unique()->sort()->values();

        return view('settings.canned-responses', compact('responses', 'categories'));
    }

    public function storeCannedResponse(Request $request)
    {
        $validated = $request->validate([
            'title'    => 'required|string|max:150',
            'shortcut' => ['nullable', 'string', 'max:50', 'regex:/^\/\S+$/',
                Rule::unique('canned_responses')->where('user_id', $request->user()->id)],
            'category' => 'required|string|max:50',
            'content'  => 'required|string|max:2000',
        ]);

        $request->user()->cannedResponses()->create($validated);

        return back()->with('success', 'Canned response saved.');
    }

    public function updateCannedResponse(Request $request, CannedResponse $cannedResponse)
    {
        abort_unless((int) $cannedResponse->user_id === (int) $request->user()->id, 403);

        $validated = $request->validate([
            'title'    => 'required|string|max:150',
            'shortcut' => ['nullable', 'string', 'max:50', 'regex:/^\/\S+$/',
                Rule::unique('canned_responses')->where('user_id', $request->user()->id)->ignore($cannedResponse->id)],
            'category' => 'required|string|max:50',
            'content'  => 'required|string|max:2000',
        ]);

        $cannedResponse->update($validated);

        return back()->with('success', 'Canned response updated.');
    }

    public function deleteCannedResponse(Request $request, CannedResponse $cannedResponse)
    {
        abort_unless((int) $cannedResponse->user_id === (int) $request->user()->id, 403);

        $cannedResponse->delete();

        return back()->with('success', 'Canned response deleted.');
    }
}
