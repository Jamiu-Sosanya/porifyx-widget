<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Widget;
use App\Services\AppointmentService;
use App\Services\BookingPaymentService;
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
        $paid = (bool) Booking::stripeSecret($s);

        return response()->json([
            'business_name' => $s['business_name'],
            'timezone'      => $s['timezone'],
            'require_phone' => (bool) $s['require_phone'],
            'auto_confirm'  => (bool) $s['auto_confirm'],
            'currency'      => $paid ? strtoupper($s['payments']['currency']) : null,
            'services'      => array_map(fn ($x) => [
                'id'          => $x['id'],
                'name'        => $x['name'],
                'duration'    => (int) $x['duration'],
                'price'       => $x['price'] ?? '',
                'description' => $x['description'] ?? '',
                'charge'      => $paid ? (float) ($x['charge'] ?? 0) : 0,
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
        $slots = array_map(fn ($x) => ['start' => $x['start'], 'label' => $x['label']], Booking::slots($widget, $service, $data['date'], $data['staff'] ?? null, $ignore));

        return response()->json(['slots' => $slots]);
    }

    public function store(Request $request, string $widgetUuid, AppointmentService $appointments, BookingPaymentService $payments): JsonResponse
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

        $checkout = null;
        if ($a->payment_status === 'unpaid') {
            try {
                $checkout = $payments->checkoutUrl($a, Booking::stripeSecret($s));
            } catch (\Throwable $e) {
                report($e);
                $a->update(['status' => 'cancelled']);
                abort(response()->json(['message' => 'Online payment is not available right now. Please try again later.'], 502));
            }
        }

        return response()->json([
            'status'       => $a->status,
            'payment'      => $a->payment_status,
            'amount'       => $a->priceLabel(),
            'checkout_url' => $checkout,
            'service'      => $a->service_name,
            'staff'        => $a->staff_name,
            'when'         => $a->localStart()->format('l j F, g:i A'),
            'timezone'     => $s['timezone'],
            'manage_url'   => $a->manageUrl(),
        ], 201);
    }
}
