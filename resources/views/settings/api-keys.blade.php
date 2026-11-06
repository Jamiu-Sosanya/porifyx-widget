@extends('layouts.settings')
@section('title', 'API Keys')
@section('page-title', 'Settings')
@section('settings')
<div class="w-full" x-data="{
    showCreate: false,
    permissions: ['read'],
    togglePerm(p) {
        if (this.permissions.includes(p)) {
            this.permissions = this.permissions.filter(x => x !== p);
        } else {
            this.permissions.push(p);
        }
    }
}">

    {{-- New key reveal --}}
    @if(session('new_key'))
    <div class="bg-emerald-500/10 border border-emerald-500/20 rounded-2xl p-5 mb-6" x-data="{ copied: false }">
        <div class="flex items-center gap-2 mb-3">
            <svg class="w-4 h-4 text-emerald-400 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 1a4.5 4.5 0 0 0-4.5 4.5V9H5a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-6a2 2 0 0 0-2-2h-.5V5.5A4.5 4.5 0 0 0 10 1Zm3 8V5.5a3 3 0 1 0-6 0V9h6Z" clip-rule="evenodd" /></svg>
            <p class="text-sm font-semibold text-emerald-400">New API key generated — copy it now, it won't be shown again.</p>
        </div>
        <div class="flex items-center gap-3">
            <code id="new-key-display" class="flex-1 block bg-black/40 border border-emerald-500/20 rounded-xl p-3 text-sm text-emerald-300 font-mono break-all select-all">{{ session('new_key') }}</code>
            <button @click="navigator.clipboard.writeText('{{ session('new_key') }}'); copied = true; setTimeout(() => copied = false, 2000)"
                    class="shrink-0 p-2.5 bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-400 rounded-xl transition">
                <svg x-show="!copied" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.666 3.888A2.25 2.25 0 0 0 13.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 0 1-.75.75H9a.75.75 0 0 1-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 0 1-2.25 2.25H6.75A2.25 2.25 0 0 1 4.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 0 1 1.927-.184" /></svg>
                <svg x-show="copied" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
            </button>
        </div>
    </div>
    @endif

    @if(session('success') && !session('new_key'))
    <div class="mb-5 p-4 bg-emerald-500/10 border border-emerald-500/20 rounded-2xl text-sm text-emerald-400">{{ session('success') }}</div>
    @endif

    {{-- Create form toggle --}}
    <div class="mb-6">
        <button @click="showCreate = !showCreate"
                class="inline-flex items-center gap-2 px-5 py-2.5 bg-brand-600 hover:bg-brand-500 text-white text-sm font-medium rounded-xl transition">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
            Create API Key
        </button>
    </div>

    {{-- Create form --}}
    <div x-show="showCreate" x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
         class="bg-gray-900/50 border border-white/5 rounded-2xl p-6 mb-6">
        <h3 class="text-base font-semibold text-white mb-5">New API Key</h3>
        <form method="POST" action="{{ route('settings.apiKeys.create') }}" class="space-y-5">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1.5">Key name</label>
                <input name="name" required placeholder="e.g. Production, Staging, My App"
                       class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white placeholder-gray-600 focus:outline-none focus:border-brand-500/50 transition">
                @error('name') <p class="text-xs text-rose-400 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-300 mb-3">Permissions</label>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                    @foreach([
                        ['read',       'Read',       'GET requests — view data'],
                        ['write',      'Write',      'POST/PUT — create & update'],
                        ['delete',     'Delete',     'DELETE requests'],
                        ['webhooks',   'Webhooks',   'Receive webhook events'],
                    ] as [$val, $label, $desc])
                    <label class="relative cursor-pointer">
                        <input type="checkbox" name="permissions[]" value="{{ $val }}"
                               x-model="permissions"
                               class="sr-only peer">
                        <div class="p-3 bg-white/2 border border-white/10 rounded-xl peer-checked:border-brand-500/40 peer-checked:bg-brand-500/10 transition">
                            <p class="text-sm font-semibold text-white">{{ $label }}</p>
                            <p class="text-[10px] text-gray-600 mt-0.5 leading-tight">{{ $desc }}</p>
                        </div>
                    </label>
                    @endforeach
                </div>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit"
                        class="px-5 py-2.5 bg-brand-600 hover:bg-brand-500 text-white text-sm font-semibold rounded-xl transition">
                    Generate Key
                </button>
                <button type="button" @click="showCreate = false"
                        class="px-5 py-2.5 bg-white/5 hover:bg-white/10 text-gray-400 text-sm rounded-xl transition">
                    Cancel
                </button>
            </div>
        </form>
    </div>

    {{-- Keys list --}}
    <div class="bg-gray-900/50 border border-white/5 rounded-2xl overflow-hidden">
        <div class="px-5 py-4 border-b border-white/5 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-white">Your API Keys</h3>
            <span class="text-xs text-gray-500">{{ $keys->count() }} key{{ $keys->count() !== 1 ? 's' : '' }}</span>
        </div>

        @forelse($keys as $key)
        <div class="px-5 py-4 hover:bg-white/1.5 transition border-b border-white/4 last:border-0" x-data>
            <div class="flex items-start gap-4">
                {{-- Icon --}}
                <div class="w-9 h-9 rounded-xl bg-brand-500/10 border border-brand-500/10 flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4 text-brand-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 0 1 3 3m3 0a6 6 0 0 1-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 0 1 21.75 8.25Z" />
                    </svg>
                </div>

                <div class="flex-1 min-w-0">
                    <div class="flex flex-wrap items-center gap-2 mb-1">
                        <p class="text-sm font-semibold text-white">{{ $key->name }}</p>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $key->isValid() ? 'bg-emerald-500/10 text-emerald-400' : 'bg-rose-500/10 text-rose-400' }}">
                            {{ $key->isValid() ? 'Active' : 'Expired' }}
                        </span>
                    </div>

                    {{-- Key preview --}}
                    <code class="text-xs text-gray-500 font-mono">{{ substr($key->key, 0, 16) }}••••••••{{ substr($key->key, -8) }}</code>

                    {{-- Stats row --}}
                    <div class="flex flex-wrap items-center gap-3 mt-2">
                        <span class="text-[10px] text-gray-500">
                            <span class="text-gray-400 font-medium">{{ number_format($key->requests_count) }}</span> requests
                        </span>
                        <span class="text-[10px] text-gray-600">·</span>
                        <span class="text-[10px] text-gray-500">
                            Last used: <span class="text-gray-400">{{ $key->last_used_at?->diffForHumans() ?? 'never' }}</span>
                        </span>
                        <span class="text-[10px] text-gray-600">·</span>
                        <span class="text-[10px] text-gray-500">
                            Created {{ $key->created_at->format('M d, Y') }}
                        </span>
                    </div>

                    {{-- Permissions --}}
                    @if(!empty($key->permissions))
                    <div class="flex flex-wrap gap-1.5 mt-2">
                        @foreach((array) $key->permissions as $perm)
                        <span class="px-2 py-0.5 text-[10px] font-mono bg-white/5 text-gray-400 rounded-md">{{ $perm }}</span>
                        @endforeach
                    </div>
                    @endif
                </div>

                {{-- Revoke --}}
                <form method="POST" action="{{ route('settings.apiKeys.delete', $key) }}"
                      onsubmit="return confirm('Revoke API key \'{{ addslashes($key->name) }}\'? Any apps using this key will stop working.')">
                    @csrf @method('DELETE')
                    <button type="submit"
                            class="px-3 py-1.5 bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 text-xs font-medium rounded-lg transition">
                        Revoke
                    </button>
                </form>
            </div>
        </div>
        @empty
        <div class="py-16 text-center">
            <svg class="w-10 h-10 mx-auto mb-3 text-gray-700" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 0 1 3 3m3 0a6 6 0 0 1-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 0 1 21.75 8.25Z" />
            </svg>
            <p class="text-gray-500 text-sm">No API keys yet.</p>
            <p class="text-gray-600 text-xs mt-1">Create a key to integrate {{ config('branding.app_name') }} with your apps and services.</p>
        </div>
        @endforelse
    </div>

    {{-- Usage docs hint --}}
    <div class="mt-4 bg-gray-900/50 border border-white/5 rounded-2xl p-4 text-xs text-gray-400">
        <p class="font-medium text-gray-400 mb-1">Using your API key</p>
        <p class="leading-relaxed">Pass your key in the <code class="bg-white/5 px-1.5 py-0.5 rounded text-gray-300 font-mono">Authorization</code> header as a Bearer token:</p>
        <code class="block mt-2 bg-black/30 border border-white/5 rounded-xl p-3 text-[10px] font-mono text-gray-400">
            Authorization: Bearer &lt;your-api-key&gt;
        </code>
        <a href="{{ route('settings.developer') }}" class="inline-block mt-2 text-brand-400 hover:text-brand-300 transition">View full API docs →</a>
    </div>
</div>
@endsection
