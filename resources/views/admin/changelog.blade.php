@extends('layouts.admin')
@section('title', 'Changelog')
@section('page-title', 'Changelog')
@section('content')
@php $field = 'w-full bg-white/5 border border-white/10 rounded-xl px-3.5 py-2.5 text-sm text-white'; @endphp
<div class="grid xl:grid-cols-[minmax(0,1fr)_420px] gap-6">
    <section class="rounded-2xl border border-white/5 bg-gray-900/50 divide-y divide-white/5">
        @forelse($entries as $e)
        <div class="p-5 flex flex-col sm:flex-row sm:items-start gap-3">
            <div class="min-w-0 flex-1">
                <p class="text-sm font-semibold text-white"><span class="mr-2 px-2 py-0.5 rounded-full text-[10px] uppercase font-bold {{ ['new' => 'bg-brand-500/15 text-brand-400', 'improved' => 'bg-accent-500/15 text-accent-300', 'fixed' => 'bg-emerald-500/15 text-emerald-400'][$e->tag] ?? '' }}">{{ $e->tag }}</span>{{ $e->title }}</p>
                <p class="mt-1 text-sm text-gray-400 whitespace-pre-line">{{ $e->body }}</p>
                <p class="mt-1 text-[11px] text-gray-500">{{ $e->published_at ? 'Published ' . \Carbon\Carbon::parse($e->published_at)->diffForHumans() : 'Draft' }}</p>
            </div>
            <div class="flex gap-2 shrink-0">
                <form method="POST" action="{{ route('admin.changelog.toggle', $e->id) }}">@csrf<button class="px-3 py-1.5 rounded-lg bg-white/5 hover:bg-white/10 text-xs font-semibold text-white">{{ $e->published_at ? 'Unpublish' : 'Publish' }}</button></form>
                <form method="POST" action="{{ route('admin.changelog.destroy', $e->id) }}" onsubmit="return confirm('Delete?')">@csrf @method('DELETE')<button class="px-3 py-1.5 rounded-lg bg-rose-500/10 text-rose-400 text-xs font-semibold">Delete</button></form>
            </div>
        </div>
        @empty
        <p class="p-10 text-center text-sm text-gray-500">No entries yet. Published entries appear at <a href="{{ route('changelog') }}" class="text-brand-400">/changelog</a> and as a “What’s new” note in customers’ dashboards.</p>
        @endforelse
    </section>
    <form method="POST" action="{{ route('admin.changelog.store') }}" class="rounded-2xl border border-white/5 bg-gray-900/50 p-5 space-y-3 self-start">
        @csrf
        <h3 class="text-sm font-semibold text-white">New entry</h3>
        <input name="title" required maxlength="200" placeholder="Title, e.g. Chat flows are here" class="{{ $field }}">
        <select name="tag" class="{{ $field }}"><option value="new">New</option><option value="improved">Improved</option><option value="fixed">Fixed</option></select>
        <textarea name="body" required rows="5" maxlength="5000" placeholder="What changed and why it matters" class="{{ $field }}"></textarea>
        <label class="flex items-center gap-2 text-sm text-gray-300"><input type="checkbox" name="publish" value="1" checked> Publish now</label>
        <button class="w-full py-2.5 rounded-xl bg-brand-500 text-white text-sm font-semibold">Save</button>
    </form>
</div>
@endsection
