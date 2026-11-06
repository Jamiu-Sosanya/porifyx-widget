@if(\App\Support\PrivacyPolicy::get()['request_form'])
<h2 id="request">Make a privacy request</h2>
@if(session('privacy_sent'))
<p class="not-prose rounded-xl bg-emerald-50 text-emerald-800 px-4 py-3">Thanks, your request was sent. We may ask you to confirm your identity before acting on it.</p>
@else
<form method="POST" action="{{ route('privacy.request') }}" class="not-prose grid sm:grid-cols-2 gap-3">
    @csrf
    <input type="text" name="website" class="hidden" tabindex="-1" autocomplete="off">
    <select name="type" required class="rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:bg-white/5 dark:border-white/10">
        <option value="access">Get a copy of my data</option><option value="delete">Delete my data</option><option value="correct">Correct my data</option><option value="restrict">Stop or limit processing</option><option value="other">Other</option>
    </select>
    <input type="email" name="email" required maxlength="190" placeholder="Your email address" class="rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:bg-white/5 dark:border-white/10">
    <input name="website_used" maxlength="190" placeholder="Website where you chatted (optional)" class="sm:col-span-2 rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:bg-white/5 dark:border-white/10">
    <textarea name="details" rows="3" maxlength="2000" placeholder="Anything that helps us find your data (optional)" class="sm:col-span-2 rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:bg-white/5 dark:border-white/10"></textarea>
    @error('email')<p class="sm:col-span-2 text-sm text-rose-600">{{ $message }}</p>@enderror
    <button class="sm:col-span-2 justify-self-start px-5 py-2.5 rounded-xl bg-[#ff6a2b] text-white text-sm font-semibold">Send request</button>
</form>
@endif
@endif
