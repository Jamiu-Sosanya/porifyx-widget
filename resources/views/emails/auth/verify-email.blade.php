@extends('emails.layouts.base')
@section('subject', 'Verify your email address')
@section('content')
<div class="card-hero">
    <p style="font-size:36px;margin:0 0 12px;">✉️</p>
    <h1>Verify your email</h1>
    <p class="lead">Click the button below to confirm your email address and activate your account.</p>
</div>

<div class="card-body">
    <p>Hi {{ $user->name ?? 'there' }},</p>
    <p>Thanks for signing up for <strong>{{ config('branding.name') }}</strong>. To complete your account setup, please verify your email address.</p>

    <div class="btn-wrap">
        <a href="{{ $verificationUrl }}" class="btn btn-success">Verify Email Address</a>
    </div>

    <div class="alert alert-info">
        This link expires in <strong>60 minutes</strong>. If it has expired, you can request a new verification email from your dashboard.
    </div>

    <p>If you didn't create an account, you can safely ignore this email.</p>

    <hr>
    <p class="text-muted">If the button doesn't work, copy and paste this URL into your browser:</p>
    <p style="word-break:break-all;font-size:12px;color:#2f5ee8;">{{ $verificationUrl }}</p>
</div>
@endsection
