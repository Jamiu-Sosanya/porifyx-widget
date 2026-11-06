<?php

namespace App\Http\Middleware;

use App\Support\Security;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Firewall (IP blocklist, auto-bans, scanner bots), admin 2FA enforcement and security headers. */
class SecurityShield
{
    public function handle(Request $request, Closure $next): Response
    {
        $s = Security::settings();
        $ip = (string) $request->ip();

        if (Security::isBanned($ip)) {
            if (\Illuminate\Support\Facades\RateLimiter::attempt('blocked-log:' . $ip, 1, fn () => true, 300)) Security::record('blocked', $ip, null, $request->method() . ' ' . $request->path());
            abort(403, 'Access denied.');
        }
        if ($s['block_bad_bots'] && ($ua = mb_strtolower((string) $request->userAgent())) && collect(Security::BAD_BOTS)->contains(fn ($b) => str_contains($ua, $b))) {
            Security::record('bad_bot', $ip, null, mb_substr($ua, 0, 200));
            abort(403, 'Access denied.');
        }

        $u = $request->user();
        if ($s['force_admin_2fa'] && $u?->isAdmin() && ! $u->two_factor_enabled
            && ! $request->routeIs('settings.twoFactor*', 'logout', 'admin.logout') && ! $request->expectsJson()) {
            return redirect()->route('settings.twoFactor')->with('warning', 'Two-factor authentication is required for admin accounts.');
        }

        $response = $next($request);

        if ($s['headers']) {
            $h = $response->headers;
            $h->set('X-Content-Type-Options', 'nosniff');
            $h->set('Referrer-Policy', 'strict-origin-when-cross-origin');
            $h->set('Permissions-Policy', 'camera=(), geolocation=(), microphone=(self)');
            // The chat widget runs inside customers' sites, so only app pages refuse framing.
            if (! $request->is('api/*', 'widget*', 'embed*', 'chat/*', 'help/*', 'booking/*')) $h->set('X-Frame-Options', 'SAMEORIGIN');
            if ($s['hsts'] && $request->isSecure()) $h->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
