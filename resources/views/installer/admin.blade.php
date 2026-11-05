@extends('installer.layout')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-white mb-2">Admin Account</h2>
        <p class="text-gray-400">Create the first administrator account for your application.</p>
    </div>

    <form action="{{ route('installer.admin.create') }}" method="POST" class="space-y-6">
        @csrf

        <div class="grid gap-4 md:grid-cols-2">
            <label class="space-y-2 text-sm text-gray-300">
                <span>Name</span>
                <input type="text" name="name" value="{{ old('name') }}" required class="w-full rounded-xl border border-white/10 bg-slate-900 px-4 py-3 text-white focus:border-brand-500 focus:outline-none" />
            </label>
            <label class="space-y-2 text-sm text-gray-300">
                <span>Email</span>
                <input type="email" name="email" value="{{ old('email') }}" required class="w-full rounded-xl border border-white/10 bg-slate-900 px-4 py-3 text-white focus:border-brand-500 focus:outline-none" />
            </label>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <label class="space-y-2 text-sm text-gray-300">
                <span>Password</span>
                <input type="password" name="password" required class="w-full rounded-xl border border-white/10 bg-slate-900 px-4 py-3 text-white focus:border-brand-500 focus:outline-none" />
            </label>
            <label class="space-y-2 text-sm text-gray-300">
                <span>Confirm Password</span>
                <input type="password" name="password_confirmation" required class="w-full rounded-xl border border-white/10 bg-slate-900 px-4 py-3 text-white focus:border-brand-500 focus:outline-none" />
            </label>
        </div>

        <div class="flex justify-between gap-4">
            <a href="{{ route('installer.database') }}" class="flex-1 bg-slate-700 hover:bg-slate-600 text-white font-medium py-3 rounded-lg transition text-center">← Back</a>
            <button type="submit" class="flex-1 bg-brand-600 hover:bg-brand-500 text-white font-medium py-3 rounded-lg transition">Create Admin →</button>
        </div>
    </form>
</div>
@endsection
