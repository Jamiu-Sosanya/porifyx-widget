<?php

namespace App\Http\Middleware;

use App\Models\Widget;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class WidgetRateLimit
{
    public function handle(Request $request, Closure $next)
    {
        $widgetUuid = $request->route('widgetUuid');
        $widgetId = $widgetUuid ?? $request->input('widget_id');
        $ip = $request->ip();
        $key = 'widget-rate:'.$widgetId.':'.$ip;

        $maxAttempts = (int) config('ai.widget.rate_limit_per_minute', 30);
        if ($widgetUuid) {
            $perWidget = cache()->remember('widget-rate-limit:'.$widgetUuid, 60, function () use ($widgetUuid) {
                $v = Widget::where('uuid', $widgetUuid)->value('rate_limit');

                return $v !== null ? (int) $v : null;
            });
            if ($perWidget !== null && $perWidget > 0) {
                $maxAttempts = $perWidget;
            }
        }

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            return response()->json([
                'error' => 'Too many requests. Please slow down.',
                'retry_after' => RateLimiter::availableIn($key),
            ], 429);
        }

        RateLimiter::hit($key, 60);

        return $next($request);
    }
}
