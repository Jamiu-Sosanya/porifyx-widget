@extends('layouts.app')
@section('title', 'Edit Automation')
@section('page-title', 'Edit Automation')
@section('content')
<div class="max-w-3xl">
    <a href="{{ route('automations.index') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-400 hover:text-white mb-6 transition">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg>
        Back to Automations
    </a>

    @if(session('success'))
    <div class="mb-5 p-4 bg-emerald-500/10 border border-emerald-500/20 rounded-2xl text-sm text-emerald-400 flex items-center gap-2">
        <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd" /></svg>
        {{ session('success') }}
    </div>
    @endif

    <form id="automation-edit-form"
          method="POST" action="{{ route('automations.update', $automation) }}"
          class="space-y-6"
          x-data="{
              trigger: '{{ old('trigger', $automation->trigger) }}',
              action: '{{ old('action', $automation->action) }}'
          }">
        @csrf @method('PUT')

        {{-- Basic Info --}}
        <div class="bg-gray-900/50 border border-white/5 rounded-2xl p-6 space-y-5">
            <div class="flex items-center justify-between">
                <h3 class="text-base font-semibold text-white">Basic Info</h3>
                {{-- Active toggle --}}
                <label class="flex items-center gap-2 cursor-pointer">
                    <span class="text-xs text-gray-400">Active</span>
                    <div class="relative">
                        <input type="checkbox" name="is_active" value="1" class="sr-only peer"
                               {{ old('is_active', $automation->is_active) ? 'checked' : '' }}>
                        <div class="w-10 h-5 bg-white/10 rounded-full peer-checked:bg-brand-600 transition peer-focus:ring-2 peer-focus:ring-brand-500/40"></div>
                        <div class="absolute left-0.5 top-0.5 w-4 h-4 bg-white rounded-full shadow transition peer-checked:translate-x-5"></div>
                    </div>
                </label>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1.5">Name</label>
                <input name="name" required value="{{ old('name', $automation->name) }}"
                       class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-brand-500/50 transition"
                       placeholder="e.g. Notify on hot lead">
                @error('name') <p class="text-xs text-rose-400 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1.5">Description <span class="text-gray-600">(optional)</span></label>
                <textarea name="description" rows="2"
                          class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-brand-500/50 transition resize-none"
                          placeholder="What does this automation do?">{{ old('description', $automation->description) }}</textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1.5">Widget <span class="text-gray-600">(optional — leave blank for all widgets)</span></label>
                <select name="widget_id"
                        class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-brand-500/50 transition">
                    <option value="">All Widgets</option>
                    @foreach($widgets as $w)
                    <option value="{{ $w->id }}" {{ old('widget_id', $automation->widget_id) == $w->id ? 'selected' : '' }}>{{ $w->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- Trigger --}}
        <div class="bg-gray-900/50 border border-white/5 rounded-2xl p-6 space-y-4">
            <h3 class="text-base font-semibold text-white">
                <span class="mr-1.5">⚡</span> Trigger
            </h3>
            <p class="text-xs text-gray-500 -mt-2">When should this automation run?</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                @foreach($triggers as $key => $t)
                <label class="flex items-start gap-3 p-3.5 bg-white/[0.02] border border-white/[0.07] rounded-xl cursor-pointer hover:bg-white/[0.04] transition"
                       :class="trigger === '{{ $key }}' ? 'border-brand-500/40 bg-brand-500/5' : ''">
                    <input type="radio" name="trigger" value="{{ $key }}" x-model="trigger"
                           class="mt-0.5 text-brand-600 bg-white/5 border-white/20 focus:ring-brand-500/30">
                    <div>
                        <p class="text-sm font-medium text-white">{{ $t['name'] }}</p>
                        <p class="text-xs text-gray-500 mt-0.5">{{ $t['description'] }}</p>
                    </div>
                </label>
                @endforeach
            </div>
            @error('trigger') <p class="text-xs text-rose-400">{{ $message }}</p> @enderror
        </div>

        {{-- Action --}}
        <div class="bg-gray-900/50 border border-white/5 rounded-2xl p-6 space-y-4">
            <h3 class="text-base font-semibold text-white">
                <span class="mr-1.5">🎯</span> Action
            </h3>
            <p class="text-xs text-gray-500 -mt-2">What should happen when the trigger fires?</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                @foreach($actions as $key => $a)
                <label class="flex items-start gap-3 p-3.5 bg-white/[0.02] border border-white/[0.07] rounded-xl cursor-pointer hover:bg-white/[0.04] transition"
                       :class="action === '{{ $key }}' ? 'border-brand-500/40 bg-brand-500/5' : ''">
                    <input type="radio" name="action" value="{{ $key }}" x-model="action"
                           class="mt-0.5 text-brand-600 bg-white/5 border-white/20 focus:ring-brand-500/30">
                    <div>
                        <p class="text-sm font-medium text-white">{{ $a['name'] }}</p>
                        <p class="text-xs text-gray-500 mt-0.5">{{ $a['description'] }}</p>
                    </div>
                </label>
                @endforeach
            </div>
            @error('action') <p class="text-xs text-rose-400">{{ $message }}</p> @enderror

            {{-- Email / webhook config --}}
            <div x-show="action === 'send_email' || action === 'webhook'" x-transition
                 class="space-y-3 mt-4 p-4 bg-white/[0.02] border border-white/[0.05] rounded-xl">
                <div x-show="action === 'send_email'">
                    <label class="block text-sm font-medium text-gray-300 mb-1.5">Notify Email</label>
                    <input name="action_config[email]" type="email"
                           value="{{ old('action_config.email', $automation->action_config['email'] ?? '') }}"
                           class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-brand-500/50 transition"
                           placeholder="notify@company.com">
                </div>
                <div x-show="action === 'send_email'">
                    <label class="block text-sm font-medium text-gray-300 mb-1.5">Email Subject</label>
                    <input name="action_config[subject]"
                           value="{{ old('action_config.subject', $automation->action_config['subject'] ?? '') }}"
                           class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-brand-500/50 transition"
                           placeholder="New hot lead from chat">
                </div>
                <div x-show="action === 'webhook'">
                    <label class="block text-sm font-medium text-gray-300 mb-1.5">Webhook URL</label>
                    <input name="action_config[url]" type="url"
                           value="{{ old('action_config.url', $automation->action_config['url'] ?? '') }}"
                           class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-brand-500/50 transition"
                           placeholder="https://api.example.com/hook">
                </div>
            </div>

            {{-- Tag config --}}
            <div x-show="action === 'tag_lead'" x-transition
                 class="mt-4 p-4 bg-white/[0.02] border border-white/[0.05] rounded-xl">
                <label class="block text-sm font-medium text-gray-300 mb-1.5">Tags <span class="text-gray-600">(comma separated)</span></label>
                <input name="action_config[tags]"
                       value="{{ old('action_config.tags', $automation->action_config['tags'] ?? '') }}"
                       class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-brand-500/50 transition"
                       placeholder="vip, high-value, urgent">
            </div>

            {{-- Hidden fallback for required action_config --}}
            <input type="hidden" name="action_config[_set]" value="1">
        </div>

        {{-- Submit --}}
        <div class="flex items-center gap-3">
            <button type="submit"
                    class="px-6 py-2.5 bg-linear-to-r from-brand-600 to-accent-600 hover:from-brand-500 hover:to-accent-500 text-white text-sm font-semibold rounded-xl transition shadow-lg shadow-brand-500/20">
                Save Changes
            </button>
            <a href="{{ route('automations.index') }}"
               class="px-5 py-2.5 bg-white/5 hover:bg-white/10 text-gray-400 text-sm rounded-xl transition">
                Cancel
            </a>
            <button type="submit" form="form-delete-automation"
                    class="ml-auto px-4 py-2.5 bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 text-xs font-medium rounded-xl transition">
                Delete Automation
            </button>
        </div>
    </form>

    {{-- Standalone delete form — outside main form to avoid nested-form browser bug --}}
    <form id="form-delete-automation"
          method="POST" action="{{ route('automations.destroy', $automation) }}"
          onsubmit="return confirm('Delete this automation permanently?')">
        @csrf @method('DELETE')
    </form>
</div>
@endsection
