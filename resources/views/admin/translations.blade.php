@extends('layouts.admin')
@section('title', 'Translations')
@section('page-title', 'Translations')
@section('content')
<div class="flex flex-col lg:flex-row lg:items-end gap-3 mb-5">
    <form method="GET" class="flex flex-wrap gap-2 flex-1">
        <select name="locale" onchange="this.form.submit()" class="bg-white/5 border border-white/10 rounded-xl px-3 py-2 text-sm text-white">
            @foreach(\App\Support\I18n::LOCALES as $code => $name)
            @continue($code === 'en')
            <option value="{{ $code }}" @selected($code === $locale)>{{ $name }} ({{ $code }}) {{ isset($counts[$code]) ? '· ' . round($counts[$code] / max(1, $total) * 100) . '%' : '' }}</option>
            @endforeach
        </select>
        <input name="q" value="{{ $q }}" placeholder="Search text" class="bg-white/5 border border-white/10 rounded-xl px-3 py-2 text-sm text-white">
        <label class="flex items-center gap-2 text-xs text-gray-300"><input type="checkbox" name="show" value="missing" @checked(request('show') === 'missing') onchange="this.form.submit()"> Missing only</label>
    </form>
    <div class="flex items-center gap-3">
        <span class="text-xs text-gray-400">{{ $count }} / {{ $total }} translated</span>
        <form method="POST" action="{{ route('admin.translations.generate') }}">@csrf<input type="hidden" name="locale" value="{{ $locale }}">
            <button class="px-4 py-2 rounded-xl bg-brand-500 text-white text-sm font-semibold">Translate missing with AI</button></form>
    </div>
</div>
<p class="mb-4 text-xs text-gray-500">Languages are translated automatically the first time someone picks them. Your edits here are kept and never overwritten by the AI. Customer data (chats, names, leads) is never translated or sent.</p>

<form method="POST" action="{{ route('admin.translations.save') }}" class="rounded-2xl border border-white/5 bg-gray-900/50 divide-y divide-white/5">
    @csrf @method('PUT')
    <input type="hidden" name="locale" value="{{ $locale }}">
    @forelse($rows as $src)
    @php $h = md5($src); $t = $done[$src] ?? null; @endphp
    <div class="grid md:grid-cols-2 gap-2 p-3">
        <p class="text-sm text-gray-300 break-words">{{ $src }}</p>
        <div class="flex gap-2 items-start">
            <input type="hidden" name="sources[{{ $h }}]" value="{{ $src }}">
            <!--notranslate--><input name="values[{{ $h }}]" value="{{ $t->value ?? '' }}" placeholder="Not translated yet" @if(in_array($locale, \App\Support\I18n::RTL)) dir="rtl" @endif
                   class="flex-1 min-w-0 bg-white/5 border {{ $t?->is_override ? 'border-brand-500/40' : 'border-white/10' }} rounded-lg px-3 py-1.5 text-sm text-white"><!--/notranslate-->
        </div>
    </div>
    @empty
    <p class="p-10 text-center text-sm text-gray-500">Nothing matches.</p>
    @endforelse
    <div class="p-4 flex items-center justify-between gap-3"><div>{{ $rows->links() }}</div><button class="px-5 py-2.5 rounded-xl bg-brand-500 text-white text-sm font-semibold">Save this page</button></div>
</form>
@endsection
