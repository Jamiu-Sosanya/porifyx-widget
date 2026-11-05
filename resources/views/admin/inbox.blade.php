@extends('layouts.admin')
@section('title', 'Admin: Inbox')
@section('page-title', 'Inbox')
@section('content')
<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-gray-400">Messages sent from the public contact and sales form. <span class="text-white font-semibold">{{ $open }}</span> still need a reply.</p>
        <div class="flex flex-wrap gap-2 text-xs font-semibold">
            <a href="{{ route('admin.inbox') }}" class="px-3 py-1.5 rounded-lg {{ request('topic') ? 'bg-white/5 text-gray-400' : 'bg-brand-500/20 text-brand-300' }}">All</a>
            @foreach(\App\Models\ContactRequest::TOPICS as $key => $label)
                <a href="{{ route('admin.inbox', ['topic' => $key]) }}" class="px-3 py-1.5 rounded-lg {{ request('topic') === $key ? 'bg-brand-500/20 text-brand-300' : 'bg-white/5 text-gray-400' }}">{{ $label }}</a>
            @endforeach
        </div>
    </div>

    @forelse($requests as $r)
    <article class="bg-gray-900/50 border border-white/5 rounded-2xl p-5 {{ $r->handled_at ? 'opacity-60' : '' }}">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="min-w-0">
                <p class="font-semibold text-white">{{ $r->name }} @if($r->company)<span class="text-gray-400 font-normal">· {{ $r->company }}</span>@endif</p>
                <a href="mailto:{{ $r->email }}" class="text-sm text-brand-400 break-all">{{ $r->email }}</a>
            </div>
            <div class="flex items-center gap-2 text-xs">
                <span class="px-2 py-1 rounded-md bg-white/5 text-gray-300">{{ \App\Models\ContactRequest::TOPICS[$r->topic] ?? $r->topic }}</span>
                <span class="text-gray-500">{{ $r->created_at->diffForHumans() }}</span>
            </div>
        </div>
        @php $meta = array_filter(['Website' => $r->website, 'Team' => $r->team_size, 'Monthly chats' => $r->volume]); @endphp
        @if($meta)
        <p class="mt-3 text-xs text-gray-500">@foreach($meta as $k => $v){{ $k }}: <span class="text-gray-300">{{ $v }}</span>@if(! $loop->last) · @endif @endforeach</p>
        @endif
        <p class="mt-3 text-sm text-gray-300 whitespace-pre-line">{{ $r->message }}</p>
        <div class="mt-4 flex gap-2">
            <a href="mailto:{{ $r->email }}?subject={{ rawurlencode('Re: your message to ' . config('branding.name')) }}" class="px-3 py-1.5 rounded-lg bg-brand-500 text-white text-xs font-semibold">Reply by email</a>
            <form method="POST" action="{{ route('admin.inbox.toggle', $r) }}">@csrf @method('PATCH')
                <button class="px-3 py-1.5 rounded-lg bg-white/5 text-gray-300 text-xs font-semibold">{{ $r->handled_at ? 'Mark as open' : 'Mark as handled' }}</button>
            </form>
        </div>
    </article>
    @empty
    <p class="text-center text-gray-500 py-16">No messages yet.</p>
    @endforelse

    {{ $requests->links() }}
</div>
@endsection
