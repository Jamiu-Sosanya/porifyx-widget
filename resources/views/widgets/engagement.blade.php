@extends('layouts.app')
@section('title', 'Engagement: ' . $widget->name)
@section('page-title', 'Engagement')
@section('content')
@php
    $field = 'w-full bg-white/5 border border-white/10 rounded-xl px-3.5 py-2.5 text-sm text-white';
    $types = \App\Http\Controllers\EngagementController::TRIGGERS;
    $n = $s['ai_notice'];
@endphp
<div class="grid xl:grid-cols-2 gap-6">

    {{-- Behaviour triggers --}}
    <section class="rounded-2xl border border-white/5 bg-gray-900/50 p-5 sm:p-6 min-w-0" x-data="{ type: 'time_on_page' }">
        <h3 class="text-base font-semibold text-white">Behaviour triggers</h3>
        <p class="mt-1 text-xs text-gray-500">Open a conversation at the right moment. Each trigger shows at most once per visit.</p>

        <form method="POST" action="{{ route('widgets.triggers.store', $widget) }}" class="mt-4 grid sm:grid-cols-2 gap-3">
            @csrf
            <select name="trigger_type" x-model="type" class="{{ $field }}">
                @foreach($types as $k => [$label]) <option value="{{ $k }}">{{ $label }}</option> @endforeach
            </select>
            <input name="trigger_value" type="number" min="0" max="3600" x-show="type !== 'exit_intent'"
                   :placeholder="{ time_on_page: 'After how many seconds?', scroll_depth: 'At what % of the page?', idle: 'Idle for how many seconds?' }[type]" class="{{ $field }}">
            <textarea name="message" required maxlength="500" rows="2" placeholder="Message, e.g. Looking for pricing? I can help." class="{{ $field }} sm:col-span-2"></textarea>
            <input name="target_url_pattern" maxlength="200" placeholder="Only on pages (optional), e.g. /pricing or /blog/*" class="{{ $field }}">
            <input name="quick_replies" maxlength="300" placeholder="Quick replies, comma separated (optional)" class="{{ $field }}">
            <button class="sm:col-span-2 justify-self-start px-5 py-2.5 rounded-xl bg-brand-500 text-white text-sm font-semibold">Add trigger</button>
        </form>

        <div class="mt-5 space-y-2">
            @forelse($triggers as $tr)
            <details class="rounded-xl border border-white/5 bg-white/[0.02]">
                <summary class="flex items-center gap-3 px-4 py-3 cursor-pointer list-none">
                    <span class="w-2 h-2 shrink-0 rounded-full {{ $tr->is_active ? 'bg-emerald-400' : 'bg-gray-600' }}"></span>
                    <span class="flex-1 min-w-0">
                        <span class="block truncate text-sm text-gray-200">{{ $tr->message }}</span>
                        <span class="block text-[11px] text-gray-500">{{ $types[$tr->trigger_type][0] ?? $tr->trigger_type }}{{ ($types[$tr->trigger_type][1] ?? null) ? ' · ' . $tr->trigger_value . ' ' . $types[$tr->trigger_type][1] : '' }}{{ $tr->target_url_pattern ? ' · ' . $tr->target_url_pattern : '' }}</span>
                    </span>
                    <span class="shrink-0 text-[11px] text-gray-500">{{ $tr->shown_count }} shown · {{ $tr->clicked_count }} clicked</span>
                </summary>
                <form method="POST" action="{{ route('widgets.triggers.update', [$widget, $tr]) }}" class="px-4 pb-4 grid sm:grid-cols-2 gap-2.5">
                    @csrf @method('PUT')
                    <select name="trigger_type" class="{{ $field }}">@foreach($types as $k => [$label])<option value="{{ $k }}" @selected($tr->trigger_type === $k)>{{ $label }}</option>@endforeach</select>
                    <input name="trigger_value" type="number" min="0" max="3600" value="{{ $tr->trigger_value }}" class="{{ $field }}">
                    <textarea name="message" required maxlength="500" rows="2" class="{{ $field }} sm:col-span-2">{{ $tr->message }}</textarea>
                    <input name="target_url_pattern" value="{{ $tr->target_url_pattern }}" maxlength="200" placeholder="Only on pages" class="{{ $field }}">
                    <input name="quick_replies" value="{{ implode(', ', $tr->quick_replies ?? []) }}" maxlength="300" placeholder="Quick replies" class="{{ $field }}">
                    <div class="sm:col-span-2 flex flex-wrap items-center gap-3">
                        <label class="flex items-center gap-2 text-xs text-gray-300"><input type="checkbox" name="is_active" value="1" @checked($tr->is_active)> Active</label>
                        <button class="ml-auto px-4 py-2 rounded-lg bg-brand-500 text-white text-xs font-semibold">Save</button>
                        <button form="del-tr-{{ $tr->id }}" class="px-4 py-2 rounded-lg bg-rose-500/10 text-rose-400 text-xs font-semibold">Delete</button>
                    </div>
                </form>
                <form id="del-tr-{{ $tr->id }}" method="POST" action="{{ route('widgets.triggers.destroy', [$widget, $tr]) }}" onsubmit="return confirm('Delete this trigger?')">@csrf @method('DELETE')</form>
            </details>
            @empty
            <p class="py-6 text-center text-sm text-gray-500">No triggers yet.</p>
            @endforelse
        </div>
    </section>

    {{-- Settings --}}
    <form method="POST" action="{{ route('widgets.engagement.save', $widget) }}" class="space-y-6 min-w-0"
          x-data="{ cards: @js(array_values($s['cards']) ?: []) }">
        @csrf @method('PUT')

        <section class="rounded-2xl border border-white/5 bg-gray-900/50 p-5 sm:p-6 space-y-3">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="text-base font-semibold text-white">AI notice</h3>
                    <p class="mt-1 text-xs text-gray-500">Tell visitors they are talking to an AI before their first message (EU AI Act). Shown once; asked again only if you change the wording.</p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer shrink-0">
                    <input type="checkbox" name="ai_notice[enabled]" value="1" @checked($n['enabled']) class="sr-only peer">
                    <span class="w-11 h-6 bg-white/15 rounded-full peer-checked:bg-brand-500 transition"></span>
                    <span class="absolute left-0.5 top-0.5 w-5 h-5 bg-white rounded-full transition peer-checked:translate-x-5"></span>
                </label>
            </div>
            <input name="ai_notice[title]" value="{{ $n['title'] }}" maxlength="120" class="{{ $field }}">
            <textarea name="ai_notice[text]" rows="3" maxlength="600" class="{{ $field }}">{{ $n['text'] }}</textarea>
            <input name="ai_notice[privacy_url]" value="{{ $n['privacy_url'] }}" type="url" placeholder="Privacy policy link (optional)" class="{{ $field }}">
            <input name="ai_notice[decline_text]" value="{{ $n['decline_text'] }}" maxlength="300" placeholder="Shown if the visitor declines" class="{{ $field }}">
        </section>

        <section class="rounded-2xl border border-white/5 bg-gray-900/50 p-5 sm:p-6 space-y-3">
            <h3 class="text-base font-semibold text-white">Action cards</h3>
            <p class="text-xs text-gray-500">Buttons shown under an answer when the question or answer mentions a keyword (up to 3 per reply).</p>
            <template x-for="(c, i) in cards" :key="i">
                <div class="grid sm:grid-cols-2 gap-2 p-3 rounded-xl border border-white/5 bg-white/[0.02]">
                    <input :name="`cards[${i}][title]`" x-model="c.title" maxlength="80" placeholder="Title, e.g. See our pricing" class="{{ $field }}">
                    <input :name="`cards[${i}][keywords]`" x-model="c.keywords" maxlength="200" placeholder="Keywords, e.g. price, cost, plans" class="{{ $field }}">
                    <input :name="`cards[${i}][text]`" x-model="c.text" maxlength="160" placeholder="Short text (optional)" class="{{ $field }} sm:col-span-2">
                    <input :name="`cards[${i}][button]`" x-model="c.button" maxlength="30" placeholder="Button label" class="{{ $field }}">
                    <input :name="`cards[${i}][url]`" x-model="c.url" type="url" placeholder="https://…" class="{{ $field }}">
                    <button type="button" @click="cards.splice(i, 1)" class="sm:col-span-2 justify-self-end text-xs text-rose-400">Remove</button>
                </div>
            </template>
            <button type="button" x-show="cards.length < 12" @click="cards.push({ title: '', keywords: '', text: '', button: 'Open', url: '' })" class="px-4 py-2 rounded-lg bg-white/5 text-gray-200 text-xs font-semibold hover:bg-white/10">+ Add card</button>
        </section>

        <section class="rounded-2xl border border-white/5 bg-gray-900/50 p-5 sm:p-6 space-y-3">
            <h3 class="text-base font-semibold text-white">Where the widget appears</h3>
            <label class="block text-xs text-gray-400">Hide on these pages (one per line, * matches anything)</label>
            <textarea name="hidden_paths" rows="3" placeholder="/checkout*&#10;/account/*" class="{{ $field }} font-mono">{{ implode("\n", $s['hidden_paths']) }}</textarea>
            <label class="flex items-center gap-3 pt-2 text-sm text-gray-200">
                <input type="checkbox" name="voice_input_enabled" value="1" @checked($widget->voice_input_enabled)>
                Voice input: visitors can speak their question (supported browsers)
            </label>
        </section>

        @php $h = $s['hours']; @endphp
        <section class="rounded-2xl border border-white/5 bg-gray-900/50 p-5 sm:p-6 space-y-3">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="text-base font-semibold text-white">Business hours</h3>
                    <p class="mt-1 text-xs text-gray-500">When your team is available. Outside these hours "Talk to a human" is hidden and visitors see your away note; the AI keeps answering.</p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer shrink-0">
                    <input type="checkbox" name="hours[enabled]" value="1" @checked($h['enabled']) class="sr-only peer">
                    <span class="w-11 h-6 bg-white/15 rounded-full peer-checked:bg-brand-500 transition"></span>
                    <span class="absolute left-0.5 top-0.5 w-5 h-5 bg-white rounded-full transition peer-checked:translate-x-5"></span>
                </label>
            </div>
            <select name="hours[timezone]" class="{{ $field }}">
                @foreach(timezone_identifiers_list() as $tz)<option value="{{ $tz }}" @selected($h['timezone'] === $tz)>{{ $tz }}</option>@endforeach
            </select>
            <div class="space-y-1.5">
                @foreach(['mon' => 'Monday', 'tue' => 'Tuesday', 'wed' => 'Wednesday', 'thu' => 'Thursday', 'fri' => 'Friday', 'sat' => 'Saturday', 'sun' => 'Sunday'] as $k => $day)
                @php $d = $h['days'][$k] ?? null; @endphp
                <div class="flex flex-wrap items-center gap-2 text-sm text-gray-300">
                    <label class="flex items-center gap-2 w-28"><input type="checkbox" name="hours[open][{{ $k }}]" value="1" @checked(is_array($d))> {{ $day }}</label>
                    <input type="time" name="hours[days][{{ $k }}][0]" value="{{ $d[0] ?? '09:00' }}" class="bg-white/5 border border-white/10 rounded-lg px-2 py-1.5 text-white text-xs">
                    <span class="text-gray-500">to</span>
                    <input type="time" name="hours[days][{{ $k }}][1]" value="{{ $d[1] ?? '17:00' }}" class="bg-white/5 border border-white/10 rounded-lg px-2 py-1.5 text-white text-xs">
                </div>
                @endforeach
            </div>
            <input name="hours[away_text]" value="{{ $h['away_text'] }}" maxlength="300" placeholder="Away note" class="{{ $field }}">
        </section>

        <button class="w-full sm:w-auto px-6 py-3 rounded-xl bg-brand-500 text-white text-sm font-semibold">Save engagement settings</button>
    </form>
</div>
@endsection
