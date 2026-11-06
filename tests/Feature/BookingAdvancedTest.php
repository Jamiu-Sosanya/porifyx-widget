<?php

namespace Tests\Feature;

use App\Jobs\SyncAppointment;
use App\Models\ApiKey;
use App\Models\Appointment;
use App\Models\CalendarConnection;
use App\Models\CrmConnection;
use App\Models\User;
use App\Models\Widget;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class BookingAdvancedTest extends TestCase
{
    use RefreshDatabase;

    private function widget(array $booking = []): Widget
    {
        $user = User::factory()->create();

        return Widget::create([
            'user_id' => $user->id, 'name' => 'Clinic', 'uuid' => (string) Str::uuid(), 'token' => Str::random(32), 'is_active' => true,
            'booking' => array_merge([
                'enabled' => true, 'timezone' => 'UTC', 'min_notice' => 0, 'slot_step' => 60,
                'services' => [['id' => 'checkup', 'name' => 'Check-up', 'duration' => 60, 'price' => '', 'description' => '', 'charge' => 0]],
                'hours' => array_fill_keys(['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'], [['09:00', '11:00']]),
            ], $booking),
        ]);
    }

    private function book(Widget $w, array $extra = [])
    {
        return $this->postJson("/api/widget/{$w->uuid}/booking", array_merge([
            'service_id' => 'checkup', 'start' => '2026-11-10T09:00:00Z', 'name' => 'Ada Lovelace', 'email' => 'ada@example.com',
        ], $extra));
    }

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Queue::fake();
        $this->travelTo(CarbonImmutable::parse('2026-11-10 06:00:00', 'UTC'));
    }

    public function test_team_members_each_have_their_own_diary(): void
    {
        $w = $this->widget(['staff' => [
            ['id' => 'ann', 'name' => 'Ann', 'email' => null, 'services' => [], 'days' => []],
            ['id' => 'bob', 'name' => 'Bob', 'email' => null, 'services' => [], 'days' => ['mon']], // 2026-11-10 is a Tuesday
        ]]);

        $this->book($w)->assertCreated()->assertJsonPath('staff', 'Ann');
        // Ann is now busy at 9:00 and Bob does not work Tuesdays, so 9:00 is gone for "anyone".
        $this->book($w, ['email' => 'b@example.com'])->assertStatus(422);
        $slots = $this->getJson("/api/widget/{$w->uuid}/booking/slots?service=checkup&date=2026-11-10")->json('slots');
        $this->assertSame(['10:00 AM'], array_column($slots, 'label'));
    }

    public function test_required_questions_are_enforced_and_saved(): void
    {
        $w = $this->widget(['questions' => [
            ['id' => 'first', 'label' => 'First visit?', 'type' => 'select', 'options' => 'Yes, No', 'required' => true],
        ]]);

        $this->book($w)->assertStatus(422)->assertJsonValidationErrors('answers.first');
        $this->book($w, ['answers' => ['first' => 'Maybe']])->assertStatus(422);
        $this->book($w, ['answers' => ['first' => 'Yes']])->assertCreated();
        $this->assertSame(['First visit?' => 'Yes'], Appointment::first()->answers);
    }

    public function test_paid_service_holds_slot_until_paid_then_confirms(): void
    {
        Http::fake([
            'api.stripe.com/v1/checkout/sessions/cs_1' => Http::response(['id' => 'cs_1', 'payment_status' => 'paid', 'payment_intent' => 'pi_1']),
            'api.stripe.com/v1/checkout/sessions' => Http::response(['id' => 'cs_1', 'url' => 'https://checkout.stripe.com/c/cs_1']),
            'api.stripe.com/v1/refunds' => Http::response(['id' => 're_1']),
        ]);
        $w = $this->widget([
            'services' => [['id' => 'checkup', 'name' => 'Check-up', 'duration' => 60, 'price' => '£40', 'charge' => 40]],
            'payments' => ['enabled' => true, 'currency' => 'gbp', 'secret' => Crypt::encryptString('sk_test_abc'), 'refund_on_cancel' => true],
        ]);

        $this->book($w)->assertCreated()->assertJsonPath('checkout_url', 'https://checkout.stripe.com/c/cs_1')->assertJsonPath('payment', 'unpaid');
        $a = Appointment::first();
        $this->assertSame(4000, $a->amount);
        Mail::assertNothingQueued(); // no confirmation until paid

        // Slot is held while unpaid…
        $this->book($w, ['email' => 'other@example.com'])->assertStatus(422);

        // …and confirmed after Stripe sends the visitor back.
        $this->get(route('appointments.paid', $a->token) . '?session_id=cs_1')->assertRedirect();
        $this->assertSame('paid', $a->fresh()->payment_status);
        Mail::assertQueued(\App\Mail\AppointmentMail::class, fn ($m) => $m->kind === 'booked');

        // Owner cancels: refund goes out.
        $this->actingAs($w->user)->patch(route('appointments.update', $a), ['status' => 'cancelled']);
        $this->assertSame('refunded', $a->fresh()->payment_status);
        Http::assertSent(fn ($r) => str_contains($r->url(), '/v1/refunds') && $r['payment_intent'] === 'pi_1');
    }

    public function test_unpaid_hold_expires(): void
    {
        $w = $this->widget();
        Appointment::create([
            'widget_id' => $w->id, 'user_id' => $w->user_id, 'service_id' => 'checkup', 'service_name' => 'Check-up', 'duration' => 60,
            'name' => 'X', 'email' => 'x@example.com', 'starts_at' => '2026-11-10 09:00:00', 'ends_at' => '2026-11-10 10:00:00',
            'status' => 'confirmed', 'payment_status' => 'unpaid', 'amount' => 100, 'currency' => 'gbp',
        ]);
        $this->assertCount(1, $this->getJson("/api/widget/{$w->uuid}/booking/slots?service=checkup&date=2026-11-10")->json('slots'));
        $this->travel(31)->minutes();
        $this->assertCount(2, $this->getJson("/api/widget/{$w->uuid}/booking/slots?service=checkup&date=2026-11-10")->json('slots'));
    }

    public function test_connected_calendar_busy_time_blocks_slots(): void
    {
        config(['services.google.client_id' => 'id', 'services.google.client_secret' => 'secret']);
        Http::fake(['www.googleapis.com/calendar/v3/freeBusy' => Http::response(['calendars' => ['primary' => ['busy' => [
            ['start' => '2026-11-10T10:00:00Z', 'end' => '2026-11-10T10:30:00Z'],
        ]]]])]);
        $w = $this->widget();
        CalendarConnection::create(['widget_id' => $w->id, 'provider' => 'google', 'access_token' => 't', 'expires_at' => now()->addHour()]);

        $slots = $this->getJson("/api/widget/{$w->uuid}/booking/slots?service=checkup&date=2026-11-10")->json('slots');
        $this->assertSame(['9:00 AM'], array_column($slots, 'label'));
    }

    public function test_bookings_sync_to_hubspot(): void
    {
        Http::fake([
            'api.hubapi.com/crm/v3/objects/contacts/search' => Http::response(['results' => []]),
            'api.hubapi.com/crm/v3/objects/contacts' => Http::response(['id' => '501']),
            'api.hubapi.com/crm/v3/objects/meetings' => Http::response(['id' => '901']),
            'api.hubapi.com/crm/v3/objects/meetings/901' => Http::response(['id' => '901']),
        ]);
        $w = $this->widget();
        $crm = CrmConnection::create(['widget_id' => $w->id, 'provider' => 'hubspot', 'credentials' => ['token' => 'pat-1']]);

        $this->book($w)->assertCreated();
        $a = Appointment::first();
        (new SyncAppointment($a->id, 'appointment.booked'))->handle(app(\App\Services\CalendarService::class), app(\App\Services\CrmService::class));
        $this->assertSame(['contact' => '501', 'record' => '901'], $a->fresh()->external_refs[$crm->refKey()]);

        $a->update(['status' => 'cancelled']);
        (new SyncAppointment($a->id, 'appointment.cancelled'))->handle(app(\App\Services\CalendarService::class), app(\App\Services\CrmService::class));
        Http::assertSent(fn ($r) => $r->method() === 'PATCH' && $r['properties']['hs_meeting_outcome'] === 'CANCELED');
        $this->assertNull($crm->fresh()->last_error);
    }

    public function test_every_crm_driver_creates_contact_and_record(): void
    {
        Http::fake([
            'acme.pipedrive.com/*' => Http::response(['data' => ['id' => 7, 'items' => []]]),
            'services.leadconnectorhq.com/*' => Http::response(['contact' => ['id' => 'c1'], 'id' => 'g1']),
            'www.zohoapis.eu/*' => Http::response(['data' => [['details' => ['id' => 'z1']]]]),
            'acme.my.salesforce.com/*' => Http::response(['records' => [], 'id' => 's1']),
            'hooks.example.com/*' => Http::response(['ok' => true]),
        ]);
        $w = $this->widget();
        $creds = [
            'pipedrive'   => ['token' => 't', 'domain' => 'acme.pipedrive.com'],
            'gohighlevel' => ['token' => 't', 'location_id' => 'L1', 'calendar_id' => 'C1'],
            'zoho'        => ['token' => 't', 'dc' => 'eu'],
            'salesforce'  => ['token' => 't', 'instance_url' => 'https://acme.my.salesforce.com'],
            'webhook'     => ['url' => 'https://hooks.example.com/x', 'secret' => 's'],
        ];
        foreach ($creds as $p => $c) {
            CrmConnection::create(['widget_id' => $w->id, 'provider' => $p, 'credentials' => $c]);
        }
        $this->book($w)->assertCreated();
        (new SyncAppointment(Appointment::first()->id, 'appointment.booked'))->handle(app(\App\Services\CalendarService::class), app(\App\Services\CrmService::class));

        foreach (CrmConnection::all() as $c) {
            $this->assertNull($c->last_error, $c->provider . ': ' . $c->last_error);
        }
        Http::assertSent(fn ($r) => str_contains($r->url(), 'hooks.example.com') && $r->hasHeader('X-PoriFyx-Signature'));
    }

    public function test_rest_api_lets_a_crm_manage_bookings(): void
    {
        $w = $this->widget();
        $key = ApiKey::create(['user_id' => $w->user_id, 'name' => 'CRM', 'is_active' => true]);
        $h = ['Authorization' => 'Bearer ' . $key->key];

        $this->getJson('/api/v1/appointments')->assertUnauthorized();
        $this->getJson("/api/v1/widgets/{$w->id}/availability?service=checkup&date=2026-11-10", $h)->assertOk()->assertJsonCount(2, 'slots');

        $id = $this->postJson('/api/v1/appointments', ['widget_id' => $w->id, 'service_id' => 'checkup', 'start' => '2026-11-10T09:00:00Z', 'name' => 'Ada', 'email' => 'ada@example.com'], $h)
            ->assertCreated()->assertJsonPath('data.source', 'api')->json('data.id');
        $this->patchJson("/api/v1/appointments/{$id}", ['start' => '2026-11-10T10:00:00Z'], $h)->assertOk()->assertJsonPath('data.starts_at', '2026-11-10T10:00:00Z');
        $this->deleteJson("/api/v1/appointments/{$id}", [], $h)->assertOk()->assertJsonPath('data.status', 'cancelled');
        $this->getJson('/api/v1/appointments?status=cancelled', $h)->assertOk()->assertJsonPath('meta.total', 1);

        // Another account's key cannot see it.
        $other = ApiKey::create(['user_id' => User::factory()->create()->id, 'name' => 'x', 'is_active' => true]);
        $this->getJson("/api/v1/appointments/{$id}", ['Authorization' => 'Bearer ' . $other->key])->assertNotFound();
    }

    public function test_visitor_can_reschedule_from_their_link(): void
    {
        $w = $this->widget();
        $this->book($w)->assertCreated();
        $a = Appointment::first();

        $this->post(route('appointments.reschedule.visitor', $a->token), ['start' => '2026-11-10T10:00:00Z'])->assertRedirect();
        $this->assertSame('2026-11-10 10:00:00', $a->fresh()->starts_at->utc()->format('Y-m-d H:i:s'));
        Mail::assertQueued(\App\Mail\AppointmentMail::class, fn ($m) => $m->kind === 'owner_rescheduled');
        Queue::assertPushed(SyncAppointment::class, fn ($j) => $j->event === 'appointment.rescheduled');
    }

    public function test_booking_number_lets_visitor_manage_from_chat(): void
    {
        $w = $this->widget();
        $ref = $this->book($w)->assertCreated()->json('reference');
        $this->assertMatchesRegularExpression('/^BK-[2-9A-HJ-NP-Z]{6}$/', $ref);
        Mail::assertQueued(\App\Mail\AppointmentMail::class, fn ($m) => $m->kind === 'booked' && str_contains($m->envelope()->subject, $ref));

        // Wrong email or number: nothing found.
        $this->postJson("/api/widget/{$w->uuid}/booking/lookup", ['reference' => $ref, 'email' => 'eve@example.com'])->assertNotFound();
        // Typed loosely ("bk xxxxxx", any case) with the right email: found.
        $loose = 'bk ' . strtolower(substr($ref, 3));
        $d = $this->postJson("/api/widget/{$w->uuid}/booking/lookup", ['reference' => $loose, 'email' => 'ADA@example.com'])
            ->assertOk()->assertJsonPath('reference', $ref)->assertJsonPath('can_move', true)->json();

        $this->postJson("/api/widget/{$w->uuid}/booking/manage/{$d['token']}/reschedule", ['start' => '2026-11-10T10:00:00Z'])
            ->assertOk()->assertJsonPath('when', 'Tuesday 10 November, 10:00 AM');
        $this->postJson("/api/widget/{$w->uuid}/booking/manage/{$d['token']}/cancel")->assertOk()->assertJsonPath('status', 'cancelled');
        $this->postJson("/api/widget/{$w->uuid}/booking/manage/{$d['token']}/cancel")->assertForbidden();

        // A token from another widget does not work here.
        $other = $this->widget();
        $this->postJson("/api/widget/{$other->uuid}/booking/manage/{$d['token']}/cancel")->assertNotFound();
    }

    public function test_visitor_cannot_move_booking_into_the_no_change_window(): void
    {
        $w = $this->widget(['cancel_notice' => 12, 'max_days_ahead' => 5]);
        $this->postJson("/api/widget/{$w->uuid}/booking", [
            'service_id' => 'checkup', 'start' => '2026-11-12T09:00:00Z', 'name' => 'Ada', 'email' => 'ada@example.com',
        ])->assertCreated();
        $token = Appointment::first()->token;

        // Now is 06:00 on the 10th: 09:00 today is inside 12 hours, so it is not offered or accepted.
        $this->assertSame([], $this->getJson("/api/widget/{$w->uuid}/booking/slots?service=checkup&date=2026-11-10&ignore={$token}")->json('slots'));
        $this->postJson("/api/widget/{$w->uuid}/booking/manage/{$token}/reschedule", ['start' => '2026-11-10T09:00:00Z'])->assertStatus(422);
        $this->postJson("/api/widget/{$w->uuid}/booking/manage/{$token}/reschedule", ['start' => '2026-11-11T10:00:00Z'])->assertOk();
    }

    public function test_visitor_emails_carry_the_business_brand(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        $w = $this->widget(['business_name' => 'Acme Clinic']);
        $s = \App\Support\Booking::settings($w);

        // Owner uploads a logo and sets website + reply-to through the settings form.
        $this->actingAs($w->user)->put(route('widgets.booking.update', $w), [
            'enabled' => 1, 'business_name' => 'Acme Clinic', 'button_label' => 'Book', 'timezone' => 'UTC',
            'slot_step' => 60, 'buffer' => 0, 'min_notice' => 0, 'max_days_ahead' => 30, 'daily_limit' => 0,
            'reminder_hours' => 24, 'cancel_notice' => 0, 'payments_currency' => 'GBP',
            'services' => $s['services'], 'hours' => $s['hours'],
            'website' => 'https://acme.example', 'reply_to' => 'desk@acme.example',
            'logo' => \Illuminate\Http\UploadedFile::fake()->image('logo.png', 200, 60),
        ])->assertSessionHasNoErrors();
        $w->refresh();
        \Illuminate\Support\Facades\Storage::disk('public')->assertExists($w->booking['logo']);

        $this->book($w)->assertCreated();
        $mail = new \App\Mail\AppointmentMail(Appointment::first(), 'booked');
        $html = $mail->render();
        $this->assertStringContainsString('storage/' . $w->booking['logo'], $html);
        $this->assertStringContainsString('© ' . date('Y') . ' Acme Clinic', $html);
        $this->assertStringNotContainsString('because you have an account', $html);
        $this->assertSame('Acme Clinic', $mail->envelope()->from->name);
        $this->assertSame('desk@acme.example', $mail->envelope()->replyTo[0]->address);

        // The business's own notification keeps the platform look.
        $owner = new \App\Mail\AppointmentMail(Appointment::first(), 'owner_new');
        $this->assertStringContainsString(config('branding.name'), $owner->render());
        $this->assertNull($owner->envelope()->from);
    }

    public function test_assistant_is_told_to_offer_booking(): void
    {
        $w = $this->widget();
        $method = new \ReflectionMethod(\App\Services\AiChatService::class, 'buildSystemPrompt');
        $prompt = $method->invoke(app(\App\Services\AiChatService::class), $w, collect(), 'en');
        $this->assertStringContainsString('[[BOOKING]]', $prompt);
        $this->assertStringContainsString('Check-up (60 min)', $prompt);
    }
}
