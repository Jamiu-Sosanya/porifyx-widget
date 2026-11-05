@extends('emails.layouts.base')
@section('subject', '⚠️ ' . $usagePercent . '% of your ' . $resourceName . ' used this month')
@section('content')
<div class="card-hero">
    <p style="font-size:36px;margin:0 0 12px;">📈</p>
    <h1>Usage limit approaching</h1>
    <p class="lead">You've used <strong>{{ $usagePercent }}%</strong> of your monthly {{ strtolower($resourceName) }}.</p>
</div>

<div class="card-body">
    <p>Hi {{ $user->name }},</p>
    <p>You're approaching your monthly limit for <strong>{{ $resourceName }}</strong> on your {{ $plan->name ?? 'current' }} plan.</p>

    <table class="data-table">
        <tr class="data-row">
            <td class="data-label">Resource</td>
            <td class="data-value">{{ $resourceName }}</td>
        </tr>
        <tr class="data-row">
            <td class="data-label">Used</td>
            <td class="data-value">{{ number_format($used) }} / {{ $limit == -1 ? 'Unlimited' : number_format($limit) }}</td>
        </tr>
        <tr class="data-row">
            <td class="data-label">Usage</td>
            <td class="data-value">
                <div style="background:rgba(255,255,255,0.05);border-radius:4px;overflow:hidden;height:8px;width:120px;display:inline-block;vertical-align:middle;margin-right:8px;">
                    <div style="height:100%;width:{{ $usagePercent }}%;background:{{ $usagePercent >= 90 ? '#ef4444' : '#f59e0b' }};border-radius:4px;"></div>
                </div>
                {{ $usagePercent }}%
            </td>
        </tr>
        <tr class="data-row">
            <td class="data-label">Resets</td>
            <td class="data-value">{{ $resetDate?->format('M d, Y') ?? 'End of month' }}</td>
        </tr>
    </table>

    @if($usagePercent >= 90)
    <div class="alert alert-danger">
        <strong>Critical:</strong> When you reach 100%, new {{ strtolower($resourceName) }} will be blocked until your next billing cycle.
    </div>
    @else
    <div class="alert alert-warning">
        Upgrade your plan to get more capacity and avoid service interruption.
    </div>
    @endif

    <div class="btn-wrap">
        <a href="{{ config('app.url') }}/settings/billing" class="btn">Upgrade Plan →</a>
    </div>

    <p class="text-muted text-center">Manage usage notifications in <a href="{{ config('app.url') }}/settings/notifications" class="link">notification settings</a>.</p>
</div>
@endsection
