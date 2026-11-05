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
    ][$kind];
    $lead = [
        'booked'          => $a->status === 'pending' ? 'Thanks, ' . $a->name . '. ' . $s['business_name'] . ' will confirm your time shortly.' : 'Thanks, ' . $a->name . '. The details are below and a calendar invite is attached.',
        'confirmed'       => $s['business_name'] . ' has confirmed your time. A calendar invite is attached.',
        'cancelled'       => 'This appointment with ' . $s['business_name'] . ' will not go ahead. You are welcome to book another time.',
        'reminder'        => 'This is a reminder of your appointment with ' . $s['business_name'] . '.',
        'owner_new'       => $a->name . ' booked through ' . $a->widget->name . '.' . ($a->status === 'pending' ? ' It is waiting for you to confirm it.' : ''),
        'owner_cancelled' => $a->name . ' cancelled their appointment. The time is free again.',
    ][$kind];
@endphp
@section('subject', $heading)
@section('content')
<div class="card-hero">
    <p style="font-size:36px;margin:0 0 12px;">📅</p>
    <h1>{{ $heading }}</h1>
    <p class="lead">{{ $lead }}</p>
</div>

<div class="card-body">
    <table class="data-table">
        <tr class="data-row"><td class="data-label">Service</td><td class="data-value">{{ $a->service_name }} ({{ $a->duration }} min)</td></tr>
        <tr class="data-row"><td class="data-label">When</td><td class="data-value">{{ $a->localStart()->format('l j F Y, g:i A') }} ({{ $s['timezone'] }})</td></tr>
        @if($owner)
        <tr class="data-row"><td class="data-label">Name</td><td class="data-value">{{ $a->name }}</td></tr>
        <tr class="data-row"><td class="data-label">Email</td><td class="data-value">{{ $a->email }}</td></tr>
        @if($a->phone)<tr class="data-row"><td class="data-label">Phone</td><td class="data-value">{{ $a->phone }}</td></tr>@endif
        @if($a->notes)<tr class="data-row"><td class="data-label">Notes</td><td class="data-value">{{ $a->notes }}</td></tr>@endif
        @else
        <tr class="data-row"><td class="data-label">With</td><td class="data-value">{{ $s['business_name'] }}</td></tr>
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
