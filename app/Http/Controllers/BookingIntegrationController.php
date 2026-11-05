<?php

namespace App\Http\Controllers;

use App\Models\CalendarConnection;
use App\Models\CrmConnection;
use App\Models\Widget;
use App\Services\CalendarService;
use App\Services\CrmService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\Rule;

/** Calendar (Google / Microsoft) and CRM connections for a widget's bookings. */
class BookingIntegrationController extends Controller
{
    private function own(Request $request, Widget $widget): void
    {
        abort_unless($widget->user_id === $request->user()->id, 403);
    }

    // ── Calendars ────────────────────────────────────────────────────────────

    public function connectCalendar(Request $request, Widget $widget, string $provider, CalendarService $calendars)
    {
        $this->own($request, $widget);
        abort_unless(array_key_exists($provider, CalendarConnection::PROVIDERS), 404);
        if (! $calendars->configured($provider)) {
            return back()->with('error', CalendarConnection::PROVIDERS[$provider] . ' is not set up on this server yet. Ask your administrator to add the app credentials.');
        }
        $state = Crypt::encryptString(json_encode([
            'w' => $widget->id, 's' => $request->query('staff') ?: null, 'u' => $request->user()->id, 't' => now()->timestamp,
        ]));

        return redirect()->away($calendars->authUrl($provider, $state));
    }

    public function calendarCallback(Request $request, string $provider, CalendarService $calendars)
    {
        abort_unless(array_key_exists($provider, CalendarConnection::PROVIDERS), 404);
        try {
            $state = json_decode(Crypt::decryptString((string) $request->query('state')), true);
        } catch (\Throwable) {
            abort(400, 'This sign-in link has expired. Please try connecting again.');
        }
        abort_unless(($state['u'] ?? null) === $request->user()->id && now()->timestamp - ($state['t'] ?? 0) < 900, 400);
        $widget = Widget::findOrFail($state['w']);
        $this->own($request, $widget);
        $back = route('widgets.booking', $widget) . '#integrations';

        if ($request->filled('error') || ! $request->filled('code')) {
            return redirect()->to($back)->with('error', 'Calendar connection was cancelled.');
        }
        try {
            $tokens = $calendars->exchange($provider, $request->query('code'));
        } catch (\Throwable $e) {
            report($e);

            return redirect()->to($back)->with('error', 'We could not connect that calendar. Please try again.');
        }

        CalendarConnection::updateOrCreate(
            ['widget_id' => $widget->id, 'staff_id' => $state['s'], 'provider' => $provider],
            $tokens + ['last_error' => null]
        );

        return redirect()->to($back)->with('success', CalendarConnection::PROVIDERS[$provider] . ' connected' . ($tokens['account_email'] ? ' (' . $tokens['account_email'] . ')' : '') . '.');
    }

    public function updateCalendar(Request $request, Widget $widget, CalendarConnection $connection)
    {
        $this->own($request, $widget);
        abort_unless($connection->widget_id === $widget->id, 404);
        $data = $request->validate(['calendar_id' => 'required|string|max:255']);
        $connection->update($data + ['check_busy' => $request->boolean('check_busy'), 'push_events' => $request->boolean('push_events')]);

        return back()->with('success', 'Calendar settings saved.');
    }

    public function disconnectCalendar(Request $request, Widget $widget, CalendarConnection $connection)
    {
        $this->own($request, $widget);
        abort_unless($connection->widget_id === $widget->id, 404);
        $connection->delete();

        return back()->with('success', 'Calendar disconnected.');
    }

    // ── CRMs ─────────────────────────────────────────────────────────────────

    public function saveCrm(Request $request, Widget $widget, CrmService $crm)
    {
        $this->own($request, $widget);
        $provider = $request->validate(['provider' => ['required', Rule::in(array_keys(CrmConnection::PROVIDERS))]])['provider'];
        $fields = CrmConnection::PROVIDERS[$provider][1];
        $existing = CrmConnection::where('widget_id', $widget->id)->where('provider', $provider)->first();

        $rules = [];
        foreach ($fields as $name => $label) {
            $optional = str_contains($label, 'optional') || ($existing && in_array($name, ['token', 'secret', 'refresh_token', 'client_secret'], true));
            $rules["credentials.$name"] = ($optional ? 'nullable' : 'required') . '|string|max:2000' . ($name === 'url' || $name === 'instance_url' ? '|url:https' : '');
        }
        $given = $request->validate($rules)['credentials'] ?? [];

        // Blank secret fields keep the saved value.
        $credentials = array_merge($existing?->credentials ?? [], array_filter($given, fn ($v) => filled($v)));
        $connection = CrmConnection::updateOrCreate(
            ['widget_id' => $widget->id, 'provider' => $provider],
            ['credentials' => $credentials, 'is_active' => true, 'last_error' => null]
        );

        $result = $crm->test($connection);
        if ($result !== true) {
            $connection->update(['last_error' => mb_substr($result, 0, 500)]);

            return redirect()->to(route('widgets.booking', $widget) . '#integrations')
                ->with('error', $connection->label() . ' was saved, but the test failed: ' . mb_substr($result, 0, 200));
        }

        return redirect()->to(route('widgets.booking', $widget) . '#integrations')
            ->with('success', $connection->label() . ' connected. New and changed bookings will sync automatically.');
    }

    public function toggleCrm(Request $request, Widget $widget, CrmConnection $connection)
    {
        $this->own($request, $widget);
        abort_unless($connection->widget_id === $widget->id, 404);
        $connection->update(['is_active' => ! $connection->is_active]);

        return back()->with('success', $connection->label() . ($connection->is_active ? ' sync turned on.' : ' sync paused.'));
    }

    public function deleteCrm(Request $request, Widget $widget, CrmConnection $connection)
    {
        $this->own($request, $widget);
        abort_unless($connection->widget_id === $widget->id, 404);
        $connection->delete();

        return back()->with('success', $connection->label() . ' disconnected.');
    }
}
