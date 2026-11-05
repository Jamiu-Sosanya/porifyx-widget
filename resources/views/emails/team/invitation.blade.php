@extends('emails.layouts.base')
@section('subject', $inviter->name . ' invited you to join ' . config('branding.name'))
@section('content')
<div class="card-hero">
    <p style="font-size:36px;margin:0 0 12px;">🤝</p>
    <h1>You've been invited!</h1>
    <p class="lead"><strong>{{ $inviter->name }}</strong> invited you to join their team on {{ config('branding.name') }}.</p>
</div>

<div class="card-body">
    <p>Hi{{ $inviteeName ? ' ' . $inviteeName : '' }},</p>
    <p><strong>{{ $inviter->name }}</strong> ({{ $inviter->email }}) has invited you to collaborate on their {{ config('branding.name') }} workspace as a <strong>{{ ucfirst($role ?? 'member') }}</strong>.</p>

    @if($inviter->company_name)
    <div class="alert alert-info">
        <strong>Team:</strong> {{ $inviter->company_name }}
    </div>
    @endif

    <table class="data-table">
        <tr class="data-row">
            <td class="data-label">Invited by</td>
            <td class="data-value">{{ $inviter->name }} ({{ $inviter->email }})</td>
        </tr>
        <tr class="data-row">
            <td class="data-label">Your role</td>
            <td class="data-value">{{ ucfirst($role ?? 'member') }}</td>
        </tr>
        <tr class="data-row">
            <td class="data-label">Invitation expires</td>
            <td class="data-value">{{ $expiresAt?->format('M d, Y') ?? 'In 7 days' }}</td>
        </tr>
    </table>

    <div class="btn-wrap">
        <a href="{{ $acceptUrl }}" class="btn btn-success">Accept Invitation →</a>
    </div>

    <p class="text-muted">If you don't want to join, simply ignore this email. The invitation will expire automatically.</p>
    <p class="text-muted">Don't recognise this invitation? Contact us at <a href="mailto:{{ config('branding.support_email') }}" class="link">{{ config('branding.support_email') }}</a>.</p>

    <hr>
    <p class="text-muted">If the button doesn't work, copy and paste this URL into your browser:</p>
    <p style="word-break:break-all;font-size:12px;color:#2f5ee8;">{{ $acceptUrl }}</p>
</div>
@endsection
