<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" id="html-root" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#2f5ee8">
    <meta name="application-name" content="{{ config('app.name', 'PoriFyx Widget') }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Chat Widget">
    <meta name="description" content="AI-powered chat widget for lead generation and customer engagement">
    <meta name="push-public-key" content="{{ config('services.vapid.public_key') }}">
    <meta name="msapplication-TileColor" content="#2f5ee8">
    <meta name="msapplication-TileImage" content="/images/icon-192x192.png">
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="/images/icon-192x192.png">
    <link rel="icon" type="image/png" sizes="192x192" href="/images/icon-192x192.png">
    <link rel="icon" type="image/png" sizes="512x512" href="/images/icon-512x512.png">
    
    <title>@yield('title', 'Dashboard') — {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:300,400,500,600,700,800&display=swap" rel="stylesheet" />
    {{-- Apply saved theme before CSS renders to prevent flash --}}
    <script>
        (function() {
            var t = localStorage.getItem('theme') || 'dark';
            document.documentElement.classList.remove('dark', 'light');
            document.documentElement.classList.add(t);
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="bg-gray-950 text-gray-200 antialiased"
      x-data="{
          sidebarOpen: false,
          sidebarCollapsed: localStorage.getItem('sidebarCollapsed') === 'true',
          theme: localStorage.getItem('theme') || 'dark',
          toggleTheme() {
              this.theme = this.theme === 'dark' ? 'light' : 'dark';
              document.documentElement.classList.remove('dark', 'light');
              document.documentElement.classList.add(this.theme);
              localStorage.setItem('theme', this.theme);
          }
      }">
    <div class="flex h-screen overflow-hidden">

        {{-- Sidebar --}}
        @include('layouts.partials.sidebar')

        {{-- Main Content --}}
        <div class="flex-1 flex flex-col overflow-hidden" :class="sidebarCollapsed ? 'lg:ml-20' : 'lg:ml-64'" style="transition: margin-left 0.3s ease">
            {{-- Top Bar --}}
            @include('layouts.partials.topbar')

            {{-- Page Content --}}
            <main class="flex-1 overflow-y-auto">
                {{-- Demo Mode Banner --}}
                @if(env('DEMO_MODE') === 'true' || env('DEMO_MODE') === true || env('DEMO_MODE') === '1')
                <div class="sticky top-0 z-30 flex items-center justify-center gap-2 bg-amber-500 px-4 py-2 text-xs font-semibold text-amber-950">
                    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></svg>
                    Demo Mode Active — all changes are disabled. This is a read-only preview.
                </div>
                @endif
                {{-- Flash Messages --}}
                @if(session('success'))
                <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
                     x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-2"
                     class="mx-6 mt-4">
                    <div class="p-4 bg-emerald-500/10 border border-emerald-500/20 rounded-xl text-emerald-400 text-sm flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                            {{ session('success') }}
                        </div>
                        <button @click="show = false" class="text-emerald-400/60 hover:text-emerald-400"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg></button>
                    </div>
                </div>
                @endif
                @if(session('error'))
                <div x-data="{ show: true }" x-show="show" class="mx-6 mt-4">
                    <div class="p-4 bg-rose-500/10 border border-rose-500/20 rounded-xl text-rose-400 text-sm flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" /></svg>
                            {{ session('error') }}
                        </div>
                        <button @click="show = false" class="text-rose-400/60 hover:text-rose-400"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg></button>
                    </div>
                </div>
                @endif

                @if(session('impersonating'))
                <div class="mx-6 mt-4 p-3 bg-amber-500/10 border border-amber-500/20 rounded-xl text-amber-400 text-sm flex items-center justify-between">
                    <span>⚡ You are impersonating <strong>{{ auth()->user()->name }}</strong></span>
                    <a href="{{ route('admin.stopImpersonating') }}" class="px-3 py-1 bg-amber-500/20 hover:bg-amber-500/30 rounded-lg text-xs font-medium transition">Stop</a>
                </div>
                @endif

                <div class="p-6">
                    @yield('content')
                </div>
            </main>
        </div>
    </div>

    @stack('modals')
    @stack('scripts')

    <!-- PWA Manager -->
    <script src="{{ asset('js/pwa.js') }}" defer></script>

    @include('layouts.partials.cookie-consent')
</body>
</html>
