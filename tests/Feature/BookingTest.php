<?php

namespace Tests\Feature;

use App\Mail\AppointmentMail;
use App\Models\Appointment;
use App\Models\User;
use App\Models\Widget;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class BookingTest extends TestCase
{
    use RefreshDatabase;

    private function widget(array $booking = []): Widget
    {
        $user = User::factory()->create();
        $all = [['09:00', '12:00']];

        return Widget::create([
            'user_id' => $user->id, 'name' => 'Clinic', 'uuid' => (string) Str::uuid(), 'token' => Str::random(32), 'is_active' => true,
            'booking' => array_merge([
                'enabled' => true, 'timezone' => 'UTC', 'min_notice' => 0, 'slot_step' => 60, 'buffer' => 0,
                'services' => [['id' => 'checkup', 'name' => 'Check-up', 'duration' => 60, 'price' => '', 'description' => '']],
                'hours' => array_fill_keys(['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'], $all),
            ], $booking),
        ]);
    }

    public function test_visitor_can_book_and_slot_is_then_taken(): void
    {
        Mail::fake();
        $this->travelTo(CarbonImmutable::parse('2026-11-10 06:00:00', 'UTC'));
        $w = $this->widget();

        $this->getJson("/api/widget/{$w->uuid}/booking")->assertOk()->assertJsonPath('services.0.id', 'checkup');

        $slots = $this->getJson("/api/widget/{$w->uuid}/booking/slots?service=checkup&date=2026-11-10")->assertOk()->json('slots');
        $this->assertSame(['9:00 AM', '10:00 AM', '11:00 AM'], array_column($slots, 'label'));

        $payload = ['service_id' => 'checkup', 'start' => $slots[0]['start'], 'name' => 'Ada', 'email' => 'ada@example.com'];
        $this->postJson("/api/widget/{$w->uuid}/booking", $payload)->assertCreated()->assertJsonPath('status', 'confirmed');

        // Same time again is refused, and the slot list no longer offers it.
        $this->postJson("/api/widget/{$w->uuid}/booking", $payload)->assertStatus(422)->assertJsonValidationErrors('start');
        $this->assertCount(2, $this->getJson("/api/widget/{$w->uuid}/booking/slots?service=checkup&date=2026-11-10")->json('slots'));

        $a = Appointment::first();
        $this->assertNotNull($a->lead_id);
        Mail::assertQueued(AppointmentMail::class, fn ($m) => $m->kind === 'booked');
        Mail::assertQueued(AppointmentMail::class, fn ($m) => $m->kind === 'owner_new');

        // Visitor cancels from their email link; the time frees up.
        $this->post(route('appointments.cancel', $a->token))->assertRedirect();
        $this->assertSame('cancelled', $a->fresh()->status);
        $this->assertCount(3, $this->getJson("/api/widget/{$w->uuid}/booking/slots?service=checkup&date=2026-11-10")->json('slots'));
    }

    public function test_notice_buffer_and_disabled_booking(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-10 08:30:00', 'UTC'));
        $w = $this->widget(['min_notice' => 1, 'slot_step' => 30, 'services' => [['id' => 'x', 'name' => 'X', 'duration' => 30]]]);

        // 08:30 + 1h notice: first start is 09:30.
        $labels = array_column($this->getJson("/api/widget/{$w->uuid}/booking/slots?service=x&date=2026-11-10")->json('slots'), 'label');
        $this->assertSame('9:30 AM', $labels[0]);

        $w->update(['booking' => array_merge($w->booking, ['enabled' => false])]);
        $this->getJson("/api/widget/{$w->uuid}/booking")->assertNotFound();
    }

    public function test_owner_sees_bookings_and_can_change_status(): void
    {
        Mail::fake();
        $w = $this->widget(['auto_confirm' => false]);
        $a = Appointment::create([
            'widget_id' => $w->id, 'user_id' => $w->user_id, 'service_id' => 'checkup', 'service_name' => 'Check-up', 'duration' => 60,
            'name' => 'Ada', 'email' => 'ada@example.com', 'starts_at' => now()->addDay(), 'ends_at' => now()->addDay()->addHour(), 'status' => 'pending',
        ]);

        $this->actingAs($w->user)->get(route('appointments.index'))->assertOk()->assertSee('Ada');
        $this->actingAs($w->user)->patch(route('appointments.update', $a), ['status' => 'confirmed'])->assertRedirect();
        $this->assertSame('confirmed', $a->fresh()->status);
        Mail::assertQueued(AppointmentMail::class, fn ($m) => $m->kind === 'confirmed');

        $this->actingAs(User::factory()->create())->patch(route('appointments.update', $a), ['status' => 'cancelled'])->assertForbidden();
    }
}
