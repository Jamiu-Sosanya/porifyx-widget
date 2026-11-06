@extends('layouts.settings')
@section('title', 'Team')
@section('page-title', 'Settings')
@section('settings')
<div class="w-full">

    @if(session('success'))
    <div class="mb-5 p-4 bg-emerald-500/10 border border-emerald-500/20 rounded-2xl text-sm text-emerald-400 flex items-center gap-2">
        <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd" /></svg>
        {{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div class="mb-5 p-4 bg-rose-500/10 border border-rose-500/20 rounded-2xl text-sm text-rose-400">{{ session('error') }}</div>
    @endif

    {{-- Invite Form --}}
    <div class="bg-gray-900/50 border border-white/5 rounded-2xl p-6 mb-6">
        <h3 class="text-base font-semibold text-white mb-1">Invite Team Member</h3>
        <p class="text-xs text-gray-500 mb-5">Invited members receive an email to set up their account and join your workspace.</p>
        <form method="POST" action="{{ route('settings.team.invite') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            @csrf
            <div class="sm:col-span-1">
                <label class="block text-xs font-medium text-gray-400 mb-1.5">Email address</label>
                <input name="email" type="email" required
                       class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white placeholder-gray-600 focus:outline-none focus:border-brand-500/50 transition"
                       placeholder="colleague@company.com" value="{{ old('email') }}">
                @error('email') <p class="text-xs text-rose-400 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-400 mb-1.5">Role</label>
                <select name="role" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-brand-500/50 transition">
                    <option value="agent">Agent — can view &amp; reply to chats</option>
                    <option value="admin">Admin — full access except billing</option>
                    <option value="viewer">Viewer — read-only access</option>
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full px-5 py-2.5 bg-brand-600 hover:bg-brand-500 text-white text-sm font-medium rounded-xl transition">
                    Send Invite
                </button>
            </div>
        </form>
    </div>

    {{-- Team Members List --}}
    <div class="bg-gray-900/50 border border-white/5 rounded-2xl overflow-hidden">
        <div class="px-5 py-4 border-b border-white/5 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-white">Team Members</h3>
            <span class="text-xs text-gray-500">{{ $members->count() }} member{{ $members->count() !== 1 ? 's' : '' }}</span>
        </div>

        @forelse($members as $member)
        <div class="px-5 py-4 hover:bg-white/[0.015] transition border-b border-white/[0.04] last:border-0"
             x-data="{ editingRole: false }">
            <div class="flex items-center gap-4">
                {{-- Avatar --}}
                <div class="w-10 h-10 rounded-xl bg-linear-to-br from-brand-600/20 to-accent-600/20 border border-white/5 flex items-center justify-center text-xs font-bold text-brand-300 shrink-0">
                    {{ strtoupper(substr($member->user?->name ?? $member->user?->email ?? '?', 0, 2)) }}
                </div>

                {{-- Info --}}
                <div class="flex-1 min-w-0">
                    <div class="flex flex-wrap items-center gap-2 mb-0.5">
                        <p class="text-sm font-medium text-white">{{ $member->user?->name ?? 'Pending user' }}</p>
                        {{-- Status badge --}}
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold
                            {{ $member->status === 'active' ? 'bg-emerald-500/10 text-emerald-400' : ($member->status === 'pending' ? 'bg-amber-500/10 text-amber-400' : 'bg-rose-500/10 text-rose-400') }}">
                            {{ ucfirst($member->status) }}
                        </span>
                        {{-- Role badge --}}
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-medium bg-brand-500/10 text-brand-400">
                            {{ ucfirst($member->role) }}
                        </span>
                    </div>
                    <p class="text-xs text-gray-500">{{ $member->user?->email }}</p>
                    @if($member->status === 'pending' && $member->invite_expires_at)
                    <p class="text-[10px] text-gray-600 mt-0.5">
                        Invite expires {{ \Carbon\Carbon::parse($member->invite_expires_at)->diffForHumans() }}
                    </p>
                    @endif
                </div>

                {{-- Actions --}}
                <div class="flex items-center gap-2 shrink-0">
                    {{-- Change role --}}
                    <div x-show="!editingRole">
                        <button @click="editingRole = true"
                                class="px-3 py-1.5 bg-white/5 hover:bg-white/10 text-gray-400 hover:text-white text-xs rounded-lg transition">
                            Change Role
                        </button>
                    </div>
                    <div x-show="editingRole" x-transition class="flex items-center gap-1.5">
                        <form method="POST" action="{{ route('settings.team.updateRole', $member) }}" class="flex items-center gap-1.5">
                            @csrf @method('PATCH')
                            <select name="role" class="bg-gray-800 border border-white/10 rounded-lg px-2 py-1.5 text-xs text-white focus:outline-none focus:border-brand-500/50">
                                @foreach(['agent' => 'Agent', 'admin' => 'Admin', 'viewer' => 'Viewer'] as $val => $label)
                                <option value="{{ $val }}" {{ $member->role === $val ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="px-3 py-1.5 bg-brand-600 hover:bg-brand-500 text-white text-xs rounded-lg transition">Save</button>
                        </form>
                        <button @click="editingRole = false" class="px-2 py-1.5 bg-white/5 text-gray-500 text-xs rounded-lg transition hover:text-white">✕</button>
                    </div>

                    {{-- Resend if pending --}}
                    @if($member->status === 'pending')
                    <form method="POST" action="{{ route('settings.team.resend', $member) }}">
                        @csrf
                        <button type="submit" class="px-3 py-1.5 bg-amber-500/10 hover:bg-amber-500/20 text-amber-400 text-xs font-medium rounded-lg transition">
                            Resend
                        </button>
                    </form>
                    @endif

                    {{-- Remove --}}
                    <form method="POST" action="{{ route('settings.team.remove', $member) }}"
                          onsubmit="return confirm('Remove {{ addslashes($member->user?->name ?? $member->user?->email ?? 'this member') }} from your team?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="p-1.5 text-gray-600 hover:text-rose-400 rounded-lg transition" title="Remove">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M22 10.5h-6m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM4 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 10.374 21c-2.331 0-4.512-.645-6.374-1.766Z" />
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
        </div>
        @empty
        <div class="py-16 text-center">
            <svg class="w-10 h-10 mx-auto mb-3 text-gray-700" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
            </svg>
            <p class="text-gray-500 text-sm">No team members yet.</p>
            <p class="text-gray-600 text-xs mt-1">Invite colleagues to collaborate on your workspace.</p>
        </div>
        @endforelse
    </div>

    {{-- Role legend --}}
    <div class="mt-4 bg-gray-900/30 border border-white/[0.04] rounded-2xl p-4">
        <h4 class="text-xs font-semibold text-gray-400 mb-3 uppercase tracking-wider">Role Permissions</h4>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
            @foreach([
                ['Agent', 'brand', ['View conversations', 'Reply to chats', 'View leads', 'View widgets']],
                ['Admin', 'accent', ['All Agent permissions', 'Manage widgets', 'Manage automations', 'View reports']],
                ['Viewer', 'gray',  ['View conversations', 'View leads', 'Read-only access', 'No actions']],
            ] as [$role, $color, $perms])
            <div class="bg-white/[0.02] rounded-xl p-3">
                <p class="font-semibold text-{{ $color }}-400 mb-2">{{ $role }}</p>
                <ul class="space-y-1">
                    @foreach($perms as $perm)
                    <li class="flex items-center gap-1.5 text-gray-500">
                        <svg class="w-3 h-3 text-{{ $color }}-500/60 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" /></svg>
                        {{ $perm }}
                    </li>
                    @endforeach
                </ul>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
