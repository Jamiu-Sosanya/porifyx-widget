@extends('layouts.app')
@section('title', 'Bookings')
@section('page-title', 'Bookings')
@section('content')
@php
    use App\Models\Appointment;
    $badge = [
        'pending'   => 'bg-amber-500/15 text-amber-400',
        'confirmed' => 'bg-emerald-500/15 text-emerald-400',
        'completed' => 'bg-brand-500/15 text-brand-400',
        'cancelled' => 'bg-white/5 text-gray-400',
        'no_show'   => 'bg-rose-500/15 text-rose-400',
    ];
    $field = 'bg-white/5 border border-white/10 rounded-xl px-3 py-2 text-sm text-white focus:outline-none focus:border-brand-500';
    $bookable = $widgets->filter(fn ($w) => \App\Support\Booking::settings($w)['enabled'])->map(fn ($w) => [
        'id' => $w->id, 'uuid' => $w->uuid, 'name' => $w->name,
        'services' => array_map(fn ($s) => ['id' => $s['id'], 'name' => $s['name']], \App\Support\Booking::settings($w)['services']),
        'staff' => array_map(fn ($m) => ['id' => $m['id'], 'name' => $m['name']], \App\Support\Booking::settings($w)['staff']),
    ])->values();
@endphp

<div x-data="bookingDesk(@js($bookable), @js(url('/api/widget')))">

{{-- Stats --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    @foreach([['Today', $stats['today'], 'Booked for today'], ['Next 7 days', $stats['week'], 'Pending and confirmed'], ['Awaiting you', $stats['pending'], 'Need confirming'], ['No-show rate', $stats['no_show'] . '%', 'Of finished bookings']] as [$label, $value, $hint])
    <div class="bg-gray-900/50 border border-white/5 rounded-2xl p-5">
        <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">{{ $label }}</p>
        <p class="mt-2 text-2xl sm:text-3xl font-extrabold text-white truncate">{{ $value }}</p>
        <p class="mt-1 text-xs text-gray-500">{{ $hint }}</p>
    </div>
    @endforeach
</div>

{{-- Booking setup per widget --}}
<div class="bg-gray-900/50 border border-white/5 rounded-2xl p-5 mb-6 flex flex-wrap items-center justify-between gap-4">
    <div>
        <p class="font-semibold text-white">Booking setup</p>
        <p class="text-sm text-gray-400">Services, team, calendars and CRM for each widget.</p>
        <div class="mt-3 flex flex-wrap gap-2">
            @forelse($widgets as $w)
                @php $on = (bool) ($w->booking['enabled'] ?? false); @endphp
                <a href="{{ route('widgets.booking', $w) }}" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl border border-white/10 hover:border-brand-500/50 text-sm text-gray-200 transition">
                    <span class="w-2 h-2 rounded-full {{ $on ? 'bg-emerald-400' : 'bg-gray-500' }}"></span>
                    {{ $w->name }} <span class="text-xs text-gray-500">{{ $on ? 'On' : 'Off' }}</span>
                </a>
            @empty
                <a href="{{ route('widgets.create') }}" class="text-sm text-brand-400">Create a widget first →</a>
            @endforelse
        </div>
    </div>
    @if($bookable->count())
    <button type="button" @click="openNew()" class="px-4 py-2.5 rounded-xl bg-brand-500 hover:bg-brand-600 text-white text-sm font-semibold">+ Add booking</button>
    @endif
</div>

{{-- Filters --}}
<form method="GET" class="flex flex-wrap items-center gap-2 mb-5">
    <div class="flex p-1 rounded-xl bg-white/5 border border-white/10">
        @foreach(['upcoming' => 'Upcoming', 'week' => 'Week', 'past' => 'Past', 'all' => 'All'] as $key => $label)
            <a href="{{ request()->fullUrlWithQuery(['view' => $key, 'page' => null]) }}" class="px-3 py-1.5 rounded-lg text-sm font-semibold {{ $view === $key ? 'bg-brand-500 text-white' : 'text-gray-400 hover:text-white' }}">{{ $label }}</a>
        @endforeach
    </div>
    <input type="hidden" name="view" value="{{ $view }}">
    <input type="search" name="q" value="{{ request('q') }}" placeholder="Name, email or BK- number" class="{{ $field }} w-52">
    <select name="status" class="{{ $field }}" onchange="this.form.submit()">
        <option value="">Any status</option>
        @foreach(Appointment::STATUSES as $key => $label)<option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>@endforeach
    </select>
    @if($staff->count())
    <select name="staff" class="{{ $field }}" onchange="this.form.submit()">
        <option value="">Everyone</option>
        @foreach($staff as $m)<option value="{{ $m['id'] }}" @selected(request('staff') === $m['id'])>{{ $m['name'] }}</option>@endforeach
    </select>
    @endif
    @if($widgets->count() > 1)
    <select name="widget" class="{{ $field }}" onchange="this.form.submit()">
        <option value="">All widgets</option>
        @foreach($widgets as $w)<option value="{{ $w->id }}" @selected(request('widget') == $w->id)>{{ $w->name }}</option>@endforeach
    </select>
    @endif
</form>

@if($view === 'week')
    {{-- Week calendar --}}
    @php $from = $week['from']; @endphp
    <div class="flex items-center justify-between mb-3">
        <p class="font-bold text-white">{{ $from->format('j M') }} – {{ $from->addDays(6)->format('j M Y') }}</p>
        <div class="flex gap-1">
            <a href="{{ request()->fullUrlWithQuery(['from' => $from->subWeek()->toDateString()]) }}" class="px-3 py-1.5 rounded-lg bg-white/5 hover:bg-white/10 text-sm text-gray-200">‹ Previous</a>
            <a href="{{ request()->fullUrlWithQuery(['from' => null]) }}" class="px-3 py-1.5 rounded-lg bg-white/5 hover:bg-white/10 text-sm text-gray-200">This week</a>
            <a href="{{ request()->fullUrlWithQuery(['from' => $from->addWeek()->toDateString()]) }}" class="px-3 py-1.5 rounded-lg bg-white/5 hover:bg-white/10 text-sm text-gray-200">Next ›</a>
        </div>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-7 gap-2">
        @foreach($week['days'] as $date => $items)
            @php $d = \Carbon\Carbon::parse($date); @endphp
            <div class="bg-gray-900/50 border border-white/5 rounded-2xl p-2.5 min-h-[180px] {{ $d->isToday() ? 'ring-1 ring-brand-500/50' : '' }}">
                <p class="text-xs font-bold uppercase tracking-wider {{ $d->isToday() ? 'text-brand-400' : 'text-gray-400' }}">{{ $d->format('D') }} <span class="text-white">{{ $d->format('j') }}</span></p>
                <div class="mt-2 space-y-1.5">
                    @forelse($items as $a)
                        <div class="rounded-lg p-2 text-xs {{ $badge[$a->status] }}" title="{{ $a->service_name }} · {{ $a->email }}">
                            <p class="font-bold">{{ $a->localStart()->format('g:i') }} {{ $a->name }}</p>
                            <p class="opacity-80 truncate">{{ $a->service_name }}@if($a->staff_name) · {{ $a->staff_name }}@endif</p>
                        </div>
                    @empty
                        <p class="text-xs text-gray-600">Nothing booked</p>
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>
@else
    {{-- Agenda --}}
    @forelse($days as $date => $items)
        @php $d = \Carbon\Carbon::parse($date); @endphp
        <div class="mb-6">
            <p class="mb-2 text-sm font-bold text-white">
                {{ $d->isToday() ? 'Today' : ($d->isTomorrow() ? 'Tomorrow' : $d->format('l')) }}
                <span class="font-normal text-gray-500">· {{ $d->format('j F Y') }}</span>
            </p>
            <div class="bg-gray-900/50 border border-white/5 rounded-2xl divide-y divide-white/5">
                @foreach($items as $a)
                <div class="p-4 flex flex-col md:flex-row md:items-center gap-3 md:gap-5">
                    <div class="md:w-28 shrink-0">
                        <p class="text-lg font-extrabold text-white leading-none">{{ $a->localStart()->format('g:i A') }}</p>
                        <p class="mt-1 text-xs text-gray-500">{{ $a->duration }} min</p>
                        <p class="mt-1 text-[11px] font-mono text-gray-500">{{ $a->reference }}</p>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-semibold text-white truncate">{{ $a->name }}
                            <span class="ml-1 px-2 py-0.5 rounded-full text-[11px] font-bold {{ $badge[$a->status] }}">{{ Appointment::STATUSES[$a->status] }}</span>
                            @if($a->source !== 'widget')<span class="ml-1 text-[11px] text-gray-500">via {{ $a->source }}</span>@endif
                        </p>
                        <p class="text-sm text-gray-400 truncate">{{ $a->service_name }}@if($a->staff_name) · with {{ $a->staff_name }}@endif · {{ $a->widget->name }} · <a href="mailto:{{ $a->email }}" class="hover:text-white">{{ $a->email }}</a>@if($a->phone) · {{ $a->phone }}@endif</p>
                        @if($a->notes)<p class="mt-1 text-xs text-gray-500 line-clamp-2">“{{ $a->notes }}”</p>@endif
                        @if($a->answers)<p class="mt-1 text-xs text-gray-500 line-clamp-2">@foreach($a->answers as $q => $v){{ $q }}: <span class="text-gray-300">{{ is_bool($v) ? ($v ? 'Yes' : 'No') : $v }}</span>@if(! $loop->last) · @endif @endforeach</p>@endif
                    </div>
                    <div class="flex flex-wrap items-center gap-2 shrink-0">
                        @if($a->lead_id)<a href="{{ route('leads.show', $a->lead_id) }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold text-gray-300 bg-white/5 hover:bg-white/10">Lead</a>@endif
                        @if($a->conversation_id)<a href="{{ route('conversations.show', $a->conversation_id) }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold text-gray-300 bg-white/5 hover:bg-white/10">Chat</a>@endif
                        @if(in_array($a->status, Appointment::ACTIVE))
                            <button type="button" @click="openMove(@js(['id' => $a->id, 'widget' => $a->widget_id, 'service' => $a->service_id, 'staff' => $a->staff_id, 'name' => $a->name, 'date' => $a->localStart()->toDateString(), 'url' => route('appointments.reschedule', $a)]))" class="px-3 py-1.5 rounded-lg text-xs font-semibold text-gray-300 bg-white/5 hover:bg-white/10">Move</button>
                        @endif
                        @foreach(array_filter([
                            'confirmed' => $a->status === 'pending' ? 'Confirm' : null,
                            'completed' => in_array($a->status, ['confirmed', 'no_show']) && $a->starts_at->isPast() ? 'Completed' : null,
                            'no_show'   => $a->status === 'confirmed' && $a->starts_at->isPast() ? 'No-show' : null,
                            'cancelled' => in_array($a->status, Appointment::ACTIVE) ? 'Cancel' : null,
                        ]) as $status => $label)
                        <form method="POST" action="{{ route('appointments.update', $a) }}" @if($status === 'cancelled') onsubmit="return confirm('Cancel this booking? {{ $a->name }} will get an email.')" @endif>
                            @csrf @method('PATCH')
                            <input type="hidden" name="status" value="{{ $status }}">
                            <button class="px-3 py-1.5 rounded-lg text-xs font-semibold {{ $status === 'confirmed' ? 'bg-brand-500 text-white hover:bg-brand-600' : ($status === 'cancelled' ? 'text-rose-400 hover:bg-rose-500/10' : 'text-gray-300 bg-white/5 hover:bg-white/10') }}">{{ $label }}</button>
                        </form>
                        @endforeach
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    @empty
        <div class="bg-gray-900/50 border border-white/5 rounded-2xl p-12 text-center">
            <p class="text-4xl">📅</p>
            <p class="mt-3 font-semibold text-white">No bookings {{ $view === 'past' ? 'in the past' : 'yet' }}</p>
            <p class="mt-1 text-sm text-gray-400">Turn on booking for a widget above, and visitors can pick a time straight from the chat.</p>
        </div>
    @endforelse

    {{ $appointments->links() }}
@endif

{{-- Add / move booking modal --}}
<div x-show="modal" x-cloak class="fixed inset-0 z-50 grid place-items-center p-4 bg-black/60 backdrop-blur-sm" @keydown.escape.window="modal = null">
    <form method="POST" :action="modal === 'move' ? move.url : @js(route('appointments.store'))" @click.outside="modal = null"
          class="w-full max-w-lg bg-gray-900 border border-white/10 rounded-2xl p-6 space-y-3">
        @csrf
        <template x-if="modal === 'move'"><input type="hidden" name="_method" value="PATCH"></template>
        <p class="text-lg font-bold text-white" x-text="modal === 'move' ? 'Move booking for ' + move.name : 'Add a booking'"></p>

        <div class="grid grid-cols-2 gap-3" x-show="modal === 'new'">
            <select name="widget_id" x-model="form.widget" @change="form.service = current().services[0]?.id; load()" class="{{ $field }} col-span-2">
                <template x-for="w in widgets" :key="w.id"><option :value="w.id" x-text="w.name"></option></template>
            </select>
            <select name="service_id" x-model="form.service" @change="load()" class="{{ $field }}">
                <template x-for="s in current().services" :key="s.id"><option :value="s.id" x-text="s.name"></option></template>
            </select>
            <select name="staff_id" x-model="form.staff" @change="load()" class="{{ $field }}" x-show="current().staff.length">
                <option value="">Anyone free</option>
                <template x-for="m in current().staff" :key="m.id"><option :value="m.id" x-text="m.name"></option></template>
            </select>
        </div>
        <template x-if="modal === 'move' && current().staff.length">
            <select name="staff_id" x-model="form.staff" @change="load()" class="{{ $field }} w-full">
                <option value="">Anyone free</option>
                <template x-for="m in current().staff" :key="m.id"><option :value="m.id" x-text="m.name"></option></template>
            </select>
        </template>

        <input type="date" x-model="form.date" @change="load()" class="{{ $field }} w-full">
        <div class="grid grid-cols-4 gap-1.5 max-h-48 overflow-y-auto">
            <template x-for="s in slots" :key="s.start">
                <label class="cursor-pointer">
                    <input type="radio" name="start" :value="s.start" class="peer sr-only" required>
                    <span class="block text-center py-2 rounded-lg text-xs font-semibold border border-white/10 text-gray-300 peer-checked:bg-brand-500 peer-checked:border-brand-500 peer-checked:text-white" x-text="s.label"></span>
                </label>
            </template>
            <p x-show="!loading && !slots.length" class="col-span-4 text-sm text-gray-500">No free times on this day.</p>
            <p x-show="loading" class="col-span-4 text-sm text-gray-500">Loading times…</p>
        </div>

        <div class="grid grid-cols-2 gap-3" x-show="modal === 'new'">
            <input name="name" placeholder="Name *" class="{{ $field }}" :required="modal === 'new'">
            <input name="email" type="email" placeholder="Email *" class="{{ $field }}" :required="modal === 'new'">
            <input name="phone" placeholder="Phone" class="{{ $field }}">
            <input name="notes" placeholder="Notes" class="{{ $field }}">
        </div>
        <div class="flex justify-end gap-2 pt-2">
            <button type="button" @click="modal = null" class="px-4 py-2 rounded-xl text-sm text-gray-300 hover:text-white">Close</button>
            <button class="px-4 py-2 rounded-xl bg-brand-500 hover:bg-brand-600 text-white text-sm font-semibold" x-text="modal === 'move' ? 'Move and notify' : 'Add and send confirmation'"></button>
        </div>
    </form>
</div>
</div>

<script>
function bookingDesk(widgets, apiBase) {
    return {
        widgets, modal: null, slots: [], loading: false, move: {},
        form: { widget: widgets[0]?.id, service: widgets[0]?.services[0]?.id, staff: '', date: new Date().toISOString().slice(0, 10) },
        current() { return this.widgets.find(w => w.id == this.form.widget) || { services: [], staff: [] }; },
        openNew() { this.modal = 'new'; this.load(14); },
        openMove(a) {
            this.move = a; this.modal = 'move';
            this.form = { widget: a.widget, service: a.service, staff: a.staff || '', date: a.date };
            this.load();
        },
        async load(skip = 0) {
            const w = this.current();
            if (!w.uuid || !this.form.service || !this.form.date) return;
            this.loading = true; this.slots = [];
            const q = new URLSearchParams({ service: this.form.service, date: this.form.date });
            if (this.form.staff) q.set('staff', this.form.staff);
            try {
                const r = await fetch(apiBase + '/' + w.uuid + '/booking/slots?' + q, { headers: { Accept: 'application/json' } });
                this.slots = r.ok ? (await r.json()).slots : [];
            } finally { this.loading = false; }
            // On open, move forward to the first day that still has free times.
            if (skip && !this.slots.length) {
                const d = new Date(this.form.date + 'T12:00:00'); d.setDate(d.getDate() + 1);
                this.form.date = d.toISOString().slice(0, 10);
                return this.load(skip - 1);
            }
        },
    };
}
</script>
@endsection
