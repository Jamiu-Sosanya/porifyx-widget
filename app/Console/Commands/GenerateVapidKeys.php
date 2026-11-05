<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Minishlink\WebPush\VAPID;

class GenerateVapidKeys extends Command
{
    protected $signature   = 'webpush:vapid';
    protected $description = 'Generate VAPID public/private key pair and write them to .env';

    public function handle(): int
    {
        $envPath = base_path('.env');

        if (! file_exists($envPath)) {
            $this->error('.env file not found. Run the installer first.');
            return self::FAILURE;
        }

        try {
            $keys = VAPID::createVapidKeys();
        } catch (\Throwable $e) {
            $this->error('VAPID key generation failed: ' . $e->getMessage());
            $this->line('Ensure your server has OpenSSL with EC (prime256v1) support enabled.');
            return self::FAILURE;
        }

        $content = file_get_contents($envPath);
        $content = $this->writeLine($content, 'VAPID_PUBLIC_KEY',  $keys['publicKey']);
        $content = $this->writeLine($content, 'VAPID_PRIVATE_KEY', $keys['privateKey']);

        // Set subject to mailto:admin@{domain} if it's still the placeholder
        if (preg_match('/^VAPID_SUBJECT=mailto:admin@yourdomain\.com\s*$/m', $content) ||
            preg_match('/^VAPID_SUBJECT=\s*$/m', $content)) {
            $domain  = parse_url(config('app.url', 'http://localhost'), PHP_URL_HOST) ?: 'example.com';
            $content = $this->writeLine($content, 'VAPID_SUBJECT', 'mailto:admin@' . $domain);
        }

        file_put_contents($envPath, $content);

        $this->info('VAPID keys generated and written to .env');
        $this->line('  Public key : ' . substr($keys['publicKey'], 0, 30) . '...');
        $this->line('  Private key: ' . substr($keys['privateKey'], 0, 30) . '...');
        $this->line('');
        $this->line('Run <comment>php artisan config:clear</comment> to apply the new keys.');

        return self::SUCCESS;
    }

    private function writeLine(string $content, string $key, string $value): string
    {
        $formatted = "{$key}={$value}";
        $pattern   = "/^{$key}=.*$/m";

        if (preg_match($pattern, $content)) {
            return preg_replace_callback($pattern, fn () => $formatted, $content);
        }

        return rtrim($content) . "\n{$formatted}\n";
    }
}
