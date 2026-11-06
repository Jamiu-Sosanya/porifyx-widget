@extends('layouts.app')
@section('title', 'Create Automation')
@section('page-title', 'Create Automation')
@section('content')
<div class="max-w-3xl">
    <a href="{{ route('automations.index') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-400 hover:text-white mb-6 transition">← Back</a>
    <form method="POST" action="{{ route('automations.store') }}" class="space-y-6" x-data="{ trigger: @js(old('trigger', request('trigger', ''))), action: @js(old('action', request('action', ''))) }">
        @csrf
        @if($errors->any())
        <div class="p-4 bg-rose-500/10 border border-rose-500/20 rounded-2xl text-sm text-rose-400 space-y-1">
            @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </div>
        @endif
        <div class="bg-gray-900/50 border border-white/5 rounded-2xl p-6 space-y-5">
            <h3 class="text-base font-semibold text-white">Basic Info</h3>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1.5">Name</label>
                <input name="name" required value="{{ old('name', request('name')) }}" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-brand-500/50 transition" placeholder="e.g. Notify on hot lead">
                @error('name') <p class="text-xs text-rose-400 mt-1">{{ $message }}</p> @enderror
            </div>
            <div><label class="block text-sm font-medium text-gray-300 mb-1.5">Description</label><textarea name="description" rows="2" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-brand-500/50 transition resize-none">{{ old('description') }}</textarea></div>
            <div><label class="block text-sm font-medium text-gray-300 mb-1.5">Widget (optional)</label>
                <select name="widget_id" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-brand-500/50 transition"><option value="">All Widgets</option>@foreach($widgets as $w)<option value="{{ $w->id }}">{{ $w->name }}</option>@endforeach</select>
            </div>
        </div>
        <div class="bg-gray-900/50 border border-white/5 rounded-2xl p-6 space-y-4">
            <h3 class="text-base font-semibold text-white">⚡ Trigger</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                @foreach($triggers as $key => $t)
                <label class="flex items-start gap-3 p-3 bg-white/[0.02] border border-white/5 rounded-xl cursor-pointer hover:bg-white/[0.04] transition" :class="trigger === '{{ $key }}' ? 'border-brand-500/30 bg-brand-500/5' : ''">
                    <input type="radio" name="trigger" value="{{ $key }}" x-model="trigger" class="mt-0.5 text-brand-600 bg-white/5 border-white/20 focus:ring-brand-500/30" {{ old('trigger') === $key ? 'checked' : '' }}>
                    <div><p class="text-sm font-medium text-white">{{ $t['name'] }}</p><p class="text-xs text-gray-500">{{ $t['description'] }}</p></div>
                </label>
                @endforeach
            </div>
            @error('trigger') <p class="text-xs text-rose-400">{{ $message }}</p> @enderror
        </div>
        <div class="bg-gray-900/50 border border-white/5 rounded-2xl p-6 space-y-4">
            <h3 class="text-base font-semibold text-white">🎯 Action</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                @foreach($actions as $key => $a)
                <label class="flex items-start gap-3 p-3 bg-white/[0.02] border border-white/5 rounded-xl cursor-pointer hover:bg-white/[0.04] transition" :class="action === '{{ $key }}' ? 'border-brand-500/30 bg-brand-500/5' : ''">
                    <input type="radio" name="action" value="{{ $key }}" x-model="action" class="mt-0.5 text-brand-600 bg-white/5 border-white/20 focus:ring-brand-500/30" {{ old('action') === $key ? 'checked' : '' }}>
                    <div><p class="text-sm font-medium text-white">{{ $a['name'] }}</p><p class="text-xs text-gray-500">{{ $a['description'] }}</p></div>
                </label>
                @endforeach
            </div>
            @error('action') <p class="text-xs text-rose-400">{{ $message }}</p> @enderror
            {{-- Action Config --}}
            <div x-show="action === 'send_email' || action === 'webhook'" x-transition class="space-y-3 mt-4 p-4 bg-white/[0.02] rounded-xl">
                <div x-show="action === 'send_email'"><label class="block text-sm font-medium text-gray-300 mb-1">Email To</label><input name="action_config[email]" type="email" value="{{ old('action_config.email') }}" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-brand-500/50 transition" placeholder="notify@company.com"></div>
                <div x-show="action === 'webhook'"><label class="block text-sm font-medium text-gray-300 mb-1">Webhook URL</label><input name="action_config[url]" type="url" value="{{ old('action_config.url') }}" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-brand-500/50 transition" placeholder="https://api.example.com/hook"></div>
                <div x-show="action === 'send_email'"><label class="block text-sm font-medium text-gray-300 mb-1">Email Subject</label><input name="action_config[subject]" value="{{ old('action_config.subject') }}" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-brand-500/50 transition" placeholder="New hot lead from chat"></div>
            </div>
            <div x-show="action === 'tag_lead'" x-transition class="p-4 bg-white/[0.02] rounded-xl">
                <label class="block text-sm font-medium text-gray-300 mb-1">Tags (comma separated)</label>
                <input name="action_config[tags]" value="{{ old('action_config.tags') }}" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-brand-500/50 transition" placeholder="vip, high-value">
            </div>
            {{-- Ensures action_config is always present as an array in the POST body --}}
            @include('automations.partials.config', ['automation' => $automation ?? null])
            <input type="hidden" name="action_config[_set]" value="1">
        </div>
        <button type="submit" class="px-6 py-2.5 bg-linear-to-r from-brand-600 to-accent-600 hover:from-brand-500 hover:to-accent-500 text-white text-sm font-medium rounded-xl transition shadow-lg shadow-brand-500/20">Create Automation</button>
    </form>
</div>
@endsection
