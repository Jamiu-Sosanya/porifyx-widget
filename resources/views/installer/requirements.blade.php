@extends('installer.layout')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-white mb-2">Server Requirements</h2>
        <p class="text-gray-400">Verify that your server meets all requirements to run {{ config('branding.name', 'PoriFyx Widget') }}</p>
    </div>

    <div class="space-y-3 mb-8">
        @foreach($requirements as $req)
        <div class="flex items-center gap-4 p-4 bg-slate-700/30 border {{ $req['status'] ? 'border-emerald-500/20' : 'border-rose-500/20' }} rounded-lg">
            <div class="flex-shrink-0">
                @if($req['status'])
                    <svg class="w-6 h-6 text-emerald-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                @else
                    <svg class="w-6 h-6 text-rose-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                @endif
            </div>
            <div class="flex-1 min-w-0">
                <p class="font-medium text-white">{{ $req['name'] }}</p>
                <p class="text-sm text-gray-500">Required: {{ $req['version'] ?? 'Enabled' }} • Current: {{ $req['current'] ?? 'N/A' }}</p>
            </div>
            <div>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium {{ $req['status'] ? 'bg-emerald-500/10 text-emerald-300' : 'bg-rose-500/10 text-rose-300' }}">
                    {{ $req['status'] ? '✓ OK' : '✗ FAILED' }}
                </span>
            </div>
        </div>
        @endforeach
    </div>

    @if(!$allPassed)
    <div class="bg-rose-500/10 border border-rose-500/20 rounded-xl p-4 mb-8">
        <p class="text-sm text-rose-200"><strong>⚠️ Error:</strong> Some requirements are not met. Please fix them before proceeding.</p>
    </div>
    @else
    <div class="bg-emerald-500/10 border border-emerald-500/20 rounded-xl p-4 mb-8">
        <p class="text-sm text-emerald-200"><strong>✓ All requirements met!</strong> You can proceed to the next step.</p>
    </div>
    @endif

    <div class="flex gap-4">
        <a href="{{ route('installer.welcome') }}" class="flex-1 bg-slate-700 hover:bg-slate-600 text-white font-medium py-3 rounded-lg transition text-center">
            ← Back
        </a>
        @if($allPassed)
        <a href="{{ route('installer.permissions') }}" class="flex-1 bg-brand-600 hover:bg-brand-500 text-white font-medium py-3 rounded-lg transition text-center">
            Continue →
        </a>
        @else
        <button disabled class="flex-1 bg-gray-700 text-gray-400 font-medium py-3 rounded-lg cursor-not-allowed text-center">
            Continue →
        </button>
        @endif
    </div>
</div>
@endsection
