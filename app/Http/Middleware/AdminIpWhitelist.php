<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class AdminIpWhitelist
{
    public function handle(Request $request, Closure $next): Response
    {
        $whitelist = config('branding.admin_ip_whitelist', '');

        if (empty(trim($whitelist))) {
            return $next($request);
        }

        $allowedIps = array_filter(array_map('trim', explode(',', $whitelist)));

        if (empty($allowedIps)) {
            return $next($request);
        }

        $clientIp = $request->ip();

        foreach ($allowedIps as $allowed) {
            if ($this->ipMatches($clientIp, $allowed)) {
                return $next($request);
            }
        }

        Log::warning('Admin access blocked — IP not whitelisted', [
            'ip'         => $clientIp,
            'path'       => $request->path(),
            'user_agent' => $request->userAgent(),
        ]);

        abort(403, 'Access denied: your IP address is not authorised to access the admin panel.');
    }

    private function ipMatches(string $ip, string $allowed): bool
    {
        if ($ip === $allowed) {
            return true;
        }

        // CIDR range support (e.g. 192.168.1.0/24)
        if (str_contains($allowed, '/')) {
            [$range, $bits] = explode('/', $allowed, 2);
            $mask = -1 << (32 - (int) $bits);
            return (ip2long($ip) & $mask) === (ip2long($range) & $mask);
        }

        // Wildcard support (e.g. 192.168.1.*)
        if (str_contains($allowed, '*')) {
            $pattern = '/^' . str_replace('\*', '\d+', preg_quote($allowed, '/')) . '$/';
            return (bool) preg_match($pattern, $ip);
        }

        return false;
    }
}
