@extends('installer.layout')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-white mb-2">Application Environment</h2>
        <p class="text-gray-400">Enter your application name, URL, and database credentials.</p>
    </div>

    <form action="{{ route('installer.environment.save') }}" method="POST" class="space-y-6">
        @csrf

        <div class="grid gap-4 md:grid-cols-2">
            <label class="space-y-2 text-sm text-gray-300">
                <span>App Name</span>
                <input type="text" name="app_name" value="{{ old('app_name', config('app.name', 'PoriFyx Widget')) }}" required class="w-full rounded-xl border border-white/10 bg-slate-900 px-4 py-3 text-white focus:border-brand-500 focus:outline-none" />
            </label>
            <label class="space-y-2 text-sm text-gray-300">
                <span>App URL</span>
                <input type="url" name="app_url" value="{{ old('app_url', request()->getSchemeAndHttpHost()) }}" required class="w-full rounded-xl border border-white/10 bg-slate-900 px-4 py-3 text-white focus:border-brand-500 focus:outline-none" />
            </label>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <label class="space-y-2 text-sm text-gray-300">
                <span>DB Connection</span>
                <select name="db_connection" class="w-full rounded-xl border border-white/10 bg-slate-900 px-4 py-3 text-white focus:border-brand-500 focus:outline-none">
                    <option value="mysql" {{ old('db_connection') === 'mysql' ? 'selected' : '' }}>MySQL</option>
                    <option value="pgsql" {{ old('db_connection') === 'pgsql' ? 'selected' : '' }}>PostgreSQL</option>
                    <option value="sqlite" {{ old('db_connection') === 'sqlite' ? 'selected' : '' }}>SQLite</option>
                    <option value="sqlsrv" {{ old('db_connection') === 'sqlsrv' ? 'selected' : '' }}>SQL Server</option>
                </select>
            </label>
            <label class="space-y-2 text-sm text-gray-300">
                <span>DB Host</span>
                <input type="text" name="db_host" value="{{ old('db_host', '127.0.0.1') }}" required class="w-full rounded-xl border border-white/10 bg-slate-900 px-4 py-3 text-white focus:border-brand-500 focus:outline-none" />
            </label>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <label class="space-y-2 text-sm text-gray-300">
                <span>DB Port</span>
                <input type="number" name="db_port" value="{{ old('db_port', '3306') }}" required class="w-full rounded-xl border border-white/10 bg-slate-900 px-4 py-3 text-white focus:border-brand-500 focus:outline-none" />
            </label>
            <label class="space-y-2 text-sm text-gray-300">
                <span>DB Name</span>
                <input type="text" name="db_database" value="{{ old('db_database', 'porifyx_widget') }}" required class="w-full rounded-xl border border-white/10 bg-slate-900 px-4 py-3 text-white focus:border-brand-500 focus:outline-none" />
            </label>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <label class="space-y-2 text-sm text-gray-300">
                <span>DB Username</span>
                <input type="text" name="db_username" value="{{ old('db_username', 'root') }}" required class="w-full rounded-xl border border-white/10 bg-slate-900 px-4 py-3 text-white focus:border-brand-500 focus:outline-none" />
            </label>
            <label class="space-y-2 text-sm text-gray-300">
                <span>DB Password</span>
                <input type="password" name="db_password" value="{{ old('db_password') }}" class="w-full rounded-xl border border-white/10 bg-slate-900 px-4 py-3 text-white focus:border-brand-500 focus:outline-none" />
            </label>
        </div>

        <div class="flex justify-between gap-4">
            <a href="{{ route('installer.permissions') }}" class="flex-1 bg-slate-700 hover:bg-slate-600 text-white font-medium py-3 rounded-lg transition text-center">← Back</a>
            <button type="submit" class="flex-1 bg-brand-600 hover:bg-brand-500 text-white font-medium py-3 rounded-lg transition">Save & Continue →</button>
        </div>
    </form>
</div>
@endsection
