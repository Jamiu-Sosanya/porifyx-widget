<?php

namespace App\Support;

use App\Models\Appointment;
use App\Models\Widget;
use Carbon\Carbon;
use Carbon\CarbonImmutable;

/**
 * Booking rules for one widget: settings, open days and free time slots.
 * Times are stored in UTC and worked out in the business's timezone.
 */
class Booking
{
    public const DAYS = ['mon' => 'Monday', 'tue' => 'Tuesday', 'wed' => 'Wednesday', 'thu' => 'Thursday', 'fri' => 'Friday', 'sat' => 'Saturday', 'sun' => 'Sunday'];

    public static function defaults(): array
    {
        $weekday = [['09:00', '17:00']];

        return [
            'enabled'          => false,
            'business_name'    => '',
            'button_label'     => 'Book an appointment',
            'timezone'         => config('app.timezone', 'UTC'),
            'slot_step'        => 30,   // minutes between possible start times
            'buffer'           => 0,    // minutes kept free after each appointment
            'min_notice'       => 2,    // hours
            'max_days_ahead'   => 30,
            'daily_limit'      => 0,    // 0 = no limit
            'auto_confirm'     => true,
            'require_phone'    => false,
            'reminder_hours'   => 24,
            'services'         => [['id' => 'consult', 'name' => 'Consultation', 'duration' => 30, 'price' => '', 'description' => '']],
            'hours'            => ['mon' => $weekday, 'tue' => $weekday, 'wed' => $weekday, 'thu' => $weekday, 'fri' => $weekday, 'sat' => [], 'sun' => []],
            'blocked_dates'    => [],
        ];
    }

    public static function settings(Widget $widget): array
    {
        $s = array_replace(self::defaults(), (array) $widget->booking);
        $s['business_name'] = $s['business_name'] ?: $widget->name;

        return $s;
    }

    public static function service(array $settings, string $id): ?array
    {
        foreach ($settings['services'] as $service) {
            if ((string) $service['id'] === $id) {
                return $service;
            }
        }

        return null;
    }

    /** Dates (Y-m-d, business timezone) inside the booking window that have opening hours. */
    public static function openDates(array $s): array
    {
        $tz = $s['timezone'];
        $day = CarbonImmutable::now($tz)->startOfDay();
        $dates = [];
        for ($i = 0; $i <= (int) $s['max_days_ahead']; $i++, $day = $day->addDay()) {
            if (self::windows($s, $day) !== []) {
                $dates[] = $day->toDateString();
            }
        }

        return $dates;
    }

    /** Opening windows for a date as [start, end] pairs in the business timezone. */
    private static function windows(array $s, CarbonImmutable $day): array
    {
        if (in_array($day->toDateString(), (array) $s['blocked_dates'], true)) {
            return [];
        }
        $key = strtolower($day->format('D'));
        $out = [];
        foreach ((array) ($s['hours'][$key] ?? []) as [$from, $to]) {
            $start = $day->setTimeFromTimeString($from);
            $end = $day->setTimeFromTimeString($to);
            if ($end > $start) {
                $out[] = [$start, $end];
            }
        }

        return $out;
    }

    /**
     * Free start times for one service on one date.
     *
     * @return array<int, array{start: string, label: string}> start is UTC ISO-8601
     */
    public static function slots(Widget $widget, array $service, string $date, ?int $ignoreId = null): array
    {
        $s = self::settings($widget);
        $tz = $s['timezone'];

        try {
            $day = CarbonImmutable::createFromFormat('Y-m-d', $date, $tz)->startOfDay();
        } catch (\Throwable) {
            return [];
        }

        $today = CarbonImmutable::now($tz)->startOfDay();
        if ($day < $today || $day > $today->addDays((int) $s['max_days_ahead'])) {
            return [];
        }

        $windows = self::windows($s, $day);
        if ($windows === []) {
            return [];
        }

        $taken = Appointment::where('widget_id', $widget->id)
            ->whereIn('status', Appointment::ACTIVE)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->where('starts_at', '<', $day->addDay()->addHours(24)->utc())
            ->where('ends_at', '>', $day->subHours(24)->utc())
            ->get(['starts_at', 'ends_at']);

        if ($s['daily_limit'] > 0) {
            $sameDay = $taken->filter(fn ($a) => $a->starts_at->copy()->setTimezone($tz)->toDateString() === $date)->count();
            if ($sameDay >= $s['daily_limit']) {
                return [];
            }
        }

        $duration = max(5, (int) $service['duration']);
        $step = max(5, (int) $s['slot_step']);
        $buffer = max(0, (int) $s['buffer']);
        $earliest = CarbonImmutable::now()->addHours((int) $s['min_notice']);
        $slots = [];

        foreach ($windows as [$open, $close]) {
            for ($start = $open; $start->addMinutes($duration) <= $close; $start = $start->addMinutes($step)) {
                $end = $start->addMinutes($duration);
                if ($start < $earliest) {
                    continue;
                }
                $clash = $taken->contains(fn ($a) => $start < Carbon::parse($a->ends_at)->addMinutes($buffer)
                    && $end->addMinutes($buffer) > Carbon::parse($a->starts_at));
                if (! $clash) {
                    $slots[] = ['start' => $start->utc()->toIso8601ZuluString(), 'label' => $start->format('g:i A')];
                }
            }
        }

        return $slots;
    }

    /** True when the exact start time is still free for this service. */
    public static function isFree(Widget $widget, array $service, CarbonImmutable $startUtc, ?int $ignoreId = null): bool
    {
        $date = $startUtc->setTimezone(self::settings($widget)['timezone'])->toDateString();

        return collect(self::slots($widget, $service, $date, $ignoreId))
            ->contains(fn ($slot) => $slot['start'] === $startUtc->utc()->toIso8601ZuluString());
    }
}
