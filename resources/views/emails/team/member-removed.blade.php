@extends('emails.layouts.base')
@section('subject', 'Your team access has been removed')
@section('content')
<div class="card-hero">
    <p style="font-size:36px;margin:0 0 12px;">🔒</p>
    <h1>Team access removed</h1>
    <p class="lead">Your access to <strong>{{ $ownerName }}'s</strong> workspace on {{ config('branding.name') }} has been removed.</p>
</div>

<div class="card-body">
    <p>Hi {{ $memberName }},</p>
    <p>You have been removed from <strong>{{ $ownerName }}'s</strong> {{ config('branding.name') }} workspace. You can no longer access their widgets, conversations, or leads.</p>

    <table class="data-table">
        <tr class="data-row">
            <td class="data-label">Workspace</td>
            <td class="data-value">{{ $ownerName }}'s team</td>
        </tr>
        <tr class="data-row">
            <td class="data-label">Removed at</td>
            <td class="data-value">{{ now()->format('M d, Y g:i A') }}</td>
        </tr>
    </table>

    <div class="alert alert-info">
        Your personal {{ config('branding.name') }} account is unaffected. You can still log in and manage your own widgets.
    </div>

    <div class="btn-wrap">
        <a href="{{ config('app.url') }}/dashboard" class="btn btn-outline">Go to My Dashboard →</a>
    </div>

    <p>If you believe this was a mistake, please contact <strong>{{ $ownerName }}</strong> directly, or reach out to us at <a href="mailto:{{ config('branding.support_email') }}" class="link">{{ config('branding.support_email') }}</a>.</p>
</div>
@endsection
