@extends('emails.layouts.base')
@section('subject', '📬 New offline message from ' . ($offlineMessage->name ?? 'a visitor'))
@section('content')
<div class="card-hero">
    <p style="font-size:36px;margin:0 0 12px;">📬</p>
    <h1>New offline message</h1>
    <p class="lead">A visitor left a message while your widget was offline on <strong>{{ $widget->name }}</strong>.</p>
</div>

<div class="card-body">
    <table class="data-table">
        <tr class="data-row">
            <td class="data-label">Name</td>
            <td class="data-value">{{ $offlineMessage->name ?? '—' }}</td>
        </tr>
        <tr class="data-row">
            <td class="data-label">Email</td>
            <td class="data-value">{{ $offlineMessage->email ?? '—' }}</td>
        </tr>
        <tr class="data-row">
            <td class="data-label">Widget</td>
            <td class="data-value">{{ $widget->name }}</td>
        </tr>
        <tr class="data-row">
            <td class="data-label">Sent at</td>
            <td class="data-value">{{ $offlineMessage->created_at?->format('M d, Y g:i A') ?? now()->format('M d, Y g:i A') }}</td>
        </tr>
    </table>

    <hr>
    <p><strong>Their message:</strong></p>
    <div style="background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.06);border-radius:10px;padding:16px 18px;font-size:15px;color:#d1d5db;line-height:1.7;margin:16px 0;">
        "{{ $offlineMessage->message }}"
    </div>

    <div class="btn-wrap">
        @if($offlineMessage->email)
        <a href="mailto:{{ $offlineMessage->email }}" class="btn">Reply via Email →</a>
        @else
        <a href="{{ config('app.url') }}/conversations" class="btn">View All Conversations →</a>
        @endif
    </div>

    <p class="text-muted text-center">Manage offline form settings in your <a href="{{ config('app.url') }}/widgets/{{ $widget->id }}/edit" class="link">widget settings</a>.</p>
</div>
@endsection
