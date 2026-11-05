@extends('layouts.marketing')

@section('title', $title)

@section('content')
    <div class="max-w-5xl mx-auto px-5 lg:px-8 py-16">
        <a href="{{ route('home') }}" class="text-sm text-brand-400 hover:text-brand-300 transition">&larr; Back to home</a>
        <h1 class="mt-8 text-4xl font-bold tracking-tight text-white">{{ $heading }}</h1>
        <p class="mt-4 text-lg text-gray-400 max-w-3xl">{{ $subheading }}</p>

        @foreach($sections as $section)
            <section class="mt-12">
                <h2 class="text-2xl font-semibold text-white">{{ $section['title'] }}</h2>
                <p class="mt-4 text-gray-400 leading-8">{{ $section['body'] }}</p>
            </section>
        @endforeach
    </div>
@endsection
