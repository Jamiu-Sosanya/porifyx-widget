@extends('layouts.admin')
@section('title', 'System Settings')
@section('page-title', 'System Settings')
@section('content')
<div class="max-w-5xl" x-data="{
    tab: 'app',
    testEmailAddr: '{{ auth()->user()->email }}',
    testEmailMsg: '',
    testEmailOk: null,
    testEmailLoading: false,
    aiStatus: {},
    aiLoading: {},

    async sendTestEmail() {
        this.testEmailLoading = true;
        this.testEmailMsg = '';
        this.testEmailOk = null;
        try {
            const fd = new FormData();
            fd.append('email', this.testEmailAddr);
            fd.append('_token', document.querySelector('meta[name=csrf-token]').content);
            const res = await fetch('{{ route('admin.settings.testEmail') }}', { method: 'POST', body: fd });
            const data = await res.json();
            this.testEmailMsg = data.message;
            this.testEmailOk = data.ok;
        } catch (e) {
            this.testEmailMsg = 'Request failed: ' + e.message;
            this.testEmailOk = false;
        } finally {
            this.testEmailLoading = false;
        }
    },

    async testAi(provider) {
        this.aiLoading[provider] = true;
        this.aiStatus[provider] = null;
        try {
            const fd = new FormData();
            fd.append('provider', provider);
            fd.append('_token', document.querySelector('meta[name=csrf-token]').content);
            const res = await fetch('{{ route('admin.settings.testAi') }}', { method: 'POST', body: fd });
            const data = await res.json();
            this.aiStatus[provider] = data;
        } catch (e) {
            this.aiStatus[provider] = { ok: false, message: e.message };
        } finally {
            this.aiLoading[provider] = false;
        }
    }
}">

    {{-- Page header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-lg font-semibold text-white">System Settings</h2>
            <p class="text-xs text-gray-500 mt-0.5">All changes are saved to the database and take effect immediately — no server restart required.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.dashboard') }}" class="px-3 py-2 text-xs text-gray-400 hover:text-white transition">← Dashboard</a>
        </div>
    </div>

    @if(session('success'))
    <div class="mb-5 p-4 bg-emerald-500/10 border border-emerald-500/20 rounded-2xl text-sm text-emerald-400 flex items-center gap-2">
        <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd" /></svg>
        {{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div class="mb-5 p-4 bg-rose-500/10 border border-rose-500/20 rounded-2xl text-sm text-rose-400">{{ session('error') }}</div>
    @endif

    {{-- Tab navigation --}}
    <div class="flex items-center gap-1 bg-gray-900/50 border border-white/5 rounded-2xl p-1.5 mb-6 overflow-x-auto">
        @foreach([
            'app'      => ['⚙️',  'General'],
            'branding' => ['🎨',  'Branding'],
            'ai'       => ['🤖',  'AI Providers'],
            'mail'     => ['📧',  'Email / SMTP'],
            'payment'  => ['💳',  'Payment'],
            'security' => ['🔒',  'Security'],
            'features' => ['✨',  'Features'],
        ] as $key => [$icon, $label])
        <button @click="tab = '{{ $key }}'"
                :class="tab === '{{ $key }}' ? 'bg-white/10 text-white shadow' : 'text-gray-400 hover:text-white hover:bg-white/5'"
                class="px-4 py-2 text-xs font-medium rounded-xl transition whitespace-nowrap flex items-center gap-1.5">
            <span>{{ $icon }}</span> {{ $label }}
        </button>
        @endforeach
    </div>

    {{-- ── Branding Asset Uploads (outside main form — needs multipart) ──────── --}}
    <div x-show="tab === 'branding'" x-transition class="mb-6 space-y-4">
        <div class="bg-gray-900/50 border border-white/5 rounded-2xl overflow-hidden">
            <div class="px-6 py-4 border-b border-white/4">
                <h3 class="text-sm font-semibold text-white">Logo &amp; Icon Uploads</h3>
                <p class="text-xs text-gray-500 mt-0.5">Upload images directly — PNG, JPG, SVG, ICO supported. Max 2 MB each.</p>
            </div>

            @php
            $logoUrl    = \App\Models\SystemSetting::where('key', 'brand_logo_url')->value('value')    ?: '';
            $faviconUrl = \App\Models\SystemSetting::where('key', 'brand_favicon_url')->value('value') ?: '';
            $ogUrl      = \App\Models\SystemSetting::where('key', 'brand_og_image_url')->value('value') ?: '';
            @endphp

            {{-- Logo --}}
            <div class="px-6 py-5 grid grid-cols-1 md:grid-cols-5 gap-4 items-start border-b border-white/4">
                <div class="md:col-span-2">
                    <p class="text-sm font-medium text-gray-200 mb-1">App Logo</p>
                    <p class="text-xs text-gray-500 leading-relaxed">Shown in the navbar and on auth pages. Recommended: 200 × 50 px, PNG/SVG with transparent background.</p>
                </div>
                <div class="md:col-span-3" x-data="{ preview: '{{ $logoUrl }}', dragging: false }">
                    {{-- Current preview --}}
                    <div x-show="preview" class="mb-3 flex items-center gap-3 p-3 bg-white/3 border border-white/5 rounded-xl">
                        <img :src="preview" alt="Logo" class="h-10 max-w-40 object-contain rounded">
                        <div class="flex-1 min-w-0">
                            <p class="text-xs text-gray-400 truncate" x-text="preview"></p>
                        </div>
                        <form method="POST" action="{{ route('admin.settings.deleteBranding', 'logo') }}" onsubmit="return confirm('Remove logo?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="p-1.5 text-gray-600 hover:text-rose-400 transition">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                            </button>
                        </form>
                    </div>
                    {{-- Upload form --}}
                    <form method="POST" action="{{ route('admin.settings.uploadBranding') }}" enctype="multipart/form-data"
                          x-on:submit="$el.querySelector('button[type=submit]').disabled = true">
                        @csrf
                        <input type="hidden" name="asset" value="logo">
                        <label class="flex flex-col items-center justify-center w-full h-28 border-2 border-dashed rounded-xl cursor-pointer transition"
                               :class="dragging ? 'border-brand-500 bg-brand-500/10' : 'border-white/10 hover:border-white/20 bg-white/2 hover:bg-white/4'"
                               @dragover.prevent="dragging = true" @dragleave.prevent="dragging = false"
                               @drop.prevent="dragging = false; const f = $event.dataTransfer.files[0]; if(f){ $el.querySelector('input[type=file]').files = $event.dataTransfer.files; preview = URL.createObjectURL(f); }">
                            <svg class="w-7 h-7 text-gray-600 mb-2" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" /></svg>
                            <p class="text-xs text-gray-500">Drag & drop or <span class="text-brand-400">browse</span></p>
                            <p class="text-[10px] text-gray-600 mt-0.5">PNG, SVG, JPG · Max 2 MB</p>
                            <input type="file" name="file" accept=".png,.jpg,.jpeg,.svg,.gif,.webp" class="hidden"
                                   @change="preview = URL.createObjectURL($event.target.files[0])">
                        </label>
                        <button type="submit" class="mt-2 w-full px-4 py-2.5 bg-brand-600 hover:bg-brand-500 text-white text-xs font-medium rounded-xl transition">
                            Upload Logo
                        </button>
                    </form>
                </div>
            </div>

            {{-- Favicon --}}
            <div class="px-6 py-5 grid grid-cols-1 md:grid-cols-5 gap-4 items-start border-b border-white/4">
                <div class="md:col-span-2">
                    <p class="text-sm font-medium text-gray-200 mb-1">Favicon</p>
                    <p class="text-xs text-gray-500 leading-relaxed">Displayed in the browser tab. Recommended: 32 × 32 px ICO or PNG. SVG also works in modern browsers.</p>
                </div>
                <div class="md:col-span-3" x-data="{ preview: '{{ $faviconUrl }}', dragging: false }">
                    <div x-show="preview" class="mb-3 flex items-center gap-3 p-3 bg-white/3 border border-white/5 rounded-xl">
                        <img :src="preview" alt="Favicon" class="w-8 h-8 object-contain rounded">
                        <div class="flex-1 min-w-0">
                            <p class="text-xs text-gray-400 truncate" x-text="preview"></p>
                        </div>
                        <form method="POST" action="{{ route('admin.settings.deleteBranding', 'favicon') }}" onsubmit="return confirm('Remove favicon?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="p-1.5 text-gray-600 hover:text-rose-400 transition">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                            </button>
                        </form>
                    </div>
                    <form method="POST" action="{{ route('admin.settings.uploadBranding') }}" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="asset" value="favicon">
                        <label class="flex flex-col items-center justify-center w-full h-28 border-2 border-dashed rounded-xl cursor-pointer transition"
                               :class="dragging ? 'border-brand-500 bg-brand-500/10' : 'border-white/10 hover:border-white/20 bg-white/2 hover:bg-white/4'"
                               @dragover.prevent="dragging = true" @dragleave.prevent="dragging = false"
                               @drop.prevent="dragging = false; const f = $event.dataTransfer.files[0]; if(f){ $el.querySelector('input[type=file]').files = $event.dataTransfer.files; preview = URL.createObjectURL(f); }">
                            <svg class="w-7 h-7 text-gray-600 mb-2" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" /></svg>
                            <p class="text-xs text-gray-500">Drag & drop or <span class="text-brand-400">browse</span></p>
                            <p class="text-[10px] text-gray-600 mt-0.5">ICO, PNG, SVG · Max 2 MB</p>
                            <input type="file" name="file" accept=".ico,.png,.svg,.jpg,.jpeg" class="hidden"
                                   @change="preview = URL.createObjectURL($event.target.files[0])">
                        </label>
                        <button type="submit" class="mt-2 w-full px-4 py-2.5 bg-brand-600 hover:bg-brand-500 text-white text-xs font-medium rounded-xl transition">
                            Upload Favicon
                        </button>
                    </form>
                </div>
            </div>

            {{-- OG Image --}}
            <div class="px-6 py-5 grid grid-cols-1 md:grid-cols-5 gap-4 items-start">
                <div class="md:col-span-2">
                    <p class="text-sm font-medium text-gray-200 mb-1">OG / Social Image</p>
                    <p class="text-xs text-gray-500 leading-relaxed">Used when sharing links on Twitter, LinkedIn, Slack, etc. Recommended: 1200 × 630 px PNG/JPG.</p>
                </div>
                <div class="md:col-span-3" x-data="{ preview: '{{ $ogUrl }}', dragging: false }">
                    <div x-show="preview" class="mb-3 flex items-center gap-3 p-3 bg-white/3 border border-white/5 rounded-xl">
                        <img :src="preview" alt="OG Image" class="h-12 max-w-30 object-cover rounded">
                        <div class="flex-1 min-w-0">
                            <p class="text-xs text-gray-400 truncate" x-text="preview"></p>
                        </div>
                        <form method="POST" action="{{ route('admin.settings.deleteBranding', 'og_image') }}" onsubmit="return confirm('Remove OG image?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="p-1.5 text-gray-600 hover:text-rose-400 transition">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                            </button>
                        </form>
                    </div>
                    <form method="POST" action="{{ route('admin.settings.uploadBranding') }}" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="asset" value="og_image">
                        <label class="flex flex-col items-center justify-center w-full h-28 border-2 border-dashed rounded-xl cursor-pointer transition"
                               :class="dragging ? 'border-brand-500 bg-brand-500/10' : 'border-white/10 hover:border-white/20 bg-white/2 hover:bg-white/4'"
                               @dragover.prevent="dragging = true" @dragleave.prevent="dragging = false"
                               @drop.prevent="dragging = false; const f = $event.dataTransfer.files[0]; if(f){ $el.querySelector('input[type=file]').files = $event.dataTransfer.files; preview = URL.createObjectURL(f); }">
                            <svg class="w-7 h-7 text-gray-600 mb-2" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" /></svg>
                            <p class="text-xs text-gray-500">Drag & drop or <span class="text-brand-400">browse</span></p>
                            <p class="text-[10px] text-gray-600 mt-0.5">PNG, JPG, WebP · 1200×630 · Max 2 MB</p>
                            <input type="file" name="file" accept=".png,.jpg,.jpeg,.webp" class="hidden"
                                   @change="preview = URL.createObjectURL($event.target.files[0])">
                        </label>
                        <button type="submit" class="mt-2 w-full px-4 py-2.5 bg-brand-600 hover:bg-brand-500 text-white text-xs font-medium rounded-xl transition">
                            Upload OG Image
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.settings.update') }}" id="settings-form">
        @csrf

        @php
        if (! function_exists('renderSettingInput')):
        function renderSettingInput($setting): string {
            $name  = "settings[{$setting->key}]";
            $value = $setting->value ?? '';
            $class = "w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-brand-500/50 focus:ring-2 focus:ring-brand-500/20 transition text-sm";

            if ($setting->type === 'boolean') {
                $checked = $value ? 'checked' : '';
                return <<<HTML
<label class="relative inline-flex items-center cursor-pointer gap-3">
    <input type="hidden" name="{$name}" value="0">
    <input type="checkbox" name="{$name}" value="1" {$checked}
           class="sr-only peer">
    <div class="w-11 h-6 bg-gray-700 peer-focus:ring-2 peer-focus:ring-brand-500/30 rounded-full peer peer-checked:bg-brand-600 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-5 relative"></div>
    <span class="text-sm text-gray-300 peer-checked:text-white">Enabled</span>
</label>
HTML;
            }

            if ($setting->key === 'brand_primary_color') {
                return <<<HTML
<div class="flex items-center gap-3">
    <input type="color" name="{$name}" value="{$value}" class="w-12 h-12 rounded-xl border border-white/10 bg-transparent cursor-pointer">
    <input type="text" value="{$value}" class="flex-1 bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white text-sm font-mono focus:outline-none focus:border-brand-500/50 transition" readonly>
</div>
HTML;
            }

            if ($setting->is_sensitive) {
                return "<input type=\"password\" name=\"{$name}\" value=\"\" autocomplete=\"new-password\" placeholder=\"••••••••  (leave blank to keep current)\" class=\"{$class} font-mono\">";
            }

            if (in_array($setting->key, ['brand_description'])) {
                return "<textarea name=\"{$name}\" rows=\"3\" class=\"{$class} resize-none\">{$value}</textarea>";
            }

            if ($setting->key === 'stripe_mode') {
                $liveSelected  = $value === 'live'  ? 'selected' : '';
                $testSelected  = $value !== 'live'  ? 'selected' : '';
                return "<select name=\"{$name}\" class=\"{$class}\"><option value=\"test\" {$testSelected}>Test Mode (pk_test_...)</option><option value=\"live\" {$liveSelected}>Live Mode (pk_live_...)</option></select>";
            }

            if ($setting->key === 'mail_mailer') {
                $options = ['smtp','sendmail','mailgun','ses','postmark','log','null'];
                $opts = implode('', array_map(fn($o) => "<option value=\"{$o}\"" . ($value === $o ? ' selected' : '') . ">{$o}</option>", $options));
                return "<select name=\"{$name}\" class=\"{$class}\">{$opts}</select>";
            }

            if ($setting->key === 'mail_encryption') {
                $opts = ['tls' => 'TLS (STARTTLS)', 'ssl' => 'SSL', '' => 'None'];
                $optsHtml = implode('', array_map(fn($v, $l) => "<option value=\"{$v}\"" . ($value === $v ? ' selected' : '') . ">{$l}</option>", array_keys($opts), $opts));
                return "<select name=\"{$name}\" class=\"{$class}\">{$optsHtml}</select>";
            }

            $type = $setting->type === 'integer' ? 'number' : 'text';
            return "<input type=\"{$type}\" name=\"{$name}\" value=\"" . e($value) . "\" class=\"{$class}\">";
        }
        endif;
        @endphp

        @foreach($settings as $group => $items)
        <div x-show="tab === '{{ $group }}'" x-transition class="space-y-6">

            {{-- Section description banners --}}
            @if($group === 'ai')
            <div class="bg-brand-500/5 border border-brand-500/15 rounded-2xl px-5 py-4 flex items-start gap-3">
                <svg class="w-4 h-4 text-brand-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" /></svg>
                <p class="text-xs text-brand-300/80 leading-relaxed">Configure API keys for each AI provider. Users can choose their provider per widget. You only need to configure the providers you want to offer. Blank fields keep the current saved value.</p>
            </div>
            @elseif($group === 'payment')
            <div class="bg-amber-500/5 border border-amber-500/15 rounded-2xl px-5 py-4 flex items-start gap-3">
                <svg class="w-4 h-4 text-amber-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></svg>
                <p class="text-xs text-amber-300/80 leading-relaxed">Stripe keys are stored encrypted in the database. Use Test keys during development and switch to Live keys only on production. Set up your webhook in the <a href="https://dashboard.stripe.com/webhooks" target="_blank" class="underline">Stripe Dashboard</a> pointing to <code class="font-mono">{{ url('/api/stripe/webhook') }}</code>.</p>
            </div>
            @elseif($group === 'security')
            <div class="bg-rose-500/5 border border-rose-500/15 rounded-2xl px-5 py-4 flex items-start gap-3">
                <svg class="w-4 h-4 text-rose-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" /></svg>
                <p class="text-xs text-rose-300/80 leading-relaxed">Security settings affect all users. Changes take effect on the next request. Test your IP whitelist carefully before enabling — locking yourself out requires direct database access.</p>
            </div>
            @endif

            {{-- Settings card --}}
            <div class="bg-gray-900/50 border border-white/5 rounded-2xl divide-y divide-white/[0.04]">
                <div class="px-6 py-4">
                    <h3 class="text-sm font-semibold text-white capitalize">
                        @php
                        $groupLabels = ['app' => 'General', 'branding' => 'Branding & White-Label', 'ai' => 'AI Providers', 'mail' => 'Email / SMTP', 'payment' => 'Payment (Stripe)', 'security' => 'Security & Access', 'features' => 'Features & Limits'];
                        echo $groupLabels[$group] ?? ucfirst($group);
                        @endphp
                    </h3>
                </div>

                @foreach($items as $setting)
                <div class="px-6 py-5 grid grid-cols-1 md:grid-cols-5 gap-4 items-start">
                    {{-- Label + description --}}
                    <div class="md:col-span-2">
                        <div class="flex items-center gap-2 mb-1">
                            <label class="text-sm font-medium text-gray-200">{{ $setting->label ?? $setting->key }}</label>
                            @if($setting->is_sensitive)
                            <span class="px-1.5 py-0.5 text-[9px] font-semibold bg-amber-500/10 text-amber-400 rounded-md tracking-wide uppercase">sensitive</span>
                            @endif
                            @if($setting->is_public)
                            <span class="px-1.5 py-0.5 text-[9px] font-semibold bg-emerald-500/10 text-emerald-400 rounded-md tracking-wide uppercase">public</span>
                            @endif
                        </div>
                        @if($setting->description)
                        <p class="text-xs text-gray-500 leading-relaxed">{{ $setting->description }}</p>
                        @endif
                        <p class="text-[10px] text-gray-700 font-mono mt-1">{{ $setting->key }}</p>
                    </div>

                    {{-- Input --}}
                    <div class="md:col-span-3">
                        {!! renderSettingInput($setting) !!}

                        {{-- AI provider test buttons --}}
                        @if($group === 'ai' && str_ends_with($setting->key, '_api_key') && $setting->key !== 'openai_embedding_model')
                        @php $provider = str_replace('_api_key', '', $setting->key); @endphp
                        <div class="mt-2 flex items-center gap-3">
                            <button type="button" @click="testAi('{{ $provider }}')"
                                    :disabled="aiLoading['{{ $provider }}']"
                                    class="px-3 py-1.5 text-xs bg-brand-500/10 hover:bg-brand-500/20 text-brand-400 rounded-lg transition disabled:opacity-50">
                                <span x-show="!aiLoading['{{ $provider }}']">▶ Test Connection</span>
                                <span x-show="aiLoading['{{ $provider }}']">Testing…</span>
                            </button>
                            <span x-show="aiStatus['{{ $provider }}']" class="text-xs"
                                  :class="aiStatus['{{ $provider }}']?.ok ? 'text-emerald-400' : 'text-rose-400'"
                                  x-text="aiStatus['{{ $provider }}']?.message"></span>
                        </div>
                        @endif
                    </div>
                </div>
                @endforeach

                {{-- Mail: Test email form --}}
                @if($group === 'mail')
                <div class="px-6 py-5 grid grid-cols-1 md:grid-cols-5 gap-4 items-start bg-white/[0.015]">
                    <div class="md:col-span-2">
                        <p class="text-sm font-medium text-gray-200 mb-1">Send Test Email</p>
                        <p class="text-xs text-gray-500">Sends a test message to verify SMTP is working. Save settings first.</p>
                    </div>
                    <div class="md:col-span-3">
                        <div class="flex gap-2">
                            <input type="email" x-model="testEmailAddr" placeholder="your@email.com"
                                   class="flex-1 bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white text-sm focus:outline-none focus:border-brand-500/50 transition">
                            <button type="button" @click="sendTestEmail()" :disabled="testEmailLoading"
                                    class="px-4 py-3 bg-brand-600 hover:bg-brand-500 text-white text-xs font-medium rounded-xl transition disabled:opacity-50 shrink-0">
                                <span x-show="!testEmailLoading">Send Test</span>
                                <span x-show="testEmailLoading">Sending…</span>
                            </button>
                        </div>
                        <p x-show="testEmailMsg" class="mt-2 text-xs" :class="testEmailOk ? 'text-emerald-400' : 'text-rose-400'" x-text="testEmailMsg"></p>
                    </div>
                </div>
                @endif

                {{-- Payment: Stripe mode indicator --}}
                @if($group === 'payment')
                <div class="px-6 py-4 bg-white/[0.015]">
                    @php
                    $stripeKey = $settings->get('payment')?->firstWhere('key', 'stripe_key')?->value ?? '';
                    $isLive = str_starts_with($stripeKey, 'pk_live');
                    @endphp
                    <div class="flex items-center gap-3">
                        <div class="w-2 h-2 rounded-full {{ $isLive ? 'bg-emerald-400' : 'bg-amber-400' }}"></div>
                        <span class="text-xs font-medium {{ $isLive ? 'text-emerald-400' : 'text-amber-400' }}">
                            {{ $isLive ? 'Live Mode Active' : 'Test Mode Active' }}
                        </span>
                        <span class="text-xs text-gray-600">· Detected from publishable key prefix</span>
                    </div>
                </div>
                @endif
            </div>

        </div>
        @endforeach

        {{-- Save button --}}
        <div class="sticky bottom-4 flex justify-end">
            <div class="bg-gray-900/80 backdrop-blur-sm border border-white/10 rounded-2xl px-4 py-3 flex items-center gap-4 shadow-2xl">
                <p class="text-xs text-gray-500">Changes apply immediately after saving.</p>
                <button type="submit"
                        class="px-6 py-2.5 bg-linear-to-r from-brand-600 to-accent-600 hover:from-brand-500 hover:to-accent-500 text-white text-sm font-medium rounded-xl transition shadow-lg shadow-brand-500/20">
                    Save All Settings
                </button>
            </div>
        </div>
    </form>

</div>

@push('scripts')
<script>
// Sync color picker hex display
document.querySelectorAll('input[type=color]').forEach(function(picker) {
    picker.addEventListener('input', function() {
        const text = this.nextElementSibling;
        if (text) text.value = this.value;
    });
});
</script>
@endpush
@endsection
