<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\WelcomeEmail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules;
use PragmaRX\Google2FA\Google2FA;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $remember = $request->boolean('remember');

        if (!Auth::attempt($credentials, $remember)) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'These credentials do not match our records.']);
        }

        $user = Auth::user();

        if (!$user->is_active) {
            Auth::logout();
            return back()->withErrors(['email' => 'Your account has been deactivated.']);
        }

        // If 2FA is enabled, hold authentication and challenge the user
        if ($user->two_factor_enabled && $user->two_factor_secret) {
            Auth::logout();
            $request->session()->put('2fa_user_id', $user->id);
            $request->session()->put('2fa_remember', $remember);
            return redirect()->route('two-factor.challenge');
        }

        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);

        $request->session()->regenerate();

        if ($user->isAdmin()) {
            return redirect()->intended(route('admin.dashboard'));
        }

        return redirect()->intended(route('dashboard'));
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'company_name' => 'nullable|string|max:255',
        ]);

        $user = User::create([
            'name'         => $validated['name'],
            'email'        => $validated['email'],
            'password'     => Hash::make($validated['password']),
            'company_name' => $validated['company_name'] ?? null,
            'role'         => 'user',
            'is_active'    => true,
            'last_login_at'=> now(),
            'last_login_ip'=> $request->ip(),
        ]);

        // Create a trial subscription
        $starterPlan = \App\Models\Plan::where('slug', 'starter')->first();
        if ($starterPlan) {
            \App\Models\Subscription::create([
                'user_id'              => $user->id,
                'plan_id'              => $starterPlan->id,
                'status'               => 'trialing',
                'billing_cycle'        => 'monthly',
                'trial_ends_at'        => now()->addDays(config('plans.trial_days', 14)),
                'current_period_start' => now(),
                'current_period_end'   => now()->addDays(config('plans.trial_days', 14)),
            ]);
        }

        Auth::login($user);

        if (config('app.email_verification_required', true)) {
            $user->sendEmailVerificationNotification();
            return redirect()->route('verification.notice');
        }

        $user->markEmailAsVerified();
        Mail::to($user->email)->queue(new WelcomeEmail($user));

        return redirect()->route('dashboard');
    }

    public function verifyEmail(Request $request): \Illuminate\Http\RedirectResponse
    {
        $user = User::findOrFail($request->route('id'));

        // Ensure the signed link belongs to the currently authenticated user.
        abort_if((string) $request->user()->getKey() !== (string) $request->route('id'), 403);

        if (!hash_equals(sha1($user->getEmailForVerification()), (string) $request->route('hash'))) {
            return redirect()->route('verification.notice')
                ->with('error', 'This verification link is invalid or has expired. Please request a new one.');
        }

        if (!$user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            // Welcome email sent only after successful verification.
            Mail::to($user->email)->queue(new WelcomeEmail($user));
        }

        return redirect()->route('dashboard')->with('status', 'Email verified — welcome aboard!');
    }

    public function resendVerification(Request $request): \Illuminate\Http\RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('dashboard');
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('status', 'A new verification link has been sent to your email address.');
    }

    public function showForgotPassword()
    {
        return view('auth.forgot-password');
    }

    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $status = \Illuminate\Support\Facades\Password::sendResetLink(
            $request->only('email')
        );

        return $status === \Illuminate\Support\Facades\Password::RESET_LINK_SENT
            ? back()->with('status', __($status))
            : back()->withErrors(['email' => __($status)]);
    }

    public function showResetPassword(string $token)
    {
        return view('auth.reset-password', ['token' => $token]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token'    => 'required',
            'email'    => 'required|email',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $status = \Illuminate\Support\Facades\Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) use ($request) {
                $user->forceFill(['password' => Hash::make($password)])->save();
            }
        );

        return $status === \Illuminate\Support\Facades\Password::PASSWORD_RESET
            ? redirect()->route('login')->with('status', __($status))
            : back()->withErrors(['email' => [__($status)]]);
    }

    public function showAdminLogin()
    {
        if (Auth::check() && Auth::user()->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.login');
    }

    public function adminLogin(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $remember = $request->boolean('remember');

        if (!Auth::attempt($credentials, $remember)) {
            // Log the failed attempt
            \Illuminate\Support\Facades\Log::warning('Failed admin login attempt', [
                'email' => $request->email,
                'ip'    => $request->ip(),
            ]);

            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Invalid credentials.']);
        }

        $user = Auth::user();

        if (!$user->is_active) {
            Auth::logout();
            return back()->withErrors(['email' => 'This account has been deactivated.']);
        }

        if (!$user->isAdmin()) {
            Auth::logout();
            \Illuminate\Support\Facades\Log::warning('Non-admin user attempted admin login', [
                'user_id' => $user->id,
                'ip'      => $request->ip(),
            ]);
            return back()->withErrors(['email' => 'Access denied. Administrator account required.']);
        }

        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);

        $request->session()->regenerate();

        \Illuminate\Support\Facades\Log::info('Admin login successful', [
            'user_id' => $user->id,
            'ip'      => $request->ip(),
        ]);

        return redirect()->intended(route('admin.dashboard'));
    }

    public function showTwoFactorChallenge(Request $request)
    {
        if (! $request->session()->has('2fa_user_id')) {
            return redirect()->route('login');
        }

        return view('auth.two-factor-challenge');
    }

    public function twoFactorChallenge(Request $request)
    {
        $userId = $request->session()->get('2fa_user_id');

        if (! $userId) {
            return redirect()->route('login');
        }

        $user = User::find($userId);

        if (! $user) {
            return redirect()->route('login');
        }

        $request->validate(['code' => 'required|string|max:10']);
        $code = preg_replace('/\s+/', '', $request->code);

        $g2fa   = new Google2FA();
        $secret = decrypt($user->two_factor_secret);

        // Check TOTP code
        $valid = $g2fa->verifyKey($secret, $code, 1);

        // Check recovery codes if TOTP fails
        if (! $valid && strlen($code) > 6) {
            $recoveryCodes = json_decode(decrypt($user->two_factor_recovery_codes ?? '[]'), true) ?? [];
            $index = array_search($code, $recoveryCodes);

            if ($index !== false) {
                unset($recoveryCodes[$index]);
                $user->update(['two_factor_recovery_codes' => encrypt(json_encode(array_values($recoveryCodes)))]);
                $valid = true;
            }
        }

        if (! $valid) {
            return back()->withErrors(['code' => 'The authentication code you entered is incorrect.']);
        }

        $remember = $request->session()->pull('2fa_remember', false);
        $request->session()->forget('2fa_user_id');

        Auth::login($user, $remember);
        $request->session()->regenerate();

        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);

        if ($user->isAdmin()) {
            return redirect()->intended(route('admin.dashboard'));
        }

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
