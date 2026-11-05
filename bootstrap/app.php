<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Behind Cloudflare / load balancers: honour X-Forwarded-Proto so HTTPS URLs are generated.
        $middleware->trustProxies(at: '*');

        // Redirect all web requests to /install/ until installation is complete
        $middleware->web(prepend: [
            \App\Http\Middleware\EnsureInstalled::class,
        ]);

        // Demo mode runs on every web request (after the session is started)
        $middleware->web(append: [
            \App\Http\Middleware\DemoMode::class,
        ]);

        $middleware->alias([
            'admin.ip'    => \App\Http\Middleware\AdminIpWhitelist::class,
            'AdminOnly'   => \App\Http\Middleware\AdminOnly::class,
            'installer'   => \App\Http\Middleware\InstallerMiddleware::class,
            'demo'        => \App\Http\Middleware\DemoMode::class,
            'subscription' => \App\Http\Middleware\CheckSubscription::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
