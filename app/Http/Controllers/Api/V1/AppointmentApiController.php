<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Widget;
use App\Services\AppointmentService;
use App\Support\Booking;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * REST API so a CRM (or Zapier / Make) can read and manage bookings.
 * Auth: "Authorization: Bearer <API key>" from Settings > API keys.
 */
class AppointmentApiController extends Controller
{
    private function widget(Request $request, $id): Widget
    {
        return Widget::where('user_id', $request->user()->id)
            ->where(fn ($q) => $q->where('id', $id)->orWhere('uuid', $id))->firstOrFail();
    }

    private function find(Request $request, int $id): Appointment
    {
        return Appointment::where('user_id', $request->user()->id)->findOrFail($id);
    }

    public function services(Request $request, $widget): JsonResponse
    {
        $s = Booking::settings($this->widget($request, $widget));

        return response()->json([
            'enabled'   => (bool) $s['enabled'],
            'timezone'  => $s['timezone'],
            'services'  => array_map(fn ($x) => $x + ['staff' => array_column(Booking::staffFor($s, $x), 'id')], $s['services']),
            'staff'     => array_map(fn ($m) => array_intersect_key($m, array_flip(['id', 'name', 'email', 'services', 'days'])), $s['staff']),
            'questions' => $s['questions'],
        ]);
    }

    public function availability(Request $request, $widget): JsonResponse
    {
        $w = $this->widget($request, $widget);
        $data = $request->validate(['service' => 'required|string', 'date' => 'required|date_format:Y-m-d', 'staff' => 'nullable|string']);
        $service = Booking::service(Booking::settings($w), $data['service']);
        abort_unless($service, 404, 'Unknown service.');

        return response()->json(['date' => $data['date'], 'slots' => Booking::slots($w, $service, $data['date'], $data['staff'] ?? null)]);
    }

    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'widget_id'     => 'nullable|integer',
            'status'        => ['nullable', Rule::in(array_keys(Appointment::STATUSES))],
            'email'         => 'nullable|email',
            'reference'     => 'nullable|string|max:20',
            'from'          => 'nullable|date',
            'to'            => 'nullable|date',
            'updated_since' => 'nullable|date',
            'per_page'      => 'nullable|integer|min:1|max:200',
        ]);
        $page = Appointment::where('user_id', $request->user()->id)
            ->when($data['widget_id'] ?? null, fn ($q, $v) => $q->where('widget_id', $v))
            ->when($data['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($data['email'] ?? null, fn ($q, $v) => $q->where('email', $v))
            ->when($data['reference'] ?? null, fn ($q, $v) => $q->where('reference', Appointment::normaliseReference($v)))
            ->when($data['from'] ?? null, fn ($q, $v) => $q->where('starts_at', '>=', $v))
            ->when($data['to'] ?? null, fn ($q, $v) => $q->where('starts_at', '<', $v))
            ->when($data['updated_since'] ?? null, fn ($q, $v) => $q->where('updated_at', '>=', $v))
            ->orderBy('starts_at')->paginate($data['per_page'] ?? 50);

        return response()->json([
            'data' => $page->getCollection()->map(fn ($a) => AppointmentService::payload($a)),
            'meta' => ['page' => $page->currentPage(), 'per_page' => $page->perPage(), 'total' => $page->total(), 'last_page' => $page->lastPage()],
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        return response()->json(['data' => AppointmentService::payload($this->find($request, $id))]);
    }

    public function store(Request $request, AppointmentService $service): JsonResponse
    {
        $data = $request->validate([
            'widget_id'  => 'required',
            'service_id' => 'required|string|max:40',
            'staff_id'   => 'nullable|string|max:40',
            'start'      => 'required|date',
            'name'       => 'required|string|max:120',
            'email'      => 'required|email|max:190',
            'phone'      => 'nullable|string|max:40',
            'notes'      => 'nullable|string|max:1000',
            'answers'    => 'nullable|array',
        ]);
        $a = $service->book($this->widget($request, $data['widget_id']), $data, null, 'api');

        return response()->json(['data' => AppointmentService::payload($a)], 201);
    }

    public function update(Request $request, int $id, AppointmentService $service): JsonResponse
    {
        $a = $this->find($request, $id);
        $data = $request->validate([
            'status'   => ['nullable', Rule::in(array_keys(Appointment::STATUSES))],
            'start'    => 'nullable|date',
            'staff_id' => 'nullable|string|max:40',
            'notes'    => 'nullable|string|max:1000',
        ]);
        if (! empty($data['start'])) {
            $service->reschedule($a, $data['start'], $data['staff_id'] ?? null);
        }
        if (array_key_exists('notes', $data)) {
            $a->update(['notes' => $data['notes']]);
        }
        if (! empty($data['status'])) {
            $service->setStatus($a->fresh(), $data['status']);
        }

        return response()->json(['data' => AppointmentService::payload($a->fresh())]);
    }

    public function destroy(Request $request, int $id, AppointmentService $service): JsonResponse
    {
        $service->setStatus($this->find($request, $id), 'cancelled');

        return response()->json(['data' => AppointmentService::payload($this->find($request, $id))]);
    }
}
