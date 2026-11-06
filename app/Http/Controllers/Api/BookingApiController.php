<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Widget;
use App\Services\AppointmentService;
use App\Support\Booking;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Public booking endpoints used by the chat widget and the visitor's manage page. */
class BookingApiController extends Controller
{
    private function widget(string $uuid): Widget
    {
        $widget = Widget::where('uuid', $uuid)->where('is_active', true)->firstOrFail();

        $origin = request()->header('Origin') ?? request()->header('Referer');
        $host = $origin ? parse_url($origin, PHP_URL_HOST) : null;
        if ($host && $host !== request()->getHost() && $widget->domain_restriction && ! $widget->isDomainAllowed($host)) {
            abort(403, 'Domain not authorized.');
        }
        abort_unless(Booking::settings($widget)['enabled'], 404, 'Booking is not enabled.');

        return $widget;
    }

    public function show(string $widgetUuid): JsonResponse
    {
        $s = Booking::settings($this->widget($widgetUuid));

        return response()->json([
            'business_name' => $s['business_name'],
            'timezone'      => $s['timezone'],
            'require_phone' => (bool) $s['require_phone'],
            'auto_confirm'  => (bool) $s['auto_confirm'],
            'services'      => array_map(fn ($x) => [
                'id'          => $x['id'],
                'name'        => $x['name'],
                'duration'    => (int) $x['duration'],
                'price'       => $x['price'] ?? '',
                'description' => $x['description'] ?? '',
                'staff'       => array_map(fn ($m) => ['id' => $m['id'], 'name' => $m['name']], Booking::staffFor($s, $x)),
            ], $s['services']),
            'questions'     => array_map(fn ($q) => [
                'id' => $q['id'], 'label' => $q['label'], 'type' => $q['type'], 'required' => ! empty($q['required']),
                'options' => AppointmentService::options($q),
            ], $s['questions']),
            'dates'         => Booking::openDates($s),
        ]);
    }

    public function slots(Request $request, string $widgetUuid): JsonResponse
    {
        $widget = $this->widget($widgetUuid);
        $data = $request->validate([
            'service' => 'required|string|max:40',
            'date'    => 'required|date_format:Y-m-d',
            'staff'   => 'nullable|string|max:40',
            'ignore'  => 'nullable|string|max:64', // manage-page token: ignore the visitor's own booking
        ]);
        $service = Booking::service(Booking::settings($widget), $data['service']);
        abort_unless($service, 404, 'Unknown service.');

        $ignore = ! empty($data['ignore']) ? $widget->appointments()->where('token', $data['ignore'])->value('id') : null;
        $s = Booking::settings($widget);
        $slots = collect(Booking::slots($widget, $service, $data['date'], $data['staff'] ?? null, $ignore))
            ->filter(fn ($x) => ! $ignore || Booking::visitorMoveAllowed($s, $x['start']))
            ->map(fn ($x) => ['start' => $x['start'], 'label' => $x['label']])->values()->all();

        return response()->json(['slots' => $slots]);
    }

    // ── Returning visitors: find a booking by number + email, then change or cancel it ──

    private function details(\App\Models\Appointment $a, array $s): array
    {
        $canChange = Booking::visitorCanChange($a, $s);

        return [
            'reference'  => $a->reference,
            'token'      => $a->token,
            'status'     => $a->status,
            'service_id' => $a->service_id,
            'service'    => $a->service_name,
            'staff_id'   => $a->staff_id,
            'staff'      => $a->staff_name,
            'when'       => $a->localStart()->format('l j F, g:i A'),
            'timezone'   => $s['timezone'],
            'can_cancel' => $canChange,
            'can_move'   => $canChange && $s['allow_reschedule'],
            'manage_url' => $a->manageUrl(),
        ];
    }

    public function lookup(Request $request, string $widgetUuid): JsonResponse
    {
        $widget = $this->widget($widgetUuid);
        $data = $request->validate(['reference' => 'required|string|max:20', 'email' => 'required|email|max:190']);

        $a = $widget->appointments()
            ->where('reference', \App\Models\Appointment::normaliseReference($data['reference']))
            ->whereRaw('lower(email) = ?', [mb_strtolower($data['email'])])
            ->first();
        if (! $a) {
            return response()->json(['message' => 'We could not find a booking with that number and email. Please check both and try again.'], 404);
        }

        return response()->json($this->details($a, Booking::settings($widget)));
    }

    private function managed(string $widgetUuid, string $token): array
    {
        $widget = $this->widget($widgetUuid);
        $a = $widget->appointments()->where('token', $token)->firstOrFail();

        return [$a, Booking::settings($widget)];
    }

    public function manageCancel(string $widgetUuid, string $token, AppointmentService $appointments): JsonResponse
    {
        [$a, $s] = $this->managed($widgetUuid, $token);
        abort_unless($this->details($a, $s)['can_cancel'], 403, 'This booking can no longer be cancelled online. Please contact ' . $s['business_name'] . ' directly.');
        $appointments->setStatus($a, 'cancelled', byVisitor: true);

        return response()->json($this->details($a->fresh(), $s));
    }

    public function manageReschedule(Request $request, string $widgetUuid, string $token, AppointmentService $appointments): JsonResponse
    {
        [$a, $s] = $this->managed($widgetUuid, $token);
        abort_unless($this->details($a, $s)['can_move'], 403, 'This booking can no longer be moved online. Please contact ' . $s['business_name'] . ' directly.');
        $start = $request->validate(['start' => 'required|date'])['start'];
        if (! Booking::visitorMoveAllowed($s, $start)) {
            throw \Illuminate\Validation\ValidationException::withMessages(['start' => 'Please choose a time at least ' . $s['cancel_notice'] . ' hours from now.']);
        }
        $appointments->reschedule($a, $start, null, byVisitor: true);

        return response()->json($this->details($a->fresh(), $s));
    }

    public function store(Request $request, string $widgetUuid, AppointmentService $appointments): JsonResponse
    {
        $widget = $this->widget($widgetUuid);
        $s = Booking::settings($widget);
        $data = $request->validate([
            'service_id'        => 'required|string|max:40',
            'staff_id'          => 'nullable|string|max:40',
            'start'             => 'required|date',
            'name'              => 'required|string|max:120',
            'email'             => 'required|email|max:190',
            'phone'             => ($s['require_phone'] ? 'required' : 'nullable') . '|string|max:40',
            'notes'             => 'nullable|string|max:1000',
            'answers'           => 'nullable|array|max:30',
            'timezone'          => 'nullable|timezone',
            'conversation_uuid' => 'nullable|string|max:64',
            'source'            => 'nullable|in:widget,ai',
        ]);

        $conversation = ! empty($data['conversation_uuid'])
            ? Conversation::where('widget_id', $widget->id)->where('uuid', $data['conversation_uuid'])->first()
            : null;

        $a = $appointments->book($widget, $data, $conversation, $data['source'] ?? 'widget');


        return response()->json([
            'status'       => $a->status,
            'service'      => $a->service_name,
            'staff'        => $a->staff_name,
            'when'         => $a->localStart()->format('l j F, g:i A'),
            'timezone'     => $s['timezone'],
            'manage_url'   => $a->manageUrl(),
            'reference'    => $a->reference,
        ], 201);
    }
}
