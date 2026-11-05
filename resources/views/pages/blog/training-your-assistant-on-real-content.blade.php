@extends('layouts.post')
@section('body')
<p>When people hear "train your AI assistant", many imagine a long project: writing hundreds of questions and answers, hiring a consultant, building a knowledge base from nothing. It almost never needs to be like that.</p>
<p>Most businesses already have everything their assistant needs. It is just spread across the website, a few documents and the sent folder of whoever answers customer emails. This guide shows you how to gather it up in an afternoon.</p>

<h2>Start with the five pages that matter most</h2>
<p>Look at your website and pick the pages that answer the questions you get most often. For most businesses these are:</p>
<ol>
    <li><strong>Pricing or plans.</strong> Nearly every buyer asks about cost.</li>
    <li><strong>Delivery, shipping or service area.</strong> Where you work and how long it takes.</li>
    <li><strong>Returns, refunds or cancellations.</strong> What happens if it goes wrong.</li>
    <li><strong>Your FAQ page,</strong> if you have one.</li>
    <li><strong>Your main product or service pages.</strong> What you actually offer.</li>
</ol>
<p>Add those URLs to your assistant and train it. That alone will usually handle most of your incoming questions. Resist the urge to add your whole website on day one. Blog posts, old news and marketing pages tend to add noise rather than useful answers.</p>

<h2>Mine your inbox for the real questions</h2>
<p>Next, open the inbox of whoever answers customer enquiries. Search for the questions that come up again and again. You are looking for two kinds of email:</p>
<ul>
    <li><strong>Questions your website does not answer.</strong> These show you gaps. Write a clear answer and add it as a question-and-answer pair.</li>
    <li><strong>Replies your team has perfected.</strong> If someone has written the ideal explanation of your warranty twenty times, that is exactly the answer your assistant should give.</li>
</ul>
<p>Question-and-answer pairs are the most precise form of training. When the exact wording matters, for anything legal, financial or safety-related, use a pair rather than relying on a web page.</p>

<h2>Add the documents your team actually uses</h2>
<p>Product manuals, spec sheets, price lists and internal guides are often more detailed and more accurate than the website. Upload them as PDFs or Word documents.</p>
<p>One word of warning: check that they are current. If an old price list and a new one are both in the knowledge base, the assistant has no way of knowing which is right. Out-of-date content is the most common cause of wrong answers we see.</p>

<h2>Write a few instructions, not a novel</h2>
<p>Instructions tell the assistant how to behave: its tone, what to emphasise and what to avoid. Keep them short and concrete. Compare these two:</p>
<blockquote>Be helpful, friendly and professional, and always provide excellent customer service.</blockquote>
<blockquote>Keep replies under three sentences. Mention free delivery on orders over £50. Never quote a delivery date for custom orders. Offer to connect the visitor with the team instead.</blockquote>
<p>The first sounds nice but changes nothing. The second gives the assistant clear rules it can follow.</p>

<h2>Test with real questions</h2>
<p>Before you go live, ask your assistant twenty real questions taken from your inbox, word for word, typos and all. For each answer, ask yourself whether you would be happy if a customer received it.</p>
<p>When an answer is wrong or weak, do not just rephrase your test question until it works. Find the content behind it and improve that. The goal is an assistant that handles how real people ask, not how you would ask.</p>

<h2>Keep it fresh with a weekly fifteen minutes</h2>
<p>Training is not a one-off job, but it should not be a burden either. Once a week, spend fifteen minutes on three things:</p>
<ul>
    <li>Read the conversations with low ratings or no clear answer.</li>
    <li>Add or fix the content behind them, then retrain.</li>
    <li>Remove anything that has gone out of date, such as finished promotions or old prices.</li>
</ul>
<p>Every retrain is saved as a version, so if a change makes things worse you can roll back in one click. It means you can experiment without worrying about breaking anything.</p>

<h2>The short version</h2>
<p>Add your five most important pages. Turn your best email replies into question-and-answer pairs. Upload current documents. Write a few clear rules. Test with real questions. Review weekly. That is genuinely all it takes to have an assistant your customers will trust.</p>
<p>The <a href="{{ route('documentation') }}#training">training section of our documentation</a> goes into more detail on each step.</p>
@endsection
