@extends('emails.layouts.base')
@section('subject', 'Subscription confirmed — ' . ($plan->name ?? 'Plan'))
@section('content')
<div class="card-hero">
    <p style="font-size:36px;margin:0 0 12px;">🎉</p>
    <h1>You're all set!</h1>
    <p class="lead">Your <strong>{{ $plan->name }}</strong> subscription is now active.</p>
</div>

<div class="card-body">
    <p>Hi {{ $user->name }},</p>
    <p>Thank you for subscribing to {{ config('branding.name') }}. Here's a summary of your plan.</p>

    <table class="data-table">
        <tr class="data-row">
            <td class="data-label">Plan</td>
            <td class="data-value"><strong>{{ $plan->name }}</strong></td>
        </tr>
        <tr class="data-row">
            <td class="data-label">Billing</td>
            <td class="data-value">{{ ucfirst($subscription->billing_cycle ?? 'monthly') }}</td>
        </tr>
        <tr class="data-row">
            <td class="data-label">Amount</td>
            <td class="data-value">
                ${{ number_format($subscription->billing_cycle === 'yearly' ? $plan->price_yearly : $plan->price_monthly, 2) }}
                / {{ $subscription->billing_cycle === 'yearly' ? 'year' : 'month' }}
            </td>
        </tr>
        <tr class="data-row">
            <td class="data-label">Next billing date</td>
            <td class="data-value">{{ $subscription->current_period_end?->format('M d, Y') ?? 'N/A' }}</td>
        </tr>
        <tr class="data-row">
            <td class="data-label">Widgets</td>
            <td class="data-value">{{ $plan->widgets_limit == -1 ? 'Unlimited' : $plan->widgets_limit }}</td>
        </tr>
        <tr class="data-row">
            <td class="data-label">Messages/mo</td>
            <td class="data-value">{{ $plan->messages_per_month == -1 ? 'Unlimited' : number_format($plan->messages_per_month) }}</td>
        </tr>
    </table>

    <div class="btn-wrap">
        <a href="{{ config('app.url') }}/dashboard" class="btn btn-success">Go to Dashboard →</a>
    </div>

    <p>Questions about your subscription? Visit your <a href="{{ config('app.url') }}/settings/billing" class="link">billing settings</a> or contact us at <a href="mailto:{{ config('branding.support_email') }}" class="link">{{ config('branding.support_email') }}</a>.</p>
</div>
@endsection
