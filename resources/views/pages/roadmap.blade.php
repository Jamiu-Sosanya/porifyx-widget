@extends('layouts.site')
@section('title', 'Roadmap')
@section('description', "What the {$name} team is building now, next and later.")
@section('eyebrow', 'Roadmap')
@section('heading', 'What we are building, and why')
@section('lead', 'This roadmap shows where the product is heading. It is a plan, not a promise. We review it every month, and customer feedback is the biggest reason things move up or down the list.')

@php
    $columns = [
        ['Now', 'In active development', 'bg-emerald-500', [
            ['Conversation insights', 'A weekly report of the questions your assistant could not answer well, grouped by topic, with suggested content to fill each gap.'],
            ['Calendar booking', 'Let visitors book a call or appointment inside the chat, connected to Google Calendar and Outlook.'],
            ['Shopify product answers', 'Live stock, price and variant information pulled from your store, so answers are always current.'],
        ]],
        ['Next', 'Planned for the coming months', 'bg-brand-500', [
            ['Voice replies', 'Visitors can talk to the assistant and hear replies spoken aloud, which helps on mobile and for accessibility.'],
            ['CRM sync', 'Two-way sync with HubSpot, Pipedrive and Salesforce, without needing to set up webhooks.'],
            ['Answer approval', 'Ask the assistant to draft replies on sensitive topics, then have a person approve them before they are sent.'],
            ['Agency workspaces', 'Separate client workspaces with their own branding, users and billing, managed from one agency account.'],
        ]],
        ['Later', 'Exploring and researching', 'bg-slate-400', [
            ['Actions', 'Let the assistant do things, not just answer: check an order, update a booking or start a return, with your permission.'],
            ['Mobile app for agents', 'Reply to visitors and take over conversations from your phone, with push notifications.'],
            ['Self-hosted AI models', 'Run your assistant on open-source models on your own servers, for teams with strict data requirements.'],
        ]],
    ];
@endphp

@section('content')
<div class="max-w-6xl mx-auto px-5 py-14 md:py-20">
    <div class="grid md:grid-cols-3 gap-6">
        @foreach($columns as [$stage, $hint, $dot, $items])
        <section>
            <div class="flex items-center gap-2.5">
                <span class="w-2.5 h-2.5 rounded-full {{ $dot }}"></span>
                <h2 class="text-xl font-extrabold">{{ $stage }}</h2>
                <span class="ml-auto text-xs font-semibold {{ $muted }}">{{ count($items) }}</span>
            </div>
            <p class="mt-1 text-sm {{ $muted }}">{{ $hint }}</p>
            <div class="mt-5 space-y-4">
                @foreach($items as [$title, $body])
                <article class="rounded-2xl p-5 {{ $card }}">
                    <p class="font-bold">{{ $title }}</p>
                    <p class="mt-2 text-sm leading-relaxed {{ $muted }}">{{ $body }}</p>
                </article>
                @endforeach
            </div>
        </section>
        @endforeach
    </div>

    <div class="mt-20 grid md:grid-cols-2 gap-10 items-center">
        <div class="pfx-prose">
            <h2>How we decide what to build</h2>
            <p>We look at three things: how many customers are asking for something, how much it would change their results, and whether it fits what {{ $name }} should be. A feature that saves every customer an hour a week beats one that impresses in a demo.</p>
            <p>When something ships, it moves to the <a href="{{ route('changelog') }}">changelog</a>.</p>
        </div>
        <div class="rounded-3xl p-8 bg-brand-500 text-white">
            <p class="text-2xl font-extrabold">Missing something important?</p>
            <p class="mt-3 text-white/85">Tell us what you need and why it matters to your business. Every request is read by the product team.</p>
            <a href="{{ route('contact', ['topic' => 'other']) }}#form" class="mt-6 inline-block px-6 py-3 rounded-xl bg-white text-brand-700 font-bold">Suggest a feature</a>
        </div>
    </div>
</div>
@endsection
