@extends('layouts.app')
@section('title', 'Booking settings')
@section('page-title', 'Booking: ' . $widget->name)
@section('content')
@php
    $field = 'w-full bg-white/5 border border-white/10 rounded-xl px-3 py-2 text-sm text-white focus:outline-none focus:border-brand-500';
    $label = 'block text-xs font-semibold text-gray-400 mb-1.5';
    $card = 'bg-gray-900/50 border border-white/5 rounded-2xl p-5';
    $toggle = 'relative block w-11 h-6 rounded-full bg-white/15 peer-checked:bg-brand-500 transition after:absolute after:top-1 after:left-1 after:w-4 after:h-4 after:rounded-full after:bg-white after:transition peer-checked:after:translate-x-5';
    $calendars = app(\App\Services\CalendarService::class);
    $crmByProvider = $widget->crmConnections->keyBy('provider');
@endphp

@if($errors->any())
<div class="mb-5 p-4 rounded-xl bg-rose-500/10 border border-rose-500/20 text-sm text-rose-300">
    @foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach
</div>
@endif

<form method="POST" action="{{ route('widgets.booking.update', $widget) }}" enctype="multipart/form-data"
      x-data="{
          services: @js(old('services', $settings['services'])),
          staff: @js(old('staff', $settings['staff'])),
          questions: @js(old('questions', $settings['questions'])),
          hours: @js(old('hours', $settings['hours'])),
          days: @js(\App\Support\Booking::DAYS),
          uid(p) { return p + Date.now().toString(36) + Math.random().toString(36).slice(2, 5); },
      }"
      class="grid xl:grid-cols-3 gap-6 items-start">
    @csrf @method('PUT')

    <div class="xl:col-span-2 space-y-6">
        {{-- On/off --}}
        <div id="setup" class="{{ $card }} flex items-center justify-between gap-4 scroll-mt-24">
            <div>
                <p class="font-semibold text-white">Let visitors book appointments</p>
                <p class="text-sm text-gray-400">Adds a calendar button to the chat, and the assistant offers booking when a visitor asks.</p>
            </div>
            <label class="cursor-pointer shrink-0">
                <input type="checkbox" name="enabled" value="1" class="peer sr-only" @checked(old('enabled', $settings['enabled']))>
                <span class="{{ $toggle }}"></span>
            </label>
        </div>

        {{-- Services --}}
        <div class="{{ $card }}">
            <div class="flex items-center justify-between">
                <p class="font-semibold text-white">Services</p>
                <button type="button" @click="services.push({ id: uid('s'), name: '', duration: 30, price: '', description: '' })" class="text-sm font-semibold text-brand-400 hover:text-brand-300">+ Add service</button>
            </div>
            <div class="mt-4 space-y-3">
                <template x-for="(s, i) in services" :key="s.id">
                    <div class="grid grid-cols-12 gap-2 p-3 rounded-xl bg-white/[0.03] border border-white/5">
                        <input type="hidden" :name="`services[${i}][id]`" :value="s.id">
                        <div class="col-span-12 sm:col-span-4"><label class="{{ $label }}">Name</label><input :name="`services[${i}][name]`" x-model="s.name" required maxlength="80" class="{{ $field }}" placeholder="e.g. First consultation"></div>
                        <div class="col-span-6 sm:col-span-2"><label class="{{ $label }}">Minutes</label><input type="number" min="5" max="600" step="5" :name="`services[${i}][duration]`" x-model="s.duration" required class="{{ $field }}"></div>
                        <div class="col-span-6 sm:col-span-4"><label class="{{ $label }}">Price shown</label><input :name="`services[${i}][price]`" x-model="s.price" maxlength="30" class="{{ $field }}" placeholder="£40 or Free"></div>
                        <div class="col-span-12 sm:col-span-1 flex items-end justify-end">
                            <button type="button" @click="services.length > 1 && services.splice(i, 1)" class="p-2 rounded-lg text-gray-500 hover:text-rose-400" title="Remove">✕</button>
                        </div>
                        <div class="col-span-12"><input :name="`services[${i}][description]`" x-model="s.description" maxlength="200" class="{{ $field }}" placeholder="Short description shown to visitors (optional)"></div>
                    </div>
                </template>
            </div>
        </div>

        {{-- Weekly hours --}}
        <div class="{{ $card }}">
            <p class="font-semibold text-white">Opening hours</p>
            <p class="text-sm text-gray-400">Add more than one time range for a day to leave a lunch break.</p>
            <div class="mt-4 divide-y divide-white/5">
                @foreach(\App\Support\Booking::DAYS as $key => $day)
                <div class="py-3 flex flex-col sm:flex-row sm:items-start gap-2 sm:gap-4">
                    <div class="sm:w-32 shrink-0 pt-2 flex items-center gap-2">
                        <span class="text-sm font-semibold text-white">{{ $day }}</span>
                        <span x-show="!hours.{{ $key }} || !hours.{{ $key }}.length" class="text-xs text-gray-500">Closed</span>
                    </div>
                    <div class="flex-1 space-y-2">
                        <template x-for="(w, j) in (hours.{{ $key }} || [])" :key="j">
                            <div class="flex items-center gap-2">
                                <input type="time" :name="`hours[{{ $key }}][${j}][0]`" x-model="w[0]" required class="{{ $field }} max-w-[140px]">
                                <span class="text-gray-500 text-sm">to</span>
                                <input type="time" :name="`hours[{{ $key }}][${j}][1]`" x-model="w[1]" required class="{{ $field }} max-w-[140px]">
                                <button type="button" @click="hours.{{ $key }}.splice(j, 1)" class="p-2 text-gray-500 hover:text-rose-400" title="Remove">✕</button>
                            </div>
                        </template>
                        <button type="button" @click="(hours.{{ $key }} = hours.{{ $key }} || []).push(['09:00', '17:00'])" class="text-xs font-semibold text-brand-400 hover:text-brand-300">+ Add hours</button>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Team --}}
        <div id="team" class="{{ $card }} scroll-mt-24">
            <div class="flex items-center justify-between">
                <div>
                    <p class="font-semibold text-white">Team</p>
                    <p class="text-sm text-gray-400">Give each person their own diary. Visitors can pick someone or take the first free person. Leave empty if you only have one diary.</p>
                </div>
                <button type="button" @click="staff.push({ id: uid('t'), name: '', email: '', services: [], days: [] })" class="shrink-0 text-sm font-semibold text-brand-400 hover:text-brand-300">+ Add person</button>
            </div>
            <div class="mt-4 space-y-3">
                <template x-for="(m, i) in staff" :key="m.id">
                    <div class="p-3 rounded-xl bg-white/[0.03] border border-white/5 space-y-3">
                        <input type="hidden" :name="`staff[${i}][id]`" :value="m.id">
                        <div class="grid sm:grid-cols-2 gap-2">
                            <div><label class="{{ $label }}">Name</label><input :name="`staff[${i}][name]`" x-model="m.name" required maxlength="80" class="{{ $field }}" placeholder="e.g. Dr Patel"></div>
                            <div class="flex gap-2 items-end">
                                <div class="flex-1"><label class="{{ $label }}">Email (gets their own booking emails)</label><input type="email" :name="`staff[${i}][email]`" x-model="m.email" class="{{ $field }}"></div>
                                <button type="button" @click="staff.splice(i, 1)" class="p-2 rounded-lg text-gray-500 hover:text-rose-400" title="Remove">✕</button>
                            </div>
                        </div>
                        <div>
                            <p class="{{ $label }}">Services they offer <span class="font-normal text-gray-500">(none ticked = all)</span></p>
                            <div class="flex flex-wrap gap-1.5">
                                <template x-for="s in services" :key="s.id">
                                    <label class="cursor-pointer">
                                        <input type="checkbox" class="peer sr-only" :name="`staff[${i}][services][]`" :value="s.id" :checked="(m.services || []).includes(s.id)">
                                        <span class="block px-2.5 py-1 rounded-lg text-xs border border-white/10 text-gray-400 peer-checked:border-brand-500 peer-checked:text-white peer-checked:bg-brand-500/15" x-text="s.name || 'Unnamed'"></span>
                                    </label>
                                </template>
                            </div>
                        </div>
                        <div>
                            <p class="{{ $label }}">Days they work <span class="font-normal text-gray-500">(none ticked = every open day)</span></p>
                            <div class="flex flex-wrap gap-1.5">
                                <template x-for="(name, d) in days" :key="d">
                                    <label class="cursor-pointer">
                                        <input type="checkbox" class="peer sr-only" :name="`staff[${i}][days][]`" :value="d" :checked="(m.days || []).includes(d)">
                                        <span class="block px-2.5 py-1 rounded-lg text-xs border border-white/10 text-gray-400 peer-checked:border-brand-500 peer-checked:text-white peer-checked:bg-brand-500/15" x-text="name.slice(0, 3)"></span>
                                    </label>
                                </template>
                            </div>
                        </div>
                    </div>
                </template>
                <p x-show="!staff.length" class="text-sm text-gray-500">No team members. All bookings share one diary.</p>
            </div>
        </div>

        {{-- Questions --}}
        <div id="form" class="{{ $card }} scroll-mt-24">
            <div class="flex items-center justify-between">
                <div>
                    <p class="font-semibold text-white">Booking form questions</p>
                    <p class="text-sm text-gray-400">Extra questions visitors answer when they book (name, email and phone are always asked).</p>
                </div>
                <button type="button" @click="questions.push({ id: uid('q'), label: '', type: 'text', options: '', required: false })" class="shrink-0 text-sm font-semibold text-brand-400 hover:text-brand-300">+ Add question</button>
            </div>
            <div class="mt-4 space-y-3">
                <template x-for="(q, i) in questions" :key="q.id">
                    <div class="grid grid-cols-12 gap-2 p-3 rounded-xl bg-white/[0.03] border border-white/5">
                        <input type="hidden" :name="`questions[${i}][id]`" :value="q.id">
                        <div class="col-span-12 sm:col-span-6"><label class="{{ $label }}">Question</label><input :name="`questions[${i}][label]`" x-model="q.label" required maxlength="150" class="{{ $field }}" placeholder="e.g. Is this your first visit?"></div>
                        <div class="col-span-6 sm:col-span-3">
                            <label class="{{ $label }}">Answer type</label>
                            <select :name="`questions[${i}][type]`" x-model="q.type" class="{{ $field }}">
                                @foreach(\App\Support\Booking::QUESTION_TYPES as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach
                            </select>
                        </div>
                        <div class="col-span-6 sm:col-span-3 flex items-end justify-between gap-2">
                            <label class="flex items-center gap-2 text-xs text-gray-300 pb-2.5"><input type="checkbox" :name="`questions[${i}][required]`" value="1" x-model="q.required" class="accent-brand-500"> Required</label>
                            <button type="button" @click="questions.splice(i, 1)" class="p-2 rounded-lg text-gray-500 hover:text-rose-400" title="Remove">✕</button>
                        </div>
                        <div class="col-span-12" x-show="q.type === 'select'"><input :name="`questions[${i}][options]`" x-model="q.options" maxlength="500" class="{{ $field }}" placeholder="Options, separated by commas"></div>
                    </div>
                </template>
                <p x-show="!questions.length" class="text-sm text-gray-500">No extra questions.</p>
            </div>
        </div>

    </div>

    {{-- Rules --}}
    <div class="space-y-6 xl:sticky xl:top-24">
        <div class="{{ $card }} space-y-4">
            <p class="font-semibold text-white">Rules</p>
            <div><label class="{{ $label }}">Business name shown to visitors</label><input name="business_name" value="{{ old('business_name', $widget->booking['business_name'] ?? '') }}" placeholder="{{ $widget->name }}" class="{{ $field }}"></div>
            <div>
                <label class="{{ $label }}">Logo for visitor emails and pages</label>
                <div class="flex items-center gap-3">
                    @if(\App\Support\Booking::logoUrl($settings))
                        <img src="{{ \App\Support\Booking::logoUrl($settings) }}" alt="" class="h-10 max-w-[120px] object-contain rounded-lg bg-white p-1">
                    @endif
                    <input type="file" name="logo" accept="image/png,image/jpeg,image/webp" class="block w-full text-xs text-gray-400 file:mr-3 file:px-3 file:py-2 file:rounded-lg file:border-0 file:bg-white/10 file:text-white file:text-xs file:font-semibold">
                </div>
                @if($settings['logo'])<label class="mt-1.5 flex items-center gap-2 text-xs text-gray-400"><input type="checkbox" name="remove_logo" value="1" class="accent-rose-500"> Remove logo</label>@endif
                <p class="mt-1 text-xs text-gray-500">PNG, JPG or WebP, up to 1 MB. Without a logo, your business name is shown.</p>
            </div>
            <div><label class="{{ $label }}">Website</label><input name="website" type="url" value="{{ old('website', $settings['website']) }}" placeholder="https://yourbusiness.com" class="{{ $field }}"></div>
            <div><label class="{{ $label }}">Replies from visitors go to</label><input name="reply_to" type="email" value="{{ old('reply_to', $settings['reply_to']) }}" placeholder="{{ $widget->notification_email ?: $widget->user?->email }}" class="{{ $field }}"></div>
            <div><label class="{{ $label }}">Button label</label><input name="button_label" value="{{ old('button_label', $settings['button_label']) }}" required maxlength="40" class="{{ $field }}"></div>
            <div>
                <label class="{{ $label }}">Timezone</label>
                <select name="timezone" class="{{ $field }}">
                    @foreach($timezones as $tz)<option value="{{ $tz }}" @selected(old('timezone', $settings['timezone']) === $tz)>{{ $tz }}</option>@endforeach
                </select>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="{{ $label }}">Start times every</label>
                    <select name="slot_step" class="{{ $field }}">
                        @foreach([10, 15, 20, 30, 45, 60, 90, 120] as $m)<option value="{{ $m }}" @selected((int) old('slot_step', $settings['slot_step']) === $m)>{{ $m }} min</option>@endforeach
                    </select>
                </div>
                <div><label class="{{ $label }}">Gap after each (min)</label><input type="number" name="buffer" min="0" max="240" value="{{ old('buffer', $settings['buffer']) }}" class="{{ $field }}"></div>
                <div><label class="{{ $label }}">Minimum notice (hours)</label><input type="number" name="min_notice" min="0" max="720" value="{{ old('min_notice', $settings['min_notice']) }}" class="{{ $field }}"></div>
                <div><label class="{{ $label }}">Book up to (days ahead)</label><input type="number" name="max_days_ahead" min="1" max="365" value="{{ old('max_days_ahead', $settings['max_days_ahead']) }}" class="{{ $field }}"></div>
                <div><label class="{{ $label }}">Max per day (0 = no limit)</label><input type="number" name="daily_limit" min="0" max="500" value="{{ old('daily_limit', $settings['daily_limit']) }}" class="{{ $field }}"></div>
                <div><label class="{{ $label }}">Reminder (hours before)</label><input type="number" name="reminder_hours" min="0" max="168" value="{{ old('reminder_hours', $settings['reminder_hours']) }}" class="{{ $field }}"></div>
                <div class="col-span-2"><label class="{{ $label }}">Visitors can cancel or move up to (hours before)</label><input type="number" name="cancel_notice" min="0" max="720" value="{{ old('cancel_notice', $settings['cancel_notice']) }}" class="{{ $field }}"></div>
            </div>
            @foreach([
                'auto_confirm'     => ['Confirm bookings automatically', 'Turn off to approve each booking yourself.'],
                'allow_reschedule' => ['Let visitors move their booking', 'From the link in their confirmation email.'],
                'ai_booking'       => ['Let the assistant offer booking', 'Shows the booking button when visitors ask in the chat.'],
                'require_phone'    => ['Ask for a phone number', 'Makes the phone field required.'],
            ] as $name => [$title, $hint])
            <label class="flex items-start gap-3 cursor-pointer">
                <input type="checkbox" name="{{ $name }}" value="1" class="mt-1 accent-brand-500" @checked(old($name, $settings[$name]))>
                <span><span class="block text-sm text-white">{{ $title }}</span><span class="block text-xs text-gray-400">{{ $hint }}</span></span>
            </label>
            @endforeach
            <div>
                <label class="{{ $label }}">Closed dates</label>
                <textarea name="blocked_dates" rows="2" class="{{ $field }}" placeholder="2026-12-25, 2026-12-26">{{ old('blocked_dates', implode(', ', $settings['blocked_dates'])) }}</textarea>
                <p class="mt-1 text-xs text-gray-500">Holidays or days off, as YYYY-MM-DD, separated by commas.</p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <button class="px-5 py-2.5 rounded-xl bg-brand-500 hover:bg-brand-600 text-white text-sm font-semibold">Save booking settings</button>
            <a href="{{ route('appointments.index') }}" class="text-sm text-gray-400 hover:text-white">Back to bookings</a>
        </div>
    </div>
</form>

{{-- Integrations (separate forms) --}}
<div id="integrations" class="mt-10 scroll-mt-24">
    <h2 class="text-xl font-extrabold text-white">Calendars & CRM</h2>
    <p class="text-sm text-gray-400">Connected calendars block times you are already busy and receive every booking as an event. Connected CRMs get the contact and the appointment, and stay updated when it moves or is cancelled.</p>

    <div class="mt-5 grid lg:grid-cols-2 gap-6">
        {{-- Calendars --}}
        <div class="{{ $card }}">
            <p class="font-semibold text-white">Calendars</p>
            @php
                $owners = collect([['id' => null, 'name' => 'Whole business']])->merge(collect($settings['staff'])->map(fn ($m) => ['id' => $m['id'], 'name' => $m['name']]));
            @endphp
            <div class="mt-4 space-y-4">
                @foreach($owners as $owner)
                <div class="rounded-xl bg-white/[0.03] border border-white/5 p-3">
                    <p class="text-sm font-semibold text-white">{{ $owner['name'] }}</p>
                    @foreach($widget->calendarConnections->where('staff_id', $owner['id']) as $conn)
                    <form method="POST" action="{{ route('widgets.booking.calendar.update', [$widget, $conn]) }}" class="mt-2 p-3 rounded-lg bg-black/20 space-y-2">
                        @csrf @method('PUT')
                        <div class="flex items-center justify-between gap-2">
                            <p class="text-sm text-gray-200"><span class="text-emerald-400">●</span> {{ \App\Models\CalendarConnection::PROVIDERS[$conn->provider] }} <span class="text-gray-500">{{ $conn->account_email }}</span></p>
                            <button form="cal-del-{{ $conn->id }}" class="text-xs text-rose-400 hover:text-rose-300">Disconnect</button>
                        </div>
                        @if($conn->last_error)<p class="text-xs text-rose-300">Last error: {{ \Illuminate\Support\Str::limit($conn->last_error, 160) }}</p>@endif
                        <div class="flex flex-wrap items-center gap-3 text-xs text-gray-300">
                            <input name="calendar_id" value="{{ $conn->calendar_id }}" class="{{ $field }} !w-40 !py-1.5" title="Calendar ID (primary = main calendar)">
                            <label class="flex items-center gap-1.5"><input type="checkbox" name="check_busy" value="1" class="accent-brand-500" @checked($conn->check_busy)> Block busy times</label>
                            <label class="flex items-center gap-1.5"><input type="checkbox" name="push_events" value="1" class="accent-brand-500" @checked($conn->push_events)> Add bookings</label>
                            <button class="ml-auto px-2.5 py-1 rounded-lg bg-white/10 hover:bg-white/15 text-white">Save</button>
                        </div>
                    </form>
                    <form id="cal-del-{{ $conn->id }}" method="POST" action="{{ route('widgets.booking.calendar.disconnect', [$widget, $conn]) }}" onsubmit="return confirm('Disconnect this calendar?')">@csrf @method('DELETE')</form>
                    @endforeach
                    <div class="mt-2 flex flex-wrap gap-2">
                        @foreach(\App\Models\CalendarConnection::PROVIDERS as $p => $pname)
                            @unless($widget->calendarConnections->where('staff_id', $owner['id'])->where('provider', $p)->count())
                            <a href="{{ route('widgets.booking.calendar.connect', [$widget, $p]) }}{{ $owner['id'] ? '?staff=' . urlencode($owner['id']) : '' }}"
                               class="px-3 py-1.5 rounded-lg text-xs font-semibold border border-white/10 hover:border-brand-500/60 text-gray-200 {{ $calendars->configured($p) ? '' : 'opacity-60' }}"
                               @unless($calendars->configured($p)) title="Needs {{ strtoupper($p) }}_CLIENT_ID and _SECRET on the server" @endunless>+ {{ $pname }}</a>
                            @endunless
                        @endforeach
                    </div>
                </div>
                @endforeach
                @if(! $calendars->configured('google') || ! $calendars->configured('microsoft'))
                <p class="text-xs text-gray-500">Server setup: add GOOGLE_CLIENT_ID / GOOGLE_CLIENT_SECRET and MICROSOFT_CLIENT_ID / MICROSOFT_CLIENT_SECRET to .env. Redirect URIs: {{ url('/calendar/callback/google') }} and {{ url('/calendar/callback/microsoft') }}</p>
                @endif
            </div>
        </div>

        {{-- CRMs --}}
        <div class="{{ $card }}" x-data="{ open: null }">
            <p class="font-semibold text-white">CRM</p>
            <div class="mt-4 space-y-2">
                @foreach(\App\Models\CrmConnection::PROVIDERS as $p => [$pname, $fields])
                @php $conn = $crmByProvider[$p] ?? null; @endphp
                <div class="rounded-xl bg-white/[0.03] border border-white/5">
                    <button type="button" @click="open = open === '{{ $p }}' ? null : '{{ $p }}'" class="w-full flex items-center justify-between gap-3 p-3 text-left">
                        <span class="text-sm font-semibold text-white">{{ $pname }}</span>
                        @if($conn)
                            <span class="text-xs font-semibold {{ $conn->last_error ? 'text-rose-400' : ($conn->is_active ? 'text-emerald-400' : 'text-gray-500') }}">
                                {{ $conn->last_error ? 'Needs attention' : ($conn->is_active ? 'Connected' : 'Paused') }}
                            </span>
                        @else
                            <span class="text-xs text-gray-500">Connect</span>
                        @endif
                    </button>
                    <div x-show="open === '{{ $p }}'" x-collapse x-cloak class="px-3 pb-3">
                        @if($conn?->last_error)<p class="mb-2 text-xs text-rose-300">Last error: {{ \Illuminate\Support\Str::limit($conn->last_error, 200) }}</p>@endif
                        @if($conn?->last_synced_at)<p class="mb-2 text-xs text-gray-500">Last synced {{ $conn->last_synced_at->diffForHumans() }}</p>@endif
                        <form method="POST" action="{{ route('widgets.booking.crm.save', $widget) }}" class="space-y-2">
                            @csrf
                            <input type="hidden" name="provider" value="{{ $p }}">
                            @foreach($fields as $fname => $flabel)
                                @php $secretField = in_array($fname, ['token', 'secret', 'refresh_token', 'client_secret'], true); @endphp
                                <div>
                                    <label class="{{ $label }}">{{ $flabel }}</label>
                                    <input name="credentials[{{ $fname }}]" type="{{ $secretField ? 'password' : 'text' }}" autocomplete="off" class="{{ $field }}"
                                           value="{{ $secretField ? '' : ($conn?->credentials[$fname] ?? '') }}"
                                           placeholder="{{ $secretField && $conn ? 'Saved. Leave blank to keep it.' : '' }}">
                                </div>
                            @endforeach
                            <div class="flex flex-wrap items-center gap-2 pt-1">
                                <button class="px-3.5 py-2 rounded-lg bg-brand-500 hover:bg-brand-600 text-white text-xs font-semibold">{{ $conn ? 'Save and test' : 'Connect and test' }}</button>
                                @if($conn)
                                    <button form="crm-toggle-{{ $p }}" class="px-3 py-2 rounded-lg bg-white/5 hover:bg-white/10 text-gray-200 text-xs font-semibold">{{ $conn->is_active ? 'Pause sync' : 'Resume sync' }}</button>
                                    <button form="crm-del-{{ $p }}" class="px-3 py-2 text-rose-400 hover:text-rose-300 text-xs font-semibold">Disconnect</button>
                                @endif
                            </div>
                        </form>
                        @if($conn)
                            <form id="crm-toggle-{{ $p }}" method="POST" action="{{ route('widgets.booking.crm.toggle', [$widget, $conn]) }}">@csrf @method('PATCH')</form>
                            <form id="crm-del-{{ $p }}" method="POST" action="{{ route('widgets.booking.crm.delete', [$widget, $conn]) }}" onsubmit="return confirm('Disconnect {{ $pname }}?')">@csrf @method('DELETE')</form>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>

            <div class="mt-5 p-3 rounded-xl bg-brand-500/10 border border-brand-500/20 text-xs text-gray-300 space-y-1.5">
                <p class="font-semibold text-white">Manage bookings from your CRM</p>
                <p>Any CRM or automation tool can read, create, move and cancel bookings with the REST API. Create a key in <a href="{{ route('settings.apiKeys') }}" class="text-brand-300 underline">Settings › API keys</a>, then call:</p>
                <pre class="mt-1 p-2 rounded-lg bg-black/30 overflow-x-auto text-[11px] leading-5 text-gray-300">GET    {{ url('/api/v1/widgets/' . $widget->id . '/availability') }}?service=ID&amp;date=YYYY-MM-DD
GET    {{ url('/api/v1/appointments') }}?updated_since=2026-01-01
POST   {{ url('/api/v1/appointments') }}
PATCH  {{ url('/api/v1/appointments') }}/{id}   {"status":"completed"} or {"start":"…"}
DELETE {{ url('/api/v1/appointments') }}/{id}
Authorization: Bearer pfx_…</pre>
                <p>Webhook events <code>appointment.booked</code>, <code>.confirmed</code>, <code>.rescheduled</code>, <code>.cancelled</code>, <code>.completed</code>, <code>.no_show</code> go to your widget webhook.</p>
            </div>
        </div>
    </div>
</div>
@endsection
