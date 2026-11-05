@extends('layouts.app')
@section('title', 'Offline')
@section('content')
<div class="max-w-md mx-auto mt-12 text-center">
    <div class="mb-6">
        <svg class="w-24 h-24 mx-auto text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0zm0 0h-.375M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z" />
        </svg>
    </div>
    <h1 class="text-3xl font-bold text-white mb-4">You're Offline</h1>
    <p class="text-gray-300 mb-8">It looks like you've lost your internet connection. Some features may not be available.</p>
    
    <div class="bg-gray-900/50 border border-white/5 rounded-xl p-6 mb-8">
        <h2 class="text-lg font-semibold text-white mb-4">What you can do:</h2>
        <ul class="text-left space-y-3 text-gray-300">
            <li class="flex items-start gap-3">
                <svg class="w-5 h-5 text-brand-400 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                <span>View previously loaded pages</span>
            </li>
            <li class="flex items-start gap-3">
                <svg class="w-5 h-5 text-brand-400 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                <span>Access your dashboard (cached)</span>
            </li>
            <li class="flex items-start gap-3">
                <svg class="w-5 h-5 text-brand-400 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                <span>Changes will sync when you're back online</span>
            </li>
        </ul>
    </div>

    <button onclick="tryReconnect()" class="w-full px-4 py-3 bg-brand-600 hover:bg-brand-700 text-white font-medium rounded-lg transition mb-4">
        Try Reconnecting
    </button>
    <a href="/dashboard" class="block px-4 py-3 bg-gray-800 hover:bg-gray-700 text-white font-medium rounded-lg transition">
        Go to Dashboard (Cached)
    </a>
</div>

<script>
function tryReconnect() {
    window.location.reload();
}

// Auto-reload when connection is restored
window.addEventListener('online', () => {
    console.log('Connection restored');
    // You could show a toast or redirect here
    const interval = setInterval(() => {
        fetch('/api/health').then(() => {
            clearInterval(interval);
            window.location.href = '/dashboard';
        }).catch(() => {
            // Still offline
        });
    }, 1000);
});
</script>
@endsection
