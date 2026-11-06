<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\CalendarConnection;
use App\Models\Widget;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Google Calendar and Microsoft 365 (Outlook) sync:
 * reads busy times so they cannot be booked, and writes bookings as events.
 * App credentials live in config/services.php (google, microsoft).
 */
class CalendarService
{
    private const ENDPOINTS = [
        'google' => [
            'auth'  => 'https://accounts.google.com/o/oauth2/v2/auth',
            'token' => 'https://oauth2.googleapis.com/token',
            'scope' => 'openid email https://www.googleapis.com/auth/calendar.events https://www.googleapis.com/auth/calendar.readonly',
        ],
        'microsoft' => [
            'auth'  => 'https://login.microsoftonline.com/common/oauth2/v2.0/authorize',
            'token' => 'https://login.microsoftonline.com/common/oauth2/v2.0/token',
            'scope' => 'offline_access User.Read Calendars.ReadWrite',
        ],
    ];

    public function configured(string $provider): bool
    {
        return filled(config("services.{$provider}.client_id")) && filled(config("services.{$provider}.client_secret"));
    }

    public function redirectUri(string $provider): string
    {
        return route('calendar.callback', $provider);
    }

    public function authUrl(string $provider, string $state): string
    {
        $e = self::ENDPOINTS[$provider];

        return $e['auth'] . '?' . http_build_query(array_filter([
            'client_id'     => config("services.{$provider}.client_id"),
            'redirect_uri'  => $this->redirectUri($provider),
            'response_type' => 'code',
            'scope'         => $e['scope'],
            'state'         => $state,
            'access_type'   => $provider === 'google' ? 'offline' : null,
            'prompt'        => $provider === 'google' ? 'consent' : 'select_account',
        ]));
    }

    /** Swap the OAuth code for tokens and the account email. */
    public function exchange(string $provider, string $code): array
    {
        $tokens = Http::asForm()->post(self::ENDPOINTS[$provider]['token'], [
            'client_id'     => config("services.{$provider}.client_id"),
            'client_secret' => config("services.{$provider}.client_secret"),
            'redirect_uri'  => $this->redirectUri($provider),
            'grant_type'    => 'authorization_code',
            'code'          => $code,
        ])->throw()->json();

        $email = $provider === 'google'
            ? Http::withToken($tokens['access_token'])->get('https://www.googleapis.com/oauth2/v3/userinfo')->json('email')
            : (fn ($me) => $me['mail'] ?? $me['userPrincipalName'] ?? null)(Http::withToken($tokens['access_token'])->get('https://graph.microsoft.com/v1.0/me')->json());

        return [
            'access_token'  => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'] ?? null,
            'expires_at'    => now()->addSeconds(($tokens['expires_in'] ?? 3600) - 60),
            'account_email' => $email,
        ];
    }

    private function client(CalendarConnection $c): PendingRequest
    {
        if ($c->expires_at && $c->expires_at->isPast() && $c->refresh_token) {
            $t = Http::asForm()->post(self::ENDPOINTS[$c->provider]['token'], [
                'client_id'     => config("services.{$c->provider}.client_id"),
                'client_secret' => config("services.{$c->provider}.client_secret"),
                'grant_type'    => 'refresh_token',
                'refresh_token' => $c->refresh_token,
            ])->throw()->json();
            $c->update([
                'access_token'  => $t['access_token'],
                'refresh_token' => $t['refresh_token'] ?? $c->refresh_token,
                'expires_at'    => now()->addSeconds(($t['expires_in'] ?? 3600) - 60),
            ]);
        }

        return Http::withToken($c->access_token)->acceptJson()->timeout(15)
            ->baseUrl($c->provider === 'google' ? 'https://www.googleapis.com/calendar/v3' : 'https://graph.microsoft.com/v1.0');
    }

    /**
     * Busy intervals from the calendars connected for a staff member (or the whole business when $staffId is null).
     * Cached for 5 minutes; failures are logged and treated as "no busy times".
     *
     * @return array<int, array{0: Carbon, 1: Carbon}>
     */
    public function busyFor(Widget $widget, ?string $staffId, CarbonInterface $from, CarbonInterface $to): array
    {
        $connections = $widget->calendarConnections->where('check_busy', true)
            ->filter(fn ($c) => (string) $c->staff_id === (string) $staffId);

        $out = [];
        foreach ($connections as $c) {
            $key = 'cal-busy:' . $c->id . ':' . $from->timestamp . ':' . $to->timestamp;
            $rows = Cache::remember($key, 300, function () use ($c, $from, $to) {
                try {
                    $rows = $this->fetchBusy($c, $from->copy()->utc(), $to->copy()->utc());
                    $c->last_error && $c->update(['last_error' => null]);

                    return $rows;
                } catch (\Throwable $e) {
                    $c->update(['last_error' => mb_substr($e->getMessage(), 0, 500)]);
                    Log::warning("Calendar busy check failed for connection {$c->id}: " . $e->getMessage());

                    return [];
                }
            });
            foreach ($rows as [$a, $b]) {
                $out[] = [Carbon::parse($a), Carbon::parse($b)];
            }
        }

        return $out;
    }

    private function fetchBusy(CalendarConnection $c, CarbonInterface $from, CarbonInterface $to): array
    {
        if ($c->provider === 'google') {
            $res = $this->client($c)->post('/freeBusy', [
                'timeMin' => $from->toIso8601ZuluString(),
                'timeMax' => $to->toIso8601ZuluString(),
                'items'   => [['id' => $c->calendar_id]],
            ])->throw()->json();

            return collect($res['calendars'][$c->calendar_id]['busy'] ?? [])->map(fn ($b) => [$b['start'], $b['end']])->all();
        }

        $res = $this->client($c)->withHeaders(['Prefer' => 'outlook.timezone="UTC"'])->get('/me/calendarView', [
            'startDateTime' => $from->toIso8601ZuluString(),
            'endDateTime'   => $to->toIso8601ZuluString(),
            '$select'       => 'start,end,showAs,isCancelled',
            '$top'          => 250,
        ])->throw()->json();

        return collect($res['value'] ?? [])
            ->reject(fn ($e) => ($e['showAs'] ?? 'busy') === 'free' || ! empty($e['isCancelled']))
            ->map(fn ($e) => [$e['start']['dateTime'] . 'Z', $e['end']['dateTime'] . 'Z'])->all();
    }

    /** Create, move or remove the calendar event for a booking on every relevant connection. */
    public function sync(Appointment $a): void
    {
        $connections = $a->widget->calendarConnections->where('push_events', true)
            ->filter(fn ($c) => $c->staff_id === null || (string) $c->staff_id === (string) $a->staff_id);

        foreach ($connections as $c) {
            $eventId = $a->external_refs[$c->refKey()] ?? null;
            try {
                if (! in_array($a->status, Appointment::ACTIVE, true) && $a->status !== 'completed') {
                    if ($eventId) {
                        $this->client($c)->delete($this->eventPath($c, $eventId));
                        $a->setRef($c->refKey(), null);
                    }
                    continue;
                }
                $body = $this->eventBody($c, $a);
                if ($eventId) {
                    $this->client($c)->patch($this->eventPath($c, $eventId), $body)->throw();
                } else {
                    $id = $this->client($c)->post($this->eventPath($c), $body)->throw()->json('id');
                    $a->setRef($c->refKey(), $id);
                }
                $c->last_error && $c->update(['last_error' => null]);
            } catch (\Throwable $e) {
                $c->update(['last_error' => mb_substr($e->getMessage(), 0, 500)]);
                Log::warning("Calendar event sync failed for appointment {$a->id}: " . $e->getMessage());
            }
        }
    }

    private function eventPath(CalendarConnection $c, ?string $id = null): string
    {
        $base = $c->provider === 'google' ? '/calendars/' . rawurlencode($c->calendar_id) . '/events' : '/me/events';

        return $id ? $base . '/' . rawurlencode($id) : $base;
    }

    private function eventBody(CalendarConnection $c, Appointment $a): array
    {
        $title = ($a->status === 'pending' ? '[Pending] ' : '') . $a->service_name . ' – ' . $a->name;
        $lines = array_filter([
            'Booking ' . $a->reference . ' through ' . $a->widget->name,
            'Name: ' . $a->name, 'Email: ' . $a->email, $a->phone ? 'Phone: ' . $a->phone : null,
            $a->staff_name ? 'With: ' . $a->staff_name : null, $a->notes ? 'Notes: ' . $a->notes : null,
            ...collect($a->answers ?? [])->map(fn ($v, $k) => $k . ': ' . (is_bool($v) ? ($v ? 'Yes' : 'No') : $v))->values()->all(),
        ]);

        if ($c->provider === 'google') {
            return [
                'summary'     => $title,
                'description' => implode("\n", $lines),
                'start'       => ['dateTime' => $a->starts_at->toIso8601ZuluString()],
                'end'         => ['dateTime' => $a->ends_at->toIso8601ZuluString()],
                'attendees'   => [['email' => $a->email, 'displayName' => $a->name]],
            ];
        }

        return [
            'subject'   => $title,
            'body'      => ['contentType' => 'text', 'content' => implode("\n", $lines)],
            'start'     => ['dateTime' => $a->starts_at->format('Y-m-d\TH:i:s'), 'timeZone' => 'UTC'],
            'end'       => ['dateTime' => $a->ends_at->format('Y-m-d\TH:i:s'), 'timeZone' => 'UTC'],
            'attendees' => [['emailAddress' => ['address' => $a->email, 'name' => $a->name], 'type' => 'required']],
        ];
    }
}
