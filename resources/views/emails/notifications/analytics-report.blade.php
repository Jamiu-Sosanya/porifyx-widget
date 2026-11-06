@extends('emails.layouts.base')
@section('subject', 'Your analytics report')
@section('content')
@php
    $k = $report['kpis'];
    $labels = ['conversations' => 'Conversations', 'engaged' => 'Engaged chats', 'leads' => 'Leads', 'hot_leads' => 'Hot leads', 'bookings' => 'Bookings', 'handoffs' => 'Human handoffs', 'tickets' => 'Tickets', 'messages' => 'Messages'];
@endphp
<div class="card-hero">
    <h1>{{ \App\Support\AnalyticsReport::PERIODS[$report['days']] ?? $report['days'] . ' days' }}</h1>
    <p class="lead">{{ $report['from']->format('j M') }} – {{ $report['to']->format('j M Y') }} · {{ $owner->company_name ?: $owner->name }}</p>
</div>
<div class="card-body">
    <table class="data-table">
        @foreach($labels as $key => $label)
        <tr class="data-row">
            <td class="data-label">{{ $label }}</td>
            <td class="data-value"><strong>{{ number_format($k[$key]['value']) }}</strong>
                <span style="color:{{ $k[$key]['change'] >= 0 ? '#059669' : '#dc2626' }};font-size:12px;">{{ $k[$key]['change'] >= 0 ? '▲' : '▼' }} {{ abs($k[$key]['change']) }}%</span></td>
        </tr>
        @endforeach
        <tr class="data-row"><td class="data-label">Lead conversion</td><td class="data-value"><strong>{{ $report['conversion'] }}%</strong></td></tr>
        <tr class="data-row"><td class="data-label">Answered by AI</td><td class="data-value"><strong>{{ $report['answered'] }}%</strong></td></tr>
    </table>
    @if($report['gaps']->isNotEmpty())
    <hr>
    <p><strong>Questions the assistant could not answer</strong></p>
    <ul>@foreach($report['gaps'] as $g)<li>{{ $g->question }} ({{ $g->times_asked }}×)</li>@endforeach</ul>
    @endif
    @if($report['top_pages']->isNotEmpty())
    <hr>
    <p><strong>Top pages</strong></p>
    <ul>@foreach($report['top_pages']->take(5) as $p)<li>{{ \Illuminate\Support\Str::limit($p->url, 70) }} ({{ $p->c }})</li>@endforeach</ul>
    @endif
    <p style="text-align:center;margin-top:20px;"><a class="btn" href="{{ route('analytics.index') }}">Open analytics</a></p>
    <p style="font-size:12px;color:#94a3b8;text-align:center;">Change or stop these emails in Analytics → Email reports.</p>
</div>
@endsection
