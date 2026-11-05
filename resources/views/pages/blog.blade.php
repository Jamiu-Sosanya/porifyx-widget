@extends('layouts.site')
@section('title', 'Blog')
@section('description', "Practical advice on AI assistants, customer conversations and turning website visitors into customers, from the {$name} team.")
@section('eyebrow', 'Blog')
@section('heading', 'Notes on better customer conversations')
@section('lead', 'Practical, honest advice from the team building ' . $name . '. No hype, just what we have learned from watching thousands of real conversations between businesses and their visitors.')

@section('content')
<div class="max-w-6xl mx-auto px-5 py-14 md:py-20">
    @php $first = array_key_first($posts); @endphp
    <div class="grid lg:grid-cols-3 gap-6">
        @foreach($posts as $slug => [$title, $summary, $date, $minutes])
        <a href="{{ route('blog.post', $slug) }}" class="group flex flex-col rounded-3xl overflow-hidden {{ $card }} hover:border-brand-400 transition {{ $slug === $first ? 'lg:col-span-3 lg:flex-row' : '' }}">
            <div class="relative {{ $slug === $first ? 'lg:w-1/2 min-h-56' : 'h-44' }} bg-gradient-to-br from-brand-500 via-brand-600 to-accent-500 overflow-hidden">
                <div class="absolute inset-0 opacity-30" style="background-image: radial-gradient(circle at 20% 30%, #fff 0, transparent 35%), radial-gradient(circle at 80% 80%, #22d3ee 0, transparent 40%)"></div>
                <p class="absolute left-6 bottom-5 text-white/90 text-sm font-semibold">{{ $minutes }} min read</p>
            </div>
            <div class="p-6 md:p-8 flex flex-col {{ $slug === $first ? 'lg:w-1/2 lg:justify-center' : 'flex-1' }}">
                <time class="text-sm {{ $muted }}">{{ \Illuminate\Support\Carbon::parse($date)->format('j F Y') }}</time>
                <h2 class="mt-2 font-extrabold tracking-tight group-hover:text-brand-600 dark:group-hover:text-brand-300 {{ $slug === $first ? 'text-2xl md:text-3xl' : 'text-xl' }}">{{ $title }}</h2>
                <p class="mt-3 leading-relaxed {{ $muted }}">{{ $summary }}</p>
                <span class="mt-5 text-sm font-semibold text-brand-600 dark:text-brand-300">Read article →</span>
            </div>
        </a>
        @endforeach
    </div>
</div>
@endsection
