@extends('layouts.app')
@section('title', 'Create Widget')
@section('page-title', 'Create Widget')
@section('content')
<div class="max-w-2xl">
    <div class="bg-gray-900/50 border border-white/5 rounded-2xl p-6">
        <h3 class="text-lg font-semibold text-white mb-1">New Widget</h3>
        <p class="text-sm text-gray-400 mb-6">Create your AI chat widget. You can customize it further after creation.</p>

        <form method="POST" action="{{ route('widgets.store') }}" class="space-y-5">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1.5">Widget Name</label>
                <input name="name" type="text" value="{{ old('name') }}" required placeholder="e.g. Support Bot, Sales Assistant"
                       class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-gray-500 focus:outline-none focus:border-brand-500/50 focus:ring-2 focus:ring-brand-500/20 transition">
                @error('name')<p class="mt-1 text-sm text-rose-400">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1.5">AI Assistant Name</label>
                <input name="ai_name" type="text" value="{{ old('ai_name', 'AI Assistant') }}" placeholder="e.g. Aria, ChatBot, SupportBot"
                       class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-gray-500 focus:outline-none focus:border-brand-500/50 focus:ring-2 focus:ring-brand-500/20 transition">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1.5">Tone</label>
                <select name="tone" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-brand-500/50 focus:ring-2 focus:ring-brand-500/20 transition">
                    <option value="friendly" {{ old('tone') == 'friendly' ? 'selected' : '' }}>😊 Friendly</option>
                    <option value="professional" {{ old('tone') == 'professional' ? 'selected' : '' }}>💼 Professional</option>
                    <option value="sales" {{ old('tone') == 'sales' ? 'selected' : '' }}>🚀 Sales</option>
                    <option value="support" {{ old('tone') == 'support' ? 'selected' : '' }}>🎧 Support</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1.5">Brand Color</label>
                <div class="flex items-center gap-3">
                    <input name="primary_color" type="color" value="{{ old('primary_color', '#ff6a2b') }}" class="w-12 h-12 rounded-xl border border-white/10 bg-transparent cursor-pointer">
                    <input type="text" value="{{ old('primary_color', '#ff6a2b') }}" readonly class="flex-1 bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white text-sm font-mono">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1.5">Welcome Message</label>
                <textarea name="welcome_message" rows="3" placeholder="Hi! How can I help you today?"
                          class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-gray-500 focus:outline-none focus:border-brand-500/50 focus:ring-2 focus:ring-brand-500/20 transition resize-none">{{ old('welcome_message', 'Hi! How can I help you today?') }}</textarea>
            </div>
            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="px-6 py-2.5 bg-gradient-to-r from-brand-600 to-accent-600 hover:from-brand-500 hover:to-accent-500 text-white text-sm font-medium rounded-xl transition shadow-lg shadow-brand-500/20">
                    Create Widget
                </button>
                <a href="{{ route('widgets.index') }}" class="px-4 py-2.5 text-sm text-gray-400 hover:text-white transition">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
