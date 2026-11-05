{{-- Blog article. Child views receive $post = [title, summary, date, minutes], $slug and $posts. --}}
@extends('layouts.site')
@section('title', $post[0])
@section('description', $post[1])
@section('eyebrow', 'Blog')
@section('heading', $post[0])
@section('lead', $post[1])
@section('meta', \Illuminate\Support\Carbon::parse($post[2])->format('j F Y') . ' · ' . $post[3] . ' min read · The ' . $name . ' team')

@section('content')
<div class="max-w-3xl mx-auto px-5 py-14 md:py-20">
    <article class="pfx-prose">@yield('body')</article>

    <div class="mt-16 pt-10 border-t border-slate-200 dark:border-white/10">
        <p class="text-sm font-bold uppercase tracking-widest text-slate-400">Keep reading</p>
        <div class="mt-5 grid sm:grid-cols-2 gap-4">
            @foreach(collect($posts)->except($slug) as $s => [$title, $summary])
            <a href="{{ route('blog.post', $s) }}" class="group rounded-2xl p-5 {{ $card }} hover:border-brand-400">
                <p class="font-bold group-hover:text-brand-600 dark:group-hover:text-brand-300">{{ $title }}</p>
                <p class="mt-2 text-sm line-clamp-2 {{ $muted }}">{{ $summary }}</p>
            </a>
            @endforeach
        </div>
    </div>
</div>
@endsection
