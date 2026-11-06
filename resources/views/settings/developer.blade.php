@extends('layouts.settings')
@section('title', 'Developer — Webhooks & API')
@section('page-title', 'Developer documentation')
@section('settings')
<div class="w-full space-y-8" x-data="{ section: 'overview' }">
    <p class="text-sm text-gray-400">Reference for embedding the widget, verifying outbound webhooks, and connecting <strong class="text-gray-300">Zapier</strong> or <strong class="text-gray-300">Make</strong>. Bundle path: <code class="text-brand-400 text-xs">/documentation/</code> on disk for buyers.</p>

    <div class="flex flex-wrap gap-2">
        @foreach(['overview' => 'Overview', 'payloads' => 'Payloads', 'signature' => 'Verify signature', 'zapier' => 'Zapier', 'make' => 'Make', 'embed' => 'Embed API'] as $id => $label)
        <button type="button" @click="section = '{{ $id }}'"
                :class="section === '{{ $id }}' ? 'bg-brand-500/20 text-brand-300 border-brand-500/30' : 'bg-white/5 text-gray-400 border-white/10 hover:bg-white/10'"
                class="px-3 py-1.5 rounded-lg text-xs font-medium border transition">{{ $label }}</button>
        @endforeach
    </div>

    {{-- Overview --}}
    <div x-show="section === 'overview'" x-transition class="space-y-4">
        <div class="bg-gray-900/50 border border-white/5 rounded-2xl p-6 space-y-3 text-sm text-gray-400">
            <h3 class="text-base font-semibold text-white">Outbound webhooks</h3>
            <p>Configure a URL per widget (Security → integrations). Each request is an HTTP <code class="text-brand-400">POST</code> with <code class="text-brand-400">Content-Type: application/json</code> and a JSON body.</p>
            <ul class="list-disc pl-5 space-y-1">
                <li><code class="text-emerald-400">webhook.test</code> — fired from “Send test webhook” in the widget editor.</li>
                <li><code class="text-emerald-400">conversation.handoff</code> — visitor clicked “Talk to a human”.</li>
                <li><code class="text-emerald-400">lead.qualified</code> — AI lead scoring ran; by default only on <strong class="text-gray-300">new lead</strong>, <strong class="text-gray-300">score jump ≥ {{ $scoreThreshold }}</strong>, or <strong class="text-gray-300">temperature change</strong> (toggle “every refresh” in the widget).</li>
                <li><code class="text-emerald-400">conversation.feedback</code> — visitor submitted CSAT (1–5).</li>
            </ul>
            <p class="text-xs text-gray-500">Headers: <code class="text-gray-400">X-PFX-Event</code>, <code class="text-gray-400">X-PFX-Widget</code>, optional <code class="text-gray-400">X-PFX-Signature</code> when a secret is set.</p>
            <p class="text-xs text-amber-400/90 border border-amber-500/20 rounded-xl p-3 bg-amber-500/5">GDPR: your webhook endpoint is your data processor. Only forward fields you need; avoid logging full transcripts in third-party tools without consent.</p>
        </div>
    </div>

    {{-- Payloads --}}
    <div x-show="section === 'payloads'" x-transition class="space-y-4">
        <div class="bg-gray-900/50 border border-white/5 rounded-2xl p-6">
            <h3 class="text-base font-semibold text-white mb-3">Envelope (all events)</h3>
            <pre class="text-xs text-emerald-400/90 bg-black/40 border border-white/10 rounded-xl p-4 overflow-x-auto font-mono leading-relaxed">{
  "event": "lead.qualified",
  "widget_uuid": "{{ $sampleWidgetUuid }}",
  "timestamp": "2026-04-12T12:00:00+00:00",
  "data": { ... }
}</pre>
        </div>
        <div class="bg-gray-900/50 border border-white/5 rounded-2xl p-6 space-y-3">
            <h4 class="text-sm font-semibold text-white">lead.qualified — data</h4>
            <pre class="text-xs text-gray-400 bg-black/40 border border-white/10 rounded-xl p-4 overflow-x-auto font-mono leading-relaxed">{
  "conversation_uuid": "…",
  "lead_id": 1,
  "score": 72,
  "temperature": "warm",
  "email": "visitor@example.com",
  "name": "Jane",
  "meta": {
    "is_new_record": false,
    "score_jump": true,
    "temperature_changed": false
  }
}</pre>
        </div>
        <div class="bg-gray-900/50 border border-white/5 rounded-2xl p-6 space-y-3">
            <h4 class="text-sm font-semibold text-white">conversation.handoff — data</h4>
            <pre class="text-xs text-gray-400 bg-black/40 border border-white/10 rounded-xl p-4 overflow-x-auto font-mono leading-relaxed">{
  "conversation_uuid": "…",
  "visitor": { "email": null, "name": null },
  "page_url": "https://yoursite.com/pricing"
}</pre>
        </div>
        <div class="bg-gray-900/50 border border-white/5 rounded-2xl p-6 space-y-3">
            <h4 class="text-sm font-semibold text-white">conversation.feedback — data</h4>
            <pre class="text-xs text-gray-400 bg-black/40 border border-white/10 rounded-xl p-4 overflow-x-auto font-mono leading-relaxed">{
  "conversation_uuid": "…",
  "rating": 5,
  "comment": null
}</pre>
        </div>
    </div>

    {{-- Signature --}}
    <div x-show="section === 'signature'" x-transition class="space-y-4">
        <div class="bg-gray-900/50 border border-white/5 rounded-2xl p-6 space-y-3 text-sm text-gray-400">
            <p>Compute HMAC-SHA256 over the <strong class="text-white">raw request body bytes</strong> (the same JSON string Laravel sends). Header format:</p>
            <pre class="text-xs text-brand-400 bg-black/40 border border-white/10 rounded-xl p-3 font-mono">X-PFX-Signature: sha256=&lt;lowercase_hex&gt;</pre>
        </div>
        <div class="bg-gray-900/50 border border-white/5 rounded-2xl p-6">
            <h4 class="text-sm font-semibold text-white mb-2">PHP (Laravel / vanilla)</h4>
            <pre class="text-xs text-gray-400 bg-black/40 border border-white/10 rounded-xl p-4 overflow-x-auto font-mono leading-relaxed">$payload = $request-&gt;getContent();
$expected = 'sha256=' . hash_hmac('sha256', $payload, $secret);
if (! hash_equals($expected, $request-&gt;header('X-PFX-Signature', ''))) {
    abort(401);
}</pre>
        </div>
        <div class="bg-gray-900/50 border border-white/5 rounded-2xl p-6">
            <h4 class="text-sm font-semibold text-white mb-2">Node.js (Express)</h4>
            <pre class="text-xs text-gray-400 bg-black/40 border border-white/10 rounded-xl p-4 overflow-x-auto font-mono leading-relaxed">const crypto = require('crypto');
app.post('/pfx', express.raw({ type: 'application/json' }), (req, res) => {
  const sig = req.get('x-pfx-signature') || '';
  const body = req.body;
  const hex = 'sha256=' + crypto.createHmac('sha256', process.env.PFX_SECRET)
    .update(body).digest('hex');
  if (!crypto.timingSafeEqual(Buffer.from(sig), Buffer.from(hex))) return res.sendStatus(401);
  const json = JSON.parse(body.toString());
  res.json({ ok: true });
});</pre>
        </div>
    </div>

    {{-- Zapier --}}
    <div x-show="section === 'zapier'" x-transition class="space-y-4">
        <div class="bg-gray-900/50 border border-white/5 rounded-2xl p-6 space-y-3 text-sm text-gray-400">
            <h3 class="text-base font-semibold text-white">Zapier (Catch Hook)</h3>
            <ol class="list-decimal pl-5 space-y-2">
                <li>Create a Zap → trigger <strong class="text-gray-300">Webhooks by Zapier</strong> → <strong class="text-gray-300">Catch Hook</strong>.</li>
                <li>Copy the “Custom Webhook URL” Zapier gives you (looks like <code class="text-brand-400">https://hooks.zapier.com/hooks/catch/…</code>).</li>
                <li>Paste it into your widget’s <strong class="text-gray-300">Webhook URL</strong>, enable webhooks, save.</li>
                <li>Use <strong class="text-gray-300">Send test webhook</strong> in the widget editor, then in Zapier click <strong class="text-gray-300">Test trigger</strong> to pull the sample.</li>
                <li>Map <code class="text-gray-400">event</code>, <code class="text-gray-400">data.email</code>, <code class="text-gray-400">data.score</code>, etc. in later Zap steps (Slack, CRM, Google Sheets).</li>
            </ol>
            <p class="text-xs text-gray-500">Zapier cannot verify HMAC without a Code step; for production, prefer a tiny relay that validates the signature then forwards to Zapier, or use Make’s HTTP module with a custom scenario.</p>
        </div>
    </div>

    {{-- Make --}}
    <div x-show="section === 'make'" x-transition class="space-y-4">
        <div class="bg-gray-900/50 border border-white/5 rounded-2xl p-6 space-y-3 text-sm text-gray-400">
            <h3 class="text-base font-semibold text-white">Make (Integromat)</h3>
            <ol class="list-decimal pl-5 space-y-2">
                <li>Create a scenario → add <strong class="text-gray-300">Webhooks</strong> → <strong class="text-gray-300">Custom webhook</strong> (instant trigger).</li>
                <li>Make shows a public URL — paste it as the widget <strong class="text-gray-300">Webhook URL</strong>.</li>
                <li>Save the widget, send a test webhook, then run the scenario once so Make learns the JSON structure.</li>
                <li>Add routers/filters on <code class="text-brand-400">event</code> (e.g. only <code class="text-brand-400">lead.qualified</code>).</li>
            </ol>
        </div>
    </div>

    {{-- Embed API --}}
    <div x-show="section === 'embed'" x-transition class="space-y-4">
        <div class="bg-gray-900/50 border border-white/5 rounded-2xl p-6 space-y-3 text-sm text-gray-400">
            <h3 class="text-base font-semibold text-white">Public widget API base</h3>
            <pre class="text-xs text-emerald-400 bg-black/40 border border-white/10 rounded-xl p-3 font-mono break-all">{{ $appUrl }}/api/widget</pre>
            <p class="text-xs">Endpoints: <code class="text-gray-400">GET /{uuid}/config</code>, <code class="text-gray-400">POST /{uuid}/conversations</code>, <code class="text-gray-400">POST /{uuid}/conversations/{id}/messages</code>, <code class="text-gray-400">…/stream</code>, <code class="text-gray-400">…/handoff</code>, <code class="text-gray-400">…/feedback</code>. CORS applies to <code class="text-gray-400">api/*</code> per Laravel defaults.</p>
            <p class="text-xs text-gray-500">The embed snippet must pass <code class="text-brand-400">apiBase</code> pointing at this installation when the script runs on a different domain.</p>
        </div>
    </div>
</div>
@endsection
