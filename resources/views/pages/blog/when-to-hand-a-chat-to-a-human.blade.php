@extends('layouts.post')
@section('body')
<p>We have all been there. You have a problem that does not fit the script, and you are stuck in a loop with a chatbot that keeps offering you the same three links. By the time you finally reach a person, you are annoyed, and you have to explain everything again from the start.</p>
<p>That experience gives automated support its bad name. The good news is that it is almost entirely avoidable. Handoff, the moment a conversation moves from the assistant to a person, is a design decision. Get it right and customers barely notice the switch. Get it wrong and nothing else you do will matter.</p>

<h2>Always let people ask for a person</h2>
<p>This is the first rule, and it is not negotiable. If a visitor types "can I talk to someone", "human please" or "agent", they should get a clear path to a person straight away. Do not make them prove they have tried the assistant first. Do not ask them three qualifying questions.</p>
<p>It might feel as if this undermines the assistant. In practice the opposite happens. When people know they can reach a human whenever they want, they are much more willing to give the assistant a chance first.</p>

<h2>The signals that should trigger a handoff</h2>
<p>Beyond direct requests, there are a few situations where the assistant should offer a person on its own initiative:</p>
<ul>
    <li><strong>Frustration.</strong> Repeated questions, capital letters, or phrases like "that is not what I asked". Sentiment detection can spot these and flag the conversation.</li>
    <li><strong>Money disputes.</strong> Refunds, billing errors and charges the customer does not recognise. These need judgement and often access to systems the assistant should not have.</li>
    <li><strong>High-value opportunities.</strong> A visitor asking about a large order, an enterprise plan or a long-term contract deserves a conversation with your sales team, not a link to the pricing page.</li>
    <li><strong>Anything personal or sensitive.</strong> Health, safety, complaints about staff, or anything where an automated reply could feel dismissive.</li>
    <li><strong>Repeated "I do not know".</strong> If the assistant cannot answer twice in a row, it is time to bring someone in.</li>
</ul>

<h2>Hand over the context, not just the conversation</h2>
<p>The worst handoff is one where the agent says, "Hi, how can I help you today?" after the customer has already spent five minutes explaining. It tells the customer that everything they said was wasted.</p>
<p>A good handoff gives the agent everything at a glance: the full transcript, a two-line summary of what the visitor wants, their mood, the page they were on, and any details they have already shared. With that, the agent's first message can be, "Hi Sarah, I can see your order arrived damaged. I am sorry about that. Let me sort out a replacement for you."</p>
<p>That single sentence turns a frustrating experience into a good one.</p>

<h2>Be honest about wait times</h2>
<p>If nobody is available right now, say so. "Our team is offline until 9am tomorrow. Leave your email and the details here, and we will reply first thing" is far better than leaving someone waiting in an empty chat window.</p>
<p>Offline messages should go straight to your team's inbox and email, with the conversation attached, so the next person to log in can pick them up without searching.</p>

<h2>Let the assistant take back the routine part</h2>
<p>Handoff does not have to be permanent. Once an agent has dealt with the tricky part, such as approving a refund, they can hand the conversation back to the assistant to deal with follow-up questions about tracking or delivery times. Your team spends its time where judgement is needed, and nowhere else.</p>

<h2>Measure it</h2>
<p>Keep an eye on three numbers:</p>
<ul>
    <li><strong>Handoff rate:</strong> the share of conversations that reach a person. A sudden rise usually means a gap in your knowledge base or a new problem with your product.</li>
    <li><strong>Time to first human reply:</strong> how long visitors wait once they have asked for a person.</li>
    <li><strong>Ratings after handoff:</strong> whether the experience actually helped.</li>
</ul>
<p>The aim is not to get handoffs down to zero. It is to make sure that the conversations that reach your team are the ones that really need them, and that those conversations go well.</p>

<h2>A simple checklist</h2>
<ol>
    <li>Visitors can always ask for a person, in their own words.</li>
    <li>Frustration, money disputes and big opportunities trigger an offer of help.</li>
    <li>Agents see a summary and the full context before they reply.</li>
    <li>Wait times are honest, and offline messages are never lost.</li>
    <li>Routine follow-ups can go back to the assistant.</li>
</ol>
<p>{{ config('branding.name') }} does all of this out of the box. If you would like to see how it works with your own team, <a href="{{ route('contact', ['topic' => 'sales']) }}">book a short demo</a>.</p>
@endsection
