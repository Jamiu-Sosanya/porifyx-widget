@extends('layouts.app')
@section('title', $ticket->number . ' ' . $ticket->subject)
@section('page-title', $ticket->number)
@section('content')
@php $field = 'w-full bg-white/5 border border-white/10 rounded-xl px-3.5 py-2.5 text-sm text-white'; @endphp
<div class="grid lg:grid-cols-[minmax(0,1fr)_300px] gap-6">
    <div class="min-w-0 space-y-4">
        <div class="rounded-2xl border border-white/5 bg-gray-900/50 p-5">
            <h2 class="text-lg font-bold text-white break-words">{{ $ticket->subject }}</h2>
            <p class="mt-1 text-xs text-gray-500">{{ $ticket->requester_name ? $ticket->requester_name . ' · ' : '' }}{{ $ticket->requester_email }} · opened {{ $ticket->created_at->diffForHumans() }}
                @if($ticket->conversation) · <a href="{{ route('conversations.show', $ticket->conversation) }}" class="text-brand-400">view chat</a>@endif</p>
        </div>

        {{-- Timeline --}}
        <ol class="space-y-3">
            @foreach($ticket->messages as $m)
            @if($m->type === 'event')
            <li class="flex items-center gap-2 px-2 text-[11px] text-gray-500"><span class="w-1.5 h-1.5 shrink-0 rounded-full bg-gray-600"></span>{{ $m->body }} · {{ $m->user?->name ? $m->user->name . ' · ' : '' }}{{ $m->created_at->diffForHumans() }}</li>
            @else
            <li class="rounded-2xl border p-4 {{ $m->type === 'note' ? 'border-amber-500/20 bg-amber-500/5' : ($m->type === 'reply' ? 'border-brand-500/20 bg-brand-500/5 sm:ml-10' : 'border-white/5 bg-gray-900/50 sm:mr-10') }}">
                <p class="text-[11px] font-semibold {{ $m->type === 'note' ? 'text-amber-400' : 'text-gray-400' }}">
                    {{ $m->type === 'customer' ? ($ticket->requester_name ?: $ticket->requester_email) : ($m->user?->name ?? 'Team') }}
                    · {{ $m->type === 'note' ? 'Internal note' : ($m->type === 'reply' ? 'Reply' : 'Customer') }} · {{ $m->created_at->format('j M, H:i') }}
                </p>
                <div class="mt-2 text-sm text-gray-200 whitespace-pre-line break-words">{{ $m->body }}</div>
            </li>
            @endif
            @endforeach
        </ol>

        {{-- Composer --}}
        <form method="POST" action="{{ route('tickets.reply', $ticket) }}" x-data="{ type: 'reply' }" class="rounded-2xl border border-white/5 bg-gray-900/50 p-4 space-y-3">
            @csrf
            <div class="flex gap-1.5">
                <button type="button" @click="type = 'reply'" :class="type === 'reply' ? 'bg-brand-500 text-white' : 'bg-white/5 text-gray-300'" class="px-3 py-1.5 rounded-lg text-xs font-semibold">Reply to customer</button>
                <button type="button" @click="type = 'note'" :class="type === 'note' ? 'bg-amber-500 text-white' : 'bg-white/5 text-gray-300'" class="px-3 py-1.5 rounded-lg text-xs font-semibold">Internal note</button>
            </div>
            <input type="hidden" name="type" :value="type">
            <textarea name="body" required rows="5" maxlength="10000" :placeholder="type === 'reply' ? @js('Emailed to ' . $ticket->requester_email) : 'Only your team sees this'" class="{{ $field }}"></textarea>
            <div class="flex flex-wrap items-center gap-2">
                <select name="status" class="bg-white/5 border border-white/10 rounded-xl px-3 py-2 text-xs text-white">
                    <option value="">Status: keep default</option>
                    @foreach(\App\Models\Ticket::STATUSES as $k => $l)<option value="{{ $k }}">Set {{ $l }}</option>@endforeach
                </select>
                <button class="ml-auto px-5 py-2.5 rounded-xl bg-brand-500 text-white text-sm font-semibold" x-text="type === 'reply' ? 'Send reply' : 'Add note'">Send reply</button>
            </div>
            <p class="text-[11px] text-gray-500" x-show="type === 'reply'">After a reply the ticket waits on the customer unless you pick another status.</p>
        </form>
    </div>

    <aside class="space-y-4">
        <form method="POST" action="{{ route('tickets.update', $ticket) }}" class="rounded-2xl border border-white/5 bg-gray-900/50 p-5 space-y-3">
            @csrf @method('PUT')
            <label class="block text-xs text-gray-400">Status
                <select name="status" class="mt-1 {{ $field }}">@foreach(\App\Models\Ticket::STATUSES as $k => $l)<option value="{{ $k }}" @selected($ticket->status === $k)>{{ $l }}</option>@endforeach</select></label>
            <label class="block text-xs text-gray-400">Priority
                <select name="priority" class="mt-1 {{ $field }}">@foreach(\App\Models\Ticket::PRIORITIES as $k => $l)<option value="{{ $k }}" @selected($ticket->priority === $k)>{{ $l }}</option>@endforeach</select></label>
            <label class="block text-xs text-gray-400">Assignee
                <select name="assigned_to" class="mt-1 {{ $field }}"><option value="">Unassigned</option>@foreach($agents as $a)<option value="{{ $a->id }}" @selected($ticket->assigned_to === $a->id)>{{ $a->name }}</option>@endforeach</select></label>
            <button class="w-full py-2.5 rounded-xl bg-white/5 hover:bg-white/10 text-sm font-semibold text-white">Update</button>
            @if($ticket->escalated_at)<p class="text-xs text-rose-400">Escalated {{ $ticket->escalated_at->diffForHumans() }}</p>@endif
        </form>
        <form method="POST" action="{{ route('tickets.merge', $ticket) }}" class="rounded-2xl border border-white/5 bg-gray-900/50 p-5 space-y-2" onsubmit="return confirm('Merge this ticket into the other one? This cannot be undone.')">
            @csrf
            <p class="text-sm font-semibold text-white">Merge duplicate</p>
            <p class="text-xs text-gray-500">Move this ticket's messages into another ticket and close this one.</p>
            <div class="flex gap-2"><input name="into" required placeholder="TK-1004" class="{{ $field }}"><button class="px-3 rounded-xl bg-white/5 hover:bg-white/10 text-xs font-semibold text-white">Merge</button></div>
            @error('into')<p class="text-xs text-rose-400">{{ $message }}</p>@enderror
        </form>
        <div class="rounded-2xl border border-white/5 bg-gray-900/50 p-5">
            <p class="text-xs text-gray-500">Customer link (read and reply without an account)</p>
            <input readonly value="{{ $ticket->publicUrl() }}" onclick="this.select()" class="mt-2 w-full bg-white/5 border border-white/10 rounded-lg px-2.5 py-1.5 text-[11px] text-gray-300">
        </div>
    </aside>
</div>
@endsection
