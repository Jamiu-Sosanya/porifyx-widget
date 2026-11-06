@extends('layouts.app')
@section('title', 'Answers: ' . $widget->name)
@section('page-title', 'Answers & gaps')
@section('content')
@php $field = 'w-full bg-white/5 border border-white/10 rounded-xl px-3.5 py-2.5 text-sm text-white'; @endphp
<div class="grid xl:grid-cols-2 gap-6" x-data="{ q: '', gap: '' }">

    {{-- Unanswered questions --}}
    <section class="rounded-2xl border border-white/5 bg-gray-900/50 p-5 sm:p-6 min-w-0">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h3 class="text-base font-semibold text-white">Questions the assistant couldn't answer</h3>
                <p class="mt-1 text-xs text-gray-500">Grouped by how often visitors ask. Answer one and the assistant uses your words from then on.</p>
            </div>
            <span class="shrink-0 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-500/15 text-amber-400">{{ $gaps->count() }} open</span>
        </div>
        <ul class="mt-5 divide-y divide-white/5">
            @forelse($gaps as $g)
            <li class="py-3 flex items-start gap-3">
                <span class="mt-0.5 grid place-items-center min-w-8 h-8 px-1.5 rounded-lg bg-white/5 text-xs font-bold text-gray-300" title="Times asked">{{ $g->times_asked }}×</span>
                <div class="min-w-0 flex-1">
                    <p class="text-sm text-gray-200 break-words">{{ $g->question }}</p>
                    <p class="text-[11px] text-gray-500">Last asked {{ $g->last_asked_at?->diffForHumans() }}</p>
                </div>
                <div class="flex shrink-0 gap-1.5">
                    <button type="button" @click="q = @js($g->question); gap = '{{ $g->id }}'; $nextTick(() => $refs.answer.focus())" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-brand-500 text-white">Answer</button>
                    <form method="POST" action="{{ route('widgets.gaps.dismiss', [$widget, $g]) }}">@csrf
                        <button class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-white/5 text-gray-300 hover:bg-white/10">Dismiss</button>
                    </form>
                </div>
            </li>
            @empty
            <li class="py-10 text-center text-sm text-gray-500">No unanswered questions yet. They appear here as visitors chat.</li>
            @endforelse
        </ul>
    </section>

    <div class="space-y-6 min-w-0">
        {{-- New curated answer --}}
        <section class="rounded-2xl border border-white/5 bg-gray-900/50 p-5 sm:p-6">
            <h3 class="text-base font-semibold text-white">Add a curated answer</h3>
            <p class="mt-1 text-xs text-gray-500">For pricing, refunds or anything that must be worded exactly. Used instead of the AI when a visitor's question matches.</p>
            <form method="POST" action="{{ route('widgets.answers.store', $widget) }}" class="mt-4 space-y-3">
                @csrf
                <input type="hidden" name="gap_id" :value="gap">
                <input name="question" x-model="q" required maxlength="255" placeholder="Question, e.g. What is your refund policy?" class="{{ $field }}">
                <input name="keywords" maxlength="255" placeholder="Trigger phrases, comma separated (optional), e.g. refund, money back" class="{{ $field }}">
                <textarea name="answer" x-ref="answer" required rows="4" maxlength="4000" placeholder="Your exact answer. Markdown links and bold work." class="{{ $field }}"></textarea>
                <button class="w-full sm:w-auto px-5 py-2.5 rounded-xl bg-brand-500 text-white text-sm font-semibold">Save answer</button>
            </form>
        </section>

        {{-- Existing curated answers --}}
        <section class="rounded-2xl border border-white/5 bg-gray-900/50 p-5 sm:p-6">
            <h3 class="text-base font-semibold text-white">Curated answers <span class="text-gray-500 font-normal">({{ $answers->count() }})</span></h3>
            <div class="mt-4 space-y-3">
                @forelse($answers as $a)
                <details class="group rounded-xl border border-white/5 bg-white/[0.02]">
                    <summary class="flex items-center gap-3 px-4 py-3 cursor-pointer list-none">
                        <span class="w-2 h-2 shrink-0 rounded-full {{ $a->is_active ? 'bg-emerald-400' : 'bg-gray-600' }}"></span>
                        <span class="flex-1 min-w-0 truncate text-sm text-gray-200">{{ $a->question }}</span>
                        <span class="shrink-0 text-[11px] text-gray-500">{{ $a->hits }} uses</span>
                    </summary>
                    <form method="POST" action="{{ route('widgets.answers.update', [$widget, $a]) }}" class="px-4 pb-4 space-y-2.5">
                        @csrf @method('PUT')
                        <input name="question" value="{{ $a->question }}" required maxlength="255" class="{{ $field }}">
                        <input name="keywords" value="{{ $a->keywords }}" maxlength="255" placeholder="Trigger phrases, comma separated" class="{{ $field }}">
                        <textarea name="answer" rows="4" required maxlength="4000" class="{{ $field }}">{{ $a->answer }}</textarea>
                        <div class="flex flex-wrap items-center gap-3">
                            <label class="flex items-center gap-2 text-xs text-gray-300"><input type="checkbox" name="is_active" value="1" @checked($a->is_active)> Active</label>
                            <button class="ml-auto px-4 py-2 rounded-lg bg-brand-500 text-white text-xs font-semibold">Save</button>
                            <button form="del-{{ $a->id }}" class="px-4 py-2 rounded-lg bg-rose-500/10 text-rose-400 text-xs font-semibold">Delete</button>
                        </div>
                    </form>
                    <form id="del-{{ $a->id }}" method="POST" action="{{ route('widgets.answers.destroy', [$widget, $a]) }}" onsubmit="return confirm('Delete this answer?')">@csrf @method('DELETE')</form>
                </details>
                @empty
                <p class="py-6 text-center text-sm text-gray-500">No curated answers yet.</p>
                @endforelse
            </div>
        </section>
    </div>
</div>
@endsection
