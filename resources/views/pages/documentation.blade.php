@php
    $toc = [
        'overview' => 'Overview', 'quick-start' => 'Quick start', 'install' => 'Installing the widget', 'platforms' => 'Platform guides',
        'javascript' => 'JavaScript controls', 'training' => 'Training your assistant', 'behaviour' => 'Behaviour and tone',
        'appearance' => 'Appearance', 'models' => 'AI models and failover', 'leads' => 'Lead capture', 'handoff' => 'Human handoff',
        'proactive' => 'Proactive messages', 'channels' => 'Other channels', 'webhooks' => 'Webhooks and automations',
        'team' => 'Team and security', 'troubleshooting' => 'Troubleshooting',
    ];
@endphp
@extends('layouts.doc')
@section('title', 'Documentation')
@section('description', "Everything you need to install, train and run your {$name} assistant.")
@section('eyebrow', 'Documentation')
@section('heading', 'Set up your assistant and get the most out of it')
@section('lead', 'Start with the quick start and you can be live in about fifteen minutes. The rest of this guide explains each feature in depth, with practical advice from teams who use it every day.')

@section('body')
<h2 id="overview">Overview</h2>
<p>{{ $name }} has three parts that work together:</p>
<ul>
    <li><strong>The widget</strong> is the chat window your visitors see. It loads from one small script and works on any website.</li>
    <li><strong>The assistant</strong> is the AI behind the widget. It answers from the knowledge you give it, follows your instructions and knows when to hand over to a person.</li>
    <li><strong>The dashboard</strong> is where your team trains the assistant, reads conversations, manages leads and changes settings.</li>
</ul>
<p>You can run several assistants from one account, for example one per website, brand or language. Each has its own knowledge, appearance and settings.</p>

<h2 id="quick-start">Quick start</h2>
<ol>
    <li><strong>Create your account.</strong> <a href="{{ route('register') }}">Sign up</a> and confirm your email address.</li>
    <li><strong>Create an assistant.</strong> In the dashboard, open <em>Widgets</em> and choose <em>New widget</em>. Give it a name and a welcome message, and pick your brand colour.</li>
    <li><strong>Add knowledge.</strong> Open the assistant's <em>Knowledge</em> tab and add your website address. We read the pages for you. You can add documents and question-and-answer pairs later.</li>
    <li><strong>Test it.</strong> Use the preview in the dashboard to ask the questions your customers ask most. If an answer is weak, add or improve the content behind it.</li>
    <li><strong>Install it.</strong> Copy the embed code from the <em>Embed</em> page and paste it into your website, just before the closing <code>&lt;/body&gt;</code> tag.</li>
    <li><strong>Watch the first conversations.</strong> For the first few days, read the conversations in your inbox. It is the fastest way to see what your visitors really want.</li>
</ol>

<h2 id="install">Installing the widget</h2>
<p>Every assistant has its own embed code on its <em>Embed</em> page. It looks like this:</p>
<pre><code>&lt;script&gt;
  (function(w,d,s,o,f,js,fjs){
    w[o]=w[o]||function(){(w[o].q=w[o].q||[]).push(arguments)};
    js=d.createElement(s),fjs=d.getElementsByTagName(s)[0];
    js.id=o;js.src=f;js.async=1;fjs.parentNode.insertBefore(js,fjs);
  }(window,document,'script','pfx','{{ url('/widget.js') }}'));
  pfx('init', { widgetId: 'YOUR-WIDGET-ID' });
&lt;/script&gt;</code></pre>
<p>Paste it once, on every page where you want the assistant to appear. Most sites put it in a shared footer or layout template so it appears everywhere automatically.</p>
<p>The script loads asynchronously, after the rest of your page. It does not block rendering and has no measurable effect on your page speed scores.</p>
<h3>Restricting which sites can use your widget</h3>
<p>In the assistant's settings you can list the domains that are allowed to load it, for example <code>example.com</code> and <code>shop.example.com</code>. Requests from any other site are refused. We recommend setting this before you go live, so nobody can copy your embed code and use your assistant on their own site.</p>

<h2 id="platforms">Platform guides</h2>
<h3>WordPress</h3>
<p>Install a header-and-footer plugin such as <em>WPCode</em>, open its footer section and paste your embed code. If you use a block theme, you can also add a <em>Custom HTML</em> block to the footer template part.</p>
<h3>Shopify</h3>
<p>In your Shopify admin, go to <em>Online Store</em>, then <em>Themes</em>. Choose <em>Edit code</em> on your live theme, open <code>theme.liquid</code> and paste the code just above <code>&lt;/body&gt;</code>. Save, then preview your store.</p>
<h3>Webflow</h3>
<p>Open <em>Site settings</em>, then <em>Custom code</em>. Paste the code into the <em>Footer code</em> box and publish your site. Custom code only runs on the published site, not inside the Designer.</p>
<h3>Wix and Squarespace</h3>
<p>On Wix, go to <em>Settings</em>, then <em>Custom code</em>, add the code, set it to load on all pages and place it in the body end. On Squarespace, go to <em>Settings</em>, <em>Advanced</em>, then <em>Code injection</em>, and paste it into the footer box.</p>
<h3>React, Next.js, Vue and other single-page apps</h3>
<p>Add the snippet to your root HTML template, such as <code>index.html</code>, or load it once in your root layout. In Next.js, use the <code>Script</code> component with <code>strategy="afterInteractive"</code>. The widget keeps the same conversation open as visitors move between routes.</p>

<h2 id="javascript">JavaScript controls</h2>
<p>Once the script has loaded, you can control the widget from your own code using the <code>pfx</code> function:</p>
<pre><code>pfx('open');   // open the chat window
pfx('close');  // close it again</code></pre>
<p>A common use is a "Chat with us" button elsewhere on your page:</p>
<pre><code>&lt;button onclick="pfx('open')"&gt;Questions? Chat with us&lt;/button&gt;</code></pre>
<p>Calls made before the script finishes loading are queued and run as soon as it is ready, so you never need to wait for it yourself.</p>

<h2 id="training">Training your assistant</h2>
<p>Your assistant answers from its knowledge base. The better the content, the better the answers. You can add knowledge in three ways:</p>
<ul>
    <li><strong>Website pages.</strong> Add a URL and we read the page. Add your pricing, shipping, returns, FAQ and product pages first. These cover most questions.</li>
    <li><strong>Documents.</strong> Upload PDFs, Word documents and text files, such as product manuals, policy documents or price lists.</li>
    <li><strong>Question-and-answer pairs.</strong> Write the exact answer you want for a specific question. These are ideal for the questions where wording really matters, such as refunds or legal topics.</li>
</ul>
<p>Training runs in the background. Each time you retrain, we keep the previous version, so if a change makes answers worse you can roll back in one click.</p>
<h3>Tips for better answers</h3>
<ul>
    <li>Write content the way you would explain it to a new member of staff. Clear and specific beats long and vague.</li>
    <li>Remove outdated pages. If old and new prices are both in the knowledge base, the assistant may quote either.</li>
    <li>Check the <em>Unanswered</em> and low-rated conversations every week. Each one shows you a gap to fill.</li>
</ul>

<h2 id="behaviour">Behaviour and tone</h2>
<p>In the assistant's settings you can write instructions that shape how it behaves. Good instructions are short and specific, for example:</p>
<blockquote>You are the assistant for Green Leaf Florists. Be warm and concise. Always mention same-day delivery for orders placed before 2pm. Never promise a delivery time for weddings. Offer to connect the visitor with the team instead.</blockquote>
<p>You can also adjust the <strong>temperature</strong>. A lower value makes answers more consistent and factual. A higher value makes them more varied and conversational. For most businesses a low setting works best.</p>
<h3>Strict mode</h3>
<p>With strict mode on, the assistant answers only from your knowledge base. If it cannot find the answer, it says so and offers to connect the visitor with your team instead of guessing. We recommend strict mode for any business where a wrong answer could cause real problems, such as healthcare, finance or legal services.</p>
<h3>Languages</h3>
<p>The assistant replies in the language the visitor writes in. You can set a different welcome message and input placeholder for each language your visitors use.</p>

<h2 id="appearance">Appearance</h2>
<p>You can change the widget's colour, position, launcher icon, assistant name, avatar and welcome message to match your brand. Starter questions appear as tappable chips when the chat opens. They are a simple way to show visitors what the assistant can help with. Changes appear on your website within a minute, with no need to change your embed code.</p>
<p>To test two designs or two welcome messages against each other, use an <strong>A/B test</strong>. Visitors are split between the versions, and the dashboard shows which one starts more conversations and captures more leads.</p>

<h2 id="models">AI models and failover</h2>
<p>Each assistant can use a different AI provider and model: OpenAI, Anthropic Claude, Google Gemini, Groq or OpenRouter. OpenRouter gives you access to many more models with a single key.</p>
<p>As a rule of thumb, smaller, faster models are perfect for most customer questions and cost less. Larger models are worth it for complex products or long, technical documents.</p>
<p>If you configure a backup provider and your main provider has an outage or times out, {{ $name }} automatically sends the request to the backup instead. Your visitors still get an answer, and nothing needs to change on your side.</p>

<h2 id="leads">Lead capture</h2>
<p>The assistant can ask for contact details at the right moment in a conversation, for example after it has helped with a question or when a visitor shows buying intent. You decide which details to ask for and when.</p>
<p>Every lead is scored <strong>cold</strong>, <strong>warm</strong> or <strong>hot</strong> based on what was said, so your team can focus on the most promising ones first. Leads appear in the <em>Leads</em> section, where you can add notes, change their status, and export them to Excel or PDF. To send them straight to your CRM, use <a href="#webhooks">webhooks</a> or automations.</p>

<h2 id="handoff">Human handoff</h2>
<p>A visitor can ask to speak to a person at any time, and the assistant can offer it when it cannot help. When that happens, the conversation appears in your team's inbox and everyone who is online is notified.</p>
<p>When an agent takes over, the AI stops replying. The agent sees the full conversation, a short AI summary, the visitor's mood and suggested replies, so they never need to ask the visitor to repeat themselves. When the agent is finished, they can hand the conversation back to the assistant.</p>
<p>Outside your working hours, visitors can leave a message instead. It arrives in your inbox and by email, so nothing gets lost.</p>

<h2 id="proactive">Proactive messages</h2>
<p>Proactive messages start the conversation for the visitor. You can show a message when someone has spent a certain time on a page, scrolled most of the way down, or is about to leave. For example, on a pricing page you might show: "Comparing plans? I can help you find the right one."</p>
<p>Use them sparingly. One well-timed message on a high-intent page works far better than a pop-up on every page.</p>

<h2 id="channels">Other channels</h2>
<p>The same trained assistant can answer on WhatsApp, Telegram, Facebook Messenger, Instagram, X, Slack, Discord, SMS and email. Connect a channel from the <em>Channels</em> page, follow the steps for that platform and send a test message. Conversations from every channel appear in the same inbox.</p>

<h2 id="webhooks">Webhooks and automations</h2>
<p>Webhooks send a message to your own system whenever something important happens, such as a lead being qualified or a visitor asking for a person. Add your endpoint URL in the assistant's settings, and use the test button to check it works.</p>
<p>Every request is signed with your webhook secret, so you can confirm it really came from us. The full list of events, the payload format and code for checking signatures are in the <a href="{{ route('api.reference') }}#webhooks">API reference</a>.</p>
<p>If you would rather not write code, <strong>automations</strong> let you set up simple rules in the dashboard, such as "when a hot lead is captured, email the sales team".</p>

<h2 id="team">Team and security</h2>
<ul>
    <li><strong>Team members:</strong> invite colleagues from <em>Settings</em>. Each person has their own login, so you can see who replied to which conversation.</li>
    <li><strong>Two-factor authentication:</strong> turn it on in your profile. We strongly recommend it for every account with access to customer conversations.</li>
    <li><strong>Data export and deletion:</strong> you can export conversations and leads at any time, and delete individual conversations when a visitor asks you to.</li>
</ul>

<h2 id="troubleshooting">Troubleshooting</h2>
<h3>The widget does not appear</h3>
<ul>
    <li>Check that the embed code is on the page. In your browser, right-click, choose <em>View page source</em> and search for <code>widget.js</code>.</li>
    <li>If you set allowed domains, make sure your site's exact domain is in the list, including any <code>www</code> or subdomain.</li>
    <li>Check that the assistant is switched on in the dashboard.</li>
    <li>Some caching or optimisation plugins delay or combine scripts. Exclude <code>widget.js</code> from them and clear your site cache.</li>
</ul>
<h3>The assistant gives wrong or vague answers</h3>
<ul>
    <li>Ask the same question in the dashboard preview and check which knowledge it used.</li>
    <li>Add a question-and-answer pair for that topic, or improve the page it is based on, then retrain.</li>
    <li>Turn on strict mode if it is guessing instead of saying it does not know.</li>
</ul>
<h3>Replies are slow</h3>
<p>Reply speed mostly depends on the AI model. Try a faster model, such as one hosted on Groq, or reduce very long instructions. If your provider is having problems, you can check our <a href="{{ route('status') }}">status page</a>.</p>
<h3>Still stuck?</h3>
<p><a href="{{ route('contact', ['topic' => 'support']) }}">Contact support</a> and tell us your widget name and the page where you see the problem. Screenshots help a lot.</p>
@endsection
