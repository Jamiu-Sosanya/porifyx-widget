@extends('installer.layout')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="text-center mb-8">
        <div class="w-16 h-16 bg-brand-600/20 rounded-2xl flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-brand-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>
        <h2 class="text-3xl font-bold text-white mb-2">Welcome to Installation</h2>
        <p class="text-gray-400">Let's set up {{ config('branding.name', 'PoriFyx Widget') }} on your server</p>
    </div>

    <div class="bg-slate-700/30 border border-white/10 rounded-xl p-6 mb-8 space-y-4">
        <div class="flex items-start gap-3">
            <svg class="w-5 h-5 text-brand-400 mt-1 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
            </svg>
            <div>
                <p class="font-semibold text-white">Server Requirements Check</p>
                <p class="text-sm text-gray-400">We'll verify your server has all necessary extensions</p>
            </div>
        </div>

        <div class="flex items-start gap-3">
            <svg class="w-5 h-5 text-brand-400 mt-1 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
            </svg>
            <div>
                <p class="font-semibold text-white">File Permissions</p>
                <p class="text-sm text-gray-400">Ensure your system can write to required directories</p>
            </div>
        </div>

        <div class="flex items-start gap-3">
            <svg class="w-5 h-5 text-brand-400 mt-1 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
            </svg>
            <div>
                <p class="font-semibold text-white">Database Configuration</p>
                <p class="text-sm text-gray-400">Set up your database connection and run migrations</p>
            </div>
        </div>

        <div class="flex items-start gap-3">
            <svg class="w-5 h-5 text-brand-400 mt-1 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
            </svg>
            <div>
                <p class="font-semibold text-white">Admin Account Setup</p>
                <p class="text-sm text-gray-400">Create your administrator account to manage the system</p>
            </div>
        </div>
    </div>

    <div class="bg-amber-500/10 border border-amber-500/20 rounded-xl p-4 mb-8">
        <p class="text-sm text-amber-200">❕ <strong>Tip:</strong> This wizard will only be available until after installation is complete. Make sure you have your database credentials ready before proceeding.</p>
    </div>

    <div class="flex gap-4">
        <a href="{{ route('installer.requirements') }}" class="flex-1 bg-brand-600 hover:bg-brand-500 text-white font-medium py-3 rounded-lg transition text-center">
            Start Installation →
        </a>
    </div>
</div>
@endsection
