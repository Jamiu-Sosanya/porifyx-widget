{{-- Long-form page: sticky contents list beside the article. Child views set $toc = [id => label]. --}}
@extends('layouts.site')

@section('content')
<div class="max-w-6xl mx-auto px-5 py-14 md:py-20 lg:grid lg:grid-cols-[220px_minmax(0,1fr)] lg:gap-14">
    @if(! empty($toc))
    <aside class="mb-10 lg:mb-0">
        <nav class="lg:sticky lg:top-24 rounded-2xl p-5 {{ $card }}" aria-label="On this page">
            <p class="text-xs font-bold uppercase tracking-widest text-slate-400">On this page</p>
            <ol class="mt-4 space-y-2.5 text-sm">
                @foreach($toc as $id => $label)
                <li><a href="#{{ $id }}" class="{{ $muted }} hover:text-brand-600 dark:hover:text-brand-300">{{ $label }}</a></li>
                @endforeach
            </ol>
        </nav>
    </aside>
    @endif
    <article class="pfx-prose min-w-0 max-w-3xl">@yield('body')</article>
</div>
@endsection
