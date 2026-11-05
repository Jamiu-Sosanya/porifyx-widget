@extends('emails.layouts.base')
@section('subject', 'Welcome to ' . config('branding.name') . '!')
@section('content')
<div class="card-hero">
    <p style="font-size:36px;margin:0 0 12px;">👋</p>
    <h1>Welcome to {{ config('branding.name') }}, {{ $user->name }}!</h1>
    <p class="lead">Your account is ready. Let's build your first AI chat widget.</p>
</div>

<div class="card-body">
    <p>Your {{ config('branding.name') }} account has been created successfully. You're on a <strong>{{ config('branding.trial_days', 14) }}-day free trial</strong> — no credit card required.</p>

    <div class="btn-wrap">
        <a href="{{ config('app.url') }}/dashboard" class="btn">Go to Your Dashboard →</a>
    </div>

    <hr>

    <h2>Get started in 3 steps</h2>

    <div class="feature-item">
        <div class="feature-icon">①</div>
        <div class="feature-text"><strong>Create your first widget</strong><br>Give it a name, pick a theme, and set your welcome message.</div>
    </div>
    <div class="feature-item">
        <div class="feature-icon">②</div>
        <div class="feature-text"><strong>Train the AI on your data</strong><br>Upload PDFs, paste URLs, or add FAQs — your AI learns your business in minutes.</div>
    </div>
    <div class="feature-item">
        <div class="feature-icon">③</div>
        <div class="feature-text"><strong>Embed on your website</strong><br>Copy a single script tag and paste it before <code style="color:#6b8ff2;">&lt;/body&gt;</code>. Done!</div>
    </div>

    <hr>

    <p>Need help? Visit our <a href="{{ config('branding.docs_url') ?: config('app.url').'/docs' }}" class="link">documentation</a> or reply to this email — we're happy to assist.</p>
    <p class="text-muted">Your trial ends in {{ config('branding.trial_days', 14) }} days. You can upgrade anytime from your <a href="{{ config('app.url') }}/settings/billing" class="link">billing settings</a>.</p>
</div>
@endsection
