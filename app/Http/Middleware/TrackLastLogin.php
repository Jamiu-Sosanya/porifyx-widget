<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class TrackLastLogin
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->user() && !$request->user()->last_login_at?->isToday()) {
            $request->user()->update([
                'last_login_at' => now(),
                'last_login_ip' => $request->ip(),
            ]);
        }

        return $next($request);
    }
}
