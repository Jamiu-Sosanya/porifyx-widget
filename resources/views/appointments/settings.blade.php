@extends('layouts.app')
@section('title', 'Booking settings')
@section('page-title', 'Booking: ' . $widget->name)
@section('content')
@php
    $field = 'w-full bg-white/5 border border-white/10 rounded-xl px-3 py-2 text-sm text-white focus:outline-none focus:border-brand-500';
    $label = 'block text-xs font-semibold text-gray-400 mb-1.5';
    $card = 'bg-gray-900/50 border border-white/5 rounded-2xl p-5';
@endphp

@if($errors->any())
<div class="mb-5 p-4 rounded-xl bg-rose-500/10 border border-rose-500/20 text-sm text-rose-300">
    @foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach
</div>
@endif

<form method="POST" action="{{ route('widgets.booking.update', $widget) }}"
      x-data="{
          services: @js(old('services', $settings['services'])),
          hours: @js(old('hours', $settings['hours'])),
          addService() { this.services.push({ id: 's' + Date.now().toString(36), name: '', duration: 30, price: '', description: '' }); },
      }"
      class="grid xl:grid-cols-3 gap-6 items-start">
    @csrf @method('PUT')

    <div class="xl:col-span-2 space-y-6">
        {{-- On/off --}}
        <div class="{{ $card }} flex items-center justify-between gap-4">
            <div>
                <p class="font-semibold text-white">Let visitors book appointments</p>
                <p class="text-sm text-gray-400">Adds a calendar button to the chat, and offers booking when a visitor asks for an appointment.</p>
            </div>
            <label class="cursor-pointer shrink-0">
                <input type="checkbox" name="enabled" value="1" class="peer sr-only" @checked(old('enabled', $settings['enabled']))>
                <span class="relative block w-11 h-6 rounded-full bg-white/15 peer-checked:bg-brand-500 transition after:absolute after:top-1 after:left-1 after:w-4 after:h-4 after:rounded-full after:bg-white after:transition peer-checked:after:translate-x-5"></span>
            </label>
        </div>

        {{-- Services --}}
        <div class="{{ $card }}">
            <div class="flex items-center justify-between">
                <p class="font-semibold text-white">Services</p>
                <button type="button" @click="addService()" class="text-sm font-semibold text-brand-400 hover:text-brand-300">+ Add service</button>
            </div>
            <div class="mt-4 space-y-3">
                <template x-for="(s, i) in services" :key="s.id">
                    <div class="grid grid-cols-12 gap-2 p-3 rounded-xl bg-white/[0.03] border border-white/5">
                        <input type="hidden" :name="`services[${i}][id]`" :value="s.id">
                        <div class="col-span-12 sm:col-span-5"><label class="{{ $label }}">Name</label><input :name="`services[${i}][name]`" x-model="s.name" required maxlength="80" class="{{ $field }}" placeholder="e.g. First consultation"></div>
                        <div class="col-span-5 sm:col-span-3"><label class="{{ $label }}">Length (min)</label><input type="number" min="5" max="600" step="5" :name="`services[${i}][duration]`" x-model="s.duration" required class="{{ $field }}"></div>
                        <div class="col-span-5 sm:col-span-3"><label class="{{ $label }}">Price (optional)</label><input :name="`services[${i}][price]`" x-model="s.price" maxlength="30" class="{{ $field }}" placeholder="£40"></div>
                        <div class="col-span-2 sm:col-span-1 flex items-end justify-end">
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
    </div>

    {{-- Rules --}}
    <div class="space-y-6 xl:sticky xl:top-24">
        <div class="{{ $card }} space-y-4">
            <p class="font-semibold text-white">Rules</p>
            <div><label class="{{ $label }}">Business name shown to visitors</label><input name="business_name" value="{{ old('business_name', $widget->booking['business_name'] ?? '') }}" placeholder="{{ $widget->name }}" class="{{ $field }}"></div>
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
                <div><label class="{{ $label }}">Reminder (hours before, 0 = off)</label><input type="number" name="reminder_hours" min="0" max="168" value="{{ old('reminder_hours', $settings['reminder_hours']) }}" class="{{ $field }}"></div>
            </div>
            @foreach(['auto_confirm' => ['Confirm bookings automatically', 'Turn off to approve each booking yourself.'], 'require_phone' => ['Ask for a phone number', 'Makes the phone field required.']] as $name => [$title, $hint])
            <label class="flex items-start gap-3 cursor-pointer">
                <input type="checkbox" name="{{ $name }}" value="1" class="mt-1 accent-brand-500" @checked(old($name, $settings[$name]))>
                <span><span class="block text-sm text-white">{{ $title }}</span><span class="block text-xs text-gray-400">{{ $hint }}</span></span>
            </label>
            @endforeach
            <div>
                <label class="{{ $label }}">Closed dates</label>
                <textarea name="blocked_dates" rows="3" class="{{ $field }}" placeholder="2026-12-25, 2026-12-26">{{ old('blocked_dates', implode(', ', $settings['blocked_dates'])) }}</textarea>
                <p class="mt-1 text-xs text-gray-500">Holidays or days off, as YYYY-MM-DD, separated by commas.</p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <button class="px-5 py-2.5 rounded-xl bg-brand-500 hover:bg-brand-600 text-white text-sm font-semibold">Save booking settings</button>
            <a href="{{ route('appointments.index') }}" class="text-sm text-gray-400 hover:text-white">Back to bookings</a>
        </div>
    </div>
</form>
@endsection
