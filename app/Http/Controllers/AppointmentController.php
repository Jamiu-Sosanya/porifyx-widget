<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Widget;
use App\Services\AppointmentService;
use App\Support\Booking;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AppointmentController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $widgets = $user->widgets()->get(['id', 'name', 'booking']);
        $view = in_array($request->view, ['upcoming', 'past', 'all'], true) ? $request->view : 'upcoming';

        $base = Appointment::where('user_id', $user->id)->with('widget:id,name,booking');
        $query = (clone $base)
            ->when($request->filled('widget'), fn ($q) => $q->where('widget_id', $request->widget))
            ->when(array_key_exists($request->status, Appointment::STATUSES), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w->where('name', 'like', "%{$request->q}%")->orWhere('email', 'like', "%{$request->q}%")))
            ->when($view === 'upcoming', fn ($q) => $q->where('ends_at', '>=', now())->orderBy('starts_at'))
            ->when($view === 'past', fn ($q) => $q->where('ends_at', '<', now())->orderByDesc('starts_at'))
            ->when($view === 'all', fn ($q) => $q->orderByDesc('starts_at'));

        $appointments = $query->paginate(40)->withQueryString();
        $days = $appointments->getCollection()->groupBy(fn ($a) => $a->localStart()->toDateString());

        $stats = [
            'today'    => (clone $base)->whereIn('status', Appointment::ACTIVE)->whereBetween('starts_at', [now()->startOfDay(), now()->endOfDay()])->count(),
            'week'     => (clone $base)->whereIn('status', Appointment::ACTIVE)->whereBetween('starts_at', [now(), now()->addDays(7)])->count(),
            'pending'  => (clone $base)->where('status', 'pending')->where('starts_at', '>=', now())->count(),
            'no_show'  => (function () use ($base) {
                $done = (clone $base)->whereIn('status', ['completed', 'no_show'])->count();

                return $done ? round((clone $base)->where('status', 'no_show')->count() / $done * 100) : 0;
            })(),
        ];

        return view('appointments.index', compact('appointments', 'days', 'widgets', 'view', 'stats'));
    }

    public function update(Request $request, Appointment $appointment, AppointmentService $service)
    {
        abort_unless($appointment->user_id === $request->user()->id, 403);
        $data = $request->validate(['status' => ['required', Rule::in(array_keys(Appointment::STATUSES))]]);
        $service->setStatus($appointment, $data['status']);

        return back()->with('success', 'Booking marked as ' . strtolower(Appointment::STATUSES[$data['status']]) . '.');
    }

    public function settings(Request $request, Widget $widget)
    {
        abort_unless($widget->user_id === $request->user()->id, 403);

        return view('appointments.settings', [
            'widget'    => $widget,
            'settings'  => Booking::settings($widget),
            'timezones' => \DateTimeZone::listIdentifiers(),
        ]);
    }

    public function saveSettings(Request $request, Widget $widget)
    {
        abort_unless($widget->user_id === $request->user()->id, 403);
        $time = 'required|date_format:H:i';
        $data = $request->validate([
            'business_name'          => 'nullable|string|max:120',
            'button_label'           => 'required|string|max:40',
            'timezone'               => 'required|timezone',
            'slot_step'              => 'required|integer|in:10,15,20,30,45,60,90,120',
            'buffer'                 => 'required|integer|min:0|max:240',
            'min_notice'             => 'required|integer|min:0|max:720',
            'max_days_ahead'         => 'required|integer|min:1|max:365',
            'daily_limit'            => 'required|integer|min:0|max:500',
            'reminder_hours'         => 'required|integer|min:0|max:168',
            'services'               => 'required|array|min:1|max:30',
            'services.*.id'          => 'required|string|max:40|alpha_dash|distinct',
            'services.*.name'        => 'required|string|max:80',
            'services.*.duration'    => 'required|integer|min:5|max:600',
            'services.*.price'       => 'nullable|string|max:30',
            'services.*.description' => 'nullable|string|max:200',
            'hours'                  => 'array',
            'hours.*'                => 'array|max:6',
            'hours.*.*.0'            => $time,
            'hours.*.*.1'            => $time,
            'blocked_dates'          => 'nullable|string|max:4000',
        ]);

        $hours = [];
        foreach (array_keys(Booking::DAYS) as $day) {
            $hours[$day] = array_values(array_filter(
                array_map(fn ($w) => [$w[0], $w[1]], $data['hours'][$day] ?? []),
                fn ($w) => $w[1] > $w[0]
            ));
        }

        $blocked = collect(preg_split('/[\s,]+/', (string) ($data['blocked_dates'] ?? ''), -1, PREG_SPLIT_NO_EMPTY))
            ->filter(fn ($d) => preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) && strtotime($d))
            ->unique()->sort()->values()->all();

        $widget->update(['booking' => array_merge($data, [
            'enabled'       => $request->boolean('enabled'),
            'auto_confirm'  => $request->boolean('auto_confirm'),
            'require_phone' => $request->boolean('require_phone'),
            'services'      => array_values($data['services']),
            'hours'         => $hours,
            'blocked_dates' => $blocked,
        ])]);

        return back()->with('success', 'Booking settings saved.');
    }

    /** Public page where a visitor sees or cancels their booking (link from their email). */
    public function manage(string $token)
    {
        $appointment = Appointment::where('token', $token)->with('widget')->firstOrFail();

        return view('appointments.manage', ['a' => $appointment, 's' => Booking::settings($appointment->widget)]);
    }

    public function cancel(string $token, AppointmentService $service)
    {
        $appointment = Appointment::where('token', $token)->firstOrFail();
        if (in_array($appointment->status, Appointment::ACTIVE, true) && $appointment->starts_at->isFuture()) {
            $service->setStatus($appointment, 'cancelled', byVisitor: true);
        }

        return redirect()->route('appointments.manage', $token)->with('cancelled', true);
    }
}
