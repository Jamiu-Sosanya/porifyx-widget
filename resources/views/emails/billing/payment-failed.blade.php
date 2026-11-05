@extends('emails.layouts.base')
@section('subject', '⚠️ Payment failed — action required')
@section('content')
<div class="card-hero">
    <p style="font-size:36px;margin:0 0 12px;">⚠️</p>
    <h1>Payment failed</h1>
    <p class="lead">We were unable to process your payment for {{ config('branding.name') }}.</p>
</div>

<div class="card-body">
    <p>Hi {{ $user->name }},</p>
    <p>Your recent payment of <strong>${{ number_format($amount ?? 0, 2) }}</strong> could not be processed. Please update your payment method to avoid any disruption to your service.</p>

    <table class="data-table">
        <tr class="data-row">
            <td class="data-label">Amount due</td>
            <td class="data-value"><strong>${{ number_format($amount ?? 0, 2) }}</strong></td>
        </tr>
        <tr class="data-row">
            <td class="data-label">Reason</td>
            <td class="data-value">{{ $failureReason ?? 'Card declined' }}</td>
        </tr>
        <tr class="data-row">
            <td class="data-label">Next retry</td>
            <td class="data-value">{{ $nextRetry?->format('M d, Y') ?? 'In 3 days' }}</td>
        </tr>
        <tr class="data-row">
            <td class="data-label">Plan</td>
            <td class="data-value">{{ $plan->name ?? '—' }}</td>
        </tr>
    </table>

    <div class="alert alert-danger">
        <strong>Action required:</strong> Your account will be downgraded if payment is not resolved within <strong>{{ $graceDays ?? 7 }} days</strong>.
    </div>

    <div class="btn-wrap">
        <a href="{{ config('app.url') }}/settings/billing" class="btn btn-danger">Update Payment Method →</a>
    </div>

    <p>Common reasons for payment failure:</p>
    <ul style="color:#9ca3af;font-size:14px;padding-left:20px;line-height:2;">
        <li>Expired card</li>
        <li>Insufficient funds</li>
        <li>Card number or CVV changed</li>
        <li>Bank blocked the transaction</li>
    </ul>

    <p>Need help? Contact us at <a href="mailto:{{ config('branding.support_email') }}" class="link">{{ config('branding.support_email') }}</a>.</p>
</div>
@endsection
