@extends('emails.layouts.base')
@section('subject', 'Reset your password')
@section('content')
<div class="card-hero">
    <p style="font-size:36px;margin:0 0 12px;">🔑</p>
    <h1>Reset your password</h1>
    <p class="lead">We received a request to reset the password for your account.</p>
</div>

<div class="card-body">
    <p>Hi {{ $user->name ?? 'there' }},</p>
    <p>Click the button below to choose a new password for your <strong>{{ config('branding.name') }}</strong> account.</p>

    <div class="btn-wrap">
        <a href="{{ $resetUrl }}" class="btn">Reset Password</a>
    </div>

    <div class="alert alert-warning">
        This link expires in <strong>{{ $expiry ?? 60 }} minutes</strong>. After that, you'll need to request a new reset link.
    </div>

    <p>If you didn't request a password reset, you can safely ignore this email. Your password will remain unchanged.</p>

    <hr>
    <p class="text-muted">For security, this request was received from IP address <strong>{{ $ipAddress ?? 'unknown' }}</strong>. If this wasn't you, please contact <a href="mailto:{{ config('branding.support_email') }}" class="link">{{ config('branding.support_email') }}</a> immediately.</p>
    <p class="text-muted">If the button doesn't work, copy and paste this URL into your browser:</p>
    <p style="word-break:break-all;font-size:12px;color:#ff6a2b;">{{ $resetUrl }}</p>
</div>
@endsection
