@extends('layouts.app')
@section('title', 'Lead: ' . ($lead->name ?? $lead->email ?? 'Unknown'))
@section('page-title', 'Lead Details')
@section('content')
<div class="max-w-5xl">
    @if(session('success'))
    <div class="mb-5 p-4 bg-emerald-500/10 border border-emerald-500/20 rounded-2xl text-sm text-emerald-400 flex items-center gap-2">
        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
        {{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div class="mb-5 p-4 bg-rose-500/10 border border-rose-500/20 rounded-2xl text-sm text-rose-400">{{ session('error') }}</div>
    @endif
    <a href="{{ route('leads.index') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-400 hover:text-white mb-6 transition">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg>
        Back to leads
    </a>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Lead Info --}}
        <div class="lg:col-span-1 space-y-6">
            <div class="bg-gray-900/50 border border-white/5 rounded-2xl p-5">
                <div class="text-center mb-4">
                    <div class="w-16 h-16 rounded-2xl bg-linear-to-br from-accent-600/30 to-accent-600/30 flex items-center justify-center text-xl font-bold text-accent-400 mx-auto mb-3">{{ strtoupper(substr($lead->name ?? $lead->email ?? '?', 0, 2)) }}</div>
                    <h3 class="text-lg font-semibold text-white">{{ $lead->name ?? 'Unknown' }}</h3>
                    <p class="text-sm text-gray-500">{{ $lead->email ?? 'No email' }}</p>
                </div>
                <div class="flex items-center justify-center gap-3 mb-4">
                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium {{ $lead->temperature_color }}">
                        @if($lead->temperature === 'hot')🔥@elseif($lead->temperature === 'warm')☀️@else❄️@endif {{ ucfirst($lead->temperature) }}
                    </span>
                    <span class="text-sm text-gray-400">Score: <strong class="text-white">{{ $lead->score }}/100</strong></span>
                </div>
                <div class="space-y-2 text-sm">
                    @if($lead->phone)<div class="flex justify-between"><span class="text-gray-500">Phone</span><span class="text-white">{{ $lead->phone }}</span></div>@endif
                    @if($lead->company)<div class="flex justify-between"><span class="text-gray-500">Company</span><span class="text-white">{{ $lead->company }}</span></div>@endif
                    @if($lead->enrichment)
                    <div class="mt-3 -mx-1 rounded-xl border border-white/5 bg-white/[0.03] p-3 space-y-1.5">
                        <div class="flex items-center gap-2">
                            @if(! empty($lead->enrichment['logo']))<img src="{{ $lead->enrichment['logo'] }}" alt="" class="w-5 h-5 rounded">@endif
                            <span class="text-white font-medium">{{ $lead->enrichment['company'] ?? $lead->enrichment['domain'] }}</span>
                            <span class="ml-auto px-2 py-0.5 rounded-full text-[10px] font-semibold {{ ($lead->enrichment['type'] ?? '') === 'business' ? 'bg-emerald-500/15 text-emerald-400' : 'bg-white/5 text-gray-400' }}">{{ ($lead->enrichment['type'] ?? '') === 'business' ? 'Business email' : 'Personal email' }}</span>
                        </div>
                        @if(! empty($lead->enrichment['description']))<p class="text-xs text-gray-400">{{ $lead->enrichment['description'] }}</p>@endif
                        @if(! empty($lead->enrichment['socials']))<p class="flex flex-wrap gap-2 text-xs">@foreach($lead->enrichment['socials'] as $soc)<a href="{{ $soc }}" target="_blank" rel="noopener" class="text-brand-400">{{ parse_url($soc, PHP_URL_HOST) }}</a>@endforeach</p>@endif
                    </div>
                    @endif
                    @foreach(array_diff_key((array) $lead->form_data, array_flip(['name', 'email', 'phone', 'company'])) as $fk => $fv)
                    <div class="flex justify-between gap-3"><span class="text-gray-500">{{ ucfirst(str_replace('_', ' ', $fk)) }}</span><span class="text-white text-right break-words min-w-0">{{ is_bool($fv) ? ($fv ? 'Yes' : 'No') : $fv }}</span></div>
                    @endforeach
                    @if($lead->service_interest)<div class="flex justify-between"><span class="text-gray-500">Interest</span><span class="text-white">{{ $lead->service_interest }}</span></div>@endif
                    @if($lead->budget_range)<div class="flex justify-between"><span class="text-gray-500">Budget</span><span class="text-white">{{ $lead->budget_range }}</span></div>@endif
                    @if($lead->timeline)<div class="flex justify-between"><span class="text-gray-500">Timeline</span><span class="text-white">{{ $lead->timeline }}</span></div>@endif
                    <div class="flex justify-between"><span class="text-gray-500">Created</span><span class="text-white">{{ $lead->created_at->format('M d, Y') }}</span></div>
                </div>
            </div>

            {{-- Update Lead --}}
            <div class="bg-gray-900/50 border border-white/5 rounded-2xl p-5">
                <h4 class="text-sm font-semibold text-white mb-3">Update Lead</h4>
                <form method="POST" action="{{ route('leads.update', $lead) }}" class="space-y-3">
                    @csrf @method('PUT')
                    <select name="status" class="w-full bg-white/5 border border-white/10 rounded-xl px-3 py-2 text-sm text-white focus:outline-none focus:border-brand-500/50 transition">
                        @foreach(\App\Support\LeadPipeline::settings(auth()->user())['statuses'] as $st)
                        <option value="{{ $st['key'] }}" {{ $lead->status === $st['key'] ? 'selected' : '' }}>{{ $st['label'] }}</option>
                        @endforeach
                    </select>
                    <select name="temperature" class="w-full bg-white/5 border border-white/10 rounded-xl px-3 py-2 text-sm text-white focus:outline-none focus:border-brand-500/50 transition">
                        @foreach(['hot' => '🔥 Hot', 'warm' => '☀️ Warm', 'cold' => '❄️ Cold'] as $val => $label)
                        <option value="{{ $val }}" {{ $lead->temperature === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="w-full px-4 py-2 bg-brand-600 hover:bg-brand-500 text-white text-sm font-medium rounded-xl transition">Update</button>
                </form>
            </div>
        </div>

        {{-- Conversation & Notes --}}
        <div class="lg:col-span-2 space-y-6">
            @if($lead->pain_point)
            <div class="bg-amber-500/5 border border-amber-500/10 rounded-2xl p-5">
                <h4 class="text-sm font-semibold text-amber-400 mb-2">💡 Pain Point</h4>
                <p class="text-sm text-gray-300">{{ $lead->pain_point }}</p>
            </div>
            @endif

            {{-- Conversation Thread --}}
            @if($lead->conversation)
            <div class="bg-gray-900/50 border border-white/5 rounded-2xl overflow-hidden">
                <div class="p-4 border-b border-white/5"><h4 class="text-sm font-semibold text-white">Conversation</h4></div>
                <div class="p-4 space-y-3 max-h-96 overflow-y-auto custom-scrollbar">
                    @foreach($lead->conversation->messages as $msg)
                    <div class="flex {{ $msg->role === 'visitor' ? 'justify-end' : 'justify-start' }}">
                        <div class="max-w-[80%] {{ $msg->role === 'visitor' ? 'bg-brand-600/20 text-brand-100' : 'bg-white/5 text-gray-300' }} rounded-xl px-3 py-2">
                            <p class="text-[10px] {{ $msg->role === 'visitor' ? 'text-brand-400' : 'text-gray-500' }} font-medium mb-0.5">{{ ucfirst($msg->role) }}</p>
                            <p class="text-xs leading-relaxed">{{ Str::limit($msg->content, 200) }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Notes --}}
            <div class="bg-gray-900/50 border border-white/5 rounded-2xl overflow-hidden">
                <div class="p-4 border-b border-white/5"><h4 class="text-sm font-semibold text-white">Notes</h4></div>
                <div class="p-4 space-y-3">
                    @foreach($lead->leadNotes as $note)
                    <div class="bg-white/2 rounded-xl p-3">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-xs font-medium text-white">{{ $note->user?->name ?? 'System' }}</span>
                            <span class="text-[10px] text-gray-500">{{ $note->created_at->diffForHumans() }}</span>
                        </div>
                        <p class="text-sm text-gray-300">{{ $note->content }}</p>
                    </div>
                    @endforeach
                    <form method="POST" action="{{ route('leads.addNote', $lead) }}" class="flex gap-2">
                        @csrf
                        <input name="content" required placeholder="Add a note..." class="flex-1 bg-white/5 border border-white/10 rounded-xl px-3 py-2 text-sm text-white placeholder-gray-500 focus:outline-none focus:border-brand-500/50 transition">
                        <button type="submit" class="px-4 py-2 bg-white/5 hover:bg-white/10 text-gray-300 text-sm font-medium rounded-xl transition">Add</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
