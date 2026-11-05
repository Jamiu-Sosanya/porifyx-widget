@php
    $toc = [
        'introduction' => 'Introduction', 'base-url' => 'Base URL', 'auth' => 'Authentication and domains', 'limits' => 'Rate limits',
        'errors' => 'Errors', 'config' => 'Get widget config', 'start' => 'Start a conversation', 'send' => 'Send a message',
        'stream' => 'Stream a reply', 'poll' => 'Poll for agent messages', 'handoff' => 'Request a human', 'feedback' => 'Rate a conversation',
        'offline' => 'Leave an offline message', 'react' => 'React to a message', 'webhooks' => 'Webhooks', 'verify' => 'Verifying signatures',
    ];
    $base = rtrim(url('/api/widget'), '/');
@endphp
@extends('layouts.doc')
@section('title', 'API reference')
@section('description', "Reference for the {$name} widget API and webhooks.")
@section('eyebrow', 'Developers')
@section('heading', 'API reference')
@section('lead', 'Build your own chat interface, connect the assistant to your app, or react to events in your own systems. Everything the standard widget does is available through this API.')

@section('body')
<h2 id="introduction">Introduction</h2>
<p>The widget API is a simple JSON API over HTTPS. It is the same API our own <code>widget.js</code> uses, so anything the standard widget can do, your code can do too. Common uses include:</p>
<ul>
    <li>building a fully custom chat interface inside a mobile app or web app;</li>
    <li>adding the assistant to an internal tool or kiosk;</li>
    <li>receiving webhook events when leads are qualified or visitors ask for a person.</li>
</ul>
<p>All requests and responses use JSON, except streaming replies, which use server-sent events. Timestamps use ISO 8601 format in UTC.</p>

<h2 id="base-url">Base URL</h2>
<pre><code>{{ $base }}</code></pre>
<p>In the endpoint paths below, <code>{widget}</code> is your widget ID, shown on the assistant's <em>Embed</em> page, and <code>{conversation}</code> is the conversation ID returned when you start a conversation.</p>

<h2 id="auth">Authentication and allowed domains</h2>
<p>The widget API is designed to be called from the browser, so it does not use secret keys. A secret key placed in front-end code would not stay secret for long. Instead, access is controlled in three ways:</p>
<ul>
    <li><strong>Widget ID:</strong> every request must include a valid ID for an active widget.</li>
    <li><strong>Allowed domains:</strong> if you have set allowed domains for the widget, requests from any other origin are refused with <code>403</code>.</li>
    <li><strong>Conversation ID:</strong> messages can only be read or sent by someone who holds the conversation's ID, which is a long random value.</li>
</ul>
<p>Each visitor is identified by a <code>fingerprint</code>: a random string you generate once and store on the visitor's device. Reusing the same fingerprint lets a visitor continue the same conversation.</p>

<h2 id="limits">Rate limits</h2>
<p>By default each widget accepts up to 30 requests per minute from the same source. You can change this limit in the widget settings. When you go over the limit, the API returns <code>429 Too Many Requests</code>. Wait a few seconds and try again, ideally with increasing delays between retries.</p>
<p>Your plan also sets a monthly message allowance. When it is used up, sending a message returns <code>429</code> with the error <code>Message limit reached.</code></p>

<h2 id="errors">Errors</h2>
<p>Errors return a standard HTTP status code and a JSON body with an <code>error</code> message. Validation errors return <code>422</code> with details for each field.</p>
<table>
    <thead><tr><th>Status</th><th>Meaning</th></tr></thead>
    <tbody>
        <tr><td><code>403</code></td><td>The request came from a domain that is not allowed, or the feature is turned off for this widget.</td></tr>
        <tr><td><code>404</code></td><td>The widget or conversation does not exist, or the widget is inactive.</td></tr>
        <tr><td><code>409</code></td><td>The AI cannot reply because a human agent has taken over the conversation.</td></tr>
        <tr><td><code>422</code></td><td>The request body is missing a field or a value is not valid.</td></tr>
        <tr><td><code>429</code></td><td>Too many requests, or the monthly message allowance has been used up.</td></tr>
        <tr><td><code>5xx</code></td><td>Something went wrong on our side. Retry with a delay, and check the <a href="{{ route('status') }}">status page</a>.</td></tr>
    </tbody>
</table>
<pre><code>{ "error": "Domain not authorized." }</code></pre>

<h2 id="config">Get widget configuration</h2>
<p><code>GET /{widget}/config</code></p>
<p>Returns everything needed to display the widget: name, colours, position, welcome message, starter questions, enabled features and any active A/B test variant. Call this first.</p>
<table>
    <thead><tr><th>Query parameter</th><th>Description</th></tr></thead>
    <tbody>
        <tr><td><code>page_url</code></td><td>Optional. The page the visitor is on. Used for page-specific proactive messages.</td></tr>
        <tr><td><code>fingerprint</code></td><td>Optional. The visitor's fingerprint, used to assign A/B test variants consistently.</td></tr>
    </tbody>
</table>

<h2 id="start">Start a conversation</h2>
<p><code>POST /{widget}/conversations</code></p>
<p>Starts a new conversation, or resumes the visitor's open one if it exists.</p>
<table>
    <thead><tr><th>Field</th><th>Description</th></tr></thead>
    <tbody>
        <tr><td><code>fingerprint</code></td><td><strong>Required.</strong> Up to 64 characters. Your stable, random visitor identifier.</td></tr>
        <tr><td><code>page_url</code>, <code>referrer</code></td><td>Optional. Full URLs for the current and referring page.</td></tr>
        <tr><td><code>name</code>, <code>email</code></td><td>Optional. Pass these if the visitor is already signed in to your site.</td></tr>
        <tr><td><code>timezone</code>, <code>device_type</code></td><td>Optional. For example <code>Europe/London</code> and <code>mobile</code>.</td></tr>
    </tbody>
</table>
<pre><code>curl -X POST {{ $base }}/{widget}/conversations \
  -H "Content-Type: application/json" \
  -d '{"fingerprint":"v_8f2c1a9e","page_url":"https://example.com/pricing"}'</code></pre>
<pre><code>{
  "conversation_uuid": "9b7e4c2a-…",
  "conversation_status": "active",
  "messages": [ … ],
  "visitor": { "name": null, "email": null }
}</code></pre>

<h2 id="send">Send a message</h2>
<p><code>POST /{widget}/conversations/{conversation}/messages</code></p>
<p>Sends the visitor's message and waits for the full reply. Use this when you do not need the text to appear word by word.</p>
<table>
    <thead><tr><th>Field</th><th>Description</th></tr></thead>
    <tbody><tr><td><code>content</code></td><td><strong>Required.</strong> The visitor's message, up to 2,000 characters by default.</td></tr></tbody>
</table>
<pre><code>{
  "visitor_message": {
    "uuid": "…", "role": "visitor", "content": "Do you ship to Ireland?",
    "sentiment_score": 0.1, "sentiment_label": "neutral", "created_at": "2026-11-05T10:24:31Z"
  },
  "ai_response": { "uuid": "…", "role": "ai", "content": "Yes, we ship to Ireland in 2 to 4 working days…" }
}</code></pre>
<p>If a human agent has taken over, <code>ai_response</code> is <code>null</code> and the agent's reply arrives through <a href="#poll">polling</a>.</p>

<h2 id="stream">Stream a reply</h2>
<p><code>POST /{widget}/conversations/{conversation}/stream</code></p>
<p>Takes the same body as <em>Send a message</em>, but returns the reply as server-sent events, so you can show it as it is written. Each event contains a piece of text. The last event says the reply is complete and may include suggested follow-up questions.</p>
<pre><code>data: {"chunk":"Yes, we ship"}
data: {"chunk":" to Ireland in 2 to 4 working days."}
data: {"done":true,"message_uuid":"…","smart_suggestions":["How much is shipping?"]}</code></pre>
<p>Because this is a <code>POST</code> request, use <code>fetch</code> and read the response body as a stream rather than the browser's <code>EventSource</code>, which only supports <code>GET</code>.</p>

<h2 id="poll">Poll for agent messages</h2>
<p><code>GET /{widget}/conversations/{conversation}/poll?after={message}</code></p>
<p>Returns messages added after the given message ID, such as replies from a human agent or system notices. Poll every few seconds while a human is handling the conversation.</p>

<h2 id="handoff">Request a human</h2>
<p><code>POST /{widget}/conversations/{conversation}/handoff</code></p>
<p>Asks for a human agent. The conversation moves to your team's inbox, online agents are notified and the <code>conversation.handoff</code> webhook is sent. Returns <code>403</code> if handoff is turned off for the widget.</p>

<h2 id="feedback">Rate a conversation</h2>
<p><code>POST /{widget}/conversations/{conversation}/feedback</code></p>
<table>
    <thead><tr><th>Field</th><th>Description</th></tr></thead>
    <tbody>
        <tr><td><code>rating</code></td><td><strong>Required.</strong> A whole number from 1 to 5.</td></tr>
        <tr><td><code>comment</code></td><td>Optional. Up to 500 characters.</td></tr>
    </tbody>
</table>

<h2 id="offline">Leave an offline message</h2>
<p><code>POST /{widget}/offline</code></p>
<p>Saves a message for your team when nobody is available. It appears in the dashboard and is sent to you by email.</p>
<table>
    <thead><tr><th>Field</th><th>Description</th></tr></thead>
    <tbody>
        <tr><td><code>email</code></td><td><strong>Required.</strong> Where your team should reply.</td></tr>
        <tr><td><code>message</code></td><td><strong>Required.</strong> Up to 2,000 characters.</td></tr>
        <tr><td><code>name</code>, <code>page_url</code>, <code>conversation_uuid</code></td><td>Optional.</td></tr>
    </tbody>
</table>

<h2 id="react">React to a message</h2>
<p><code>POST /{widget}/conversations/{conversation}/messages/{message}/react</code></p>
<p>Adds an emoji reaction to an assistant message, for example <code>{"emoji":"👍"}</code>. Reactions help you find the answers visitors liked, and the ones they did not.</p>

<h2 id="webhooks">Webhooks</h2>
<p>Webhooks let your systems react the moment something happens. Set your endpoint URL and a secret in the widget's settings. We send a <code>POST</code> request with a JSON body for each event:</p>
<table>
    <thead><tr><th>Event</th><th>When it is sent</th></tr></thead>
    <tbody>
        <tr><td><code>lead.qualified</code></td><td>The assistant captured or updated a lead and scored it.</td></tr>
        <tr><td><code>conversation.handoff</code></td><td>A visitor asked to talk to a person.</td></tr>
        <tr><td><code>conversation.feedback</code></td><td>A visitor rated a conversation.</td></tr>
        <tr><td><code>webhook.test</code></td><td>You pressed the test button in the dashboard.</td></tr>
    </tbody>
</table>
<pre><code>POST /your-endpoint
Content-Type: application/json
X-PFX-Event: lead.qualified
X-PFX-Widget: 3f1c…
X-PFX-Signature: sha256=5d41402abc4b2a76b9719d911017c592…

{
  "event": "lead.qualified",
  "widget_uuid": "3f1c…",
  "timestamp": "2026-11-05T10:31:02+00:00",
  "data": { … }
}</code></pre>
<p>Reply with any <code>2xx</code> status within 15 seconds. If your endpoint fails or times out, we try the delivery up to three times in total. Every attempt and its response are listed in the widget's <em>Webhook logs</em>.</p>
<p>Deliveries can occasionally arrive more than once or out of order. Make your handler safe to run twice, for example by ignoring an event you have already processed.</p>

<h2 id="verify">Verifying signatures</h2>
<p>When you set a webhook secret, each request includes an <code>X-PFX-Signature</code> header. It contains an HMAC-SHA256 hash of the raw request body, made with your secret. Always check it before trusting the data, and compare the values in constant time.</p>
<h3>PHP</h3>
<pre><code>$body     = file_get_contents('php://input');
$expected = 'sha256=' . hash_hmac('sha256', $body, getenv('PFX_WEBHOOK_SECRET'));

if (! hash_equals($expected, $_SERVER['HTTP_X_PFX_SIGNATURE'] ?? '')) {
    http_response_code(401);
    exit;
}

$event = json_decode($body, true);</code></pre>
<h3>Node.js</h3>
<pre><code>import crypto from 'node:crypto';

// Use the raw body, before any JSON parsing.
function isValid(rawBody, header, secret) {
  const expected = 'sha256=' + crypto.createHmac('sha256', secret).update(rawBody).digest('hex');
  return header?.length === expected.length &amp;&amp;
    crypto.timingSafeEqual(Buffer.from(header), Buffer.from(expected));
}</code></pre>
<p>Questions about the API? <a href="{{ route('contact', ['topic' => 'support']) }}">Talk to our developers</a>.</p>
@endsection
