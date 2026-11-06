<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckSubscription
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        if ($user->isAdmin()) {
            return $next($request);
        }

        $subscription = $user->subscription;

        if (($subscription && $subscription->isActive()) || \App\Models\User::freePlan()) {
            return $next($request);
        }

        // Grace period: allow access for new users within the trial window even if
        // the subscription record was not created (e.g. plan not found at registration).
        $trialDays = (int) config('plans.trial_days', 14);
        if ($user->created_at && $user->created_at->addDays($trialDays)->isFuture()) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json(['error' => 'Active subscription required.'], 403);
        }

        return redirect()->route('billing.plans')->with('warning', 'Your trial has ended. Please subscribe to a plan to continue.');
    }
}
