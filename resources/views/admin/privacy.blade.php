@extends('layouts.admin')
@section('title', 'Privacy & GDPR')
@section('page-title', 'Privacy & GDPR')
@section('content')
@php $field = 'w-full bg-white/5 border border-white/10 rounded-xl px-3.5 py-2.5 text-sm text-white'; @endphp
<form method="POST" action="{{ route('admin.privacy.save') }}" x-data="{ tab: 'overview' }" class="grid xl:grid-cols-[minmax(0,1fr)_340px] gap-6">
    @csrf @method('PUT')
    <div class="min-w-0 rounded-2xl border border-white/5 bg-gray-900/50 p-5 sm:p-6">
        <div class="flex flex-wrap items-center gap-3">
            <span class="grid place-items-center w-10 h-10 rounded-xl bg-white/5">🛡️</span>
            <div class="min-w-0 flex-1">
                <h3 class="text-base font-semibold text-white flex items-center gap-2">Privacy policy &amp; GDPR
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $p['enabled'] ? 'bg-emerald-500/15 text-emerald-400' : 'bg-white/5 text-gray-400' }}">{{ $p['enabled'] ? 'CUSTOM POLICY LIVE' : 'USING BUILT-IN POLICY' }}</span></h3>
                <p class="text-xs text-gray-500">Edit the public privacy page, the contact for data requests, and how long visitor data is kept.</p>
            </div>
            <a href="{{ url('/privacy') }}" target="_blank" class="text-xs font-semibold text-brand-400">View page ↗</a>
        </div>

        <div class="mt-5 grid sm:grid-cols-4 gap-2">
            @foreach(['overview' => ['Overview', 'Headline, date, contact'], 'collection' => ['Collection & use', 'What you collect and why'], 'rights' => ['Rights & GDPR', 'Visitor rights, requests'], 'retention' => ['Data retention', 'Automatic deletion']] as $k => [$l, $d])
            <button type="button" @click="tab = '{{ $k }}'" :class="tab === '{{ $k }}' ? 'border-white/40 bg-white/[0.06]' : 'border-white/5 bg-white/[0.02]'" class="text-left rounded-xl border p-3">
                <span class="block text-sm font-semibold text-white">{{ $l }}</span><span class="block text-[11px] text-gray-500">{{ $d }}</span>
            </button>
            @endforeach
        </div>

        <div class="mt-5 space-y-3">
            <div x-show="tab === 'overview'" class="space-y-3">
                <label class="flex items-center gap-2 text-sm text-gray-200"><input type="checkbox" name="enabled" value="1" @checked($p['enabled'])> Use this custom policy on the public privacy page</label>
                <div class="grid sm:grid-cols-2 gap-3">
                    <label class="text-xs text-gray-400">Eyebrow<input name="eyebrow" value="{{ $p['eyebrow'] }}" class="mt-1 {{ $field }}"></label>
                    <label class="text-xs text-gray-400">Effective date<input name="effective_date" value="{{ $p['effective_date'] }}" placeholder="7 May 2026" class="mt-1 {{ $field }}"></label>
                </div>
                <label class="block text-xs text-gray-400">Page title<input name="title" required value="{{ $p['title'] }}" class="mt-1 {{ $field }}"></label>
                <label class="block text-xs text-gray-400">Summary (Markdown)<textarea name="summary" rows="8" class="mt-1 {{ $field }} font-mono text-[13px]">{{ $p['summary'] }}</textarea></label>
                <div class="rounded-xl border border-white/5 p-4 space-y-3">
                    <p class="text-sm font-semibold text-white">Privacy contact</p>
                    <div class="grid sm:grid-cols-2 gap-3">
                        <label class="text-xs text-gray-400">Team name<input name="contact_team" value="{{ $p['contact_team'] }}" class="mt-1 {{ $field }}"></label>
                        <label class="text-xs text-gray-400">Email<input type="email" name="contact_email" value="{{ $p['contact_email'] }}" placeholder="privacy@yourcompany.com" class="mt-1 {{ $field }}"></label>
                    </div>
                    <label class="block text-xs text-gray-400">Response promise<textarea name="response_promise" rows="2" class="mt-1 {{ $field }}">{{ $p['response_promise'] }}</textarea></label>
                </div>
            </div>
            <label x-show="tab === 'collection'" x-cloak class="block text-xs text-gray-400">What you collect, how you use it, what stays on record (Markdown)
                <textarea name="collection" rows="16" class="mt-1 {{ $field }} font-mono text-[13px]">{{ $p['collection'] }}</textarea></label>
            <div x-show="tab === 'rights'" x-cloak class="space-y-3">
                <label class="block text-xs text-gray-400">Visitor rights and how to make a request (Markdown)
                    <textarea name="rights" rows="14" class="mt-1 {{ $field }} font-mono text-[13px]">{{ $p['rights'] }}</textarea></label>
                <label class="flex items-center gap-2 text-sm text-gray-200"><input type="checkbox" name="request_form" value="1" @checked($p['request_form'])> Show a “Make a privacy request” form on the policy page</label>
                <p class="text-xs text-gray-500">Businesses can export or erase any visitor's data from a conversation (Privacy request panel). Requests sent through the form are emailed to the contact above and logged here.</p>
            </div>
            <div x-show="tab === 'retention'" x-cloak class="space-y-3">
                <label class="block text-xs text-gray-400">Delete visitor conversations older than (days, 0 = keep forever)
                    <input type="number" name="retention_days" min="0" max="3650" value="{{ $p['retention_days'] }}" class="mt-1 w-40 {{ $field }}"></label>
                <p class="text-xs text-gray-500">Runs daily. Deletes old chat messages and empty visitor records across the platform; leads, tickets and bookings are kept. Tell customers this period in your policy.</p>
            </div>
        </div>
        <button class="mt-5 px-6 py-3 rounded-xl bg-brand-500 text-white text-sm font-semibold">Save</button>
    </div>

    <aside class="space-y-4">
        <div class="rounded-2xl border border-white/5 bg-gray-900/50 p-5">
            <p class="text-sm font-semibold text-white">Recent privacy requests</p>
            <ul class="mt-3 space-y-2 text-xs">
                @forelse($requests as $r)
                @php $pr = json_decode($r->properties, true) ?: []; @endphp
                <li class="rounded-lg bg-white/[0.03] p-2.5"><span class="text-gray-200">{{ $pr['type'] ?? 'request' }}</span> · <span class="text-gray-400">{{ $pr['email'] ?? '' }}</span><span class="block text-gray-500">{{ \Carbon\Carbon::parse($r->created_at)->diffForHumans() }}</span></li>
                @empty
                <li class="text-gray-500">None yet.</li>
                @endforelse
            </ul>
        </div>
    </aside>
</form>
@endsection
