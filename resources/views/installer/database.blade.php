@extends('installer.layout')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-white mb-2">Database Setup</h2>
        <p class="text-gray-400">Run migrations and optionally load demo data.</p>
    </div>

    <form action="{{ route('installer.migrate') }}" method="POST" class="space-y-6">
        @csrf

        <div class="rounded-2xl border border-white/10 bg-slate-900/60 p-6 space-y-4">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="font-semibold text-white">Migration & Seeding</p>
                    <p class="text-sm text-gray-400">This step will create database tables and seed initial data.</p>
                </div>
                <span class="inline-flex rounded-full bg-brand-500/10 px-3 py-1 text-xs font-medium text-brand-200">Using DB config from .env</span>
            </div>

            <label class="flex items-center gap-3 text-sm text-gray-300">
                <input type="checkbox" name="seed_demo_data" value="1" class="h-4 w-4 rounded border-gray-300 bg-slate-800 text-brand-500 focus:ring-brand-400" />
                <span>Load demo seed data after migrations</span>
            </label>

            <p class="text-xs text-gray-500">If you already have a database, skip demo seeding to preserve your data.</p>
        </div>

        <div class="flex justify-between gap-4">
            <a href="{{ route('installer.environment') }}" class="flex-1 bg-slate-700 hover:bg-slate-600 text-white font-medium py-3 rounded-lg transition text-center">← Back</a>
            <button type="submit" class="flex-1 bg-brand-600 hover:bg-brand-500 text-white font-medium py-3 rounded-lg transition">Run Migrations →</button>
        </div>
    </form>
</div>
@endsection
