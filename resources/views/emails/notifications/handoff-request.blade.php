@extends('emails.layouts.base')
@section('subject', '🙋 Human agent requested on ' . $widget->name)
@section('content')
<div class="card-hero">
    <p style="font-size:36px;margin:0 0 12px;">🙋</p>
    <h1>Human agent requested</h1>
    <p class="lead">A visitor needs to speak with a real person on <strong>{{ $widget->name }}</strong>.</p>
</div>

<div class="card-body">
    <table class="data-table">
        <tr class="data-row">
            <td class="data-label">Visitor</td>
            <td class="data-value">{{ $conversation->visitor?->name ?? $conversation->visitor?->email ?? 'Anonymous' }}</td>
        </tr>
        @if($conversation->visitor?->email)
        <tr class="data-row">
            <td class="data-label">Email</td>
            <td class="data-value">{{ $conversation->visitor->email }}</td>
        </tr>
        @endif
        <tr class="data-row">
            <td class="data-label">Widget</td>
            <td class="data-value">{{ $widget->name }}</td>
        </tr>
        <tr class="data-row">
            <td class="data-label">Messages in thread</td>
            <td class="data-value">{{ $conversation->message_count ?? 0 }}</td>
        </tr>
        <tr class="data-row">
            <td class="data-label">Requested at</td>
            <td class="data-value">{{ now()->format('M d, Y g:i A') }}</td>
        </tr>
    </table>

    @if($lastMessage ?? null)
    <hr>
    <p><strong>Last message from visitor:</strong></p>
    <div class="alert alert-info" style="font-size:14px;">
        "{{ $lastMessage->content }}"
    </div>
    @endif

    <div class="btn-wrap">
        <a href="{{ config('app.url') }}/conversations/{{ $conversation->id }}" class="btn">Join Conversation →</a>
    </div>

    <div class="alert alert-warning">
        <strong>Act fast!</strong> Visitors who get a quick response are significantly more likely to convert.
    </div>

    <p class="text-muted text-center">Manage handoff notifications in your <a href="{{ config('app.url') }}/widgets/{{ $widget->id }}/edit" class="link">widget settings</a>.</p>
</div>
@endsection
