<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/** Authenticates REST API calls with "Authorization: Bearer pfx_..." keys from Settings > API keys. */
class AuthenticateApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken() ?: $request->header('X-Api-Key');
        $key = $token ? ApiKey::where('key', $token)->first() : null;

        if (! $key || ! $key->isValid()) {
            return response()->json(['message' => 'Invalid or missing API key.'], 401);
        }
        if ($key->allowed_ips && ! in_array($request->ip(), $key->allowed_ips, true)) {
            return response()->json(['message' => 'This IP address is not allowed for this key.'], 403);
        }
        $limiter = 'api-key:' . $key->id;
        if (RateLimiter::tooManyAttempts($limiter, $key->rate_limit ?: 120)) {
            return response()->json(['message' => 'Too many requests.'], 429);
        }
        RateLimiter::hit($limiter, 60);

        $key->forceFill(['last_used_at' => now(), 'requests_count' => $key->requests_count + 1])->saveQuietly();
        Auth::setUser($key->user);
        $request->setUserResolver(fn () => $key->user);

        return $next($request);
    }
}
