@extends('emails.layouts.base')
@section('subject', $ticket->subject)
@section('content')
<div class="card-hero">
    <h1>{{ $toCustomer ? ($kind === 'created' ? 'We received your request' : 'New reply to your request') : ($kind === 'owner_escalated' ? 'Ticket needs a reply' : 'Ticket update') }}</h1>
    <p class="lead">{{ $ticket->subject }} · {{ $ticket->number }}</p>
</div>
<div class="card-body">
    @if($body !== '')
    <div style="white-space:pre-line;font-size:15px;line-height:1.6;">{{ $body }}</div>
    <hr>
    @endif
    @if($kind === 'created')
    <p>Thanks{{ $ticket->requester_name ? ', ' . $ticket->requester_name : '' }}. Our team will reply by email. You can also add details or follow the conversation here:</p>
    @elseif($kind === 'owner_escalated')
    <p>The customer has been waiting longer than your escalation time. Priority was raised to <strong>{{ $ticket->priority }}</strong>.</p>
    @endif
    <p style="text-align:center;margin-top:20px;"><a class="btn" href="{{ $link }}">{{ $toCustomer ? 'View or reply' : 'Open ticket' }}</a></p>
</div>
@endsection
