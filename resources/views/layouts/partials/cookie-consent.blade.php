<div
    x-data="{
        show: false,
        init() {
            if (!localStorage.getItem('cookie_consent')) {
                this.show = true;
            }
        },
        accept(all) {
            localStorage.setItem('cookie_consent', all ? 'all' : 'necessary');
            this.show = false;
        }
    }"
    x-show="show"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 translate-y-4"
    x-transition:enter-end="opacity-100 translate-y-0"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 translate-y-0"
    x-transition:leave-end="opacity-0 translate-y-4"
    style="display:none"
    class="fixed bottom-0 inset-x-0 z-50 p-4 sm:p-6"
    role="dialog"
    aria-label="Cookie consent"
>
    <div class="max-w-3xl mx-auto bg-gray-900 border border-white/10 rounded-2xl shadow-2xl shadow-black/50 p-5 sm:p-6 flex flex-col sm:flex-row items-start sm:items-center gap-4">
        <div class="flex-1 min-w-0">
            <p class="text-sm font-semibold text-white mb-1">We use cookies</p>
            <p class="text-xs text-gray-400 leading-relaxed">
                We use cookies to improve your experience, analyze traffic, and personalize content.
                Read our <a href="{{ route('privacy') }}" class="text-brand-400 hover:text-brand-300 underline transition">Privacy Policy</a> for details.
            </p>
        </div>
        <div class="flex items-center gap-2 shrink-0">
            <button @click="accept(false)"
                    class="px-4 py-2 text-xs font-medium text-gray-400 hover:text-white border border-white/10 hover:border-white/20 rounded-xl transition">
                Necessary only
            </button>
            <button @click="accept(true)"
                    class="px-4 py-2 text-xs font-medium bg-brand-600 hover:bg-brand-500 text-white rounded-xl transition">
                Accept all
            </button>
        </div>
    </div>
</div>
