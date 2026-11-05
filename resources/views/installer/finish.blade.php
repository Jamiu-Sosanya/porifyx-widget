@extends('installer.layout')

@section('content')
<div class="max-w-2xl mx-auto text-center">
    <div class="mb-8">
        <div class="w-20 h-20 mx-auto rounded-3xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center mb-6">
            <svg class="w-10 h-10 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
            </svg>
        </div>
        <h2 class="text-3xl font-bold text-white mb-2">Installation Complete</h2>
        <p class="text-gray-400 mb-6">Your application has been configured successfully. You can now log in with your administrator account.</p>
    </div>

    <div class="rounded-3xl border border-white/10 bg-slate-900/60 p-8 space-y-4">
        <div class="text-left text-gray-300">
            <p><strong>Installation completed at:</strong> {{ now()->format('F j, Y \a\t H:i') }}</p>
            <p><strong>Environment file:</strong> .env created and updated</p>
            <p><strong>Admin user:</strong> Created successfully</p>
        </div>

        <div class="space-y-3 text-sm text-gray-400">
            <p>If this site is running from the project root, the root <code class="bg-slate-800 px-2 py-1 rounded">.htaccess</code> file will route requests into <code class="bg-slate-800 px-2 py-1 rounded">public/</code>.</p>
            <p>If you have any additional deployment steps, configure your webserver to use the public directory as the document root.</p>
        </div>

        <div class="flex flex-col gap-4 pt-4 sm:flex-row">
            <a href="{{ route('login') }}" class="w-full bg-emerald-600 hover:bg-emerald-500 text-white font-medium py-3 rounded-lg transition">Go to Login</a>
            <a href="{{ url('/') }}" class="w-full bg-slate-700 hover:bg-slate-600 text-gray-100 font-medium py-3 rounded-lg transition">View Site</a>
        </div>
    </div>
</div>
@endsection
