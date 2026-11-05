<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Widget;
use App\Services\AppointmentService;
use App\Support\Booking;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Public booking endpoints used by the chat widget. */
class BookingApiController extends Controller
{
    private function widget(string $uuid): Widget
    {
        $widget = Widget::where('uuid', $uuid)->where('is_active', true)->firstOrFail();

        $origin = request()->header('Origin') ?? request()->header('Referer');
        if ($origin && $widget->domain_restriction && ! $widget->isDomainAllowed(parse_url($origin, PHP_URL_HOST))) {
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
            'services'      => array_map(fn ($x) => array_intersect_key($x, array_flip(['id', 'name', 'duration', 'price', 'description'])), $s['services']),
            'dates'         => Booking::openDates($s),
        ]);
    }

    public function slots(Request $request, string $widgetUuid): JsonResponse
    {
        $widget = $this->widget($widgetUuid);
        $data = $request->validate(['service' => 'required|string|max:40', 'date' => 'required|date_format:Y-m-d']);
        $service = Booking::service(Booking::settings($widget), $data['service']);
        abort_unless($service, 404, 'Unknown service.');

        return response()->json(['slots' => Booking::slots($widget, $service, $data['date'])]);
    }

    public function store(Request $request, string $widgetUuid, AppointmentService $appointments): JsonResponse
    {
        $widget = $this->widget($widgetUuid);
        $s = Booking::settings($widget);
        $data = $request->validate([
            'service_id'        => 'required|string|max:40',
            'start'             => 'required|date',
            'name'              => 'required|string|max:120',
            'email'             => 'required|email|max:190',
            'phone'             => ($s['require_phone'] ? 'required' : 'nullable') . '|string|max:40',
            'notes'             => 'nullable|string|max:1000',
            'timezone'          => 'nullable|timezone',
            'conversation_uuid' => 'nullable|string|max:64',
        ]);

        $conversation = ! empty($data['conversation_uuid'])
            ? Conversation::where('widget_id', $widget->id)->where('uuid', $data['conversation_uuid'])->first()
            : null;

        $a = $appointments->book($widget, $data, $conversation);

        return response()->json([
            'status'     => $a->status,
            'service'    => $a->service_name,
            'when'       => $a->localStart()->format('l j F, g:i A'),
            'timezone'   => $s['timezone'],
            'manage_url' => $a->manageUrl(),
        ], 201);
    }
}
