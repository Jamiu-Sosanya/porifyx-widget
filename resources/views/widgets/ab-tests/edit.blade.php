@extends('layouts.app')
@section('title', 'Edit A/B Test - ' . $widget->name)
@section('page-title', 'Edit A/B Test')
@section('content')
<div class="max-w-4xl">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <a href="{{ route('widgets.edit', $widget) }}" class="p-2 rounded-lg text-gray-400 hover:text-white hover:bg-white/5 transition">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg>
            </a>
            <div>
                <h2 class="text-lg font-semibold text-white">Edit A/B Test</h2>
                <p class="text-xs text-gray-500">Widget: {{ $widget->name }} · Test: {{ $abTest->name }}</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <form method="POST" action="{{ route('widgets.abTests.toggle', [$widget, $abTest]) }}">
                @csrf @method('PATCH')
                <button type="submit" class="px-3 py-2 text-xs font-medium rounded-xl transition {{ $abTest->is_active ? 'bg-emerald-500/10 text-emerald-400 hover:bg-emerald-500/20' : 'bg-gray-500/10 text-gray-400 hover:bg-gray-500/20' }}">
                    {{ $abTest->is_active ? 'Pause Test' : 'Resume Test' }}
                </button>
            </form>
            <form method="POST" action="{{ route('widgets.abTests.delete', [$widget, $abTest]) }}" onsubmit="return confirm('Delete this A/B test? This action cannot be undone.')">
                @csrf @method('DELETE')
                <button type="submit" class="px-3 py-2 text-xs font-medium rounded-xl bg-rose-500/10 text-rose-400 hover:bg-rose-500/20 transition">
                    Delete Test
                </button>
            </form>
        </div>
    </div>

    {{-- Test Stats --}}
    @if($abTest->impressions_a + $abTest->impressions_b > 0)
    <div class="bg-gray-900/50 border border-white/5 rounded-2xl p-6 mb-6">
        <h3 class="text-base font-semibold text-white mb-4">Test Results</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="text-center">
                <p class="text-lg font-semibold text-white">{{ $abTest->impressions_a + $abTest->impressions_b }}</p>
                <p class="text-xs text-gray-500">Total Impressions</p>
            </div>
            <div class="text-center">
                <p class="text-lg font-semibold text-white">{{ $abTest->conversations_a + $abTest->conversations_b }}</p>
                <p class="text-xs text-gray-500">Conversations</p>
            </div>
            <div class="text-center">
                <p class="text-lg font-semibold text-white">{{ $abTest->leads_a + $abTest->leads_b }}</p>
                <p class="text-xs text-gray-500">Leads</p>
            </div>
            <div class="text-center">
                <p class="text-lg font-semibold text-{{ $abTest->getWinner() === 'a' ? 'emerald' : ($abTest->getWinner() === 'b' ? 'emerald' : 'gray') }}-400">
                    {{ $abTest->getWinner() ? 'Variant ' . strtoupper($abTest->getWinner()) : 'No winner yet' }}
                </p>
                <p class="text-xs text-gray-500">Winner</p>
            </div>
        </div>

        {{-- Conversion Rates --}}
        <div class="mt-4 pt-4 border-t border-white/5">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-sm font-medium text-gray-300 mb-2">Variant A</p>
                    <div class="text-xs text-gray-500 space-y-1">
                        <div>Impressions: {{ $abTest->impressions_a }}</div>
                        <div>Conversations: {{ $abTest->conversations_a }}</div>
                        <div>Leads: {{ $abTest->leads_a }}</div>
                        <div>Conversion: {{ $abTest->getLeadConversionRate('a') }}%</div>
                    </div>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-300 mb-2">Variant B</p>
                    <div class="text-xs text-gray-500 space-y-1">
                        <div>Impressions: {{ $abTest->impressions_b }}</div>
                        <div>Conversations: {{ $abTest->conversations_b }}</div>
                        <div>Leads: {{ $abTest->leads_b }}</div>
                        <div>Conversion: {{ $abTest->getLeadConversionRate('b') }}%</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <form method="POST" action="{{ route('widgets.abTests.update', [$widget, $abTest]) }}" class="bg-gray-900/50 border border-white/5 rounded-2xl p-6 space-y-6">
        @csrf @method('PATCH')

        {{-- Test Name --}}
        <div>
            <label class="block text-sm font-medium text-gray-300 mb-1.5">Test Name</label>
            <input name="name" value="{{ old('name', $abTest->name) }}" required class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-brand-500/50 focus:ring-2 focus:ring-brand-500/20 transition">
        </div>

        {{-- Split Ratio --}}
        <div>
            <label class="block text-sm font-medium text-gray-300 mb-1.5">Traffic Split</label>
            <select name="split_ratio" required class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-brand-500/50 transition">
                <option value="0.5" {{ old('split_ratio', $abTest->split_ratio) == '0.5' ? 'selected' : '' }}>50/50 Split (50% A, 50% B)</option>
                <option value="0.6" {{ old('split_ratio', $abTest->split_ratio) == '0.6' ? 'selected' : '' }}>60/40 Split (60% A, 40% B)</option>
                <option value="0.7" {{ old('split_ratio', $abTest->split_ratio) == '0.7' ? 'selected' : '' }}>70/30 Split (70% A, 30% B)</option>
                <option value="0.8" {{ old('split_ratio', $abTest->split_ratio) == '0.8' ? 'selected' : '' }}>80/20 Split (80% A, 20% B)</option>
                <option value="0.9" {{ old('split_ratio', $abTest->split_ratio) == '0.9' ? 'selected' : '' }}>90/10 Split (90% A, 10% B)</option>
            </select>
        </div>

        {{-- Variant A --}}
        <div class="p-4 bg-white/[0.02] border border-white/5 rounded-2xl space-y-4">
            <h3 class="text-sm font-semibold text-white">Variant A (Control)</h3>

            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1.5">Welcome Message</label>
                <textarea name="variant_a_welcome_message" rows="2" required class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-brand-500/50 transition resize-none">{{ old('variant_a_welcome_message', $abTest->variant_a['welcome_message']) }}</textarea>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1.5">Primary Color</label>
                    <input name="variant_a_primary_color" type="color" value="{{ old('variant_a_primary_color', $abTest->variant_a['primary_color'] ?? $widget->primary_color) }}" class="w-full h-10 bg-white/5 border border-white/10 rounded-xl cursor-pointer">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1.5">Position</label>
                    <select name="variant_a_position" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-brand-500/50 transition">
                        @foreach(['bottom-right' => 'Bottom Right', 'bottom-left' => 'Bottom Left', 'top-right' => 'Top Right', 'top-left' => 'Top Left'] as $val => $lbl)
                        <option value="{{ $val }}" {{ old('variant_a_position', $abTest->variant_a['position'] ?? $widget->position) === $val ? 'selected' : '' }}>{{ $lbl }}</option>
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
                <textarea name="variant_b_welcome_message" rows="2" required class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-brand-500/50 transition resize-none">{{ old('variant_b_welcome_message', $abTest->variant_b['welcome_message']) }}</textarea>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1.5">Primary Color</label>
                    <input name="variant_b_primary_color" type="color" value="{{ old('variant_b_primary_color', $abTest->variant_b['primary_color'] ?? $widget->primary_color) }}" class="w-full h-10 bg-white/5 border border-white/10 rounded-xl cursor-pointer">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1.5">Position</label>
                    <select name="variant_b_position" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-brand-500/50 transition">
                        @foreach(['bottom-right' => 'Bottom Right', 'bottom-left' => 'Bottom Left', 'top-right' => 'Top Right', 'top-left' => 'Top Left'] as $val => $lbl)
                        <option value="{{ $val }}" {{ old('variant_b_position', $abTest->variant_b['position'] ?? $widget->position) === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex items-center gap-3 pt-4 border-t border-white/5">
            <button type="submit" class="px-6 py-2.5 bg-brand-600 hover:bg-brand-500 text-white text-sm font-medium rounded-xl transition shadow-lg shadow-brand-500/20">
                Update Test
            </button>
            <a href="{{ route('widgets.edit', $widget) }}" class="px-4 py-2.5 text-sm text-gray-400 hover:text-white transition">Cancel</a>
        </div>
    </form>
</div>
@endsection