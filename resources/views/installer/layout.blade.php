<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PoriFyx Widget Installation</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        * { scroll-behavior: smooth; }
        .step-indicator { @apply flex items-center justify-between relative; }
        .step-dot { @apply flex items-center justify-center w-10 h-10 rounded-full text-sm font-bold transition; }
        .step-dot.active { @apply bg-brand-600 text-white; }
        .step-dot.completed { @apply bg-emerald-600 text-white; }
        .step-dot.pending { @apply bg-gray-300 text-gray-600; }
        .step-connector { @apply absolute top-5 h-0.5 bg-gray-300 transition; }
        .step-connector.active { @apply bg-brand-600; }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 min-h-screen">
    {{-- Background Elements --}}
    <div class="fixed inset-0 overflow-hidden pointer-events-none">
        <div class="absolute top-0 right-0 w-96 h-96 bg-brand-600/10 rounded-full blur-3xl"></div>
        <div class="absolute bottom-0 left-0 w-96 h-96 bg-accent-600/10 rounded-full blur-3xl"></div>
    </div>

    <div class="relative min-h-screen flex items-center justify-center p-4">
        <div class="w-full max-w-4xl">
            {{-- Header --}}
            <div class="text-center mb-8">
                <h1 class="text-4xl font-bold text-white mb-2">{{ config('branding.name', 'PoriFyx Widget') }}</h1>
                <p class="text-gray-400">Installation Wizard</p>
            </div>

            {{-- Main Container --}}
            <div class="bg-slate-800/50 backdrop-blur border border-white/10 rounded-2xl overflow-hidden shadow-2xl">
                {{-- Step Indicator --}}
                <div class="bg-slate-900/50 border-b border-white/5 p-8">
                    <div class="max-w-2xl mx-auto">
                        <div class="flex items-center justify-between gap-2">
                            <div class="step-dot {{ request()->route()->getName() === 'installer.welcome' ? 'active' : (in_array(request()->route()->getName(), ['installer.requirements', 'installer.permissions', 'installer.environment', 'installer.database', 'installer.admin', 'installer.finish']) ? 'completed' : 'pending') }}">1</div>
                            <div class="step-connector flex-1 {{ in_array(request()->route()->getName(), ['installer.requirements', 'installer.permissions', 'installer.environment', 'installer.database', 'installer.admin', 'installer.finish']) ? 'active' : '' }}"></div>
                            <div class="step-dot {{ request()->route()->getName() === 'installer.requirements' ? 'active' : (in_array(request()->route()->getName(), ['installer.permissions', 'installer.environment', 'installer.database', 'installer.admin', 'installer.finish']) ? 'completed' : 'pending') }}">2</div>
                            <div class="step-connector flex-1 {{ in_array(request()->route()->getName(), ['installer.permissions', 'installer.environment', 'installer.database', 'installer.admin', 'installer.finish']) ? 'active' : '' }}"></div>
                            <div class="step-dot {{ request()->route()->getName() === 'installer.permissions' ? 'active' : (in_array(request()->route()->getName(), ['installer.environment', 'installer.database', 'installer.admin', 'installer.finish']) ? 'completed' : 'pending') }}">3</div>
                            <div class="step-connector flex-1 {{ in_array(request()->route()->getName(), ['installer.environment', 'installer.database', 'installer.admin', 'installer.finish']) ? 'active' : '' }}"></div>
                            <div class="step-dot {{ request()->route()->getName() === 'installer.environment' ? 'active' : (in_array(request()->route()->getName(), ['installer.database', 'installer.admin', 'installer.finish']) ? 'completed' : 'pending') }}">4</div>
                            <div class="step-connector flex-1 {{ in_array(request()->route()->getName(), ['installer.database', 'installer.admin', 'installer.finish']) ? 'active' : '' }}"></div>
                            <div class="step-dot {{ request()->route()->getName() === 'installer.database' ? 'active' : (in_array(request()->route()->getName(), ['installer.admin', 'installer.finish']) ? 'completed' : 'pending') }}">5</div>
                            <div class="step-connector flex-1 {{ in_array(request()->route()->getName(), ['installer.admin', 'installer.finish']) ? 'active' : '' }}"></div>
                            <div class="step-dot {{ request()->route()->getName() === 'installer.admin' ? 'active' : (request()->route()->getName() === 'installer.finish' ? 'completed' : 'pending') }}">6</div>
                            <div class="step-connector flex-1 {{ request()->route()->getName() === 'installer.finish' ? 'active' : '' }}"></div>
                            <div class="step-dot {{ request()->route()->getName() === 'installer.finish' ? 'active' : 'pending' }}">✓</div>
                        </div>
                        <div class="flex justify-between mt-4 text-xs font-medium text-gray-500">
                            <span>Welcome</span>
                            <span>Requirements</span>
                            <span>Permissions</span>
                            <span>Configuration</span>
                            <span>Database</span>
                            <span>Admin</span>
                            <span>Finish</span>
                        </div>
                    </div>
                </div>

                {{-- Content --}}
                <div class="p-8">
                    @if ($errors->any())
                    <div class="mb-6 p-4 bg-rose-500/10 border border-rose-500/20 rounded-lg">
                        <p class="text-sm font-medium text-rose-200 mb-2">Validation Errors:</p>
                        <ul class="space-y-1">
                            @foreach ($errors->all() as $error)
                            <li class="text-sm text-rose-300">• {{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif

                    @if (session('success'))
                    <div class="mb-6 p-4 bg-emerald-500/10 border border-emerald-500/20 rounded-lg">
                        <p class="text-sm font-medium text-emerald-200">{{ session('success') }}</p>
                    </div>
                    @endif

                    @yield('content')
                </div>
            </div>

            {{-- Footer --}}
            <div class="text-center mt-8 text-gray-500 text-sm">
                <p>{{ config('branding.name', 'PoriFyx Widget') }} v1.0 • Installation</p>
            </div>
        </div>
    </div>
</body>
</html>
