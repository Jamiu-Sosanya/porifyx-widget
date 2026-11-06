@extends('layouts.app')
@section('title', 'Help centre: ' . $widget->name)
@section('page-title', 'Help centre')
@section('content')
@php $field = 'w-full bg-white/5 border border-white/10 rounded-xl px-3.5 py-2.5 text-sm text-white'; @endphp
<div class="mb-5 flex flex-col sm:flex-row sm:items-center gap-3 rounded-2xl border border-white/5 bg-gray-900/50 p-4">
    <p class="text-sm text-gray-300 flex-1 min-w-0">Public help centre: <a href="{{ route('help.index', $widget->uuid) }}" target="_blank" class="text-brand-400 break-all">{{ route('help.index', $widget->uuid) }}</a></p>
    <span class="text-xs text-gray-500">{{ $articles->where('is_published', true)->count() }} published · {{ $articles->sum('views') }} views</span>
</div>
<div class="grid xl:grid-cols-[minmax(0,1fr)_340px] gap-6">
    {{-- Article editor --}}
    <form method="POST" action="{{ route('widgets.help.articles.save', $widget) }}" class="min-w-0 rounded-2xl border border-white/5 bg-gray-900/50 p-5 sm:p-6 space-y-3">
        @csrf
        <input type="hidden" name="id" value="{{ $editing?->id }}">
        <div class="flex items-center justify-between">
            <h3 class="text-base font-semibold text-white">{{ $editing ? 'Edit article' : 'New article' }}</h3>
            @if($editing)<a href="{{ route('widgets.help', $widget) }}" class="text-xs text-gray-400 hover:text-white">+ New instead</a>@endif
        </div>
        <input name="title" required maxlength="200" value="{{ old('title', $editing?->title) }}" placeholder="Title, e.g. How do refunds work?" class="{{ $field }}">
        <select name="help_category_id" class="{{ $field }}">
            <option value="">No category</option>
            @foreach($categories as $c)<option value="{{ $c->id }}" @selected(old('help_category_id', $editing?->help_category_id) == $c->id)>{{ $c->icon }} {{ $c->name }}</option>@endforeach
        </select>
        <textarea name="body" required rows="14" maxlength="50000" placeholder="Write in Markdown: ## Headings, **bold**, - lists, [links](https://…)" class="{{ $field }} font-mono text-[13px]">{{ old('body', $editing?->body) }}</textarea>
        <div class="flex flex-wrap items-center gap-3">
            <label class="flex items-center gap-2 text-sm text-gray-300"><input type="checkbox" name="is_published" value="1" @checked(old('is_published', $editing?->is_published ?? true))> Published</label>
            <button class="ml-auto px-6 py-2.5 rounded-xl bg-brand-500 text-white text-sm font-semibold">Save article</button>
        </div>
        @if($errors->any())<p class="text-sm text-rose-400">{{ $errors->first() }}</p>@endif
    </form>

    <div class="space-y-6 min-w-0">
        {{-- Categories --}}
        <section class="rounded-2xl border border-white/5 bg-gray-900/50 p-5">
            <h3 class="text-sm font-semibold text-white">Categories</h3>
            <ul class="mt-3 space-y-1.5">
                @forelse($categories as $c)
                <li class="flex items-center gap-2 text-sm text-gray-200">
                    <span class="w-6 text-center">{{ $c->icon ?: '📁' }}</span><span class="flex-1 truncate">{{ $c->name }}</span>
                    <span class="text-[11px] text-gray-500">{{ $c->articles_count }}</span>
                    <form method="POST" action="{{ route('widgets.help.categories.destroy', [$widget, $c]) }}" onsubmit="return confirm('Delete this category? Its articles are kept.')">@csrf @method('DELETE')<button class="text-xs text-rose-400 px-1" aria-label="Delete category">✕</button></form>
                </li>
                @empty
                <li class="text-xs text-gray-500">No categories yet.</li>
                @endforelse
            </ul>
            <form method="POST" action="{{ route('widgets.help.categories.store', $widget) }}" class="mt-4 grid grid-cols-[64px_minmax(0,1fr)] gap-2">
                @csrf
                <input name="icon" maxlength="16" placeholder="💳" class="{{ $field }} text-center">
                <input name="name" required maxlength="80" placeholder="Category name" class="{{ $field }}">
                <input name="description" maxlength="300" placeholder="Short description (optional)" class="{{ $field }} col-span-2">
                <button class="col-span-2 py-2 rounded-xl bg-white/5 hover:bg-white/10 text-xs font-semibold text-white">Add category</button>
            </form>
        </section>

        {{-- Articles --}}
        <section class="rounded-2xl border border-white/5 bg-gray-900/50 p-5">
            <h3 class="text-sm font-semibold text-white">Articles ({{ $articles->count() }})</h3>
            <ul class="mt-3 divide-y divide-white/5">
                @forelse($articles as $a)
                <li class="py-2.5 flex items-center gap-2">
                    <span class="w-1.5 h-1.5 shrink-0 rounded-full {{ $a->is_published ? 'bg-emerald-400' : 'bg-gray-600' }}" title="{{ $a->is_published ? 'Published' : 'Draft' }}"></span>
                    <a href="{{ route('widgets.help', [$widget, 'edit' => $a->id]) }}" class="flex-1 min-w-0">
                        <span class="block truncate text-sm text-gray-200 hover:text-white">{{ $a->title }}</span>
                        <span class="block text-[11px] text-gray-500">{{ $a->category?->name ?? 'No category' }} · {{ $a->views }} views</span>
                    </a>
                    <form method="POST" action="{{ route('widgets.help.articles.destroy', [$widget, $a]) }}" onsubmit="return confirm('Delete this article?')">@csrf @method('DELETE')<button class="text-xs text-rose-400 px-1" aria-label="Delete article">✕</button></form>
                </li>
                @empty
                <li class="py-4 text-xs text-gray-500">No articles yet.</li>
                @endforelse
            </ul>
        </section>
    </div>
</div>
@endsection
