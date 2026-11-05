<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\CrmConnection;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Pushes bookings into the business's CRM: finds or creates the contact,
 * then creates or updates a meeting/activity/appointment for the booking.
 * References are stored in appointments.external_refs so later changes update the same record.
 */
class CrmService
{
    public function sync(Appointment $a, string $event): void
    {
        foreach ($a->widget->crmConnections->where('is_active', true) as $c) {
            try {
                $ref = $a->external_refs[$c->refKey()] ?? [];
                $ref = $this->{$c->provider}($c, $a, $event, is_array($ref) ? $ref : []) ?? $ref;
                $a->setRef($c->refKey(), $ref ?: null);
                $c->update(['last_synced_at' => now(), 'last_error' => null]);
            } catch (\Throwable $e) {
                $c->update(['last_error' => mb_substr($e->getMessage(), 0, 500)]);
                Log::warning("CRM sync ({$c->provider}) failed for appointment {$a->id}: " . $e->getMessage());
            }
        }
    }

    /** Lightweight credential check used by the "Test connection" button. */
    public function test(CrmConnection $c): true|string
    {
        $cr = $c->credentials;
        try {
            $res = match ($c->provider) {
                'hubspot'     => $this->hub($cr)->get('/crm/v3/objects/contacts', ['limit' => 1]),
                'pipedrive'   => $this->pipe($cr)->get('/users/me'),
                'gohighlevel' => $this->ghl($cr)->get('/locations/' . $cr['location_id']),
                'zoho'        => $this->zohoClient($c)->get('/org'),
                'salesforce'  => $this->sf($cr)->get('/limits'),
                'webhook'     => $this->post($c, ['event' => 'test', 'message' => 'PoriFyx connection test']),
            };

            return $res->successful() ? true : 'The CRM answered with status ' . $res->status() . ': ' . mb_substr($res->body(), 0, 200);
        } catch (\Throwable $e) {
            return $e->getMessage();
        }
    }

    private static function nameParts(string $name): array
    {
        $parts = preg_split('/\s+/', trim($name), 2);

        return [$parts[0] ?? $name, $parts[1] ?? ''];
    }

    private static function summary(Appointment $a): string
    {
        return collect([
            'Service: ' . $a->service_name . ' (' . $a->duration . ' min)',
            $a->staff_name ? 'With: ' . $a->staff_name : null,
            'Status: ' . $a->status,
            $a->phone ? 'Phone: ' . $a->phone : null,
            $a->notes ? 'Notes: ' . $a->notes : null,
            ...collect($a->answers ?? [])->map(fn ($v, $k) => $k . ': ' . (is_bool($v) ? ($v ? 'Yes' : 'No') : $v))->values()->all(),
            'Manage: ' . $a->manageUrl(),
        ])->filter()->implode("\n");
    }

    private static function title(Appointment $a): string
    {
        $prefix = ['cancelled' => 'Cancelled: ', 'no_show' => 'No-show: ', 'pending' => 'Pending: '][$a->status] ?? '';

        return $prefix . $a->service_name . ' – ' . $a->name;
    }

    // ── HubSpot ──────────────────────────────────────────────────────────────
    private function hub(array $cr): PendingRequest
    {
        return Http::withToken($cr['token'])->acceptJson()->timeout(15)->baseUrl('https://api.hubapi.com');
    }

    private function hubspot(CrmConnection $c, Appointment $a, string $event, array $ref): array
    {
        $http = $this->hub($c->credentials);
        if (empty($ref['contact'])) {
            [$first, $last] = self::nameParts($a->name);
            $found = $http->post('/crm/v3/objects/contacts/search', [
                'filterGroups' => [['filters' => [['propertyName' => 'email', 'operator' => 'EQ', 'value' => $a->email]]]], 'limit' => 1,
            ])->throw()->json('results.0.id');
            $ref['contact'] = $found ?: $http->post('/crm/v3/objects/contacts', ['properties' => array_filter([
                'email' => $a->email, 'firstname' => $first, 'lastname' => $last, 'phone' => $a->phone,
            ])])->throw()->json('id');
        }

        $props = [
            'hs_timestamp'          => $a->starts_at->toIso8601ZuluString(),
            'hs_meeting_title'      => self::title($a),
            'hs_meeting_body'       => self::summary($a),
            'hs_meeting_start_time' => $a->starts_at->toIso8601ZuluString(),
            'hs_meeting_end_time'   => $a->ends_at->toIso8601ZuluString(),
            'hs_meeting_outcome'    => ['cancelled' => 'CANCELED', 'completed' => 'COMPLETED', 'no_show' => 'NO_SHOW'][$a->status] ?? 'SCHEDULED',
        ];
        if (empty($ref['record'])) {
            $ref['record'] = $http->post('/crm/v3/objects/meetings', [
                'properties'   => $props,
                'associations' => [['to' => ['id' => $ref['contact']], 'types' => [['associationCategory' => 'HUBSPOT_DEFINED', 'associationTypeId' => 200]]]],
            ])->throw()->json('id');
        } else {
            $http->patch('/crm/v3/objects/meetings/' . $ref['record'], ['properties' => $props])->throw();
        }

        return $ref;
    }

    // ── Pipedrive ────────────────────────────────────────────────────────────
    private function pipe(array $cr): PendingRequest
    {
        $domain = preg_replace('#^https?://|/.*$#', '', ($cr['domain'] ?? '') ?: 'api.pipedrive.com');

        return Http::acceptJson()->timeout(15)->baseUrl('https://' . $domain . '/api/v1')->withQueryParameters(['api_token' => $cr['token']]);
    }

    private function pipedrive(CrmConnection $c, Appointment $a, string $event, array $ref): array
    {
        $http = $this->pipe($c->credentials);
        if (empty($ref['contact'])) {
            $ref['contact'] = $http->get('/persons/search', ['term' => $a->email, 'fields' => 'email', 'exact_match' => 'true'])->throw()->json('data.items.0.item.id')
                ?: $http->post('/persons', array_filter([
                    'name' => $a->name, 'email' => [['value' => $a->email, 'primary' => true]],
                    'phone' => $a->phone ? [['value' => $a->phone, 'primary' => true]] : null,
                ]))->throw()->json('data.id');
        }

        $body = [
            'subject'   => self::title($a),
            'type'      => 'meeting',
            'due_date'  => $a->starts_at->copy()->utc()->format('Y-m-d'),
            'due_time'  => $a->starts_at->copy()->utc()->format('H:i'),
            'duration'  => sprintf('%02d:%02d', intdiv($a->duration, 60), $a->duration % 60),
            'person_id' => $ref['contact'],
            'note'      => nl2br(e(self::summary($a))),
            'done'      => in_array($a->status, ['completed', 'cancelled', 'no_show'], true) ? 1 : 0,
        ];
        if (empty($ref['record'])) {
            $ref['record'] = $http->post('/activities', $body)->throw()->json('data.id');
        } else {
            $http->put('/activities/' . $ref['record'], $body)->throw();
        }

        return $ref;
    }

    // ── GoHighLevel (LeadConnector v2) ───────────────────────────────────────
    private function ghl(array $cr): PendingRequest
    {
        return Http::withToken($cr['token'])->acceptJson()->timeout(15)
            ->withHeaders(['Version' => '2021-07-28'])->baseUrl('https://services.leadconnectorhq.com');
    }

    private function gohighlevel(CrmConnection $c, Appointment $a, string $event, array $ref): array
    {
        $cr = $c->credentials;
        $http = $this->ghl($cr);
        if (empty($ref['contact'])) {
            [$first, $last] = self::nameParts($a->name);
            $ref['contact'] = $http->post('/contacts/upsert', array_filter([
                'locationId' => $cr['location_id'], 'email' => $a->email, 'firstName' => $first, 'lastName' => $last,
                'phone' => $a->phone, 'source' => 'PoriFyx booking',
            ]))->throw()->json('contact.id');
        }

        if (! empty($cr['calendar_id'])) {
            $body = [
                'calendarId'        => $cr['calendar_id'],
                'locationId'        => $cr['location_id'],
                'contactId'         => $ref['contact'],
                'startTime'         => $a->starts_at->toIso8601ZuluString(),
                'endTime'           => $a->ends_at->toIso8601ZuluString(),
                'title'             => self::title($a),
                'appointmentStatus' => ['cancelled' => 'cancelled', 'completed' => 'showed', 'no_show' => 'noshow', 'pending' => 'new'][$a->status] ?? 'confirmed',
                'ignoreDateRange'   => true,
                'toNotify'          => false,
            ];
            if (empty($ref['record'])) {
                $ref['record'] = $http->post('/calendars/events/appointments', $body)->throw()->json('id');
            } else {
                $http->put('/calendars/events/appointments/' . $ref['record'], $body)->throw();
            }
        } else {
            $http->post('/contacts/' . $ref['contact'] . '/notes', ['body' => self::title($a) . "\n" . $a->starts_at->toDayDateTimeString() . " UTC\n" . self::summary($a)])->throw();
        }

        return $ref;
    }

    // ── Zoho CRM ─────────────────────────────────────────────────────────────
    private function zohoClient(CrmConnection $c): PendingRequest
    {
        $cr = $c->credentials;
        $dc = $cr['dc'] ?? 'com';

        return Http::withHeaders(['Authorization' => 'Zoho-oauthtoken ' . $cr['token']])->acceptJson()->timeout(15)
            ->baseUrl('https://www.zohoapis.' . $dc . '/crm/v2')
            ->retry(1, 0, function ($e, $request) use ($c, $dc) {
                // Refresh an expired token once when refresh details were given.
                $cr = $c->credentials;
                if (! ($e instanceof \Illuminate\Http\Client\RequestException) || $e->response->status() !== 401 || empty($cr['refresh_token'])) {
                    return false;
                }
                $cr['token'] = Http::asForm()->post('https://accounts.zoho.' . $dc . '/oauth/v2/token', [
                    'refresh_token' => $cr['refresh_token'], 'client_id' => $cr['client_id'] ?? '', 'client_secret' => $cr['client_secret'] ?? '', 'grant_type' => 'refresh_token',
                ])->json('access_token');
                $c->update(['credentials' => $cr]);
                $request->withHeaders(['Authorization' => 'Zoho-oauthtoken ' . $cr['token']]);

                return (bool) $cr['token'];
            }, throw: false);
    }

    private function zoho(CrmConnection $c, Appointment $a, string $event, array $ref): array
    {
        $http = $this->zohoClient($c);
        if (empty($ref['contact'])) {
            [$first, $last] = self::nameParts($a->name);
            $ref['contact'] = $http->post('/Contacts/upsert', [
                'data' => [array_filter(['First_Name' => $first, 'Last_Name' => $last ?: $first, 'Email' => $a->email, 'Phone' => $a->phone])],
                'duplicate_check_fields' => ['Email'],
            ])->throw()->json('data.0.details.id');
        }
        $body = ['data' => [[
            'Event_Title'    => self::title($a),
            'Start_DateTime' => $a->starts_at->toIso8601String(),
            'End_DateTime'   => $a->ends_at->toIso8601String(),
            'Who_Id'         => ['id' => $ref['contact']],
            'Description'    => self::summary($a),
        ]]];
        if (empty($ref['record'])) {
            $ref['record'] = $http->post('/Events', $body)->throw()->json('data.0.details.id');
        } else {
            $http->put('/Events/' . $ref['record'], $body)->throw();
        }

        return $ref;
    }

    // ── Salesforce ───────────────────────────────────────────────────────────
    private function sf(array $cr): PendingRequest
    {
        return Http::withToken($cr['token'])->acceptJson()->timeout(15)->baseUrl(rtrim($cr['instance_url'], '/') . '/services/data/v59.0');
    }

    private function salesforce(CrmConnection $c, Appointment $a, string $event, array $ref): array
    {
        $http = $this->sf($c->credentials);
        if (empty($ref['contact'])) {
            [$first, $last] = self::nameParts($a->name);
            $email = str_replace(["\\", "'"], ["\\\\", "\\'"], $a->email);
            $ref['contact'] = $http->get('/query', ['q' => "SELECT Id FROM Contact WHERE Email = '{$email}' LIMIT 1"])->throw()->json('records.0.Id')
                ?: $http->post('/sobjects/Contact', array_filter(['FirstName' => $first, 'LastName' => $last ?: $first, 'Email' => $a->email, 'Phone' => $a->phone]))->throw()->json('id');
        }
        $body = [
            'Subject'       => mb_substr(self::title($a), 0, 255),
            'StartDateTime' => $a->starts_at->toIso8601ZuluString(),
            'EndDateTime'   => $a->ends_at->toIso8601ZuluString(),
            'WhoId'         => $ref['contact'],
            'Description'   => self::summary($a),
        ];
        if (empty($ref['record'])) {
            $ref['record'] = $http->post('/sobjects/Event', $body)->throw()->json('id');
        } else {
            $http->patch('/sobjects/Event/' . $ref['record'], $body)->throw();
        }

        return $ref;
    }

    // ── Zapier / Make / any webhook ──────────────────────────────────────────
    private function post(CrmConnection $c, array $payload): \Illuminate\Http\Client\Response
    {
        $json = json_encode($payload);
        $req = Http::timeout(15)->withBody($json, 'application/json')->withHeaders(['User-Agent' => 'PoriFyx-Bookings/1.0']);
        if (! empty($c->credentials['secret'])) {
            $req = $req->withHeaders(['X-PoriFyx-Signature' => 'sha256=' . hash_hmac('sha256', $json, $c->credentials['secret'])]);
        }

        return $req->post($c->credentials['url']);
    }

    private function webhook(CrmConnection $c, Appointment $a, string $event, array $ref): array
    {
        $this->post($c, ['event' => $event, 'appointment' => AppointmentService::payload($a)])->throw();

        return $ref;
    }
}
