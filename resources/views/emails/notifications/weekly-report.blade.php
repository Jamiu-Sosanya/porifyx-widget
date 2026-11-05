@extends('emails.layouts.base')
@section('subject', 'Your weekly report — ' . now()->subWeek()->format('M d') . ' to ' . now()->format('M d, Y'))
@section('content')
<div class="card-hero">
    <p style="font-size:36px;margin:0 0 12px;">📊</p>
    <h1>Your weekly report</h1>
    <p class="lead">{{ now()->subWeek()->format('M d') }} – {{ now()->format('M d, Y') }} · {{ config('branding.name') }}</p>
</div>

<div class="card-body">
    <p>Hi {{ $user->name }},</p>
    <p>Here's a summary of how your AI widgets performed this week.</p>

    {{-- Stat grid --}}
    <div class="stat-grid">
        <div class="stat-box">
            <span class="stat-value">{{ number_format($stats['conversations'] ?? 0) }}</span>
            <span class="stat-label">Conversations</span>
        </div>
        <div class="stat-box">
            <span class="stat-value">{{ number_format($stats['leads'] ?? 0) }}</span>
            <span class="stat-label">Leads</span>
        </div>
        <div class="stat-box">
            <span class="stat-value">{{ number_format($stats['messages'] ?? 0) }}</span>
            <span class="stat-label">Messages</span>
        </div>
    </div>

    @if(isset($stats['conversations_change']))
    <div class="alert {{ ($stats['conversations_change'] ?? 0) >= 0 ? 'alert-success' : 'alert-warning' }}">
        Conversations are <strong>{{ abs($stats['conversations_change']) }}% {{ ($stats['conversations_change'] ?? 0) >= 0 ? 'up ↑' : 'down ↓' }}</strong> compared to last week.
    </div>
    @endif

    @if(!empty($topWidgets))
    <hr>
    <h2>Top performing widgets</h2>
    <table class="data-table">
        @foreach($topWidgets as $w)
        <tr class="data-row">
            <td class="data-label">{{ $w['name'] }}</td>
            <td class="data-value">{{ $w['conversations'] }} convos · {{ $w['leads'] }} leads</td>
        </tr>
        @endforeach
    </table>
    @endif

    @if(!empty($hotLeads))
    <hr>
    <h2>🔥 Hot leads this week</h2>
    @foreach(array_slice($hotLeads, 0, 3) as $lead)
    <div style="background:rgba(239,68,68,0.05);border:1px solid rgba(239,68,68,0.1);border-radius:10px;padding:12px 14px;margin-bottom:8px;">
        <p style="margin:0;font-size:14px;color:#f87171;font-weight:600;">{{ $lead['name'] ?? $lead['email'] ?? 'Unknown' }}</p>
        <p style="margin:4px 0 0;font-size:12px;color:#6b7280;">Score: {{ $lead['score'] }}/100 {{ $lead['service_interest'] ? '· ' . $lead['service_interest'] : '' }}</p>
    </div>
    @endforeach
    @endif

    <div class="btn-wrap">
        <a href="{{ config('app.url') }}/dashboard" class="btn">View Full Dashboard →</a>
    </div>

    <p class="text-muted text-center">Manage email preferences in your <a href="{{ config('app.url') }}/settings/notifications" class="link">notification settings</a>.</p>
</div>
@endsection
