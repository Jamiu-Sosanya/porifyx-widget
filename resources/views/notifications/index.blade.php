@extends('layouts.app')
@section('title', 'Notifications')
@section('page-title', 'Notifications')
@section('content')
<div class="max-w-4xl">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-white">Notifications</h1>
        @if(auth()->user()->unreadNotifications->count() > 0)
        <form method="POST" action="{{ route('notifications.mark-all-read') }}">
            @csrf
            <button class="px-4 py-2 bg-brand-500/10 hover:bg-brand-500/20 text-brand-400 text-sm font-medium rounded-xl transition">
                Mark All as Read
            </button>
        </form>
        @endif
    </div>

    @if($notifications->count() > 0)
        <div class="space-y-4">
            @foreach($notifications as $notification)
            <div class="bg-gray-900/50 border border-white/5 rounded-2xl p-6 {{ $notification->read_at ? 'opacity-75' : '' }}">
                @php $alertType = $notification->data['alert_type'] ?? ''; @endphp
                <div class="flex items-start gap-4">
                    <div class="flex-shrink-0 text-2xl">
                        @if($alertType === 'conversation_volume_drop')
                            📉
                        @elseif($alertType === 'conversation_volume_spike')
                            📈
                        @elseif($alertType === 'lead_quality_drop')
                            📊
                        @elseif($alertType === 'hot_lead_drop')
                            🔥
                        @elseif($alertType === 'ab_test_significant_difference')
                            🧪
                        @else
                            🔔
                        @endif
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <h3 class="text-lg font-semibold text-white mb-2">
                                    @if($alertType === 'conversation_volume_drop')
                                        Conversation Volume Alert
                                    @elseif($alertType === 'conversation_volume_spike')
                                        Conversation Volume Spike
                                    @elseif($alertType === 'lead_quality_drop')
                                        Lead Quality Alert
                                    @elseif($alertType === 'hot_lead_drop')
                                        Hot Lead Generation Alert
                                    @elseif($alertType === 'ab_test_significant_difference')
                                        A/B Test Results
                                    @else
                                        System Alert
                                    @endif
                                </h3>
                                <p class="text-gray-300 leading-relaxed mb-3">{{ $notification->data['message'] ?? 'An unusual system event has been detected.' }}</p>
                                @if(isset($notification->data['action']))
                                <a href="{{ $notification->data['action']['url'] }}" class="inline-flex items-center gap-2 px-3 py-1.5 bg-brand-500/10 hover:bg-brand-500/20 text-brand-400 text-sm font-medium rounded-lg transition">
                                    {{ $notification->data['action']['text'] }}
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" /></svg>
                                </a>
                                @endif
                            </div>
                            <div class="flex items-center gap-2 flex-shrink-0">
                                @if(!$notification->read_at)
                                <form method="POST" action="{{ route('notifications.mark-read', $notification) }}" class="inline">
                                    @csrf
                                    <button class="text-gray-500 hover:text-white transition" title="Mark as read">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                                    </button>
                                </form>
                                @endif
                                <time class="text-xs text-gray-500" title="{{ $notification->created_at->format('F j, Y \a\t g:i A') }}">
                                    {{ $notification->created_at->diffForHumans() }}
                                </time>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        <div class="mt-8">
            {{ $notifications->links() }}
        </div>
    @else
        <div class="bg-gray-900/50 border border-white/5 rounded-2xl p-12 text-center">
            <svg class="w-16 h-16 mx-auto mb-4 text-gray-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" /></svg>
            <h3 class="text-lg font-semibold text-white mb-2">No notifications</h3>
            <p class="text-gray-400">You're all caught up! System alerts will appear here when there are important updates or anomalies to review.</p>
        </div>
    @endif
</div>
@endsection