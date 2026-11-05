@extends('layouts.admin')
@section('title', 'Admin: Users')
@section('page-title', 'Manage Users')
@section('content')
<div class="flex items-center justify-between mb-6">
    <form method="GET" class="flex items-center gap-3">
        <div class="relative"><svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg><input name="search" value="{{ request('search') }}" placeholder="Search users..." class="w-64 bg-white/5 border border-white/10 rounded-xl pl-9 pr-4 py-2.5 text-sm text-white placeholder-gray-500 focus:outline-none focus:border-brand-500/50 transition"></div>
        <select name="role" onchange="this.form.submit()" class="bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none transition"><option value="">All Roles</option><option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Admin</option><option value="user" {{ request('role') === 'user' ? 'selected' : '' }}>User</option></select>
    </form>
</div>

<div class="bg-gray-900/50 border border-white/5 rounded-2xl overflow-hidden">
    <table class="w-full">
        <thead><tr class="border-b border-white/5 text-left">
            <th class="px-4 py-3 text-xs font-medium text-gray-500 uppercase">User</th>
            <th class="px-4 py-3 text-xs font-medium text-gray-500 uppercase">Plan</th>
            <th class="px-4 py-3 text-xs font-medium text-gray-500 uppercase">Widgets</th>
            <th class="px-4 py-3 text-xs font-medium text-gray-500 uppercase">Leads</th>
            <th class="px-4 py-3 text-xs font-medium text-gray-500 uppercase">Status</th>
            <th class="px-4 py-3 text-xs font-medium text-gray-500 uppercase">Joined</th>
            <th class="px-4 py-3 text-xs font-medium text-gray-500 uppercase"></th>
        </tr></thead>
        <tbody class="divide-y divide-white/5">
            @foreach($users as $user)
            <tr class="hover:bg-white/[0.02] transition">
                <td class="px-4 py-3"><div class="flex items-center gap-3"><div class="w-8 h-8 rounded-lg bg-gradient-to-br from-gray-700 to-gray-800 flex items-center justify-center text-[10px] font-bold text-white shrink-0">{{ strtoupper(substr($user->name, 0, 2)) }}</div><div><p class="text-sm font-medium text-white">{{ $user->name }}</p><p class="text-[10px] text-gray-500">{{ $user->email }}</p></div></div></td>
                <td class="px-4 py-3 text-xs text-gray-400">{{ $user->subscription?->plan?->name ?? 'None' }}</td>
                <td class="px-4 py-3 text-xs text-gray-400">{{ $user->widgets_count }}</td>
                <td class="px-4 py-3 text-xs text-gray-400">{{ $user->leads_count }}</td>
                <td class="px-4 py-3"><span class="px-2 py-0.5 rounded-full text-[10px] font-medium {{ $user->is_active ? 'bg-emerald-500/10 text-emerald-400' : 'bg-rose-500/10 text-rose-400' }}">{{ $user->is_active ? 'Active' : 'Disabled' }}</span></td>
                <td class="px-4 py-3 text-xs text-gray-500">{{ $user->created_at->format('M d, Y') }}</td>
                <td class="px-4 py-3">
                    <div class="flex items-center gap-1">
                        <a href="{{ route('admin.users.show', $user) }}" class="px-2 py-1 text-xs text-brand-400 hover:bg-brand-500/10 rounded-lg transition">View</a>
                        <form method="POST" action="{{ route('admin.users.impersonate', $user) }}">@csrf<button class="px-2 py-1 text-xs text-amber-400 hover:bg-amber-500/10 rounded-lg transition">Login As</button></form>
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
<div class="mt-6">{{ $users->withQueryString()->links() }}</div>
@endsection
