@extends('emails.layouts.base')
@section('subject', 'Your subscription has been cancelled')
@section('content')
<div class="card-hero">
    <p style="font-size:36px;margin:0 0 12px;">😢</p>
    <h1>Subscription cancelled</h1>
    <p class="lead">We're sorry to see you go. Your access continues until the end of your billing period.</p>
</div>

<div class="card-body">
    <p>Hi {{ $user->name }},</p>
    <p>Your <strong>{{ $plan->name ?? 'subscription' }}</strong> has been cancelled as requested. You'll continue to have full access to all features until <strong>{{ $endsAt?->format('M d, Y') ?? 'the end of your billing period' }}</strong>.</p>

    <table class="data-table">
        <tr class="data-row">
            <td class="data-label">Plan cancelled</td>
            <td class="data-value">{{ $plan->name ?? '—' }}</td>
        </tr>
        <tr class="data-row">
            <td class="data-label">Access until</td>
            <td class="data-value">{{ $endsAt?->format('M d, Y') ?? 'N/A' }}</td>
        </tr>
        <tr class="data-row">
            <td class="data-label">Data retained for</td>
            <td class="data-value">30 days after expiry</td>
        </tr>
    </table>

    <div class="alert alert-info">
        <strong>Changed your mind?</strong> You can reactivate your subscription at any time before your access expires — no setup required.
    </div>

    <div class="btn-wrap">
        <a href="{{ config('app.url') }}/settings/billing" class="btn">Reactivate Subscription</a>
    </div>

    <hr>
    <p>We'd love to know why you cancelled so we can improve. <a href="mailto:{{ config('branding.support_email') }}?subject=Cancellation+Feedback" class="link">Reply to this email</a> and share your feedback — it takes 30 seconds and helps us a lot.</p>
    <p class="text-muted">Thank you for being a {{ config('branding.name') }} customer.</p>
</div>
@endsection
