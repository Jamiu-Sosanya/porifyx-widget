@extends('emails.layouts.base')
@section('subject', '🔥 New lead captured: ' . ($lead->name ?? $lead->email ?? 'Unknown'))
@section('content')
<div class="card-hero">
    <p style="font-size:36px;margin:0 0 12px;">
        @if(($lead->temperature ?? 'cold') === 'hot') 🔥
        @elseif(($lead->temperature ?? 'cold') === 'warm') ☀️
        @else ❄️
        @endif
    </p>
    <h1>New lead captured!</h1>
    <p class="lead">A visitor just became a qualified lead on <strong>{{ $widget->name }}</strong>.</p>
</div>

<div class="card-body">
    <table class="data-table">
        <tr class="data-row">
            <td class="data-label">Name</td>
            <td class="data-value">{{ $lead->name ?? '—' }}</td>
        </tr>
        <tr class="data-row">
            <td class="data-label">Email</td>
            <td class="data-value">{{ $lead->email ?? '—' }}</td>
        </tr>
        @if($lead->phone)
        <tr class="data-row">
            <td class="data-label">Phone</td>
            <td class="data-value">{{ $lead->phone }}</td>
        </tr>
        @endif
        @if($lead->company)
        <tr class="data-row">
            <td class="data-label">Company</td>
            <td class="data-value">{{ $lead->company }}</td>
        </tr>
        @endif
        @if($lead->service_interest)
        <tr class="data-row">
            <td class="data-label">Interest</td>
            <td class="data-value">{{ $lead->service_interest }}</td>
        </tr>
        @endif
        @if($lead->budget_range)
        <tr class="data-row">
            <td class="data-label">Budget</td>
            <td class="data-value">{{ $lead->budget_range }}</td>
        </tr>
        @endif
        <tr class="data-row">
            <td class="data-label">Lead score</td>
            <td class="data-value">
                <span class="badge badge-{{ $lead->temperature ?? 'cold' }}">
                    {{ $lead->score }}/100 · {{ ucfirst($lead->temperature ?? 'cold') }}
                </span>
            </td>
        </tr>
        <tr class="data-row">
            <td class="data-label">Widget</td>
            <td class="data-value">{{ $widget->name }}</td>
        </tr>
        <tr class="data-row">
            <td class="data-label">Captured</td>
            <td class="data-value">{{ now()->format('M d, Y g:i A') }}</td>
        </tr>
    </table>

    @if(!empty($lead->notes) || $conversation ?? false)
    <hr>
    <p><strong>Conversation summary:</strong></p>
    <div class="alert alert-info" style="font-size:14px;">
        {{ $lead->notes ?? ($conversation->lastMessage?->content ?? 'No summary available.') }}
    </div>
    @endif

    <div class="btn-wrap">
        <a href="{{ config('app.url') }}/leads/{{ $lead->id }}" class="btn">View Lead Details →</a>
    </div>

    <p class="text-muted text-center">You're receiving this because you enabled lead notifications for this widget. <a href="{{ config('app.url') }}/widgets/{{ $widget->id }}/edit" class="link">Manage notification settings</a>.</p>
</div>
@endsection
