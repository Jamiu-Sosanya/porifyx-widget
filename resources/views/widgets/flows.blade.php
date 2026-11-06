@extends('layouts.app')
@section('title', 'Chat flows: ' . $widget->name)
@section('page-title', 'Chat flows')
@section('content')
@php
    $field = 'w-full bg-white/5 border border-white/10 rounded-xl px-3 py-2 text-sm text-white';
    $types = \App\Models\ChatFlow::STEP_TYPES;
    $ops = \App\Models\ChatFlow::OPERATORS;
    $open = $editing || $isNew;
    $steps = old('steps', $editing?->steps ?? [['type' => 'message', 'text' => 'Hi {{name}}! Happy to help with a quote.'], ['type' => 'question', 'text' => 'What is your budget?', 'var' => 'budget', 'choices' => 'Under £1k, £1k-£5k, Over £5k']]);
@endphp
<div class="grid xl:grid-cols-[280px_minmax(0,1fr)_320px] gap-6">
    {{-- Flow list --}}
    <aside class="space-y-2 min-w-0">
        <a href="{{ route('widgets.flows', [$widget, 'edit' => 'new']) }}" class="block text-center px-4 py-2.5 rounded-xl bg-brand-500 text-white text-sm font-semibold">+ New flow</a>
        @forelse($flows as $f)
        <a href="{{ route('widgets.flows', [$widget, 'edit' => $f->id]) }}" class="block rounded-xl border px-4 py-3 {{ $editing?->id === $f->id ? 'border-brand-500/50 bg-brand-500/10' : 'border-white/5 bg-gray-900/50 hover:bg-white/[0.03]' }}">
            <span class="flex items-center gap-2"><span class="w-2 h-2 rounded-full {{ $f->is_active ? 'bg-emerald-400' : 'bg-gray-600' }}"></span><span class="text-sm font-medium text-white truncate">{{ $f->name }}</span></span>
            <span class="block mt-0.5 text-[11px] text-gray-500 truncate">“{{ $f->trigger_keywords }}” · {{ count($f->steps ?? []) }} steps · {{ $f->runs }} runs</span>
        </a>
        @empty
        <p class="text-xs text-gray-500 px-1">Flows run fixed, step-by-step conversations (quotes, qualification, returns) when a visitor's message matches a trigger. Outside a flow the AI answers as usual.</p>
        @endforelse
    </aside>

    @if($open)
    {{-- Editor --}}
    <form method="POST" action="{{ route('widgets.flows.save', $widget) }}" class="min-w-0 space-y-4" x-data="{ steps: @js(array_values($steps)), types: @js($types),
            add(t) { this.steps.push({ type: t, text: '', var: '', choices: '', op: 'equals', value: '', goto: '', tag: '', url: '' }) },
            move(i, d) { const j = i + d; if (j < 0 || j >= this.steps.length) return; const s = this.steps.splice(i, 1)[0]; this.steps.splice(j, 0, s) } }">
        @csrf
        <input type="hidden" name="id" value="{{ $editing?->id }}">
        <section class="rounded-2xl border border-white/5 bg-gray-900/50 p-5 grid sm:grid-cols-2 gap-3">
            <input name="name" required maxlength="120" value="{{ old('name', $editing?->name) }}" placeholder="Flow name, e.g. Get a quote" class="{{ $field }} sm:col-span-2">
            <input name="trigger_keywords" required maxlength="500" value="{{ old('trigger_keywords', $editing?->trigger_keywords ?? 'quote, pricing for my project') }}" placeholder="Trigger phrases, comma separated" class="{{ $field }}">
            <select name="match_mode" class="{{ $field }}">
                @foreach(['any' => 'Any phrase appears', 'all' => 'All phrases appear', 'exact' => 'Message is exactly a phrase'] as $k => $l)
                <option value="{{ $k }}" @selected(old('match_mode', $editing?->match_mode ?? 'any') === $k)>{{ $l }}</option>
                @endforeach
            </select>
            <label class="flex items-center gap-2 text-sm text-gray-300"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $editing?->is_active ?? true))> Active</label>
        </section>

        <ol class="space-y-3">
            <template x-for="(s, i) in steps" :key="i">
                <li class="rounded-2xl border border-white/5 bg-gray-900/50 p-4 space-y-2">
                    <div class="flex items-center gap-2">
                        <span class="grid place-items-center w-7 h-7 rounded-full bg-brand-500/15 text-brand-400 text-xs font-bold" x-text="i + 1"></span>
                        <select :name="`steps[${i}][type]`" x-model="s.type" class="flex-1 min-w-0 bg-white/5 border border-white/10 rounded-lg px-2.5 py-1.5 text-sm text-white">
                            <template x-for="(label, k) in types" :key="k"><option :value="k" x-text="label" :selected="s.type === k"></option></template>
                        </select>
                        <button type="button" @click="move(i, -1)" class="text-gray-500 hover:text-white px-1" aria-label="Move up">▲</button>
                        <button type="button" @click="move(i, 1)" class="text-gray-500 hover:text-white px-1" aria-label="Move down">▼</button>
                        <button type="button" @click="steps.splice(i, 1)" x-show="steps.length > 1" class="text-rose-400 text-xs px-1">Remove</button>
                    </div>
                    <textarea x-show="['message', 'question', 'escalate'].includes(s.type)" :name="`steps[${i}][text]`" x-model="s.text" rows="2" maxlength="1000" placeholder="Text. Use @{{answer_name}} to insert an answer." class="{{ $field }}"></textarea>
                    <div x-show="s.type === 'question'" class="grid sm:grid-cols-2 gap-2">
                        <input :name="`steps[${i}][var]`" x-model="s.var" maxlength="40" placeholder="Save answer as (e.g. budget)" class="{{ $field }}">
                        <input :name="`steps[${i}][choices]`" x-model="s.choices" maxlength="300" placeholder="Buttons, comma separated (optional)" class="{{ $field }}">
                    </div>
                    <div x-show="s.type === 'branch'" class="grid grid-cols-2 sm:grid-cols-4 gap-2 items-center text-sm text-gray-400">
                        <input :name="`steps[${i}][var]`" x-model="s.var" maxlength="40" placeholder="If answer…" class="{{ $field }}">
                        <select :name="`steps[${i}][op]`" x-model="s.op" class="{{ $field }}">@foreach($ops as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select>
                        <input :name="`steps[${i}][value]`" x-model="s.value" maxlength="200" placeholder="value" class="{{ $field }}">
                        <input :name="`steps[${i}][goto]`" x-model="s.goto" type="number" min="1" :max="steps.length" placeholder="go to step #" class="{{ $field }}">
                        <p class="col-span-2 sm:col-span-4 text-[11px] text-gray-500">Otherwise continues with the next step.</p>
                    </div>
                    <input x-show="s.type === 'tag'" :name="`steps[${i}][tag]`" x-model="s.tag" maxlength="30" placeholder="Tag, e.g. quote-request" class="{{ $field }}">
                    <input x-show="s.type === 'webhook'" :name="`steps[${i}][url]`" x-model="s.url" type="url" maxlength="500" placeholder="https://hooks.zapier.com/… (receives all answers)" class="{{ $field }}">
                </li>
            </template>
        </ol>
        <div class="flex flex-wrap gap-1.5">
            @foreach($types as $k => $l)
            <button type="button" @click="add('{{ $k }}')" class="px-3 py-1.5 rounded-lg bg-white/5 hover:bg-white/10 text-xs font-semibold text-gray-200">+ {{ $l }}</button>
            @endforeach
        </div>
        @if($errors->any())<ul class="rounded-xl bg-rose-500/10 border border-rose-500/20 p-3 text-sm text-rose-300 space-y-1">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>@endif
        <div class="flex flex-wrap gap-3">
            <button class="px-6 py-3 rounded-xl bg-brand-500 text-white text-sm font-semibold">Save flow</button>
            @if($editing)<button form="del-flow" class="px-4 py-3 rounded-xl bg-rose-500/10 text-rose-400 text-sm font-semibold">Delete</button>@endif
        </div>
    </form>
    @if($editing)<form id="del-flow" method="POST" action="{{ route('widgets.flows.destroy', [$widget, $editing]) }}" onsubmit="return confirm('Delete this flow?')">@csrf @method('DELETE')</form>@endif

    {{-- Tester --}}
    <aside class="rounded-2xl border border-white/5 bg-gray-900/50 p-5 self-start xl:sticky xl:top-28" x-data="{ msgs: [], text: '', state: null, busy: false,
            async send(t) { t = (t || this.text).trim(); if (!t || this.busy) return; this.text = ''; this.msgs.push({ me: true, t }); this.busy = true;
                const r = await fetch(@js(route('widgets.flows.test', $widget)), { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }, body: JSON.stringify({ message: t, state: this.state }) });
                const j = await r.json(); this.busy = false; this.state = j.state;
                this.msgs.push({ me: false, t: j.reply ?? '(No flow matched: the AI would answer this.)', c: j.choices || [], tags: j.tags || [], esc: j.escalate });
                this.$nextTick(() => this.$refs.log.scrollTop = 1e9) } }">
        <p class="text-sm font-semibold text-white">Test saved flows</p>
        <p class="text-xs text-gray-500">Dry run: no webhooks are sent and nothing is stored.</p>
        <div x-ref="log" class="mt-3 h-72 overflow-y-auto space-y-2 text-sm">
            <template x-for="(m, i) in msgs" :key="i">
                <div :class="m.me ? 'text-right' : ''">
                    <p class="inline-block max-w-[90%] rounded-xl px-3 py-2 whitespace-pre-line text-left" :class="m.me ? 'bg-brand-500 text-white' : 'bg-white/5 text-gray-200'" x-text="m.t"></p>
                    <div class="mt-1 flex flex-wrap gap-1" x-show="m.c && m.c.length"><template x-for="c in m.c"><button type="button" @click="send(c)" class="px-2 py-0.5 rounded-full border border-white/10 text-xs text-gray-300" x-text="c"></button></template></div>
                    <p class="text-[10px] text-amber-400" x-show="m.tags && m.tags.length" x-text="'Tagged: ' + (m.tags || []).join(', ')"></p>
                    <p class="text-[10px] text-rose-400" x-show="m.esc">→ Handed to a human</p>
                </div>
            </template>
        </div>
        <form @submit.prevent="send()" class="mt-3 flex gap-2">
            <input x-model="text" placeholder="Type a trigger phrase…" class="flex-1 min-w-0 bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-sm text-white">
            <button class="px-3 rounded-lg bg-white/5 hover:bg-white/10 text-xs font-semibold text-white">Send</button>
        </form>
        <button type="button" @click="msgs = []; state = null" class="mt-2 text-[11px] text-gray-500 hover:text-white">Reset test</button>
    </aside>
    @else
    <div class="xl:col-span-2 rounded-2xl border border-dashed border-white/10 p-10 text-center text-sm text-gray-400">Pick a flow on the left or create a new one.</div>
    @endif
</div>
@endsection
