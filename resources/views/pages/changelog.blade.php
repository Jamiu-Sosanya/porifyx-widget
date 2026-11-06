@extends('layouts.site')
@section('title', 'Changelog')
@section('description', "New features, improvements and fixes in {$name}.")
@section('eyebrow', 'Changelog')
@section('heading', "What's new in " . $name)
@section('lead', 'Every improvement, fix and new feature, in plain language. We ship small updates often. The most useful ones usually began as a customer email.')

@php
    $tag = ['New' => 'bg-brand-500/10 text-brand-700 dark:text-brand-300', 'Improved' => 'bg-accent-500/10 text-accent-700 dark:text-accent-300', 'Fixed' => 'bg-amber-500/10 text-amber-700 dark:text-amber-300'];
    $releases = [
        ['1.0.0', '5 November 2026', 'PoriFyx Widget 1.0', 'Our first major release brings everything together under one name, with a redesigned widget, a faster dashboard and a much more reliable AI layer.', [
            ['New', 'OpenRouter support. Use hundreds of models from one account and one key, alongside OpenAI, Anthropic, Google Gemini and Groq.'],
            ['New', 'Automatic AI failover. If your main provider has an outage or times out, a backup provider answers instead, so visitors never hit a dead end.'],
            ['New', 'A redesigned chat widget with a cleaner header, clearer message bubbles, a softer launcher and better spacing on small phones.'],
            ['New', 'Light and dark mode across the public website, with the choice remembered between visits.'],
            ['New', 'A contact form for sales, support and partnership enquiries, with a shared inbox for admins.'],
            ['Improved', 'Model choices in widget settings are now grouped by provider and show sensible defaults, so it is easier to pick the right one.'],
            ['Improved', 'Groq now uses Llama 3.3 70B by default, which gives noticeably better answers at the same speed.'],
            ['Improved', 'Webhook requests now identify themselves clearly, which makes them easier to allow through firewalls.'],
            ['Fixed', 'The chat window could grow taller than the screen on some phones during long conversations.'],
            ['Fixed', 'Code snippets could push the page sideways on narrow screens.'],
        ]],
        ['0.9.0', '14 October 2026', 'Better training, safer answers', 'This release focused on answer quality: how the assistant learns from your content, and what it does when it does not know.', [
            ['New', 'Knowledge base versions. Every retrain keeps a snapshot, and you can roll back in one click if answers get worse.'],
            ['New', 'Strict mode keeps answers inside your knowledge base and offers a human instead of guessing.'],
        ]],
        ['0.8.0', '23 September 2026', 'Teams and handoff', 'Built for teams who want the assistant to handle the routine questions while people handle the rest.', [
            ['New', 'Live takeover. Any team member can take over a conversation from the inbox and hand it back when they are done.'],
            ['New', 'AI conversation summaries, visitor mood and suggested replies for agents.'],
            ['New', 'Canned responses for the answers your team types again and again.'],
            ['Improved', 'Agents can now get push notifications when a visitor asks for a person.'],
        ]],
        ['0.7.0', '2 September 2026', 'Growth tools', 'Tools that help your assistant bring in business, not just answer questions.', [
            ['New', 'Lead scoring. Every lead is marked cold, warm or hot based on the conversation.'],
            ['New', 'Proactive messages triggered by time on page, scroll depth or exit intent.'],
            ['New', 'A/B tests for welcome messages, colours and starter questions.'],
        ]],
    ];
@endphp

@section('content')
<div class="max-w-4xl mx-auto px-5 py-14 md:py-20">
    <ol class="relative border-l border-slate-200 dark:border-white/10 ml-2 space-y-16">
        @foreach($releases as [$version, $date, $title, $summary, $items])
        <li class="pl-8 md:pl-10 relative">
            <span class="absolute -left-[7px] top-2 w-3.5 h-3.5 rounded-full bg-brand-500 ring-4 ring-[#f6f7fb] dark:ring-[#08122a]"></span>
            <div class="flex flex-wrap items-center gap-3 text-sm">
                <span class="px-2.5 py-1 rounded-lg font-bold bg-slate-900 text-white dark:bg-white dark:text-slate-900">v{{ $version }}</span>
                <time class="{{ $muted }}">{{ $date }}</time>
            </div>
            <h2 class="mt-4 text-2xl md:text-3xl font-extrabold tracking-tight">{{ $title }}</h2>
            <p class="mt-3 leading-relaxed {{ $muted }}">{{ $summary }}</p>
            <ul class="mt-6 space-y-3">
                @foreach($items as [$type, $text])
                <li class="flex gap-3 items-start">
                    <span class="shrink-0 w-[4.75rem] text-center px-2 py-0.5 rounded-md text-xs font-bold {{ $tag[$type] }}">{{ $type }}</span>
                    <span class="leading-relaxed">{{ $text }}</span>
                </li>
                @endforeach
            </ul>
        </li>
        @endforeach
    </ol>
    <p class="mt-16 text-center {{ $muted }}">Want to know what is coming next? See the <a href="{{ route('roadmap') }}" class="font-semibold text-brand-600 dark:text-brand-300 underline underline-offset-4">roadmap</a>.</p>
</div>
@endsection
