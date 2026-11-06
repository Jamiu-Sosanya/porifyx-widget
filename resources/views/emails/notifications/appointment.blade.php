@extends('emails.layouts.base')
@php
    $a = $appointment;
    $s = $a->widget->bookingSettings();
    $owner = str_starts_with($kind, 'owner_');
    $heading = [
        'booked'          => $a->status === 'pending' ? 'We have your booking request' : 'Your booking is confirmed',
        'confirmed'       => 'Your booking is confirmed',
        'cancelled'       => 'Your booking has been cancelled',
        'reminder'        => 'See you soon',
        'owner_new'       => 'New booking',
        'owner_cancelled' => 'A booking was cancelled',
        'rescheduled'       => 'Your booking has moved',
        'owner_rescheduled' => 'A booking was moved',
    ][$kind];
    $lead = [
        'booked'          => $a->status === 'pending' ? 'Thanks, ' . $a->name . '. ' . $s['business_name'] . ' will confirm your time shortly.' : 'Thanks, ' . $a->name . '. The details are below and a calendar invite is attached.',
        'confirmed'       => $s['business_name'] . ' has confirmed your time. A calendar invite is attached.',
        'cancelled'       => 'This appointment with ' . $s['business_name'] . ' will not go ahead. You are welcome to book another time.',
        'reminder'        => 'This is a reminder of your appointment with ' . $s['business_name'] . '.',
        'owner_new'       => $a->name . ' booked through ' . $a->widget->name . '.' . ($a->status === 'pending' ? ' It is waiting for you to confirm it.' : ''),
        'owner_cancelled' => $a->name . ' cancelled their appointment. The time is free again.',
        'rescheduled'       => 'Your appointment with ' . $s['business_name'] . ' now has a new time. An updated calendar invite is attached.',
        'owner_rescheduled' => $a->name . ' moved their appointment to the new time below.',
    ][$kind];
@endphp
@section('subject', $heading)
@unless($owner)
@section('footer', 'You are receiving this email because you booked an appointment with ' . $s['business_name'] . '. Keep your booking number ' . $a->reference . ' to change or cancel it.')
@endunless
@section('content')
<div class="card-hero">
    <p style="font-size:36px;margin:0 0 12px;">📅</p>
    <h1>{{ $heading }}</h1>
    <p class="lead">{{ $lead }}</p>
</div>

<div class="card-body">
    <div style="text-align:center;margin:0 0 22px;padding:18px;border-radius:14px;background:rgba(47,94,232,0.10);border:1px solid rgba(47,94,232,0.30);">
        <p style="margin:0;font-size:12px;letter-spacing:0.14em;text-transform:uppercase;color:#93a4c8;">Booking number</p>
        <p style="margin:6px 0 0;font-size:28px;font-weight:800;letter-spacing:0.08em;color:#ffffff;font-family:'SFMono-Regular',Consolas,monospace;">{{ $a->reference }}</p>
        @unless($owner || $kind === 'cancelled')
        <p style="margin:10px 0 0;font-size:13px;line-height:1.6;color:#b6c2dc;">Need to change or cancel? Open the chat on our website, choose <strong>Manage my booking</strong> and enter this number with your email. Or use the button below.</p>
        @endunless
    </div>
    <table class="data-table">
        <tr class="data-row"><td class="data-label">Service</td><td class="data-value">{{ $a->service_name }} ({{ $a->duration }} min)</td></tr>
        @if($a->staff_name)<tr class="data-row"><td class="data-label">With</td><td class="data-value">{{ $a->staff_name }}</td></tr>@endif
        @if($a->amount)<tr class="data-row"><td class="data-label">Payment</td><td class="data-value">{{ $a->priceLabel() }} ({{ $a->payment_status }})</td></tr>@endif
        <tr class="data-row"><td class="data-label">When</td><td class="data-value">{{ $a->localStart()->format('l j F Y, g:i A') }} ({{ $s['timezone'] }})</td></tr>
        @if($owner)
        <tr class="data-row"><td class="data-label">Name</td><td class="data-value">{{ $a->name }}</td></tr>
        <tr class="data-row"><td class="data-label">Email</td><td class="data-value">{{ $a->email }}</td></tr>
        @if($a->phone)<tr class="data-row"><td class="data-label">Phone</td><td class="data-value">{{ $a->phone }}</td></tr>@endif
        @if($a->notes)<tr class="data-row"><td class="data-label">Notes</td><td class="data-value">{{ $a->notes }}</td></tr>@endif
        @foreach($a->answers ?? [] as $q => $v)<tr class="data-row"><td class="data-label">{{ $q }}</td><td class="data-value">{{ is_bool($v) ? ($v ? 'Yes' : 'No') : $v }}</td></tr>@endforeach
        @else
        <tr class="data-row"><td class="data-label">Business</td><td class="data-value">{{ $s['business_name'] }}</td></tr>
        @endif
    </table>

    <div class="btn-wrap">
        @if($owner)
            <a href="{{ route('appointments.index') }}" class="btn">Open bookings</a>
        @elseif($kind !== 'cancelled')
            <a href="{{ $a->manageUrl() }}" class="btn">Manage or cancel</a>
        @endif
    </div>
</div>
@endsection
