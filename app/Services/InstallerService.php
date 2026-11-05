<?php

namespace App\Services;

use App\Models\User;
use App\Services\EnvWriter;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Minishlink\WebPush\VAPID;

class InstallerService
{
    public function checkServerRequirements(): array
    {
        return [
            [
                'name'    => 'PHP Version',
                'version' => '8.2+',
                'status'  => version_compare(PHP_VERSION, '8.2.0') >= 0,
                'current' => PHP_VERSION,
            ],
            [
                'name'    => 'OpenSSL Extension',
                'status'  => extension_loaded('openssl'),
                'current' => extension_loaded('openssl') ? 'Enabled' : 'Disabled',
            ],
            [
                'name'    => 'PDO Extension',
                'status'  => extension_loaded('pdo'),
                'current' => extension_loaded('pdo') ? 'Enabled' : 'Disabled',
            ],
            [
                'name'    => 'Mbstring Extension',
                'status'  => extension_loaded('mbstring'),
                'current' => extension_loaded('mbstring') ? 'Enabled' : 'Disabled',
            ],
            [
                'name'    => 'Tokenizer Extension',
                'status'  => extension_loaded('tokenizer'),
                'current' => extension_loaded('tokenizer') ? 'Enabled' : 'Disabled',
            ],
            [
                'name'    => 'Curl Extension',
                'status'  => extension_loaded('curl'),
                'current' => extension_loaded('curl') ? 'Enabled' : 'Disabled',
            ],
            [
                'name'    => 'JSON Extension',
                'status'  => extension_loaded('json'),
                'current' => extension_loaded('json') ? 'Enabled' : 'Disabled',
            ],
            [
                'name'    => 'GD Library',
                'status'  => extension_loaded('gd'),
                'current' => extension_loaded('gd') ? 'Enabled' : 'Disabled',
            ],
            [
                'name'    => 'Zip Extension',
                'status'  => extension_loaded('zip'),
                'current' => extension_loaded('zip') ? 'Enabled' : 'Disabled',
            ],
        ];
    }

    public function checkFilePermissions(): array
    {
        $basePath = base_path();

        return [
            [
                'name'     => 'bootstrap/cache',
                'path'     => $basePath . '/bootstrap/cache',
                'writable' => is_writable($basePath . '/bootstrap/cache'),
            ],
            [
                'name'     => 'storage',
                'path'     => $basePath . '/storage',
                'writable' => is_writable($basePath . '/storage'),
            ],
            [
                'name'     => 'storage/app',
                'path'     => $basePath . '/storage/app',
                'writable' => is_writable($basePath . '/storage/app'),
            ],
            [
                'name'     => 'storage/framework',
                'path'     => $basePath . '/storage/framework',
                'writable' => is_writable($basePath . '/storage/framework'),
            ],
            [
                'name'     => 'storage/logs',
                'path'     => $basePath . '/storage/logs',
                'writable' => is_writable($basePath . '/storage/logs'),
            ],
            [
                'name'     => 'public/storage',
                'path'     => $basePath . '/public/storage',
                'writable' => is_writable($basePath . '/public/storage'),
            ],
            [
                'name'     => '.env file',
                'path'     => $basePath . '/.env',
                'writable' => ! file_exists($basePath . '/.env') || is_writable($basePath . '/.env'),
            ],
        ];
    }

    public function createEnvironmentFile(array $config): void
    {
        $envPath = base_path('.env');

        if (! file_exists($envPath)) {
            $envExample = base_path('.env.example');
            if (! file_exists($envExample)) {
                throw new \Exception('.env.example file not found!');
            }
            if (! copy($envExample, $envPath)) {
                throw new \Exception('Could not create .env file. Check that the root directory is writable.');
            }
        }

        $envContent = file_get_contents($envPath);

        $envContent = $this->updateEnvValue($envContent, 'APP_NAME',      $config['app_name']);
        $envContent = $this->updateEnvValue($envContent, 'APP_URL',       $config['app_url']);
        $envContent = $this->updateEnvValue($envContent, 'DB_CONNECTION', $config['db_connection']);
        $envContent = $this->updateEnvValue($envContent, 'DB_HOST',       $config['db_host']);
        $envContent = $this->updateEnvValue($envContent, 'DB_PORT',       (string) $config['db_port']);
        $envContent = $this->updateEnvValue($envContent, 'DB_DATABASE',   $config['db_database']);
        $envContent = $this->updateEnvValue($envContent, 'DB_USERNAME',   $config['db_username']);
        $envContent = $this->updateEnvValue($envContent, 'DB_PASSWORD',   $config['db_password'] ?? '');

        // Generate APP_KEY only if it isn't already a valid base64 key
        if (! preg_match('/^APP_KEY=base64:/m', $envContent)) {
            $appKey     = 'base64:' . base64_encode(random_bytes(32));
            $envContent = $this->updateEnvValue($envContent, 'APP_KEY', $appKey);
        }

        // Generate Reverb credentials if not already set to real values
        if (preg_match('/^REVERB_APP_KEY=your-app-key\s*$/m', $envContent) ||
            preg_match('/^REVERB_APP_KEY=\s*$/m', $envContent)) {
            $envContent = $this->updateEnvValue($envContent, 'REVERB_APP_ID',     (string) random_int(100_000, 999_999));
            $envContent = $this->updateEnvValue($envContent, 'REVERB_APP_KEY',    Str::lower(Str::random(20)));
            $envContent = $this->updateEnvValue($envContent, 'REVERB_APP_SECRET', Str::lower(Str::random(20)));
        }

        // Generate VAPID keys if not already set
        if (preg_match('/^VAPID_PUBLIC_KEY=\s*$/m', $envContent)) {
            try {
                $vapid      = VAPID::createVapidKeys();
                $domain     = parse_url($config['app_url'], PHP_URL_HOST) ?: 'example.com';
                $envContent = $this->updateEnvValue($envContent, 'VAPID_PUBLIC_KEY',  $vapid['publicKey']);
                $envContent = $this->updateEnvValue($envContent, 'VAPID_PRIVATE_KEY', $vapid['privateKey']);
                $envContent = $this->updateEnvValue($envContent, 'VAPID_SUBJECT',     'mailto:admin@' . $domain);
            } catch (\Throwable) {
                // VAPID generation is non-fatal; keys can be generated manually later
            }
        }

        if (file_put_contents($envPath, $envContent) === false) {
            throw new \Exception('Could not write .env file. Check that the root directory is writable.');
        }

        // Delete the compiled config cache file directly — calling Artisan::call('config:clear')
        // in a web request boots a second kernel instance and disrupts the current session.
        EnvWriter::clearConfigCache();
    }

    /**
     * Test that a PDO connection can be established with the current runtime config.
     * Call this after createEnvironmentFile() on a fresh request (so config is reloaded).
     */
    public function testDatabaseConnection(): void
    {
        try {
            DB::purge();
            DB::reconnect();
            DB::connection()->getPdo();
        } catch (\Exception $e) {
            throw new \Exception(
                'Cannot connect to the database. Please go back and verify your credentials. ' .
                'Server said: ' . $e->getMessage()
            );
        }
    }

    public function runMigrations(): void
    {
        try {
            // Give migrations enough time on slow shared hosting (default is often 30s).
            @set_time_limit(300);

            // Always use migrate:fresh during installation so any tables left over
            // from a previous (partial) install attempt are cleanly dropped first.
            DB::purge();
            DB::reconnect();
            Artisan::call('migrate:fresh', ['--force' => true]);

            // Always seed plans and system settings — the app won't function
            // without these records regardless of whether demo data is wanted.
            Artisan::call('db:seed', [
                '--class' => 'Database\\Seeders\\PlanSeeder',
                '--force' => true,
            ]);
            Artisan::call('db:seed', [
                '--class' => 'Database\\Seeders\\SystemSettingsSeeder',
                '--force' => true,
            ]);
        } catch (\Exception $e) {
            throw new \Exception('Migration failed: ' . $e->getMessage());
        }
    }

    public function seedDemoData(): void
    {
        try {
            // DemoSeeder creates sample users, widgets, conversations, and leads.
            // Uses updateOrCreate / firstOrCreate throughout so it is idempotent.
            Artisan::call('db:seed', [
                '--class' => 'Database\\Seeders\\DemoSeeder',
                '--force' => true,
            ]);
        } catch (\Exception $e) {
            throw new \Exception('Demo seeding failed: ' . $e->getMessage());
        }
    }

    public function createAdminUser(array $data): void
    {
        try {
            // Do NOT bcrypt here — User model has 'password' => 'hashed' cast
            $user = User::create([
                'name'              => $data['name'],
                'email'             => $data['email'],
                'password'          => $data['password'],
                'role'              => 'admin',
                'is_active'         => true,
                'email_verified_at' => now(),
            ]);

            // Assign Spatie role as well (supplementary — admin access uses role column)
            if (method_exists($user, 'assignRole')) {
                try {
                    \Spatie\Permission\Models\Role::firstOrCreate(
                        ['name' => 'admin', 'guard_name' => 'web']
                    );
                    $user->assignRole('admin');
                } catch (\Exception) {
                    // Non-fatal — isAdmin() checks the role column, not Spatie roles
                }
            }
        } catch (\Exception $e) {
            throw new \Exception('Admin user creation failed: ' . $e->getMessage());
        }
    }

    public function markInstallationComplete(): void
    {
        $envPath    = base_path('.env');
        $envContent = file_get_contents($envPath);

        $envContent = $this->updateEnvValue($envContent, 'INSTALLATION_COMPLETE', 'true');

        file_put_contents($envPath, $envContent);

        // Clear caches via direct file deletion — Artisan::call() in a web context
        // boots a second kernel that disrupts session state and can 500 on shared hosting.
        EnvWriter::clearConfigCache();
        $this->clearFileCache();
    }

    private function clearFileCache(): void
    {
        $cacheDir = storage_path('framework/cache/data');
        if (! is_dir($cacheDir)) {
            return;
        }
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($cacheDir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $file) {
            if ($file->isFile()) {
                @unlink($file->getPathname());
            }
        }
    }

    /**
     * Read directly from the .env file so the result is never stale due to
     * PHP's runtime env cache or Laravel's config cache.
     */
    public static function isInstallationComplete(): bool
    {
        $envPath = base_path('.env');

        if (! file_exists($envPath)) {
            return false;
        }

        $content = file_get_contents($envPath);

        return (bool) preg_match('/^INSTALLATION_COMPLETE=true\s*$/m', $content);
    }

    // ─── Private helpers ─────────────────────────────────────────────────────

    /**
     * Replace (or append) a key=value pair in .env content.
     *
     * Values that contain whitespace, quotes, or # are wrapped in double quotes.
     * Uses preg_replace_callback so the replacement string is never interpreted
     * for regex backreferences.
     */
    private function updateEnvValue(string $content, string $key, string $value): string
    {
        // Quote values that contain whitespace, #, ', or "
        if ($value !== '' && preg_match('/[\s#"\']/', $value)) {
            $formatted = "{$key}=\"{$value}\"";
        } else {
            $formatted = "{$key}={$value}";
        }

        $pattern = "/^{$key}=.*$/m";

        if (preg_match($pattern, $content)) {
            // Use callback so $formatted is never interpreted as a replacement expression
            return preg_replace_callback($pattern, fn () => $formatted, $content);
        }

        return rtrim($content) . "\n{$formatted}\n";
    }
}
