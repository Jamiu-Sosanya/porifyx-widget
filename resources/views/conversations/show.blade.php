@extends('layouts.app')
@section('title', 'Conversation')
@section('page-title', 'Conversation')
@section('content')
@if(session('success'))
<div class="mb-3 px-4 py-2.5 bg-emerald-500/10 border border-emerald-500/20 rounded-xl text-sm text-emerald-400 flex items-center gap-2">
    <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd" /></svg>
    {{ session('success') }}
</div>
@endif
<div class="flex gap-5 h-[calc(100dvh-200px)] lg:h-[calc(100dvh-230px)] min-h-[460px]" x-data="{ details: false }">
    {{-- Chat Thread --}}
    <div class="flex-1 flex flex-col bg-gray-900/50 border border-white/5 rounded-2xl overflow-hidden min-w-0">
        {{-- Header --}}
        @php
            $who = $conversation->visitor?->name ?? $conversation->visitor?->email ?? 'Visitor';
            $statusTone = ['resolved' => 'bg-emerald-500/15 text-emerald-400', 'human_takeover' => 'bg-amber-500/15 text-amber-400', 'waiting' => 'bg-amber-500/15 text-amber-400'][$conversation->status] ?? 'bg-brand-500/15 text-brand-400';
        @endphp
        <div class="flex flex-wrap items-center gap-x-3 gap-y-2 px-3 sm:px-4 py-3 border-b border-white/5 shrink-0">
            <div class="flex items-center gap-2.5 min-w-0 w-full sm:w-auto sm:flex-1">
                <a href="{{ route('conversations.index') }}" class="p-1.5 -ml-1 rounded-lg text-gray-400 hover:text-white hover:bg-white/5 transition shrink-0" aria-label="Back to conversations">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg>
                </a>
                <span class="grid place-items-center w-9 h-9 shrink-0 rounded-full bg-gradient-to-br from-brand-500 to-accent-500 text-white text-sm font-bold">{{ mb_strtoupper(mb_substr($who, 0, 1)) }}</span>
                <div class="min-w-0">
                    <div class="flex items-center gap-2 min-w-0">
                        <p class="text-sm font-semibold text-white truncate">{{ $who }}</p>
                        @if($conversation->detected_language)
                        <span class="px-1.5 py-0.5 bg-sky-500/10 text-sky-400 text-[10px] font-medium rounded uppercase">{{ $conversation->detected_language }}</span>
                        @endif
                    </div>
                    <p class="flex items-center gap-1.5 text-xs text-gray-500 min-w-0">
                        <span class="truncate">{{ $conversation->widget?->name }}</span>
                        <span class="shrink-0 px-1.5 py-0.5 rounded-full text-[10px] font-semibold {{ $statusTone }}">{{ ucfirst(str_replace('_', ' ', $conversation->status)) }}</span>
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-1 sm:gap-1.5 shrink-0 ml-auto">
                <button type="button" @click="details = true" class="lg:hidden px-2.5 py-1.5 rounded-lg text-xs font-semibold text-gray-300 bg-white/5 hover:bg-white/10" title="Visitor details">Details</button>
                {{-- Labels --}}
                <div x-data="labelManager()" class="flex items-center gap-1.5">
                    @if(!empty($conversation->labels))
                    @foreach($conversation->labels as $lbl)
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-brand-500/10 text-brand-400 text-[10px] font-medium rounded-full border border-brand-500/20">
                        {{ $lbl }}
                        <button @click="removeLabel('{{ $lbl }}')" class="hover:text-rose-400 transition">×</button>
                    </span>
                    @endforeach
                    @endif
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" class="p-1.5 rounded-lg text-gray-500 hover:text-white hover:bg-white/5 transition" title="Add label">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6Z" /></svg>
                        </button>
                        <div x-show="open" @click.away="open = false" x-transition class="absolute right-0 top-8 z-20 w-48 bg-gray-800 border border-white/10 rounded-xl shadow-2xl p-2">
                            @foreach(['support','sales','feedback','bug','feature','urgent','follow-up','vip'] as $preset)
                            <button @click="addLabel('{{ $preset }}'); open=false" class="w-full text-left px-3 py-1.5 text-xs text-gray-300 hover:bg-white/5 hover:text-white rounded-lg transition">{{ $preset }}</button>
                            @endforeach
                            <div class="border-t border-white/5 mt-1 pt-1">
                                <input x-model="customLabel" @keydown.enter.prevent="addLabel(customLabel); customLabel=''; open=false" placeholder="Custom label…" class="w-full bg-white/5 text-xs text-white placeholder-gray-600 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-brand-500/50">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Agent Assist --}}
                <button id="agentAssistBtn" onclick="requestAgentAssist()" class="flex items-center gap-1.5 px-3 py-1.5 bg-brand-500/10 hover:bg-brand-500/20 text-brand-400 text-xs font-medium rounded-lg transition" title="AI suggests a reply">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z" /></svg>
                    <span class="hidden sm:inline">AI Assist</span>
                </button>

                <form method="POST" action="{{ route('conversations.star', $conversation) }}">@csrf
                    <button class="p-2 rounded-lg hover:bg-white/5 transition {{ $conversation->is_starred ? 'text-amber-400' : 'text-gray-500' }}" title="{{ $conversation->is_starred ? 'Unstar' : 'Star' }}">
                        <svg class="w-4 h-4" fill="{{ $conversation->is_starred ? 'currentColor' : 'none' }}" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z" /></svg>
                    </button>
                </form>
                @if($conversation->status !== 'resolved')
                <form method="POST" action="{{ route('conversations.resolve', $conversation) }}">@csrf
                    <button class="px-2.5 sm:px-3 py-1.5 bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-400 text-xs font-semibold rounded-lg transition" title="Mark as resolved">✓<span class="hidden sm:inline"> Resolve</span></button>
                </form>
                @endif
            </div>
        </div>

        {{-- Agent Assist Banner --}}
        <div id="agentAssistBanner" class="hidden px-4 py-3 bg-brand-500/10 border-b border-brand-500/20 shrink-0">
            <div class="flex items-start gap-3">
                <div class="w-6 h-6 bg-brand-500/20 rounded-lg flex items-center justify-center shrink-0 mt-0.5">
                    <svg class="w-3.5 h-3.5 text-brand-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z" /></svg>
                </div>
                <div class="flex-1">
                    <p class="text-xs font-medium text-brand-300 mb-1">AI Suggested Reply</p>
                    <p id="agentAssistText" class="text-sm text-gray-200 leading-relaxed"></p>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <button onclick="useAssistSuggestion()" class="px-3 py-1.5 bg-brand-500/20 hover:bg-brand-500/30 text-brand-300 text-xs font-medium rounded-lg transition">Use this</button>
                    <button onclick="document.getElementById('agentAssistBanner').classList.add('hidden')" class="p-1.5 text-gray-500 hover:text-gray-300 transition">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                    </button>
                </div>
            </div>
        </div>

        {{-- Messages --}}
        <div class="flex-1 overflow-y-auto p-4 space-y-3 custom-scrollbar" id="messages-container">
            @foreach($messages as $msg)
            @php
                $isVisitor = $msg->role === 'visitor';
                $sentimentEmoji = match(true) {
                    !$isVisitor || $msg->sentiment_score === null => null,
                    $msg->sentiment_score >= 0.5  => '😊',
                    $msg->sentiment_score >= 0.15 => '🙂',
                    $msg->sentiment_score >= -0.15 => '😐',
                    $msg->sentiment_score >= -0.5  => '😕',
                    default => '😠',
                };
                $sentimentColor = match(true) {
                    $msg->sentiment_score === null || !$isVisitor => null,
                    $msg->sentiment_score >= 0.15  => 'emerald',
                    $msg->sentiment_score >= -0.15 => 'amber',
                    default => 'rose',
                };
            @endphp
            <div class="flex {{ $isVisitor ? 'justify-end' : 'justify-start' }} group">
                <div class="max-w-[72%]">
                    {{-- Role label --}}
                    @if($msg->role === 'agent')
                    <p class="text-[10px] text-emerald-400 font-medium mb-1 ml-1">{{ $msg->agent?->name ?? 'Agent' }}</p>
                    @elseif($msg->role === 'ai')
                    <p class="text-[10px] text-brand-400 font-medium mb-1 ml-1">{{ $conversation->widget?->ai_name ?? 'AI Assistant' }}</p>
                    @elseif($msg->role === 'system')
                    <p class="text-[10px] text-amber-400 font-medium mb-1 ml-1">System</p>
                    @endif

                    <div class="{{ $isVisitor ? 'bg-brand-600 text-white' : ($msg->role === 'agent' ? 'bg-emerald-600/15 text-emerald-100 border border-emerald-500/20' : ($msg->role === 'system' ? 'bg-amber-500/10 text-amber-100 border border-amber-500/20' : 'bg-white/5 text-gray-200 border border-white/5')) }} rounded-2xl px-4 py-3 {{ $isVisitor ? 'rounded-br-md' : 'rounded-bl-md' }}">
                        <p class="text-sm leading-relaxed whitespace-pre-wrap">{{ $msg->content }}</p>
                        <div class="flex items-center gap-2 mt-1.5 {{ $isVisitor ? '' : '' }}">
                            <span class="text-[10px] opacity-40">{{ $msg->created_at->format('g:i A') }}</span>
                            @if($msg->is_fallback)<span class="text-[10px] text-amber-400">⚠ Fallback</span>@endif
                            @if($msg->confidence_score)<span class="text-[10px] opacity-30">{{ round($msg->confidence_score * 100) }}% conf</span>@endif
                            @if($sentimentEmoji)
                            <span class="text-[11px] opacity-70 ml-auto" title="Sentiment: {{ $msg->sentiment_label ?? 'N/A' }} ({{ $msg->sentiment_score }})">{{ $sentimentEmoji }}</span>
                            @endif
                            @if($msg->detected_language && $msg->detected_language !== 'en')
                            <span class="text-[9px] uppercase text-sky-400/60">{{ $msg->detected_language }}</span>
                            @endif
                        </div>
                    </div>

                    {{-- Sentiment bar for visitor messages --}}
                    @if($isVisitor && $msg->sentiment_score !== null)
                    <div class="flex items-center gap-1.5 mt-1 px-1 opacity-0 group-hover:opacity-100 transition-opacity">
                        <span class="text-[9px] text-gray-600">–1</span>
                        <div class="flex-1 h-1 bg-white/5 rounded-full overflow-hidden">
                            <div class="h-full bg-{{ $sentimentColor }}-500 rounded-full transition-all"
                                style="width: {{ (($msg->sentiment_score + 1) / 2) * 100 }}%"></div>
                        </div>
                        <span class="text-[9px] text-gray-600">+1</span>
                        <span class="text-[9px] text-{{ $sentimentColor }}-400 font-medium">{{ $msg->sentiment_score > 0 ? '+' : '' }}{{ $msg->sentiment_score }}</span>
                    </div>
                    @endif

                    {{-- Reactions --}}
                    @if(!empty($msg->reactions))
                    <div class="flex flex-wrap gap-1 mt-1.5 {{ $isVisitor ? 'justify-end' : 'justify-start' }}">
                        @foreach($msg->reactions as $emoji => $count)
                        <span class="inline-flex items-center gap-0.5 px-2 py-0.5 bg-white/5 rounded-full text-xs border border-white/5">{{ $emoji }} <span class="text-[10px] text-gray-400">{{ $count }}</span></span>
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>

        {{-- Reply Box --}}
        @if($conversation->status !== 'resolved' && $conversation->status !== 'spam')
        <div class="p-3 sm:p-4 border-t border-white/5 shrink-0">
            <form method="POST" action="{{ route('conversations.sendMessage', $conversation) }}" class="flex items-end gap-2 p-1.5 rounded-2xl bg-white/5 border border-white/10 focus-within:border-brand-500/50 focus-within:ring-2 focus-within:ring-brand-500/20 transition">
                @csrf
                <textarea id="replyTextarea" name="content" rows="2" required placeholder="Reply as agent…" title="Enter to send, Shift+Enter for a new line"
                    class="flex-1 min-w-0 bg-transparent border-0 px-3 py-2.5 text-sm text-white placeholder-gray-500 focus:outline-none focus:ring-0 resize-none max-h-40" oninput="this.style.height='auto';this.style.height=Math.min(this.scrollHeight,160)+'px'"
                    onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();this.form.submit()}"></textarea>
                <button type="submit" class="grid place-items-center w-11 h-11 sm:w-auto sm:px-5 bg-brand-600 hover:bg-brand-500 text-white text-sm font-semibold rounded-xl transition shrink-0" aria-label="Send"><svg class="w-5 h-5 sm:hidden" fill="currentColor" viewBox="0 0 24 24"><path d="M2.01 21 23 12 2.01 3 2 10l15 2-15 2z"/></svg><span class="hidden sm:inline">Send</span></button>
            </form>
        </div>
        @endif
    </div>

    {{-- Sidebar --}}
    <div x-show="details" x-transition.opacity x-cloak @click="details = false" class="lg:hidden fixed inset-0 z-40 bg-black/60 backdrop-blur-sm"></div>
    <div :class="details ? 'translate-x-0' : 'translate-x-full lg:translate-x-0'"
         class="fixed lg:static inset-y-0 right-0 z-50 w-[88vw] max-w-sm lg:w-80 lg:max-w-none flex flex-col shrink-0 gap-4 overflow-y-auto custom-scrollbar p-4 lg:p-0 lg:pb-1 bg-[#0d1a33] lg:bg-transparent transition-transform duration-300 pfx-details">
        <div class="lg:hidden flex items-center justify-between">
            <p class="text-sm font-semibold text-white">Visitor details</p>
            <button type="button" @click="details = false" class="p-1.5 rounded-lg text-gray-400 hover:text-white" aria-label="Close">✕</button>
        </div>
        {{-- Visitor Info --}}
        <div class="bg-gray-900/50 border border-white/5 rounded-2xl p-5 shrink-0">
            <h3 class="text-sm font-semibold text-white mb-4">Visitor Info</h3>
            <div class="space-y-2.5 text-sm">
                @if($conversation->visitor?->name)
                <div class="flex justify-between"><span class="text-gray-500 text-xs">Name</span><span class="text-white text-xs font-medium">{{ $conversation->visitor->name }}</span></div>
                @endif
                @if($conversation->visitor?->email)
                <div class="flex justify-between items-center"><span class="text-gray-500 text-xs">Email</span><span class="text-sky-400 text-xs">{{ $conversation->visitor->email }}</span></div>
                @endif
                @if($conversation->visitor?->phone)
                <div class="flex justify-between"><span class="text-gray-500 text-xs">Phone</span><span class="text-white text-xs">{{ $conversation->visitor->phone }}</span></div>
                @endif
                @if($conversation->visitor?->country)
                <div class="flex justify-between"><span class="text-gray-500 text-xs">Location</span><span class="text-white text-xs">{{ $conversation->visitor->city ? $conversation->visitor->city.', ' : '' }}{{ $conversation->visitor->country }}</span></div>
                @endif
                <div class="flex justify-between"><span class="text-gray-500 text-xs">Visits</span><span class="text-white text-xs">{{ $conversation->visitor?->visit_count ?? 1 }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500 text-xs">First seen</span><span class="text-white text-xs">{{ $conversation->visitor?->first_seen_at?->diffForHumans() ?? 'N/A' }}</span></div>
                @if($conversation->avg_sentiment !== null)
                @php $sc2 = $conversation->avg_sentiment >= 0.15 ? 'emerald' : ($conversation->avg_sentiment >= -0.15 ? 'amber' : 'rose'); @endphp
                <div class="flex justify-between items-center">
                    <span class="text-gray-500 text-xs">Sentiment</span>
                    <span class="text-xs font-medium text-{{ $sc2 }}-400">{{ $conversation->avg_sentiment > 0 ? '+' : '' }}{{ round($conversation->avg_sentiment, 2) }}</span>
                </div>
                @endif
                @if($conversation->total_tokens_used)
                <div class="flex justify-between"><span class="text-gray-500 text-xs">Tokens used</span><span class="text-white text-xs">{{ number_format($conversation->total_tokens_used) }}</span></div>
                @endif
            </div>
        </div>

        {{-- Session Timeline --}}
        @if(!empty($timeline))
        <div class="bg-gray-900/50 border border-white/5 rounded-2xl p-5 shrink-0">
            <h3 class="text-sm font-semibold text-white mb-4 flex items-center gap-2">
                <svg class="w-3.5 h-3.5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                Session Timeline
            </h3>
            <div class="relative pl-3.5">
                {{-- Vertical line --}}
                <div class="absolute left-0 top-1 bottom-1 w-px bg-white/5"></div>
                <div class="space-y-3">
                    @foreach($timeline as $event)
                    <div class="relative">
                        {{-- Dot on timeline --}}
                        <div class="absolute -left-5 top-1 w-2 h-2 rounded-full border border-{{ $event['color'] }}-500/50 bg-{{ $event['color'] }}-500/30 ring-2 ring-gray-950"></div>
                        <p class="text-xs font-medium text-gray-300 leading-tight">{{ $event['icon'] }} {{ $event['label'] }}</p>
                        @if($event['meta'])
                        <p class="text-[10px] text-gray-500 mt-0.5 truncate" title="{{ $event['meta'] }}">{{ $event['meta'] }}</p>
                        @endif
                        <p class="text-[10px] text-gray-600 mt-0.5">{{ $event['at']->format('M j, H:i') }}</p>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        {{-- Device & Context --}}
        @php $v = $conversation->visitor; @endphp
        @if($v && ($v->browser || $v->os || $v->device_type || $conversation->current_page_url || $conversation->referrer_url))
        <div class="bg-gray-900/50 border border-white/5 rounded-2xl p-5 shrink-0">
            <h3 class="text-sm font-semibold text-white mb-3 flex items-center gap-2">
                <svg class="w-3.5 h-3.5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 0 1-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0 1 15 18.257V17.25m6-12V15a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 15V5.25m18 0A2.25 2.25 0 0 0 18.75 3H5.25A2.25 2.25 0 0 0 3 5.25m18 0H3"/></svg>
                Device & Context
            </h3>
            <div class="space-y-2">
                @if($v->browser)
                <div class="flex justify-between">
                    <span class="text-gray-500 text-xs">Browser</span>
                    <span class="text-white text-xs">{{ $v->browser }}</span>
                </div>
                @endif
                @if($v->os)
                <div class="flex justify-between">
                    <span class="text-gray-500 text-xs">OS</span>
                    <span class="text-white text-xs">{{ $v->os }}</span>
                </div>
                @endif
                @if($v->device_type)
                <div class="flex justify-between">
                    <span class="text-gray-500 text-xs">Device</span>
                    <span class="text-white text-xs capitalize">{{ $v->device_type }}</span>
                </div>
                @endif
                @if($v->language)
                <div class="flex justify-between">
                    <span class="text-gray-500 text-xs">Language</span>
                    <span class="text-white text-xs">{{ $v->language }}</span>
                </div>
                @endif
                @if($conversation->current_page_url)
                <div>
                    <p class="text-gray-500 text-xs mb-0.5">Landing Page</p>
                    <p class="text-brand-400 text-xs truncate font-mono" title="{{ $conversation->current_page_url }}">
                        {{ parse_url($conversation->current_page_url, PHP_URL_PATH) ?: $conversation->current_page_url }}
                    </p>
                </div>
                @endif
                @if($conversation->referrer_url)
                <div>
                    <p class="text-gray-500 text-xs mb-0.5">Referrer</p>
                    <p class="text-gray-400 text-xs truncate font-mono" title="{{ $conversation->referrer_url }}">
                        {{ parse_url($conversation->referrer_url, PHP_URL_HOST) ?: $conversation->referrer_url }}
                    </p>
                </div>
                @endif
            </div>
        </div>
        @endif

        {{-- Prior Conversations --}}
        @if($priorConversations->isNotEmpty())
        <div class="bg-gray-900/50 border border-white/5 rounded-2xl p-5 shrink-0">
            <h3 class="text-sm font-semibold text-white mb-3">Prior Conversations</h3>
            <div class="space-y-2">
                @foreach($priorConversations as $prior)
                <a href="{{ route('conversations.show', $prior) }}"
                   class="flex items-center justify-between p-2 rounded-lg hover:bg-white/5 transition group">
                    <div class="min-w-0">
                        <p class="text-xs text-gray-300 group-hover:text-white truncate">{{ $prior->created_at->format('M j, Y') }}</p>
                        <p class="text-[10px] text-gray-600">{{ $prior->message_count }} messages</p>
                    </div>
                    <span class="text-[10px] shrink-0 ml-2 px-1.5 py-0.5 rounded-full
                        {{ match($prior->status) {
                            'resolved' => 'bg-emerald-500/10 text-emerald-400',
                            'spam' => 'bg-rose-500/10 text-rose-400',
                            default => 'bg-amber-500/10 text-amber-400'
                        } }}">
                        {{ ucfirst(str_replace('_', ' ', $prior->status)) }}
                    </span>
                </a>
                @endforeach
            </div>
        </div>
        @endif

        {{-- CSAT --}}
        @if($conversation->csat_score)
        <div class="bg-gray-900/50 border border-white/5 rounded-2xl p-5 shrink-0">
            <h3 class="text-sm font-semibold text-white mb-3">CSAT Rating</h3>
            <div class="flex items-center gap-0.5 text-amber-400 text-lg mb-2">
                @for($i = 1; $i <= 5; $i++)
                <span>{{ $i <= $conversation->csat_score ? '★' : '☆' }}</span>
                @endfor
                <span class="text-sm text-gray-400 ml-2 font-medium">{{ $conversation->csat_score }}/5</span>
            </div>
            @if($conversation->csat_comment)
            <p class="text-xs text-gray-400 leading-relaxed italic">"{{ $conversation->csat_comment }}"</p>
            @endif
            <p class="text-[10px] text-gray-600 mt-2">{{ $conversation->csat_submitted_at?->diffForHumans() }}</p>
        </div>
        @endif

        {{-- Lead --}}
        @if($conversation->lead)
        <div class="bg-gray-900/50 border border-white/5 rounded-2xl p-5 shrink-0">
            <h3 class="text-sm font-semibold text-white mb-3">Lead</h3>
            <div class="space-y-2 text-xs">
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-medium {{ $conversation->lead->temperature_color }}">
                        @if($conversation->lead->temperature === 'hot')🔥@elseif($conversation->lead->temperature === 'warm')☀️@else❄️@endif
                        {{ ucfirst($conversation->lead->temperature) }}
                    </span>
                    @if($conversation->lead->quality_grade)
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-medium {{ $conversation->lead->quality_grade_color }}">
                        Grade {{ $conversation->lead->quality_grade }}
                    </span>
                    @endif
                    <span class="text-gray-400">Score: <strong class="text-white">{{ $conversation->lead->score }}</strong></span>
                </div>
                @if($conversation->lead->engagement_score)
                <div class="flex justify-between">
                    <span class="text-gray-400">Engagement:</span>
                    <span class="text-white">{{ $conversation->lead->engagement_score }}/100</span>
                </div>
                @endif
                @if($conversation->lead->follow_up_priority)
                <div class="flex justify-between">
                    <span class="text-gray-400">Priority:</span>
                    <span class="text-white">{{ $conversation->lead->follow_up_priority }}/100</span>
                </div>
                @endif
                @if($conversation->lead->service_interest)<p class="text-gray-400">Interest: <span class="text-gray-200">{{ $conversation->lead->service_interest }}</span></p>@endif
                @if($conversation->lead->budget_range)<p class="text-gray-400">Budget: <span class="text-gray-200">{{ $conversation->lead->budget_range }}</span></p>@endif
                @if($conversation->lead->company)<p class="text-gray-400">Company: <span class="text-gray-200">{{ $conversation->lead->company }}</span></p>@endif
                @if($conversation->lead->industry)<p class="text-gray-400">Industry: <span class="text-gray-200">{{ $conversation->lead->industry }}</span></p>@endif
                @if($conversation->lead->company_size)<p class="text-gray-400">Company Size: <span class="text-gray-200">{{ ucfirst($conversation->lead->company_size) }}</span></p>@endif
                <a href="{{ route('leads.show', $conversation->lead) }}" class="text-brand-400 hover:text-brand-300 transition inline-block mt-1">View lead details →</a>
            </div>
        </div>
        @endif

        {{-- AI Summary --}}
        @if($conversation->summary)
        <div class="bg-gray-900/50 border border-white/5 rounded-2xl p-5 shrink-0">
            <h3 class="text-sm font-semibold text-white mb-3 flex items-center gap-2">
                <svg class="w-3.5 h-3.5 text-brand-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3.104v5.714a2.25 2.25 0 0 1-.659 1.591L5 14.5M9.75 3.104c.251.023.501.05.75.082m-.75-.082a24.301 24.301 0 0 0-4.5 0m4.5 0v5.714c0 .597-.237 1.17-.659 1.591L5 14.5m0 0-3 3m3-3 3 3"/></svg>
                AI Summary
            </h3>
            <p class="text-xs text-gray-300 leading-relaxed mb-3">{{ $conversation->summary }}</p>
            @if(!empty($conversation->action_items))
            <div class="border-t border-white/5 pt-3">
                <h4 class="text-xs font-medium text-gray-400 mb-2">Action Items</h4>
                <ul class="space-y-1">
                    @foreach($conversation->action_items as $item)
                    <li class="flex items-start gap-2 text-xs text-gray-300">
                        <span class="text-brand-400 mt-0.5">•</span>
                        <span>{{ $item }}</span>
                    </li>
                    @endforeach
                </ul>
            </div>
            @endif
            <p class="text-[10px] text-gray-600 mt-3">{{ $conversation->summary_generated_at?->diffForHumans() }}</p>
        </div>
        @endif

        {{-- Actions --}}
        <div class="bg-gray-900/50 border border-white/5 rounded-2xl p-5 shrink-0">
            <h3 class="text-sm font-semibold text-white mb-3">Actions</h3>
            <div class="space-y-2">
                @if($conversation->isAiActive())
                <form method="POST" action="{{ route('conversations.takeOver', $conversation) }}" class="w-full">@csrf
                    <button class="w-full px-3 py-2 bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-400 text-xs font-medium rounded-xl transition">👤 Take Over (Human)</button>
                </form>
                @else
                <form method="POST" action="{{ route('conversations.switchToAi', $conversation) }}" class="w-full">@csrf
                    <button class="w-full px-3 py-2 bg-brand-500/10 hover:bg-brand-500/20 text-brand-400 text-xs font-medium rounded-xl transition">🤖 Switch Back to AI</button>
                </form>
                @endif
                <form method="POST" action="{{ route('conversations.spam', $conversation) }}" class="w-full">@csrf
                    <button class="w-full px-3 py-2 bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 text-xs font-medium rounded-xl transition">🚫 Mark as Spam</button>
                </form>
                <form method="POST" action="{{ route('conversations.destroy', $conversation) }}" class="w-full" onsubmit="return confirm('Delete this conversation?')">@csrf @method('DELETE')
                    <button class="w-full px-3 py-2 bg-white/5 hover:bg-white/10 text-gray-400 text-xs font-medium rounded-xl transition">🗑 Delete Conversation</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const container = document.getElementById('messages-container');
    if (container) container.scrollTop = container.scrollHeight;
});

function requestAgentAssist() {
    const btn = document.getElementById('agentAssistBtn');
    const banner = document.getElementById('agentAssistBanner');
    const textEl = document.getElementById('agentAssistText');

    btn.disabled = true;
    btn.textContent = 'Thinking…';

    fetch('{{ route('conversations.agentAssist', $conversation) }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
    })
    .then(r => r.json())
    .then(data => {
        textEl.textContent = data.suggestion ?? 'No suggestion available.';
        banner.classList.remove('hidden');
    })
    .catch(() => { textEl.textContent = 'Could not load suggestion.'; banner.classList.remove('hidden'); })
    .finally(() => { btn.disabled = false; btn.innerHTML = '<svg class="w-3.5 h-3.5 inline mr-1" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z"></path></svg>AI Assist'; });
}

function useAssistSuggestion() {
    const text = document.getElementById('agentAssistText').textContent;
    const textarea = document.getElementById('replyTextarea');
    if (textarea && text) {
        textarea.value = text;
        textarea.focus();
        document.getElementById('agentAssistBanner').classList.add('hidden');
    }
}

function labelManager() {
    return {
        customLabel: '',
        addLabel(label) {
            if (!label.trim()) return;
            fetch('{{ route('conversations.addLabel', $conversation) }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                body: JSON.stringify({ label: label.trim() })
            }).then(() => window.location.reload());
        },
        removeLabel(label) {
            fetch('{{ route('conversations.removeLabel', $conversation) }}', {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                body: JSON.stringify({ label })
            }).then(() => window.location.reload());
        }
    };
}
</script>
@endsection
