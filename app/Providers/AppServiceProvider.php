<?php

namespace App\Providers;

use App\Mail\PasswordResetMail;
use App\Mail\VerifyEmailMail;
use App\Models\SystemSetting;
use App\Models\Widget;
use App\Policies\WidgetPolicy;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Gate::policy(Widget::class, WidgetPolicy::class);

        // Shared class strings for the public site pages (light and dark pairs kept in one place).
        \Illuminate\Support\Facades\View::composer(['pages.*', 'layouts.site', 'layouts.doc'], fn ($view) => $view->with([
            'name'    => config('branding.name'),
            'card'    => 'bg-white border border-slate-200 dark:bg-white/[0.03] dark:border-white/10',
            'muted'   => 'text-slate-600 dark:text-slate-400',
            'eyebrow' => 'text-sm font-bold uppercase tracking-widest text-brand-600 dark:text-brand-400',
        ]));

        $this->bootDynamicMailConfig();
        $this->bootCustomEmailTemplates();
    }

    /**
     * Load SMTP settings from the database so queue workers always use the
     * admin-configured values, not just the stale .env values at worker startup.
     * A 60-second cache prevents a DB hit on every queued job.
     */
    private function bootDynamicMailConfig(): void
    {
        try {
            $settings = cache()->remember('pfx_mail_config', 60, function () {
                return SystemSetting::whereIn('key', [
                    'mail_mailer', 'mail_host', 'mail_port', 'mail_encryption',
                    'mail_username', 'mail_password', 'mail_from_address', 'mail_from_name',
                ])->pluck('value', 'key')->toArray();
            });

            $map = [
                'mail_mailer'       => 'mail.default',
                'mail_host'         => 'mail.mailers.smtp.host',
                'mail_port'         => 'mail.mailers.smtp.port',
                'mail_encryption'   => 'mail.mailers.smtp.encryption',
                'mail_username'     => 'mail.mailers.smtp.username',
                'mail_password'     => 'mail.mailers.smtp.password',
                'mail_from_address' => 'mail.from.address',
                'mail_from_name'    => 'mail.from.name',
            ];

            $updates = [];
            foreach ($map as $settingKey => $configKey) {
                if (!empty($settings[$settingKey])) {
                    $updates[$configKey] = $settingKey === 'mail_port'
                        ? (int) $settings[$settingKey]
                        : $settings[$settingKey];
                }
            }

            if (!empty($updates)) {
                config($updates);
            }
        } catch (\Throwable) {
            // DB not ready yet (installer wizard) — fall back to .env values silently.
        }
    }

    /**
     * Wire Laravel's built-in auth notifications to use our custom branded
     * email templates instead of the plain Laravel defaults.
     */
    private function bootCustomEmailTemplates(): void
    {
        VerifyEmail::toMailUsing(
            fn ($notifiable, $url) => new VerifyEmailMail($notifiable, $url)
        );

        ResetPassword::toMailUsing(
            fn ($notifiable, $url) => new PasswordResetMail($notifiable, $url, request()?->ip())
        );
    }
}
