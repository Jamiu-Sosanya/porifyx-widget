<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// ─── Bootstrap: ensure .env exists ────────────────────────────────────────────
$envPath    = __DIR__ . '/../.env';
$envExample = __DIR__ . '/../.env.example';

// Copy .env.example → .env on first-ever request
if (! file_exists($envPath) && file_exists($envExample)) {
    copy($envExample, $envPath);
}

// Ensure APP_KEY has a real value — replace the existing empty key in-place so
// dotenv (which reads the FIRST occurrence) always gets the generated value.
if (file_exists($envPath)) {
    $envContents = file_get_contents($envPath);

    if (! preg_match('/^APP_KEY=.{10}/m', $envContents)) {
        $key = 'base64:' . base64_encode(random_bytes(32));

        if (preg_match('/^APP_KEY=/m', $envContents)) {
            // Replace existing (empty) APP_KEY line in-place
            $envContents = preg_replace_callback(
                '/^APP_KEY=.*$/m',
                fn () => "APP_KEY={$key}",
                $envContents
            );
        } else {
            $envContents .= "\nAPP_KEY={$key}\n";
        }

        file_put_contents($envPath, $envContents);
    }
}
// ──────────────────────────────────────────────────────────────────────────────

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__ . '/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__ . '/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__ . '/../bootstrap/app.php';

$app->handleRequest(Request::capture());
