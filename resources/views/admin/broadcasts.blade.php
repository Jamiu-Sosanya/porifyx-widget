@extends('layouts.admin')
@section('title', 'Admin: Broadcasts')
@section('page-title', 'Email Broadcasts')
@section('content')

<div class="max-w-5xl" x-data="{
    segment: 'all',
    subject: '',
    body: '',
    preview: false,
    counts: @js($userCount),
    get recipientCount() { return this.counts[this.segment] ?? 0; }
}">

@if(session('success'))
<div class="mb-5 p-4 bg-emerald-500/10 border border-emerald-500/20 rounded-2xl text-sm text-emerald-400 flex items-center gap-2">
    <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd" /></svg>
    {{ session('success') }}
</div>
@endif
@if(session('error'))
<div class="mb-5 p-4 bg-rose-500/10 border border-rose-500/20 rounded-2xl text-sm text-rose-400">{{ session('error') }}</div>
@endif

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

    {{-- Compose form --}}
    <div class="xl:col-span-2">
        <div class="bg-gray-900/50 border border-white/5 rounded-2xl p-6">
            <h3 class="text-base font-semibold text-white mb-5">Compose Broadcast</h3>

            <div class="bg-amber-500/5 border border-amber-500/15 rounded-xl px-4 py-3 mb-5 flex items-start gap-3">
                <svg class="w-4 h-4 text-amber-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></svg>
                <p class="text-xs text-amber-300/80 leading-relaxed">Broadcasts are sent immediately to all matching active users. This action cannot be undone. Make sure your SMTP is configured and tested first.</p>
            </div>

            <form method="POST" action="{{ route('admin.broadcasts.send') }}" class="space-y-5"
                  onsubmit="return confirm('Send this broadcast to ' + document.getElementById('recipient-count').textContent + ' users?')">
                @csrf

                {{-- Segment --}}
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Audience Segment</label>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                        @foreach([
                            ['all',    'All Users',     'Send to every active user'],
                            ['paying', 'Paying',        'Active subscribers only'],
                            ['trial',  'Trial',         'Currently on free trial'],
                            ['free',   'Free / Lapsed', 'No active subscription'],
                        ] as [$val, $lbl, $desc])
                        <label class="relative cursor-pointer">
                            <input type="radio" name="segment" value="{{ $val }}" x-model="segment"
                                   class="sr-only peer" {{ old('segment', 'all') === $val ? 'checked' : '' }}>
                            <div class="p-3 bg-white/[0.03] border border-white/10 rounded-xl text-center peer-checked:border-brand-500/50 peer-checked:bg-brand-500/10 transition cursor-pointer">
                                <p class="text-sm font-semibold text-white peer-checked:text-brand-300">{{ $lbl }}</p>
                                <p class="text-[10px] text-gray-600 mt-0.5">{{ $desc }}</p>
                                <p class="text-xs font-bold text-brand-400 mt-1">{{ $userCount[$val] }} users</p>
                            </div>
                        </label>
                        @endforeach
                    </div>
                    @error('segment') <p class="text-xs text-rose-400 mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Subject --}}
                <div>
                    <label for="subject" class="block text-sm font-medium text-gray-300 mb-1.5">Subject Line</label>
                    <input type="text" id="subject" name="subject" x-model="subject" value="{{ old('subject') }}"
                           placeholder="Your subject line here…"
                           class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm text-white placeholder-gray-600 focus:outline-none focus:border-brand-500/50 transition">
                    @error('subject') <p class="text-xs text-rose-400 mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Body --}}
                <div>
                    <label for="body" class="block text-sm font-medium text-gray-300 mb-1.5">Message Body</label>
                    <textarea id="body" name="body" x-model="body" rows="12"
                              placeholder="Write your message here. Plain text only — no HTML tags."
                              class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm text-white placeholder-gray-600 focus:outline-none focus:border-brand-500/50 transition resize-none font-mono">{{ old('body') }}</textarea>
                    @error('body') <p class="text-xs text-rose-400 mt-1">{{ $message }}</p> @enderror
                    <p class="text-[10px] text-gray-600 mt-1">Plain text only. For HTML emails use the email template system.</p>
                </div>

                {{-- Preview toggle --}}
                <div x-show="subject && body" x-transition>
                    <button type="button" @click="preview = !preview"
                            class="text-xs text-brand-400 hover:text-brand-300 underline transition">
                        <span x-text="preview ? 'Hide preview' : 'Show email preview'"></span>
                    </button>
                    <div x-show="preview" x-transition class="mt-3 bg-black/30 border border-white/10 rounded-xl p-5 font-mono text-sm text-gray-300 whitespace-pre-wrap leading-relaxed max-h-60 overflow-y-auto">
                        <p class="text-xs text-gray-500 mb-2 font-sans">Subject: <strong class="text-gray-300" x-text="subject"></strong></p>
                        <hr class="border-white/10 mb-3">
                        <div x-text="body"></div>
                    </div>
                </div>

                {{-- Send button --}}
                <div class="flex items-center justify-between pt-2">
                    <p class="text-sm text-gray-500">
                        Sending to <strong id="recipient-count" class="text-white" x-text="recipientCount"></strong> users
                    </p>
                    <button type="submit"
                            :disabled="!subject || !body || recipientCount === 0"
                            class="px-6 py-2.5 bg-brand-600 hover:bg-brand-500 disabled:opacity-40 disabled:cursor-not-allowed text-white text-sm font-semibold rounded-xl transition flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" /></svg>
                        Send Broadcast
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Audience summary card --}}
    <div class="space-y-5">
        <div class="bg-gray-900/50 border border-white/5 rounded-2xl p-5">
            <h4 class="text-sm font-semibold text-white mb-4">Audience Overview</h4>
            <div class="space-y-3">
                @foreach([
                    ['All Active Users', $userCount['all'],    'brand'],
                    ['Paying Subs',      $userCount['paying'], 'emerald'],
                    ['On Trial',         $userCount['trial'],  'amber'],
                    ['Free / Lapsed',    $userCount['free'],   'gray'],
                ] as [$label, $count, $color])
                <div class="flex items-center justify-between py-2 border-b border-white/[0.04] last:border-0">
                    <span class="text-sm text-gray-400">{{ $label }}</span>
                    <span class="text-sm font-semibold text-white">{{ number_format($count) }}</span>
                </div>
                @endforeach
            </div>
        </div>

        <div class="bg-gray-900/50 border border-white/5 rounded-2xl p-5">
            <h4 class="text-sm font-semibold text-white mb-3">Tips</h4>
            <ul class="space-y-2 text-xs text-gray-500 leading-relaxed">
                <li class="flex gap-2"><span class="text-brand-400 shrink-0">→</span>Save your settings and test SMTP before sending.</li>
                <li class="flex gap-2"><span class="text-brand-400 shrink-0">→</span>Large sends may take several minutes to complete.</li>
                <li class="flex gap-2"><span class="text-brand-400 shrink-0">→</span>Keep subject lines under 50 characters for best deliverability.</li>
                <li class="flex gap-2"><span class="text-brand-400 shrink-0">→</span>Plain-text emails have higher open rates than HTML in some clients.</li>
            </ul>
        </div>
    </div>
</div>

{{-- Broadcast history --}}
@if($broadcasts->isNotEmpty())
<div class="mt-6 bg-gray-900/50 border border-white/5 rounded-2xl overflow-hidden">
    <div class="px-6 py-4 border-b border-white/5">
        <h3 class="text-sm font-semibold text-white">Broadcast History</h3>
    </div>
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-white/5">
                <th class="text-left px-6 py-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">Subject</th>
                <th class="text-center px-4 py-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">Segment</th>
                <th class="text-center px-4 py-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">Sent</th>
                <th class="text-left px-6 py-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">Date</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-white/[0.04]">
            @foreach($broadcasts as $b)
            <tr class="hover:bg-white/[0.02] transition">
                <td class="px-6 py-3.5 text-gray-300 font-medium">{{ $b->subject }}</td>
                <td class="px-4 py-3.5 text-center">
                    <span class="px-2.5 py-0.5 text-xs bg-brand-500/10 text-brand-400 rounded-full capitalize">{{ $b->segment }}</span>
                </td>
                <td class="px-4 py-3.5 text-center text-white font-semibold">{{ number_format($b->sent_count) }}</td>
                <td class="px-6 py-3.5 text-xs text-gray-500">{{ \Carbon\Carbon::parse($b->created_at)->format('M d, Y g:i A') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @if($broadcasts->hasPages())
    <div class="px-6 py-4 border-t border-white/5">{{ $broadcasts->links() }}</div>
    @endif
</div>
@endif

</div>
@endsection
