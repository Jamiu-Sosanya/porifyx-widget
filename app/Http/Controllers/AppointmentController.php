<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Widget;
use App\Services\AppointmentService;
use App\Services\BookingPaymentService;
use App\Support\Booking;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\Rule;

class AppointmentController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $widgets = $user->widgets()->get(['id', 'uuid', 'name', 'booking']);
        $view = in_array($request->view, ['upcoming', 'past', 'all', 'week'], true) ? $request->view : 'upcoming';

        $base = Appointment::where('user_id', $user->id)->with('widget:id,uuid,name,booking');
        $filtered = (clone $base)
            ->when($request->filled('widget'), fn ($q) => $q->where('widget_id', $request->widget))
            ->when($request->filled('staff'), fn ($q) => $q->where('staff_id', $request->staff))
            ->when(array_key_exists((string) $request->status, Appointment::STATUSES), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w->where('name', 'like', "%{$request->q}%")->orWhere('email', 'like', "%{$request->q}%")->orWhere('reference', \App\Models\Appointment::normaliseReference($request->q))));

        $week = null;
        if ($view === 'week') {
            $tz = $widgets->first() ? Booking::settings($widgets->first())['timezone'] : config('app.timezone');
            $from = CarbonImmutable::parse($request->get('from', 'now'), $tz)->startOfWeek();
            $items = (clone $filtered)->whereBetween('starts_at', [$from->utc(), $from->addWeek()->utc()])->orderBy('starts_at')->get();
            $week = ['from' => $from, 'days' => collect(range(0, 6))->mapWithKeys(fn ($i) => [
                $from->addDays($i)->toDateString() => $items->filter(fn ($a) => $a->localStart()->toDateString() === $from->addDays($i)->toDateString())->values(),
            ])];
            $appointments = null;
            $days = collect();
        } else {
            $appointments = (clone $filtered)
                ->when($view === 'upcoming', fn ($q) => $q->where('ends_at', '>=', now())->orderBy('starts_at'))
                ->when($view === 'past', fn ($q) => $q->where('ends_at', '<', now())->orderByDesc('starts_at'))
                ->when($view === 'all', fn ($q) => $q->orderByDesc('starts_at'))
                ->paginate(40)->withQueryString();
            $days = $appointments->getCollection()->groupBy(fn ($a) => $a->localStart()->toDateString());
        }

        $done = (clone $base)->whereIn('status', ['completed', 'no_show'])->count();
        $stats = [
            'today'   => (clone $base)->holdingSlot()->whereBetween('starts_at', [now()->startOfDay(), now()->endOfDay()])->count(),
            'week'    => (clone $base)->holdingSlot()->whereBetween('starts_at', [now(), now()->addDays(7)])->count(),
            'pending' => (clone $base)->where('status', 'pending')->where('starts_at', '>=', now())->count(),
            'no_show' => $done ? round((clone $base)->where('status', 'no_show')->count() / $done * 100) : 0,
            'revenue' => (clone $base)->where('payment_status', 'paid')->where('created_at', '>=', now()->subDays(30))->get(['amount', 'currency'])
                ->groupBy('currency')->map(fn ($g, $cur) => strtoupper($cur) . ' ' . number_format($g->sum('amount') / 100, 2))->implode(' · '),
        ];
        $staff = $widgets->flatMap(fn ($w) => Booking::settings($w)['staff'])->unique('id')->values();

        return view('appointments.index', compact('appointments', 'days', 'widgets', 'view', 'stats', 'week', 'staff'));
    }

    private function own(Request $request, Appointment|Widget $model): void
    {
        abort_unless($model->user_id === $request->user()->id, 403);
    }

    public function update(Request $request, Appointment $appointment, AppointmentService $service)
    {
        $this->own($request, $appointment);
        $data = $request->validate(['status' => ['required', Rule::in(array_keys(Appointment::STATUSES))]]);
        $service->setStatus($appointment, $data['status']);

        return back()->with('success', 'Booking marked as ' . strtolower(Appointment::STATUSES[$data['status']]) . '.');
    }

    public function reschedule(Request $request, Appointment $appointment, AppointmentService $service)
    {
        $this->own($request, $appointment);
        $data = $request->validate(['start' => 'required|date', 'staff_id' => 'nullable|string|max:40']);
        $service->reschedule($appointment, $data['start'], $data['staff_id'] ?? null);

        return back()->with('success', 'Booking moved. ' . $appointment->name . ' has been emailed the new time.');
    }

    /** Owner adds a booking by hand (phone or walk-in). */
    public function store(Request $request, AppointmentService $service)
    {
        $widget = Widget::findOrFail($request->input('widget_id'));
        $this->own($request, $widget);
        $data = $request->validate([
            'service_id' => 'required|string|max:40',
            'staff_id'   => 'nullable|string|max:40',
            'start'      => 'required|date',
            'name'       => 'required|string|max:120',
            'email'      => 'required|email|max:190',
            'phone'      => 'nullable|string|max:40',
            'notes'      => 'nullable|string|max:1000',
        ]);
        $service->book($widget, $data, null, 'dashboard');

        return back()->with('success', 'Booking added and confirmation sent to ' . $data['email'] . '.');
    }

    public function settings(Request $request, Widget $widget)
    {
        $this->own($request, $widget);
        $widget->load('calendarConnections', 'crmConnections');

        return view('appointments.settings', [
            'widget'    => $widget,
            'settings'  => Booking::settings($widget),
            'timezones' => \DateTimeZone::listIdentifiers(),
        ]);
    }

    public function saveSettings(Request $request, Widget $widget)
    {
        $this->own($request, $widget);
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
            'cancel_notice'          => 'required|integer|min:0|max:720',
            'services'               => 'required|array|min:1|max:30',
            'services.*.id'          => 'required|string|max:40|alpha_dash|distinct',
            'services.*.name'        => 'required|string|max:80',
            'services.*.duration'    => 'required|integer|min:5|max:600',
            'services.*.price'       => 'nullable|string|max:30',
            'services.*.charge'      => 'nullable|numeric|min:0|max:100000',
            'services.*.description' => 'nullable|string|max:200',
            'staff'                  => 'nullable|array|max:50',
            'staff.*.id'             => 'required|string|max:40|alpha_dash|distinct',
            'staff.*.name'           => 'required|string|max:80',
            'staff.*.email'          => 'nullable|email|max:190',
            'staff.*.services'       => 'nullable|array',
            'staff.*.days'           => 'nullable|array',
            'staff.*.days.*'         => Rule::in(array_keys(Booking::DAYS)),
            'questions'              => 'nullable|array|max:15',
            'questions.*.id'         => 'required|string|max:40|alpha_dash|distinct',
            'questions.*.label'      => 'required|string|max:150',
            'questions.*.type'       => ['required', Rule::in(array_keys(Booking::QUESTION_TYPES))],
            'questions.*.options'    => 'nullable|string|max:500',
            'hours'                  => 'array',
            'hours.*'                => 'array|max:6',
            'hours.*.*.0'            => $time,
            'hours.*.*.1'            => $time,
            'blocked_dates'          => 'nullable|string|max:4000',
            'payments_currency'      => 'required|string|size:3|alpha',
            'payments_secret'        => ['nullable', 'string', 'max:255', 'regex:/^(sk|rk)_(test|live)_[A-Za-z0-9]+$/'],
        ], ['payments_secret.regex' => 'That does not look like a Stripe secret key (it starts with sk_live_ or sk_test_).']);

        $old = Booking::settings($widget);
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

        $secret = filled($data['payments_secret'] ?? null) ? Crypt::encryptString($data['payments_secret']) : $old['payments']['secret'];
        if ($request->boolean('payments_remove_key')) {
            $secret = null;
        }

        $serviceIds = array_column($data['services'], 'id');
        $booking = array_merge(collect($data)->except(['payments_currency', 'payments_secret'])->all(), [
            'enabled'          => $request->boolean('enabled'),
            'auto_confirm'     => $request->boolean('auto_confirm'),
            'require_phone'    => $request->boolean('require_phone'),
            'allow_reschedule' => $request->boolean('allow_reschedule'),
            'ai_booking'       => $request->boolean('ai_booking'),
            'services'         => array_map(fn ($x) => $x + ['charge' => (float) ($x['charge'] ?? 0)], array_values($data['services'])),
            'staff'            => array_map(fn ($m) => [
                'id'       => $m['id'],
                'name'     => $m['name'],
                'email'    => $m['email'] ?? null,
                'services' => array_values(array_intersect((array) ($m['services'] ?? []), $serviceIds)),
                'days'     => array_values((array) ($m['days'] ?? [])),
            ], array_values($data['staff'] ?? [])),
            'questions'        => array_map(fn ($q) => $q + ['required' => ! empty($q['required'])], array_values($data['questions'] ?? [])),
            'hours'            => $hours,
            'blocked_dates'    => $blocked,
            'payments'         => [
                'enabled'          => $request->boolean('payments_enabled') && $secret,
                'currency'         => strtolower($data['payments_currency']),
                'secret'           => $secret,
                'refund_on_cancel' => $request->boolean('payments_refund'),
            ],
        ]);
        $widget->update(['booking' => $booking]);

        return back()->with('success', 'Booking settings saved.');
    }

    // ── Visitor's private manage page ────────────────────────────────────────

    public function manage(string $token)
    {
        $appointment = Appointment::where('token', $token)->with('widget')->firstOrFail();
        $s = Booking::settings($appointment->widget);

        return view('appointments.manage', [
            'a'          => $appointment,
            's'          => $s,
            'canChange'  => Booking::visitorCanChange($appointment, $s) && $appointment->payment_status !== 'unpaid',
            'canMove'    => $s['allow_reschedule'] && $s['enabled'],
            'payAgain'   => $appointment->payment_status === 'unpaid' && $appointment->status !== 'cancelled'
                && $appointment->created_at->gt(now()->subMinutes(Appointment::PAYMENT_HOLD)),
        ]);
    }

    public function cancel(string $token, AppointmentService $service)
    {
        $appointment = Appointment::where('token', $token)->firstOrFail();
        if (Booking::visitorCanChange($appointment, Booking::settings($appointment->widget))) {
            $service->setStatus($appointment, 'cancelled', byVisitor: true);
        }

        return redirect()->route('appointments.manage', $token)->with('notice', 'Your booking has been cancelled and the business has been told. We have emailed you a confirmation.');
    }

    public function visitorReschedule(Request $request, string $token, AppointmentService $service)
    {
        $appointment = Appointment::where('token', $token)->firstOrFail();
        $s = Booking::settings($appointment->widget);
        abort_unless($s['allow_reschedule'] && Booking::visitorCanChange($appointment, $s), 403);
        $data = $request->validate(['start' => 'required|date']);
        if (! Booking::visitorMoveAllowed($s, $data['start'])) {
            return back()->withErrors(['start' => 'Please choose a time at least ' . $s['cancel_notice'] . ' hours from now.']);
        }
        $service->reschedule($appointment, $data['start'], null, byVisitor: true);

        return redirect()->route('appointments.manage', $token)->with('notice', 'Done. Your booking has moved and we have emailed you the new time.');
    }

    /** Stripe sends the visitor back here after paying. */
    public function paid(Request $request, string $token, BookingPaymentService $payments, AppointmentService $service)
    {
        $a = Appointment::where('token', $token)->with('widget')->firstOrFail();
        if ($a->payment_status === 'unpaid' && $request->filled('session_id')) {
            $secret = Booking::stripeSecret(Booking::settings($a->widget));
            if ($secret && $payments->confirm($a, $secret, $request->session_id)) {
                $service->announce($a->fresh());
            }
        }

        return redirect()->route('appointments.manage', $token)->with('notice', $a->fresh()->payment_status === 'paid'
            ? 'Payment received. Your booking is ' . ($a->status === 'pending' ? 'waiting for confirmation' : 'confirmed') . '. Check your email for the details.'
            : 'We could not confirm your payment yet. If you paid, refresh this page in a minute.');
    }

    public function pay(string $token, BookingPaymentService $payments)
    {
        $a = Appointment::where('token', $token)->with('widget')->firstOrFail();
        $secret = Booking::stripeSecret(Booking::settings($a->widget));
        abort_unless($secret && $a->payment_status === 'unpaid' && $a->created_at->gt(now()->subMinutes(Appointment::PAYMENT_HOLD)), 410);

        return redirect()->away($payments->checkoutUrl($a, $secret));
    }
}
