@extends('layouts.admin')
@section('title', 'User: ' . $user->name)
@section('page-title', 'User Details')
@section('content')
<div class="max-w-4xl">
    <a href="{{ route('admin.users') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-400 hover:text-white mb-6 transition">← Back to users</a>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="bg-gray-900/50 border border-white/5 rounded-2xl p-5">
            <div class="text-center mb-4">
                <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-brand-600/30 to-accent-600/30 flex items-center justify-center text-xl font-bold text-brand-400 mx-auto mb-3">{{ strtoupper(substr($user->name, 0, 2)) }}</div>
                <h3 class="text-lg font-semibold text-white">{{ $user->name }}</h3>
                <p class="text-sm text-gray-500">{{ $user->email }}</p>
                <span class="inline-flex mt-2 px-2.5 py-0.5 rounded-full text-[10px] font-medium {{ $user->role === 'admin' ? 'bg-rose-500/10 text-rose-400' : 'bg-brand-500/10 text-brand-400' }}">{{ ucfirst($user->role) }}</span>
            </div>
            <div class="space-y-2 text-sm border-t border-white/5 pt-4">
                <div class="flex justify-between"><span class="text-gray-500">Plan</span><span class="text-white">{{ $user->subscription?->plan?->name ?? 'None' }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Company</span><span class="text-white">{{ $user->company_name ?? '-' }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Joined</span><span class="text-white">{{ $user->created_at->format('M d, Y') }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Last Login</span><span class="text-white">{{ $user->last_login_at?->diffForHumans() ?? 'Never' }}</span></div>
            </div>
            <form method="POST" action="{{ route('admin.users.update', $user) }}" class="mt-4 space-y-3 border-t border-white/5 pt-4">
                @csrf @method('PUT')
                <input type="hidden" name="name" value="{{ $user->name }}">
                <input type="hidden" name="email" value="{{ $user->email }}">
                <select name="role" class="w-full bg-white/5 border border-white/10 rounded-xl px-3 py-2 text-sm text-white focus:outline-none transition"><option value="user" {{ $user->role === 'user' ? 'selected' : '' }}>User</option><option value="admin" {{ $user->role === 'admin' ? 'selected' : '' }}>Admin</option></select>
                <label class="flex items-center gap-2"><input type="checkbox" name="is_active" value="1" {{ $user->is_active ? 'checked' : '' }} class="rounded bg-white/5 border-white/20 text-brand-600"><span class="text-sm text-gray-300">Active</span></label>
                <button type="submit" class="w-full px-4 py-2 bg-brand-600 hover:bg-brand-500 text-white text-sm font-medium rounded-xl transition">Update</button>
            </form>
        </div>
        <div class="lg:col-span-2 space-y-4">
            <h3 class="text-base font-semibold text-white">Widgets</h3>
            @foreach($widgetStats as $w)
            <div class="bg-gray-900/50 border border-white/5 rounded-xl p-4 flex items-center justify-between">
                <div><p class="text-sm font-medium text-white">{{ $w->name }}</p><p class="text-xs text-gray-500">{{ $w->is_active ? 'Active' : 'Inactive' }} · {{ $w->conversations_count }} chats · {{ $w->leads_count }} leads</p></div>
                <span class="w-3 h-3 rounded-full {{ $w->is_active ? 'bg-emerald-400' : 'bg-gray-600' }}"></span>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
