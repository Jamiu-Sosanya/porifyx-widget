<?php

namespace App\Support;

use App\Models\Appointment;
use App\Models\Widget;
use App\Services\CalendarService;
use Carbon\Carbon;
use Carbon\CarbonImmutable;

/**
 * Booking rules for one widget: settings, open days, staff and free time slots.
 * Times are stored in UTC and worked out in the business's timezone.
 */
class Booking
{
    public const DAYS = ['mon' => 'Monday', 'tue' => 'Tuesday', 'wed' => 'Wednesday', 'thu' => 'Thursday', 'fri' => 'Friday', 'sat' => 'Saturday', 'sun' => 'Sunday'];

    public const QUESTION_TYPES = ['text' => 'Short answer', 'textarea' => 'Long answer', 'select' => 'Dropdown', 'checkbox' => 'Tick box'];

    public static function defaults(): array
    {
        $weekday = [['09:00', '17:00']];

        return [
            'enabled'        => false,
            'business_name'  => '',
            'button_label'   => 'Book an appointment',
            'timezone'       => config('app.timezone', 'UTC'),
            'slot_step'      => 30,   // minutes between possible start times
            'buffer'         => 0,    // minutes kept free after each appointment
            'min_notice'     => 2,    // hours
            'max_days_ahead' => 30,
            'daily_limit'    => 0,    // 0 = no limit
            'auto_confirm'   => true,
            'require_phone'  => false,
            'reminder_hours' => 24,
            'allow_reschedule' => true,
            'cancel_notice'  => 0,    // hours before start after which visitors can no longer cancel/reschedule
            'ai_booking'     => true, // let the AI offer the booking button in conversation
            'services'       => [['id' => 'consult', 'name' => 'Consultation', 'duration' => 30, 'price' => '', 'description' => '']],
            // staff: [{id, name, email, services: [ids] (empty = all), days: [mon..] (empty = every open day)}]
            'staff'          => [],
            // questions: [{id, label, type, options: "a, b", required}]
            'questions'      => [],
            'hours'          => ['mon' => $weekday, 'tue' => $weekday, 'wed' => $weekday, 'thu' => $weekday, 'fri' => $weekday, 'sat' => [], 'sun' => []],
            'blocked_dates'  => [],
            // Branding for emails and pages visitors see
            'logo'           => null, // path on the public disk
            'website'        => '',
            'reply_to'       => '',
        ];
    }

    public static function settings(Widget $widget): array
    {
        $s = array_replace(self::defaults(), (array) $widget->booking);
        $s['business_name'] = $s['business_name'] ?: $widget->name;

        return $s;
    }

    public static function service(array $s, ?string $id): ?array
    {
        foreach ($s['services'] as $service) {
            if ((string) $service['id'] === (string) $id) {
                return $service;
            }
        }

        return null;
    }

    /** Staff who can deliver a service. Empty array means the business has no staff list. */
    public static function staffFor(array $s, array $service, ?string $staffId = null): array
    {
        $staff = array_values(array_filter($s['staff'], fn ($m) => empty($m['services']) || in_array($service['id'], (array) $m['services'], true)));
        if ($staffId && $staffId !== 'any') {
            $staff = array_values(array_filter($staff, fn ($m) => (string) $m['id'] === $staffId));
        }

        return $staff;
    }

    /** Dates (Y-m-d, business timezone) inside the booking window that have opening hours. */
    public static function openDates(array $s): array
    {
        $day = CarbonImmutable::now($s['timezone'])->startOfDay();
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
        $out = [];
        foreach ((array) ($s['hours'][strtolower($day->format('D'))] ?? []) as [$from, $to]) {
            $start = $day->setTimeFromTimeString($from);
            $end = $day->setTimeFromTimeString($to);
            if ($end > $start) {
                $out[] = [$start, $end];
            }
        }

        return $out;
    }

    /**
     * Busy intervals per resource for a time range: bookings plus connected calendars.
     *
     * @return array<string, array<int, array{0: Carbon, 1: Carbon}>> keyed by staff id ('' when no staff list)
     */
    private static function busy(Widget $widget, array $resources, CarbonImmutable $from, CarbonImmutable $to, ?int $ignoreId): array
    {
        $appointments = Appointment::where('widget_id', $widget->id)->holdingSlot()
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->where('starts_at', '<', $to->utc())->where('ends_at', '>', $from->utc())
            ->get(['staff_id', 'starts_at', 'ends_at']);

        $calendars = app(CalendarService::class);
        $shared = $calendars->busyFor($widget, null, $from, $to);
        $busy = [];
        foreach ($resources as $key) {
            $own = $appointments->filter(fn ($a) => $key === '' || (string) $a->staff_id === $key)
                ->map(fn ($a) => [Carbon::parse($a->starts_at), Carbon::parse($a->ends_at)])->values()->all();
            $busy[$key] = array_merge($own, $shared, $key === '' ? [] : $calendars->busyFor($widget, $key, $from, $to));
        }

        return $busy;
    }

    /**
     * Free start times for one service on one date.
     * $staffId: a staff id, 'any' / null for whoever is free.
     *
     * @return array<int, array{start: string, label: string, staff: array<int, string>}> start is UTC ISO-8601
     */
    public static function slots(Widget $widget, array $service, string $date, ?string $staffId = null, ?int $ignoreId = null): array
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

        // Resources: each eligible staff member working that day, or the business as one diary.
        $dayKey = strtolower($day->format('D'));
        $resources = $s['staff'] === []
            ? ['' => null]
            : collect(self::staffFor($s, $service, $staffId))
                ->filter(fn ($m) => empty($m['days']) || in_array($dayKey, (array) $m['days'], true))
                ->keyBy(fn ($m) => (string) $m['id'])->all();
        if ($resources === []) {
            return [];
        }

        if ($s['daily_limit'] > 0) {
            $count = Appointment::where('widget_id', $widget->id)->holdingSlot()
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->whereBetween('starts_at', [$day->utc(), $day->addDay()->utc()])->count();
            if ($count >= $s['daily_limit']) {
                return [];
            }
        }

        $busy = self::busy($widget, array_map('strval', array_keys($resources)), $day->subDay(), $day->addDays(2), $ignoreId);
        $duration = max(5, (int) $service['duration']);
        $step = max(5, (int) $s['slot_step']);
        $buffer = max(0, (int) $s['buffer']);
        $earliest = CarbonImmutable::now()->addHours((int) $s['min_notice']);
        $slots = [];

        foreach ($windows as [$open, $close]) {
            for ($start = $open; $start->addMinutes($duration) <= $close; $start = $start->addMinutes($step)) {
                if ($start < $earliest) {
                    continue;
                }
                $end = $start->addMinutes($duration);
                $free = [];
                foreach ($busy as $key => $intervals) {
                    $clash = false;
                    foreach ($intervals as [$b0, $b1]) {
                        if ($start < $b1->copy()->addMinutes($buffer) && $end->addMinutes($buffer) > $b0) {
                            $clash = true;
                            break;
                        }
                    }
                    if (! $clash) {
                        $free[] = (string) $key;
                    }
                }
                if ($free) {
                    $slots[] = ['start' => $start->utc()->toIso8601ZuluString(), 'label' => $start->format('g:i A'), 'staff' => $free];
                }
            }
        }

        return $slots;
    }

    /**
     * Returns the resource that can take this exact start time, or false.
     * Resource is a staff array, or null when the business has no staff list.
     * With "any", picks the staff member with the fewest bookings that day.
     */
    public static function claim(Widget $widget, array $service, CarbonImmutable $startUtc, ?string $staffId = null, ?int $ignoreId = null): array|null|false
    {
        $s = self::settings($widget);
        $date = $startUtc->setTimezone($s['timezone'])->toDateString();
        $slot = collect(self::slots($widget, $service, $date, $staffId, $ignoreId))
            ->firstWhere('start', $startUtc->utc()->toIso8601ZuluString());
        if (! $slot) {
            return false;
        }
        if ($s['staff'] === []) {
            return null;
        }

        $load = Appointment::where('widget_id', $widget->id)->holdingSlot()
            ->whereIn('staff_id', $slot['staff'])
            ->whereBetween('starts_at', [$startUtc->startOfDay(), $startUtc->endOfDay()])
            ->selectRaw('staff_id, count(*) as n')->groupBy('staff_id')->pluck('n', 'staff_id');
        $pick = collect($slot['staff'])->sortBy(fn ($id) => $load[$id] ?? 0)->first();

        return collect($s['staff'])->first(fn ($m) => (string) $m['id'] === $pick) ?? false;
    }

    public static function logoUrl(array $s): ?string
    {
        return $s['logo'] ? asset('storage/' . $s['logo']) : null;
    }

    /** Where visitor replies go: the business, never the platform. */
    public static function replyTo(Widget $widget, array $s): ?string
    {
        return $s['reply_to'] ?: ($widget->notification_email ?: $widget->user?->email);
    }

    /** Visitors may only move a booking to a time they could still change later. */
    public static function visitorMoveAllowed(array $s, string $startIso): bool
    {
        return CarbonImmutable::parse($startIso)->greaterThanOrEqualTo(now()->addHours((int) $s['cancel_notice']));
    }

    /** True when visitors may still change this booking themselves. */
    public static function visitorCanChange(Appointment $a, array $s): bool
    {
        return in_array($a->status, Appointment::ACTIVE, true)
            && $a->starts_at->isAfter(now()->addHours((int) $s['cancel_notice']));
    }
}
