<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DemoMode
{
    /**
     * Routes allowed to mutate state even in demo mode.
     * Patterns use Str::is() wildcard syntax (fnmatch-style).
     */
    private const ALLOWED_PATTERNS = [
        'login',
        'logout',
        'admin/login',
        'register',
        'forgot-password',
        'reset-password*',
        'two-factor-challenge',
        'install*',
        'up',
        'offline',
        'demo-chat',
        'contact',
        // Let the public widget API still receive conversations/messages
        // so an embedded demo widget keeps working
        'api/widget/*/conversation',
        'api/widget/*/message',
        'api/widget/*/stream',
        'api/widget/*/proactive',
        'api/widget/*/booking',
        'appointments/manage/*/cancel',
        'appointments/manage/*/reschedule',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->isDemoMode()) {
            return $next($request);
        }

        // Read-only requests are always fine
        if (in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            return $next($request);
        }

        // Explicitly whitelisted mutation routes
        foreach (self::ALLOWED_PATTERNS as $pattern) {
            if ($request->is($pattern)) {
                return $next($request);
            }
        }

        // Block everything else
        if ($request->expectsJson()) {
            return response()->json([
                'error'   => 'Demo mode is active. All changes are disabled.',
                'demo'    => true,
            ], 403);
        }

        return back()->with('error', 'This is a live demo — changes are disabled.');
    }

    private function isDemoMode(): bool
    {
        $val = env('DEMO_MODE', 'false');

        return $val === true || $val === 'true' || $val === '1';
    }
}
