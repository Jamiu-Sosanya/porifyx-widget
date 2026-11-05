@extends('layouts.site')
@section('title', 'Careers')
@section('description', "Work at {$name}: a small remote team building AI assistants that businesses can trust.")
@section('eyebrow', 'Careers')
@section('heading', 'Do the best work of your career on a team small enough to notice it.')
@section('lead', "We are a remote-first team building software that thousands of visitors talk to every day. We hire slowly and carefully, and we look for people who care about the details customers actually feel.")

@section('content')
<div class="max-w-6xl mx-auto px-5 py-14 md:py-20 space-y-20">

    <div class="grid lg:grid-cols-[minmax(0,1fr)_380px] gap-12">
        <div class="pfx-prose">
            <h2>What it is like to work here</h2>
            <p>There are no layers of managers between you and the product. You will own real problems from the first week, talk directly with the customers they affect, and see your work go live in days rather than quarters.</p>
            <p>We write things down. Most decisions start as a short document that anyone can comment on, which means you can do your best thinking at the time of day that suits you, wherever you live. We keep meetings few and short, and we protect long stretches of focused time.</p>
            <p>We also expect a lot. We care about clear writing, careful code review and following a problem all the way to the root instead of patching the symptom. If you enjoy that kind of craftsmanship, you will feel at home here.</p>

            <h2>How we hire</h2>
            <p>Our process is designed to respect your time and show you exactly what the job is like.</p>
            <ol>
                <li><strong>Introduction (30 minutes).</strong> A relaxed video call about what you are looking for and what we are building. Ask us anything.</li>
                <li><strong>Practical exercise (2 to 3 hours, paid).</strong> A small, realistic task based on work we actually do. No puzzles, no whiteboard algorithms. We pay for your time.</li>
                <li><strong>Review conversation (60 minutes).</strong> We talk through your work together, the way we would review a real change.</li>
                <li><strong>Meet the team (45 minutes).</strong> Informal chats with the people you would work with most.</li>
                <li><strong>Decision within five working days.</strong> Whatever the outcome, you get a clear answer and honest feedback.</li>
            </ol>
        </div>

        <aside class="space-y-5">
            <div class="rounded-2xl p-6 {{ $card }}">
                <p class="font-bold text-lg">What we offer</p>
                <ul class="mt-4 space-y-3 text-sm {{ $muted }}">
                    @foreach([
                        'Fully remote, with flexible hours across time zones',
                        'Competitive salary, reviewed every year',
                        'A yearly learning budget for books, courses and conferences',
                        'A home-office allowance for equipment you choose',
                        'Generous paid leave, plus local public holidays',
                        'Paid parental leave for every parent',
                        'A team retreat once a year, somewhere worth the trip',
                    ] as $perk)
                    <li class="flex gap-3"><span class="mt-1.5 w-1.5 h-1.5 rounded-full bg-brand-500 shrink-0"></span>{{ $perk }}</li>
                    @endforeach
                </ul>
            </div>
            <div class="rounded-2xl p-6 bg-brand-500 text-white">
                <p class="font-bold text-lg">Do not see your role?</p>
                <p class="mt-2 text-sm text-white/85">We read every speculative application. Tell us what you would like to work on and why.</p>
                <a href="{{ route('contact', ['topic' => 'careers']) }}#form" class="mt-5 inline-block px-5 py-3 rounded-xl bg-white text-brand-700 font-bold text-sm">Introduce yourself</a>
            </div>
        </aside>
    </div>

    <div>
        <p class="{{ $eyebrow }}">Where we are growing</p>
        <h2 class="mt-3 text-3xl md:text-4xl font-extrabold tracking-tight">Roles we expect to hire for</h2>
        <p class="mt-4 max-w-2xl {{ $muted }}">We do not always have an open vacancy. These are the areas where we are most likely to hire next. If one matches your experience, get in touch now. We contact people from our shortlist first.</p>
        <div class="mt-10 grid md:grid-cols-2 gap-5">
            @foreach([
                ['Senior backend engineer', 'Engineering · Remote', 'Work on the conversation engine, retrieval, AI provider integrations and the systems that keep replies fast under load. Strong PHP or similar, solid SQL, and good judgement about when not to add complexity.'],
                ['Product designer', 'Design · Remote', 'Shape the widget visitors see and the dashboard our customers use every day. You are comfortable moving from research to finished interface, and you care about accessibility and small details.'],
                ['Customer success lead', 'Customers · Remote', 'Help new customers go live quickly and get real results. You will turn what you learn into better onboarding, help articles and product feedback.'],
                ['Technical writer', 'Documentation · Remote, part-time possible', 'Own our documentation and API reference. You explain technical ideas clearly to both developers and non-technical business owners.'],
            ] as [$role, $meta, $body])
            <div class="rounded-2xl p-6 {{ $card }}">
                <p class="text-xs font-semibold uppercase tracking-wider text-brand-600 dark:text-brand-400">{{ $meta }}</p>
                <p class="mt-2 text-xl font-bold">{{ $role }}</p>
                <p class="mt-3 text-sm leading-relaxed {{ $muted }}">{{ $body }}</p>
                <a href="{{ route('contact', ['topic' => 'careers']) }}#form" class="mt-5 inline-flex items-center gap-1.5 text-sm font-semibold text-brand-600 dark:text-brand-300">Express interest <span aria-hidden="true">→</span></a>
            </div>
            @endforeach
        </div>
    </div>

    <div class="pfx-prose max-w-3xl">
        <h2>Equal opportunity</h2>
        <p>We welcome applications from people of every background, identity and experience. We judge work on its quality, not on where someone studied or who they know. If you need any adjustment during the hiring process, such as extra time for the exercise or a different interview format, just tell us. It will never count against you.</p>
    </div>
</div>
@endsection
