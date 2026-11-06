@extends('layouts.settings')
@section('title', 'Notifications')
@section('page-title', 'Settings')
@section('settings')
<div class="w-full">
    @if(session('success'))
    <div class="mb-5 p-4 bg-emerald-500/10 border border-emerald-500/20 rounded-2xl text-sm text-emerald-400 flex items-center gap-2">
        <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd" /></svg>
        {{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div class="mb-5 p-4 bg-rose-500/10 border border-rose-500/20 rounded-2xl text-sm text-rose-400">{{ session('error') }}</div>
    @endif

    <form method="POST" action="{{ route('settings.notifications.update') }}">
        @csrf @method('PUT')
        @php $prefs = $user->notification_preferences ?? []; @endphp
        <div class="bg-gray-900/50 border border-white/5 rounded-2xl p-6 space-y-4">
            <h3 class="text-base font-semibold text-white">Email Notifications</h3>
            @foreach(['email_new_lead' => 'New lead captured', 'email_new_chat' => 'New conversation started', 'email_ai_fallback' => 'AI fallback triggered', 'email_weekly_report' => 'Weekly analytics report'] as $key => $label)
            <label class="flex items-center justify-between p-3 bg-white/[0.02] rounded-xl cursor-pointer hover:bg-white/[0.04] transition">
                <span class="text-sm text-gray-300">{{ $label }}</span>
                <input type="checkbox" name="{{ $key }}" value="1" {{ ($prefs[$key] ?? false) ? 'checked' : '' }} class="w-4 h-4 rounded bg-white/5 border-white/20 text-brand-600 focus:ring-brand-500/30">
            </label>
            @endforeach
        </div>
        <div class="bg-gray-900/50 border border-white/5 rounded-2xl p-6 space-y-4 mt-6">
            <h3 class="text-base font-semibold text-white">Telegram Notifications</h3>
            @foreach(['telegram_new_lead' => 'New lead on Telegram', 'telegram_hot_lead' => 'Hot lead alert on Telegram'] as $key => $label)
            <label class="flex items-center justify-between p-3 bg-white/[0.02] rounded-xl cursor-pointer hover:bg-white/[0.04] transition">
                <span class="text-sm text-gray-300">{{ $label }}</span>
                <input type="checkbox" name="{{ $key }}" value="1" {{ ($prefs[$key] ?? false) ? 'checked' : '' }} class="w-4 h-4 rounded bg-white/5 border-white/20 text-brand-600 focus:ring-brand-500/30">
            </label>
            @endforeach
        </div>
        <div class="bg-gray-900/50 border border-white/5 rounded-2xl p-6 space-y-4 mt-6">
            <h3 class="text-base font-semibold text-white">Browser Notifications</h3>
            <label class="flex items-center justify-between p-3 bg-white/[0.02] rounded-xl cursor-pointer hover:bg-white/[0.04] transition">
                <span class="text-sm text-gray-300">New message received</span>
                <input type="checkbox" name="browser_new_message" value="1" {{ ($prefs['browser_new_message'] ?? false) ? 'checked' : '' }} class="w-4 h-4 rounded bg-white/5 border-white/20 text-brand-600 focus:ring-brand-500/30">
            </label>
        </div>
        <button type="submit" class="mt-6 px-6 py-2.5 bg-brand-600 hover:bg-brand-500 text-white text-sm font-medium rounded-xl transition">Save Preferences</button>
    </form>
</div>
@endsection
