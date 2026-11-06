@php $home = request()->routeIs('home') ? '' : route('home'); $isHome = $home === ''; @endphp
<footer class="border-t border-slate-200 bg-white dark:border-white/10 dark:bg-[#050a14]">
    <div class="max-w-6xl mx-auto px-5 pt-16 pb-24 sm:pb-10">
        <div class="grid grid-cols-2 md:grid-cols-6 gap-x-6 gap-y-10">
            <div class="col-span-2">
                <a href="{{ route('home') }}" class="flex items-center gap-2.5 font-extrabold text-lg">@include('layouts.partials.logo', ['size' => 36])</a>
                <p class="mt-4 text-sm leading-relaxed max-w-xs {{ $muted }}">{{ config('branding.tagline') }}</p>
                <a href="mailto:{{ config('branding.support_email') }}" class="mt-5 inline-flex items-center gap-2 text-sm font-semibold text-brand-600 dark:text-brand-400 break-all">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 8l9 6 9-6M5 19h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2z"/></svg>{{ config('branding.support_email') }}
                </a>
                @php $socials = array_filter(['X' => config('branding.twitter_url'), 'Facebook' => config('branding.facebook_url'), 'LinkedIn' => config('branding.linkedin_url')]); @endphp
                @if($socials)
                <div class="mt-5 flex gap-2">
                    @foreach($socials as $label => $href)
                    <a href="{{ $href }}" target="_blank" rel="noopener" class="px-3 py-1.5 rounded-lg text-xs font-semibold {{ $card }}">{{ $label }}</a>
                    @endforeach
                </div>
                @endif
            </div>
            @foreach([
                'Product'   => [['Features', $home . '#features'], ['Use cases', $home . '#use-cases'], ['Pricing', $home . '#pricing'], ['Live demo', $home . '#demo'], ['Changelog', route('changelog')], ['Roadmap', route('roadmap')]],
                'Resources' => [['Documentation', route('documentation')], ['API reference', route('api.reference')], ['Blog', route('blog')], ['System status', route('status')]],
                'Company'   => [['About', route('about')], ['Careers', route('careers')], ['Talk to sales', route('contact', ['topic' => 'sales'])], ['Contact', route('contact')]],
                'Legal'     => [['Privacy policy', route('privacy')], ['Terms of service', route('terms')], ['Cookie settings', null]],
            ] as $heading => $links)
            <nav aria-label="{{ $heading }}">
                <p class="text-sm font-bold">{{ $heading }}</p>
                <ul class="mt-4 space-y-3 text-sm {{ $muted }}">
                    @foreach($links as [$label, $href])
                    <li>@if($href)<a href="{{ $href }}" class="hover:text-brand-600 dark:hover:text-brand-300">{{ $label }}</a>@else<button type="button" onclick="window.dispatchEvent(new Event('pfx-consent-open'))" class="hover:text-brand-600 dark:hover:text-brand-300">{{ $label }}</button>@endif</li>
                    @endforeach
                </ul>
            </nav>
            @endforeach
        </div>
        <div class="mt-14 pt-6 border-t border-slate-200 dark:border-white/10 flex flex-col-reverse sm:flex-row gap-4 sm:items-center justify-between text-sm {{ $muted }}">
            <p>{{ config('branding.copyright') ?: '© ' . date('Y') . ' ' . $name . '. All rights reserved.' }}</p>
            <button type="button" @click="toggleTheme()" class="self-start sm:self-auto inline-flex items-center gap-2 px-3 py-1.5 rounded-lg {{ $card }}">
                <span x-text="dark ? '☀ Light mode' : '☾ Dark mode'"></span>
            </button>
        </div>
    </div>
    <div class="max-w-6xl mx-auto px-5 pb-8 -mt-2">
        <!--notranslate--><select aria-label="Language" onchange="location.href = @js(url('/lang')) + '/' + this.value" class="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs dark:border-white/10 dark:bg-white/5">
            @foreach(\App\Support\I18n::LOCALES as $code => $name)<option value="{{ $code }}" @selected(\App\Support\I18n::current() === $code)>{{ $name }}</option>@endforeach
        </select><!--/notranslate-->
    </div>
</footer>
