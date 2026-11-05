@extends('layouts.site')
@section('title', 'About')
@section('description', "Why we built {$name}, how we work, and what we believe a website assistant should and should not do.")
@section('eyebrow', 'About us')
@section('heading', 'We build the assistant we kept wishing our own websites had.')
@section('lead')
{{ $name }} started with a simple frustration. Good businesses were losing customers at two in the morning because nobody was there to answer a question that took ten seconds to answer.
@endsection

@section('content')
<div class="max-w-6xl mx-auto px-5 py-14 md:py-20 space-y-20">

    <div class="grid lg:grid-cols-2 gap-12 items-start">
        <div class="pfx-prose">
            <h2>Where this started</h2>
            <p>Before {{ $name }} existed, most of us had worked on websites for small and mid-sized companies: agencies, clinics, online shops, software firms. The pattern was always the same. The site looked great and the traffic came in, but visitors left with their questions unanswered. Did this plan include phone support? Could they get delivery by Friday? Was there a discount for charities?</p>
            <p>The answers were all written down somewhere, in a help page, a PDF or an old email thread. The visitor just could not find them fast enough, and the team could not be online all night to help.</p>
            <p>Early chatbots made things worse. They were rigid decision trees that forced people down menus, or they were "AI" that confidently invented return policies nobody had ever agreed to. Neither earned anyone's trust.</p>
            <p>So we set out to build something different: an assistant that learns from the material a business already has, answers in plain language, admits when it does not know, and passes the conversation to a person when that is the right thing to do.</p>
        </div>
        <div class="grid sm:grid-cols-2 gap-4">
            @foreach([
                ['24/7', 'Your assistant answers at any hour, including weekends and public holidays.'],
                ['1 script tag', 'All it takes to add it to any website, whatever it is built with.'],
                ['5 AI providers', 'OpenAI, Anthropic, Google Gemini, Groq and OpenRouter, with automatic failover.'],
                ['10 channels', 'One trained assistant for your website, WhatsApp, Messenger, Slack, email and more.'],
            ] as [$big, $small])
            <div class="rounded-2xl p-6 {{ $card }}">
                <p class="text-3xl font-extrabold tracking-tight text-brand-600 dark:text-brand-400">{{ $big }}</p>
                <p class="mt-2 text-sm leading-relaxed {{ $muted }}">{{ $small }}</p>
            </div>
            @endforeach
        </div>
    </div>

    <div>
        <p class="{{ $eyebrow }}">What we believe</p>
        <h2 class="mt-3 text-3xl md:text-4xl font-extrabold tracking-tight">Five principles that shape every decision</h2>
        <div class="mt-10 grid md:grid-cols-2 lg:grid-cols-3 gap-5">
            @foreach([
                ['Honest answers beat clever ones', 'An assistant that says "I am not sure, let me get someone" builds more trust than one that guesses. Strict mode exists because we would rather lose a sentence than mislead a customer.'],
                ['People stay in charge', 'Automation should take the repetitive work away from your team, not take your team away from your customers. Anyone on your team can take over a conversation in one click.'],
                ['Your data is yours', 'Conversations, leads and knowledge stay in your account. We do not use your content to train shared models, and you can export or delete everything whenever you like.'],
                ['Fast is a feature', 'The widget loads after your page does, so it never slows your site down. Replies stream word by word, so visitors are not left watching a spinner.'],
                ['Simple first, powerful later', 'You can go live in an afternoon with a script tag and a few URLs. Webhooks, automations, A/B tests and multi-channel routing are there when you are ready for them.'],
                ['Pricing you can explain', 'Plans are priced on what you use, with no setup fees and no per-seat surprises. If you outgrow a plan, you move up. If you do not, you never pay for more.'],
            ] as [$title, $body])
            <div class="rounded-2xl p-6 {{ $card }}">
                <p class="font-bold text-lg">{{ $title }}</p>
                <p class="mt-3 text-sm leading-relaxed {{ $muted }}">{{ $body }}</p>
            </div>
            @endforeach
        </div>
    </div>

    <div class="grid lg:grid-cols-2 gap-12">
        <div class="pfx-prose">
            <h2>Who uses {{ $name }}</h2>
            <p>Our customers are rarely large enterprises with a hundred-person support desk. More often they are teams of five to fifty people who care a great deal about every enquiry and cannot afford to miss one.</p>
            <ul>
                <li><strong>Online shops</strong> answer questions about sizing, stock and delivery, and recover carts that would otherwise be abandoned.</li>
                <li><strong>Software companies</strong> qualify trial sign-ups and route serious buyers straight to their sales team.</li>
                <li><strong>Clinics and service businesses</strong> handle opening hours, booking questions and first-contact enquiries without tying up the front desk.</li>
                <li><strong>Agencies</strong> deploy a branded assistant for each client and manage all of them from one account.</li>
                <li><strong>Schools, charities and membership groups</strong> help people find the right form, event or contact in seconds.</li>
            </ul>
        </div>
        <div class="pfx-prose">
            <h2>How we work</h2>
            <p>We are a small, remote team, and we like it that way. Everyone who builds the product also talks to customers, reads support conversations and sees what goes wrong. That keeps us honest about what actually matters.</p>
            <p>We ship small improvements often rather than big releases rarely. Every change is listed in our <a href="{{ route('changelog') }}">changelog</a>, and you can see what we are working on next in our public <a href="{{ route('roadmap') }}">roadmap</a>.</p>
            <p>Many of the best features in {{ $name }} came from customer emails. If something is missing, slow or confusing, we genuinely want to hear about it. <a href="{{ route('contact') }}">Send us a message</a> and a member of the product team will read it.</p>
        </div>
    </div>
</div>
@endsection
