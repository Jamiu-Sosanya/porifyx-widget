@extends('layouts.app')
@section('title', 'New ticket')
@section('page-title', 'New ticket')
@section('content')
@php
    $field = 'w-full bg-white/5 border border-white/10 rounded-xl px-3.5 py-2.5 text-sm text-white';
    $v = $conversation?->visitor;
    $firstQ = $conversation?->messages()->where('role', 'visitor')->oldest('id')->value('content');
    $transcript = $conversation ? $conversation->messages()->whereIn('role', ['visitor', 'ai', 'agent'])->oldest('id')->take(30)->get()
        ->map(fn ($m) => ($m->role === 'visitor' ? 'Customer' : 'Assistant') . ': ' . $m->content)->implode("\n") : '';
@endphp
<form method="POST" action="{{ route('tickets.store') }}" class="max-w-2xl rounded-2xl border border-white/5 bg-gray-900/50 p-5 sm:p-6 space-y-3">
    @csrf
    <input type="hidden" name="conversation_uuid" value="{{ $conversation?->uuid }}">
    <input name="subject" required maxlength="200" value="{{ old('subject', $firstQ ? \Illuminate\Support\Str::limit($firstQ, 80) : '') }}" placeholder="Subject" class="{{ $field }}">
    <div class="grid sm:grid-cols-2 gap-3">
        <input name="requester_name" maxlength="120" value="{{ old('requester_name', $v?->name) }}" placeholder="Customer name" class="{{ $field }}">
        <input name="requester_email" type="email" required maxlength="190" value="{{ old('requester_email', $v?->email) }}" placeholder="Customer email *" class="{{ $field }}">
    </div>
    <textarea name="message" required rows="8" maxlength="10000" placeholder="What does the customer need?" class="{{ $field }}">{{ old('message', $transcript) }}</textarea>
    <div class="grid sm:grid-cols-2 gap-3">
        <select name="priority" class="{{ $field }}">@foreach(\App\Models\Ticket::PRIORITIES as $k => $l)<option value="{{ $k }}" @selected($k === 'normal')>{{ $l }}</option>@endforeach</select>
        @unless($conversation)
        <select name="widget_id" class="{{ $field }}"><option value="">No widget</option>@foreach($widgets as $w)<option value="{{ $w->id }}">{{ $w->name }}</option>@endforeach</select>
        @endunless
    </div>
    <label class="flex items-center gap-2 text-sm text-gray-300"><input type="checkbox" name="notify" value="1" checked> Email the customer a confirmation with a link to follow up</label>
    @if($errors->any())<p class="text-sm text-rose-400">{{ $errors->first() }}</p>@endif
    <button class="w-full sm:w-auto px-6 py-3 rounded-xl bg-brand-500 text-white text-sm font-semibold">Create ticket</button>
</form>
@endsection
