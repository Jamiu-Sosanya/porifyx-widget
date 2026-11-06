@php
    $toc = [
        'summary' => 'The short version', 'who' => 'Who we are', 'roles' => 'Our two roles', 'collect' => 'What we collect',
        'use' => 'How we use it', 'ai' => 'AI providers', 'legal-basis' => 'Legal bases', 'sharing' => 'Who we share it with',
        'transfers' => 'International transfers', 'retention' => 'How long we keep it', 'security' => 'Security',
        'rights' => 'Your rights', 'cookies' => 'Cookies', 'children' => 'Children', 'changes' => 'Changes', 'contact' => 'Contact us',
    ];
    $email = config('branding.support_email');
@endphp
@extends('layouts.doc')
@section('title', 'Privacy policy')
@section('description', "How {$name} collects, uses and protects personal data.")
@section('eyebrow', 'Legal')
@section('heading', 'Privacy policy')
@section('lead', 'This policy explains what personal data we collect, why we collect it, who we share it with and the choices you have. We have tried to write it in plain language, without hiding anything in the small print.')
@section('meta', 'Last updated: 5 November 2026')

@section('body')
<h2 id="summary">The short version</h2>
<ul>
    <li>We collect only what we need to run the service, bill you and keep your account secure.</li>
    <li>We never sell personal data, and we never use your content or your visitors' conversations to train shared AI models.</li>
    <li>When your website visitors chat with your assistant, you decide what is collected. We process that data on your behalf and only on your instructions.</li>
    <li>You can export or delete your data at any time from your account, or by emailing us.</li>
</ul>

<h2 id="who">Who we are</h2>
<p>{{ $name }} ("we", "us", "our") provides software that lets businesses add an AI assistant to their websites and messaging channels. This policy covers our website, our dashboard and the chat widget that our customers install on their own sites.</p>
<p>If you have any question about this policy or how we handle personal data, you can reach us at <a href="mailto:{{ $email }}">{{ $email }}</a>.</p>

<h2 id="roles">Our two roles</h2>
<p>Data protection law treats us differently depending on whose data it is, so it helps to be clear.</p>
<h3>When we are the controller</h3>
<p>For information about our own customers, such as your account details, billing information and how you use the dashboard, we decide how and why the data is used. In those cases we are the <strong>data controller</strong>.</p>
<h3>When we are the processor</h3>
<p>When a visitor chats with an assistant on one of our customers' websites, the business that owns that website is in charge of the conversation. They decide what the assistant asks, what is stored and how long it is kept. We process that data on their behalf as a <strong>data processor</strong>. If you are a visitor and have a question about a chat you had on someone else's website, please contact that business first. We will help them answer you.</p>

<h2 id="collect">What we collect</h2>
<h3>Information you give us</h3>
<ul>
    <li><strong>Account details:</strong> your name, email address, password (stored only as a secure hash), company name and, if you turn it on, two-factor authentication settings.</li>
    <li><strong>Billing details:</strong> your billing address and plan. Card payments are handled by our payment provider. We never see or store your full card number.</li>
    <li><strong>Content you add:</strong> the website addresses, documents, question-and-answer pairs and instructions you use to train your assistant.</li>
    <li><strong>Messages to us:</strong> anything you send through our contact form, by email or in a support conversation.</li>
</ul>
<h3>Information collected automatically</h3>
<ul>
    <li><strong>Usage data:</strong> the pages of the dashboard you visit, features you use and actions you take, so we can fix problems and improve the product.</li>
    <li><strong>Device and log data:</strong> IP address, browser type, operating system and timestamps. These are kept in server logs for security and troubleshooting.</li>
</ul>
<h3>Information about your website visitors</h3>
<p>When the widget runs on your website, it may process:</p>
<ul>
    <li>The messages visitors send and the replies the assistant gives.</li>
    <li>A random, anonymous identifier stored in the visitor's browser so the conversation can continue if they move to another page.</li>
    <li>The page address where the chat started, the referring page, the time zone and the general device type.</li>
    <li>Any contact details a visitor chooses to share, such as a name, email address or phone number.</li>
    <li>Ratings and feedback a visitor leaves at the end of a chat.</li>
</ul>
<p>We do not use the widget to track visitors across other websites, and we do not build advertising profiles.</p>

<h2 id="use">How we use it</h2>
<ul>
    <li>To provide the service: hosting your assistant, generating replies, showing conversations and leads in your dashboard and sending notifications you have turned on.</li>
    <li>To keep accounts and the platform secure, including spotting abuse, fraud and unusual sign-in activity.</li>
    <li>To process payments, send invoices and manage your subscription.</li>
    <li>To answer your questions and provide support.</li>
    <li>To send important service messages, such as security alerts, billing notices and changes to these terms. You cannot opt out of these while you have an account, but we keep them rare.</li>
    <li>To send product news and tips, but only if you have agreed. Every such email has an unsubscribe link.</li>
    <li>To understand how the product is used, using aggregated data, so we can decide what to improve next.</li>
</ul>

<h2 id="ai">AI providers</h2>
<p>To generate replies, the relevant parts of a conversation and your training content are sent to the AI provider selected for your assistant. Depending on your settings this may be OpenAI, Anthropic, Google, Groq or OpenRouter. If the selected provider is unavailable, a backup provider you have configured may answer instead.</p>
<p>We choose providers whose business terms state that data sent through their API is not used to train their models. We send only what is needed to answer the current message. You can see which provider each assistant uses, and change it, in your widget settings.</p>

<h2 id="legal-basis">Legal bases for processing</h2>
<p>Where laws such as the UK GDPR or EU GDPR apply, we rely on the following legal bases:</p>
<ul>
    <li><strong>Contract:</strong> to provide the service you signed up for.</li>
    <li><strong>Legitimate interests:</strong> to keep the platform secure, prevent fraud and improve the product, where those interests are not outweighed by your rights.</li>
    <li><strong>Legal obligation:</strong> to keep financial records and respond to lawful requests.</li>
    <li><strong>Consent:</strong> for optional marketing emails and non-essential cookies. You can withdraw consent at any time.</li>
</ul>

<h2 id="sharing">Who we share it with</h2>
<p>We share personal data only with service providers who help us run {{ $name }}, and only as much as they need. These include:</p>
<ul>
    <li>hosting and infrastructure providers that store our data and run our servers;</li>
    <li>the AI providers described above;</li>
    <li>our payment processor, for subscriptions and invoices;</li>
    <li>email delivery services, for account and notification emails;</li>
    <li>messaging platforms such as WhatsApp, Telegram or Slack, but only when you connect those channels yourself.</li>
</ul>
<p>Each provider is bound by a written agreement that limits how they may use the data. We may also disclose data if the law requires it, to protect someone's safety, or as part of a merger or acquisition. In that case we would tell you before your data became subject to a different privacy policy.</p>

<h2 id="transfers">International transfers</h2>
<p>Some of our providers are based in other countries, including the United States. When personal data leaves the UK or the European Economic Area, we protect it with recognised safeguards such as the European Commission's Standard Contractual Clauses and the UK International Data Transfer Addendum.</p>

<h2 id="retention">How long we keep it</h2>
<table>
    <thead><tr><th>Type of data</th><th>How long we keep it</th></tr></thead>
    <tbody>
        <tr><td>Account details</td><td>While your account is open, then up to 30 days after you close it</td></tr>
        <tr><td>Conversations and leads</td><td>Until you delete them, or until the retention period you set for your assistant</td></tr>
        <tr><td>Training content</td><td>Until you remove it, or until your account is closed</td></tr>
        <tr><td>Invoices and payment records</td><td>As long as tax law requires, usually six to seven years</td></tr>
        <tr><td>Server and security logs</td><td>Up to 90 days</td></tr>
        <tr><td>Contact form messages</td><td>Up to 24 months, unless you become a customer</td></tr>
    </tbody>
</table>
<p>Backups are overwritten automatically on a rolling cycle. Deleted data disappears from backups within 35 days.</p>

<h2 id="security">How we protect it</h2>
<p>All data is encrypted in transit using TLS. Passwords are hashed and never stored in plain text. Dashboard sign-in supports two-factor authentication, and admin access can be limited to approved IP addresses. Webhooks we send are signed so you can check they really came from us. Access to production systems is limited to the few people who need it, and every action is logged.</p>
<p>No system is perfectly secure. If we ever discover a breach that affects your data, we will tell you without undue delay and explain what happened and what we are doing about it.</p>

<h2 id="rights">Your rights</h2>
<p>Depending on where you live, you may have the right to:</p>
<ul>
    <li>see the personal data we hold about you and get a copy of it;</li>
    <li>correct anything that is wrong or incomplete;</li>
    <li>ask us to delete your data;</li>
    <li>object to, or ask us to restrict, certain processing;</li>
    <li>receive your data in a portable, machine-readable format;</li>
    <li>withdraw consent you have given, at any time.</li>
</ul>
<p>Most of this you can do yourself from your account settings. For anything else, email <a href="mailto:{{ $email }}">{{ $email }}</a>. We reply within 30 days and never charge for a reasonable request. If you are not happy with our response, you have the right to complain to your local data protection authority. In the UK that is the Information Commissioner's Office.</p>

<h2 id="cookies">Cookies</h2>
<p>We use a small number of cookies and similar technologies:</p>
<ul>
    <li><strong>Essential cookies</strong> keep you signed in, protect forms against forgery and remember your cookie choice. The site cannot work without them.</li>
    <li><strong>Preference storage</strong> remembers settings such as light or dark mode.</li>
    <li><strong>Widget storage</strong> keeps a visitor's conversation going between pages using an anonymous identifier in the browser.</li>
</ul>
<p>We do not use third-party advertising cookies. You can control cookies through the banner on our site and in your browser settings.</p>

<h2 id="children">Children</h2>
<p>{{ $name }} is a business service and is not meant for children. We do not knowingly collect personal data from anyone under 16. If you believe a child has given us personal data, please contact us and we will delete it.</p>

<h2 id="changes">Changes to this policy</h2>
<p>We will update this policy when our practices change or the law requires it. The date at the top always shows the latest version. If a change is significant, we will email account owners at least 14 days before it takes effect.</p>

<h2 id="contact">Contact us</h2>
<p>For privacy questions or requests, email <a href="mailto:{{ $email }}">{{ $email }}</a> or use our <a href="{{ route('contact', ['topic' => 'other']) }}">contact form</a>. Please mention "Privacy" in your message so it reaches the right person quickly.</p>
@include('pages.partials.privacy-request')
@endsection
