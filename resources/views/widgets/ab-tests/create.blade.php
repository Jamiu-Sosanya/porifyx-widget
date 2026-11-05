@extends('layouts.app')
@section('title', 'Create A/B Test - ' . $widget->name)
@section('page-title', 'Create A/B Test')
@section('content')
<div class="max-w-4xl">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <a href="{{ route('widgets.edit', $widget) }}" class="p-2 rounded-lg text-gray-400 hover:text-white hover:bg-white/5 transition">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg>
            </a>
            <div>
                <h2 class="text-lg font-semibold text-white">Create A/B Test</h2>
                <p class="text-xs text-gray-500">Widget: {{ $widget->name }}</p>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('widgets.abTests.store', $widget) }}" class="bg-gray-900/50 border border-white/5 rounded-2xl p-6 space-y-6">
        @csrf

        {{-- Test Name --}}
        <div>
            <label class="block text-sm font-medium text-gray-300 mb-1.5">Test Name</label>
            <input name="name" value="{{ old('name') }}" required class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-brand-500/50 focus:ring-2 focus:ring-brand-500/20 transition">
            <p class="mt-1 text-xs text-gray-500">Give your test a descriptive name like "Welcome Message Test" or "Color Scheme A/B"</p>
        </div>

        {{-- Split Ratio --}}
        <div>
            <label class="block text-sm font-medium text-gray-300 mb-1.5">Traffic Split</label>
            <select name="split_ratio" required class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-brand-500/50 transition">
                <option value="0.5" {{ old('split_ratio', '0.5') == '0.5' ? 'selected' : '' }}>50/50 Split (50% A, 50% B)</option>
                <option value="0.6" {{ old('split_ratio') == '0.6' ? 'selected' : '' }}>60/40 Split (60% A, 40% B)</option>
                <option value="0.7" {{ old('split_ratio') == '0.7' ? 'selected' : '' }}>70/30 Split (70% A, 30% B)</option>
                <option value="0.8" {{ old('split_ratio') == '0.8' ? 'selected' : '' }}>80/20 Split (80% A, 20% B)</option>
                <option value="0.9" {{ old('split_ratio') == '0.9' ? 'selected' : '' }}>90/10 Split (90% A, 10% B)</option>
            </select>
            <p class="mt-1 text-xs text-gray-500">How to split traffic between the two variants. Start with 50/50 for equal testing.</p>
        </div>

        {{-- Variant A --}}
        <div class="p-4 bg-white/[0.02] border border-white/5 rounded-2xl space-y-4">
            <h3 class="text-sm font-semibold text-white">Variant A (Control)</h3>

            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1.5">Welcome Message</label>
                <textarea name="variant_a_welcome_message" rows="2" required class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-brand-500/50 transition resize-none">{{ old('variant_a_welcome_message', $widget->welcome_message) }}</textarea>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1.5">Primary Color</label>
                    <input name="variant_a_primary_color" type="color" value="{{ old('variant_a_primary_color', $widget->primary_color) }}" class="w-full h-10 bg-white/5 border border-white/10 rounded-xl cursor-pointer">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1.5">Position</label>
                    <select name="variant_a_position" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-brand-500/50 transition">
                        @foreach(['bottom-right' => 'Bottom Right', 'bottom-left' => 'Bottom Left', 'top-right' => 'Top Right', 'top-left' => 'Top Left'] as $val => $lbl)
                        <option value="{{ $val }}" {{ old('variant_a_position', $widget->position) === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- Variant B --}}
        <div class="p-4 bg-white/[0.02] border border-white/5 rounded-2xl space-y-4">
            <h3 class="text-sm font-semibold text-white">Variant B (Test)</h3>

            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1.5">Welcome Message</label>
                <textarea name="variant_b_welcome_message" rows="2" required class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-brand-500/50 transition resize-none">{{ old('variant_b_welcome_message') }}</textarea>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1.5">Primary Color</label>
                    <input name="variant_b_primary_color" type="color" value="{{ old('variant_b_primary_color', $widget->primary_color) }}" class="w-full h-10 bg-white/5 border border-white/10 rounded-xl cursor-pointer">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1.5">Position</label>
                    <select name="variant_b_position" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-brand-500/50 transition">
                        @foreach(['bottom-right' => 'Bottom Right', 'bottom-left' => 'Bottom Left', 'top-right' => 'Top Right', 'top-left' => 'Top Left'] as $val => $lbl)
                        <option value="{{ $val }}" {{ old('variant_b_position', $widget->position) === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex items-center gap-3 pt-4 border-t border-white/5">
            <button type="submit" class="px-6 py-2.5 bg-brand-600 hover:bg-brand-500 text-white text-sm font-medium rounded-xl transition shadow-lg shadow-brand-500/20">
                Create & Start Test
            </button>
            <a href="{{ route('widgets.edit', $widget) }}" class="px-4 py-2.5 text-sm text-gray-400 hover:text-white transition">Cancel</a>
        </div>
    </form>

    {{-- Help Text --}}
    <div class="mt-6 p-4 bg-brand-500/5 border border-brand-500/10 rounded-xl text-sm text-brand-300">
        💡 <strong>How A/B testing works:</strong> Visitors are randomly assigned to variants based on their fingerprint. The test runs until you manually stop it. Track conversion rates, conversations, and leads to determine the winner. Tests can be paused/resumed at any time.
    </div>
</div>
@endsection