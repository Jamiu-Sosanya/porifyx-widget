<?php

namespace App\Support;

use App\Models\User;

/** A user's lead statuses (board columns) and Hot/Warm score thresholds (users.lead_settings JSON). */
class LeadPipeline
{
    public const DEFAULT_STATUSES = [
        ['key' => 'new',       'label' => 'New',       'color' => '#6c97d6'],
        ['key' => 'contacted', 'label' => 'Contacted', 'color' => '#8fb4e3'],
        ['key' => 'qualified', 'label' => 'Qualified', 'color' => '#a78bfa'],
        ['key' => 'proposal',  'label' => 'Proposal',  'color' => '#f59e0b'],
        ['key' => 'won',       'label' => 'Won',       'color' => '#10b981'],
        ['key' => 'lost',      'label' => 'Lost',      'color' => '#6b7280'],
    ];

    public static function settings(?User $user): array
    {
        $s = is_array($user?->lead_settings) ? $user->lead_settings : [];
        $statuses = array_values(array_filter((array) ($s['statuses'] ?? []), fn ($x) => is_array($x) && filled($x['key'] ?? null)));

        return [
            'statuses' => $statuses ?: self::DEFAULT_STATUSES,
            'warm'     => (int) ($s['warm'] ?? config('ai.lead_qualification.warm_threshold', 50)),
            'hot'      => (int) ($s['hot'] ?? config('ai.lead_qualification.hot_threshold', 80)),
        ];
    }

    public static function keys(?User $user): array
    {
        return array_column(self::settings($user)['statuses'], 'key');
    }

    public static function temperature(?User $user, int $score): string
    {
        $s = self::settings($user);
        return $score >= $s['hot'] ? 'hot' : ($score >= $s['warm'] ? 'warm' : 'cold');
    }
}
