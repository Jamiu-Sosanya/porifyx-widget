<?php

namespace App\Providers;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class BootSystemSettings extends ServiceProvider
{
    public function boot(): void
    {
        // Guard: skip during migrations / if table doesn't exist yet
        if (! $this->tableExists()) {
            return;
        }

        try {
            $settings = SystemSetting::whereNotNull('config_key')
                ->whereNotNull('value')
                ->where('value', '!=', '')
                ->get(['config_key', 'value', 'type']);

            foreach ($settings as $setting) {
                $value = match ($setting->type) {
                    'boolean' => (bool) $setting->value,
                    'integer' => (int)  $setting->value,
                    'json'    => json_decode($setting->value, true),
                    default   => $setting->value,
                };

                config([$setting->config_key => $value]);

                // Keep branding.name in sync with app.name since config/branding.php
                // reads from env at config-load time and never gets updated otherwise.
                if ($setting->config_key === 'app.name') {
                    config(['branding.name' => $value]);
                }
            }
        } catch (\Throwable) {
            // Never crash on boot — settings are optional overrides
        }
    }

    private function tableExists(): bool
    {
        try {
            return Schema::hasTable('system_settings');
        } catch (\Throwable) {
            return false;
        }
    }
}
