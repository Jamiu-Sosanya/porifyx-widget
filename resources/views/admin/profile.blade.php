@extends('layouts.admin')
@section('title', 'Profile Information')
@section('page-title', 'Profile Information')
@section('content')
<div class="max-w-3xl">
    @if(session('success'))
    <div class="mb-5 p-4 bg-emerald-500/10 border border-emerald-500/20 rounded-2xl text-sm text-emerald-400 flex items-center gap-2">
        <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd" /></svg>
        {{ session('success') }}
    </div>
    @endif

    <div class="bg-gray-900/50 border border-white/5 rounded-2xl p-6 mb-6">
        <h3 class="text-base font-semibold text-white mb-4">Profile Information</h3>
        <form method="POST" action="{{ route('admin.profile.update') }}" enctype="multipart/form-data" class="space-y-5">
            @csrf
            @method('PUT')
            <div class="flex items-center gap-4 mb-4">
                <img src="{{ $user->avatarUrl() }}" alt="" class="w-16 h-16 rounded-2xl object-cover">
                <input name="avatar" type="file" accept="image/*" class="text-sm text-gray-400 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:bg-white/5 file:text-gray-300 hover:file:bg-white/10">
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Name</label>
                    <input name="name" value="{{ old('name', $user->name) }}" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-brand-500/50 transition">
                    @error('name')<p class="mt-1 text-sm text-rose-400">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Email</label>
                    <input name="email" type="email" value="{{ old('email', $user->email) }}" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-brand-500/50 transition">
                    @error('email')<p class="mt-1 text-sm text-rose-400">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Company</label>
                    <input name="company_name" value="{{ old('company_name', $user->company_name) }}" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-brand-500/50 transition">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Website</label>
                    <input name="company_website" value="{{ old('company_website', $user->company_website) }}" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-brand-500/50 transition">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Phone</label>
                    <input name="phone" value="{{ old('phone', $user->phone) }}" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-brand-500/50 transition">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Timezone</label>
                    <input name="timezone" value="{{ old('timezone', $user->timezone) }}" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-brand-500/50 transition">
                </div>
            </div>
            <button type="submit" class="px-6 py-2.5 bg-brand-600 hover:bg-brand-500 text-white text-sm font-medium rounded-xl transition">Save Changes</button>
        </form>
    </div>

    <div class="bg-gray-900/50 border border-white/5 rounded-2xl p-6">
        <h3 class="text-base font-semibold text-white mb-4">Change Password</h3>
        <form method="POST" action="{{ route('admin.profile.password.update') }}" class="space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Current Password</label>
                <input name="current_password" type="password" required class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-brand-500/50 transition">
                @error('current_password')<p class="mt-1 text-sm text-rose-400">{{ $message }}</p>@enderror
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">New Password</label>
                    <input name="password" type="password" required class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-brand-500/50 transition">
                    @error('password')<p class="mt-1 text-sm text-rose-400">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Confirm Password</label>
                    <input name="password_confirmation" type="password" required class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-brand-500/50 transition">
                </div>
            </div>
            <button type="submit" class="px-6 py-2.5 bg-white/5 hover:bg-white/10 text-white text-sm font-medium rounded-xl transition">Update Password</button>
        </form>
    </div>
</div>
@endsection
