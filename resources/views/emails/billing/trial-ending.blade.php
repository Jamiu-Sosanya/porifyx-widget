@extends('emails.layouts.base')
@section('subject', 'Your trial ends in ' . $daysLeft . ' day' . ($daysLeft !== 1 ? 's' : '') . ' — upgrade to keep access')
@section('content')
<div class="card-hero">
    <p style="font-size:36px;margin:0 0 12px;">⏳</p>
    <h1>Your trial ends {{ $daysLeft === 1 ? 'tomorrow' : 'in ' . $daysLeft . ' days' }}</h1>
    <p class="lead">Upgrade now to keep your widgets running and never lose a lead.</p>
</div>

<div class="card-body">
    <p>Hi {{ $user->name }},</p>
    <p>Your {{ config('branding.trial_days', 14) }}-day free trial expires on <strong>{{ $trialEndsAt?->format('M d, Y') }}</strong>. After that, your widgets will be paused and you'll lose access to all features.</p>

    <div class="alert alert-warning">
        <strong>Don't lose your data!</strong> Upgrade before your trial ends to keep all your conversations, leads, and trained AI knowledge bases.
    </div>

    @if(!empty($usageStats))
    <h2>Your trial usage</h2>
    <div class="stat-grid">
        <div class="stat-box">
            <span class="stat-value">{{ number_format($usageStats['conversations'] ?? 0) }}</span>
            <span class="stat-label">Conversations</span>
        </div>
        <div class="stat-box">
            <span class="stat-value">{{ number_format($usageStats['leads'] ?? 0) }}</span>
            <span class="stat-label">Leads Captured</span>
        </div>
        <div class="stat-box">
            <span class="stat-value">{{ number_format($usageStats['messages'] ?? 0) }}</span>
            <span class="stat-label">Messages Sent</span>
        </div>
    </div>
    @endif

    <div class="btn-wrap">
        <a href="{{ config('app.url') }}/settings/billing" class="btn">Upgrade My Plan →</a>
    </div>

    <hr>

    <p><strong>Most popular plan:</strong></p>
    <div style="background:rgba(47, 94, 232,0.08);border:1px solid rgba(47, 94, 232,0.2);border-radius:12px;padding:16px 18px;">
        <p style="margin:0 0 4px;font-size:16px;font-weight:700;color:#ffffff;">Pro Plan — $79/month</p>
        <p style="margin:0;font-size:13px;color:#9ca3af;">5 widgets · 5,000 messages/mo · 500 leads/mo · Live chat + AI insights</p>
    </div>

    <p class="text-muted" style="margin-top:16px;">Questions before upgrading? Reply to this email or contact us at <a href="mailto:{{ config('branding.support_email') }}" class="link">{{ config('branding.support_email') }}</a>.</p>
</div>
@endsection
