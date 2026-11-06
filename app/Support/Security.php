<?php

namespace App\Support;

use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;

/** Firewall, login protection, security events and the security score shown in Admin > Security & performance. */
class Security
{
    public const DEFAULTS = [
        'headers' => true, 'hsts' => false, 'block_bad_bots' => true, 'force_admin_2fa' => false,
        'login_max' => 5, 'login_decay' => 15, 'auto_ban' => true, 'ban_after' => 3, 'ban_minutes' => 60,
        'blocked_ips' => [], 'allowed_ips' => [],
    ];

    public const BAD_BOTS = ['sqlmap', 'nikto', 'masscan', 'zgrab', 'nmap', 'acunetix', 'wpscan', 'dirbuster', 'gobuster', 'nuclei', 'havij'];

    public static function settings(): array
    {
        return Cache::remember('pfx:security', 60, function () {
            $v = SystemSetting::get('security');
            $v = is_array($v) ? $v : (json_decode((string) $v, true) ?: []);

            return array_merge(self::DEFAULTS, $v);
        });
    }

    public static function save(array $values): void
    {
        SystemSetting::updateOrCreate(['key' => 'security'], ['label' => 'Security', 'type' => 'json', 'group' => 'security', 'value' => json_encode(array_merge(self::settings(), $values))]);
        Cache::forget('pfx:security');
    }

    public static function record(string $type, ?string $ip = null, ?string $email = null, ?string $detail = null): void
    {
        try {
            DB::table('security_events')->insert(['type' => $type, 'ip' => $ip, 'email' => $email ? mb_substr($email, 0, 190) : null,
                'detail' => $detail ? mb_substr($detail, 0, 500) : null, 'created_at' => now()]);
        } catch (\Throwable) {
            // never break a request because logging failed
        }
    }

    /** True when an IP or CIDR list contains the address. */
    public static function ipIn(string $ip, array $list): bool
    {
        foreach ($list as $rule) {
            $rule = trim($rule);
            if ($rule === '') continue;
            if (! str_contains($rule, '/')) { if ($rule === $ip) return true; continue; }
            [$net, $bits] = explode('/', $rule, 2);
            $a = @inet_pton($ip); $n = @inet_pton($net);
            if (! $a || ! $n || strlen($a) !== strlen($n)) continue;
            $bits = (int) $bits; $bytes = intdiv($bits, 8); $rem = $bits % 8;
            if (strncmp($a, $n, $bytes) !== 0) continue;
            if ($rem === 0 || ((ord($a[$bytes]) ^ ord($n[$bytes])) & (0xFF << (8 - $rem)) & 0xFF) === 0) return true;
        }

        return false;
    }

    public static function isBanned(string $ip): bool
    {
        $s = self::settings();
        if (self::ipIn($ip, (array) $s['allowed_ips'])) return false;

        return self::ipIn($ip, (array) $s['blocked_ips']) || Cache::has('pfx:ban:' . $ip);
    }

    /** Called on every failed login. Returns an error message when the caller is locked out. */
    public static function failedLogin(string $email, string $ip): ?string
    {
        $s = self::settings();
        $key = 'login:' . mb_strtolower($email) . '|' . $ip;
        RateLimiter::hit($key, $s['login_decay'] * 60);
        self::record('login_failed', $ip, $email);
        if (RateLimiter::tooManyAttempts($key, $s['login_max'])) {
            self::record('lockout', $ip, $email, "Locked for {$s['login_decay']} minutes");
            if ($s['auto_ban'] && RateLimiter::hit('lockouts:' . $ip, 3600) >= $s['ban_after']) {
                Cache::put('pfx:ban:' . $ip, true, now()->addMinutes($s['ban_minutes']));
                self::record('auto_ban', $ip, $email, "Banned for {$s['ban_minutes']} minutes after repeated lockouts");
            }
        }

        return self::lockedMessage($email, $ip);
    }

    public static function lockedMessage(string $email, string $ip): ?string
    {
        $key = 'login:' . mb_strtolower($email) . '|' . $ip;
        if (! RateLimiter::tooManyAttempts($key, self::settings()['login_max'])) return null;

        return 'Too many attempts. Try again in ' . max(1, (int) ceil(RateLimiter::availableIn($key) / 60)) . ' minute(s).';
    }

    public static function clearLogin(string $email, string $ip): void
    {
        RateLimiter::clear('login:' . mb_strtolower($email) . '|' . $ip);
    }

    /** [label, ok|warn|fail, detail, fix] */
    public static function checks(): array
    {
        $s = self::settings();
        $admins = User::where('role', 'admin')->count();
        $admins2fa = User::where('role', 'admin')->where('two_factor_enabled', true)->count();
        $https = str_starts_with((string) config('app.url'), 'https://');
        $prod = app()->environment('production');

        return [
            ['Debug mode off', config('app.debug') ? ($prod ? 'fail' : 'warn') : 'ok', config('app.debug') ? 'APP_DEBUG=true shows code and secrets on errors' : 'Errors are hidden from visitors', 'Set APP_DEBUG=false in .env'],
            ['Production environment', $prod ? 'ok' : 'warn', 'APP_ENV=' . app()->environment(), 'Set APP_ENV=production on the live server'],
            ['HTTPS', $https ? 'ok' : 'fail', (string) config('app.url'), 'Use an https:// APP_URL and an SSL certificate'],
            ['Secure cookies', config('session.secure') || $https ? 'ok' : 'warn', config('session.secure') ? 'Session cookie is HTTPS-only' : 'SESSION_SECURE_COOKIE not set', 'Set SESSION_SECURE_COOKIE=true'],
            ['App key', filled(config('app.key')) ? 'ok' : 'fail', filled(config('app.key')) ? 'Encryption key set' : 'APP_KEY missing', 'Run php artisan key:generate'],
            ['Admin two-factor', $admins && $admins2fa === $admins ? 'ok' : 'warn', "{$admins2fa} of {$admins} admins use 2FA", 'Turn on "Require 2FA for admins" below'],
            ['Security headers', $s['headers'] ? 'ok' : 'warn', $s['headers'] ? 'Clickjacking, sniffing and referrer protection on' : 'Off', 'Turn on security headers'],
            ['Login protection', $s['login_max'] <= 10 ? 'ok' : 'warn', "Lock after {$s['login_max']} tries for {$s['login_decay']} min" . ($s['auto_ban'] ? ', auto-ban on' : ''), 'Lower the attempt limit'],
            ['.env not public', ! file_exists(public_path('.env')) ? 'ok' : 'fail', file_exists(public_path('.env')) ? '.env is inside public/' : 'Secrets are outside the web root', 'Move .env out of public/'],
            ['Stripe webhook signed', ! filled(config('services.stripe.secret')) || filled(config('services.stripe.webhook_secret')) ? 'ok' : 'fail', 'Unsigned webhooks are rejected', 'Add the Stripe webhook secret'],
        ];
    }

    public static function score(array $checks): int
    {
        $pts = array_sum(array_map(fn ($c) => ['ok' => 2, 'warn' => 1, 'fail' => 0][$c[1]], $checks));

        return (int) round($pts / (count($checks) * 2) * 100);
    }
}
