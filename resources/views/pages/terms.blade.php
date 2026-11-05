@php
    $toc = [
        'agreement' => 'The agreement', 'accounts' => 'Your account', 'service' => 'Using the service', 'acceptable-use' => 'Acceptable use',
        'ai-output' => 'AI-generated replies', 'your-content' => 'Your content', 'our-ip' => 'Our property', 'billing' => 'Plans and billing',
        'trial' => 'Free trials', 'cancel' => 'Cancelling', 'availability' => 'Availability', 'third-party' => 'Third-party services',
        'warranty' => 'Disclaimers', 'liability' => 'Liability', 'indemnity' => 'Indemnity', 'termination' => 'Suspension and termination',
        'changes' => 'Changes', 'general' => 'General', 'contact' => 'Contact',
    ];
    $email = config('branding.support_email');
@endphp
@extends('layouts.doc')
@section('title', 'Terms of service')
@section('description', "The terms that apply when you use {$name}.")
@section('eyebrow', 'Legal')
@section('heading', 'Terms of service')
@section('lead', 'These terms set out what you can expect from us and what we expect from you. Please read them carefully. By creating an account or using the service, you agree to them.')
@section('meta', 'Last updated: 5 November 2026')

@section('body')
<h2 id="agreement">1. The agreement</h2>
<p>These terms form a contract between you and {{ $name }} ("we", "us", "our"). If you use {{ $name }} on behalf of a company or other organisation, you confirm that you have the authority to accept these terms for it, and "you" means that organisation.</p>
<p>Our <a href="{{ route('privacy') }}">privacy policy</a> explains how we handle personal data and forms part of these terms. If you have signed a separate written agreement with us, that agreement takes priority where it conflicts with these terms.</p>

<h2 id="accounts">2. Your account</h2>
<ul>
    <li>You must be at least 18 years old and able to enter into a binding contract.</li>
    <li>The information you give us when you sign up must be accurate, and you must keep it up to date.</li>
    <li>You are responsible for keeping your password safe and for everything that happens under your account, including actions by team members you invite. We strongly recommend turning on two-factor authentication.</li>
    <li>If you think someone has accessed your account without permission, tell us straight away at <a href="mailto:{{ $email }}">{{ $email }}</a>.</li>
</ul>

<h2 id="service">3. Using the service</h2>
<p>While you follow these terms and your plan is active, we give you a non-exclusive, non-transferable right to use {{ $name }} for your business. This includes installing the chat widget on websites you own or are authorised to manage, and connecting the messaging channels your plan includes.</p>
<p>Each plan has limits, such as the number of assistants, conversations, messages or team members. These are shown on our <a href="{{ route('home') }}#pricing">pricing section</a> and in your dashboard. If you reach a limit, the service may pause the affected feature until the next billing period or until you upgrade. We will never charge you for extra usage without asking first.</p>

<h2 id="acceptable-use">4. Acceptable use</h2>
<p>You agree not to use {{ $name }}, or let anyone else use it, to:</p>
<ul>
    <li>break any law or regulation, including consumer protection, privacy and anti-spam laws;</li>
    <li>pretend to be a real person, or hide from visitors that they are talking to an automated assistant when the law requires you to tell them;</li>
    <li>collect sensitive personal data, such as health or financial details, unless you have a lawful basis and appropriate safeguards in place;</li>
    <li>send unsolicited bulk messages through any connected channel;</li>
    <li>publish content that is unlawful, defamatory, hateful, sexually explicit or that infringes anyone else's rights;</li>
    <li>give advice that requires a professional licence, such as medical, legal or financial advice, without a qualified person checking it;</li>
    <li>attack, overload, reverse engineer or try to get around the security or usage limits of the service;</li>
    <li>resell or rent the service to others, unless you have an agency or reseller agreement with us.</li>
</ul>
<p>We may remove content or suspend an assistant that breaks these rules. Where we reasonably can, we will contact you first and give you a chance to fix the problem.</p>

<h2 id="ai-output">5. AI-generated replies</h2>
<p>Your assistant uses large language models from third-party AI providers. These models are powerful, but they are not perfect. They can sometimes give answers that are incomplete, out of date or wrong.</p>
<ul>
    <li>You are responsible for the training content you provide and for checking that your assistant's replies are suitable for your business.</li>
    <li>We provide tools that reduce risk, such as strict mode, human handoff and conversation review. We recommend you use them, especially when you first go live.</li>
    <li>Replies generated for your visitors are not statements made by us, and you should not rely on them as professional advice.</li>
</ul>

<h2 id="your-content">6. Your content</h2>
<p>You keep full ownership of everything you upload or create in {{ $name }}: training material, instructions, branding, conversations and leads ("your content").</p>
<p>You give us permission to host, copy, process and display your content only as needed to provide the service to you. We do not use your content to train shared AI models, and we do not share it with other customers.</p>
<p>You confirm that you have the rights to the content you upload, and that using it with {{ $name }} will not break any law or anyone else's rights.</p>

<h2 id="our-ip">7. Our property</h2>
<p>{{ $name }}, including the software, design, documentation and brand, belongs to us and our licensors. These terms do not give you any ownership rights. If you send us feedback or suggestions, we may use them freely and without any obligation to you, although we always appreciate them.</p>

<h2 id="billing">8. Plans and billing</h2>
<ul>
    <li>Paid plans are billed in advance, either monthly or yearly, using the payment method you provide.</li>
    <li>Prices are shown before you buy and exclude any taxes, such as VAT, which we add where the law requires.</li>
    <li>Your plan renews automatically at the end of each billing period until you cancel.</li>
    <li>If you upgrade during a billing period, the new plan starts straight away and we charge the difference for the rest of the period. Downgrades take effect at the start of the next period.</li>
    <li>If a payment fails, we will try again and email you. If payment is still outstanding after 14 days, we may limit your account until it is paid.</li>
    <li>We may change our prices. Any change will apply from your next renewal, and we will give you at least 30 days' notice by email.</li>
</ul>

<h2 id="trial">9. Free trials</h2>
<p>New accounts may start with a free trial. You do not need a payment card to start one. At the end of the trial, you can choose a paid plan to keep your assistant running. If you do not, your assistant stops replying, but we keep your account and settings for 30 days in case you change your mind.</p>

<h2 id="cancel">10. Cancelling</h2>
<p>You can cancel at any time from your billing settings. When you cancel, your plan stays active until the end of the period you have already paid for. We do not usually give refunds for partial periods, but if something has gone wrong, talk to us. We are reasonable people.</p>
<p>After cancellation you can export your conversations and leads for 30 days. After that we delete your content as described in our <a href="{{ route('privacy') }}#retention">privacy policy</a>.</p>
<p>Nothing in these terms affects any statutory rights you have as a consumer, including any right to cancel within a cooling-off period.</p>

<h2 id="availability">11. Availability and changes to the service</h2>
<p>We work hard to keep {{ $name }} available around the clock, and you can follow its current health on our <a href="{{ route('status') }}">status page</a>. Sometimes we need to carry out maintenance. Where we can, we will schedule it at quiet times and announce it in advance.</p>
<p>We improve the product all the time, so features may change. If we remove a feature that is central to your plan, we will tell you at least 30 days in advance and, if you are on a yearly plan, offer you a fair refund if you choose to leave.</p>

<h2 id="third-party">12. Third-party services</h2>
<p>{{ $name }} connects to services run by other companies, including AI providers, payment processors and messaging platforms such as WhatsApp, Telegram and Slack. Your use of those services is also governed by their own terms. We are not responsible for outages or changes on their side, but we will always try to work around them, for example by switching to a backup AI provider.</p>

<h2 id="warranty">13. Disclaimers</h2>
<p>We provide {{ $name }} with reasonable skill and care. Apart from that, and to the extent the law allows, the service is provided "as is". We do not promise that it will be free of errors or interruptions, or that AI-generated replies will always be accurate.</p>

<h2 id="liability">14. Limits on liability</h2>
<p>Nothing in these terms limits liability that cannot legally be limited, such as liability for death or personal injury caused by negligence, or for fraud.</p>
<p>Subject to that, neither side is liable for indirect or consequential losses, or for loss of profit, revenue, data or goodwill. Our total liability to you in any 12-month period is limited to the amount you paid us during that period.</p>

<h2 id="indemnity">15. Indemnity</h2>
<p>If someone brings a claim against us because of your content or because you used the service in breach of these terms, you agree to cover our reasonable costs of dealing with that claim. We will tell you about the claim promptly and let you take part in handling it.</p>

<h2 id="termination">16. Suspension and termination</h2>
<p>You can close your account at any time. We may suspend or close your account if you seriously or repeatedly break these terms, if you do not pay, or if we are required to by law. Unless the situation is urgent or illegal, we will give you notice and a chance to put things right first.</p>
<p>The sections on your content, our property, liability, indemnity and general terms continue to apply after the agreement ends.</p>

<h2 id="changes">17. Changes to these terms</h2>
<p>We may update these terms from time to time. If a change is significant, we will email account owners at least 30 days before it takes effect. If you do not agree with the new terms, you can cancel before they apply. If you keep using the service after that, you accept the updated terms.</p>

<h2 id="general">18. General</h2>
<ul>
    <li>Neither of us is responsible for delays caused by events outside our reasonable control.</li>
    <li>You may not transfer this agreement without our written consent. We may transfer it as part of a merger or sale of our business, and we will tell you if that happens.</li>
    <li>If a court finds part of these terms unenforceable, the rest still applies.</li>
    <li>If we do not enforce a right straight away, we have not given it up.</li>
    <li>These terms are governed by the laws of the country where {{ $name }} is registered, and its courts have jurisdiction, unless your local consumer law gives you the right to bring a claim where you live.</li>
</ul>

<h2 id="contact">19. Contact</h2>
<p>If you have a question about these terms, email <a href="mailto:{{ $email }}">{{ $email }}</a> or use our <a href="{{ route('contact', ['topic' => 'other']) }}">contact form</a>.</p>
@endsection
