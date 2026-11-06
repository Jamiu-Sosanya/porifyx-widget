@extends('layouts.app')
@section('title', 'Edit Widget: ' . $widget->name)
@section('page-title', 'Edit Widget')
@section('content')
<div x-data="{
    tab: location.hash.slice(1) || 'appearance',
    init() { window.addEventListener('hashchange', () => { this.tab = location.hash.slice(1) || 'appearance'; window.scrollTo({ top: 0 }); }); },
    pColor: @js($widget->primary_color ?? '#2f5ee8'),
    pBg: @js($widget->bg_color ?? '#0f0f13'),
    pText: @js($widget->text_color ?? '#ffffff'),
    pName: @js($widget->ai_name ?? 'AI Assistant'),
    pWelcome: @js($widget->welcome_message ?? 'Hi! How can I help you today?'),
    pPlaceholder: @js($widget->placeholder_text ?? 'Type a message...'),
    pPosition: @js($widget->position ?? 'bottom-right'),
    pShape: @js($widget->launcher_shape ?? 'circle'),
    pTheme: @js($widget->theme ?? 'dark'),
    pFont: @js($widget->font_family ?? 'Plus Jakarta Sans, system-ui, sans-serif'),
    pChatOpen: true,
    appName: @js(config('branding.name', config('app.name'))),
}" class="w-full">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <a href="{{ route('widgets.index') }}" class="p-2 rounded-lg text-gray-400 hover:text-white hover:bg-white/5 transition">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg>
            </a>
            <div>
                <h2 class="text-lg font-semibold text-white">{{ $widget->name }}</h2>
                <p class="text-xs text-gray-500">UUID: {{ $widget->uuid }} · Provider: <span class="text-brand-400">{{ $widget->getProviderLabel() }}</span></p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <form method="POST" action="{{ route('widgets.toggle', $widget) }}">@csrf
                <button type="submit" class="px-3 py-2 text-xs font-medium rounded-xl transition {{ $widget->is_active ? 'bg-emerald-500/10 text-emerald-400 hover:bg-emerald-500/20' : 'bg-gray-500/10 text-gray-400 hover:bg-gray-500/20' }}">
                    {{ $widget->is_active ? '● Active' : '○ Inactive' }}
                </button>
            </form>
            <a href="{{ route('widgets.embed', $widget) }}" class="px-4 py-2 bg-brand-600 hover:bg-brand-500 text-white text-xs font-medium rounded-xl transition">Get Embed Code</a>
        </div>
    </div>


    <form id="widget-edit-form" method="POST" action="{{ route('widgets.update', $widget) }}" enctype="multipart/form-data">
        @csrf @method('PUT')

        {{-- ── Appearance ─────────────────────────────────────────────────────── --}}
        @php
            $ls = \App\Support\LauncherIcons::style($widget);
            $iconSvgs = collect(\App\Support\LauncherIcons::PRESETS)->map(fn ($p, $k) => \App\Support\LauncherIcons::svg($k, 24, '__C__'))->all();
            $card = 'bg-gray-900/50 border border-white/5 rounded-2xl p-5 sm:p-6';
            $lbl = 'block text-xs font-semibold text-gray-400 mb-1.5';
            $in = 'w-full bg-white/5 border border-white/10 rounded-xl px-3.5 py-2.5 text-sm text-white focus:outline-none focus:border-brand-500/60 focus:ring-2 focus:ring-brand-500/15 transition';
            $seg = 'pfx-seg flex-1 text-center px-2 py-2 rounded-lg text-xs font-semibold cursor-pointer transition';
        @endphp
        <div x-show="tab === 'appearance'" x-cloak
             x-data="{
                 L: @js($ls),
                 shape: @js(old('launcher_shape', $widget->launcher_shape ?: 'circle')),
                 icon: @js(old('launcher_icon', $widget->launcher_icon ?: 'default')),
                 svgs: @js($iconSvgs),
                 upload: @js($widget->launcher_icon_path ? asset('storage/' . $widget->launcher_icon_path) : null),
                 radius() { const s = +this.L.size; return this.shape === 'square' ? Math.round(s * .18) : this.shape === 'rounded' ? Math.round(s * .3) : this.shape === 'custom' ? +this.L.radius : 999; },
                 bg() { return this.L.bg_type === 'gradient' ? `linear-gradient(${this.L.angle}deg, ${this.L.bg}, ${this.L.bg2})` : this.L.bg; },
                 shadow() { return { none: 'none', soft: '0 6px 18px -6px rgba(0,0,0,.35)', medium: '0 10px 28px -6px rgba(0,0,0,.45)', strong: '0 18px 44px -8px rgba(0,0,0,.6)' }[this.L.shadow]; },
                 iconHtml() { return this.icon === 'custom' && this.upload ? `<img src='${this.upload}' style='width:100%;height:100%;object-fit:cover'>` : (this.svgs[this.icon] || this.svgs.default).replaceAll('__C__', this.L.icon_color); },
                 launcherStyle() {
                     const s = +this.L.size, label = this.L.label.trim();
                     return `${label ? `height:${s}px;padding:0 ${Math.round(s * .32)}px 0 ${Math.round(s * .22)}px;gap:10px;` : `width:${s}px;height:${s}px;`}border-radius:${this.radius()}px;background:${this.bg()};box-shadow:${this.shadow()};border:${+this.L.border_width ? `${this.L.border_width}px solid ${this.L.border_color}` : 'none'};color:${this.L.bg}`;
                 },
                 chatPos() {
                     const sc = .6, x = Math.round(this.L.offset_x * sc) + 'px', gap = Math.round(this.L.offset_y * sc + +this.L.size + 10) + 'px';
                     return (pPosition.includes('left') ? `left:${x};` : `right:${x};`) + (pPosition.includes('top') ? `top:${gap};` : `bottom:${gap};`);
                 },
                 corner() {
                     const sc = .6, x = Math.round(this.L.offset_x * sc) + 'px', y = Math.round(this.L.offset_y * sc) + 'px';
                     return (pPosition.includes('left') ? `left:${x};` : `right:${x};`) + (pPosition.includes('top') ? `top:${y};` : `bottom:${y};`);
                 },
             }"
             class="grid grid-cols-1 xl:grid-cols-[minmax(0,1fr)_360px] gap-6 items-start">

            <div class="space-y-6 min-w-0">
                {{-- Identity --}}
                <section class="{{ $card }}">
                    <h3 class="text-base font-semibold text-white">Identity</h3>
                    <p class="text-xs text-gray-500 mt-0.5">How the assistant introduces itself.</p>
                    <div class="mt-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div><label class="{{ $lbl }}">Widget name</label><input name="name" value="{{ old('name', $widget->name) }}" required class="{{ $in }}"></div>
                        <div><label class="{{ $lbl }}">Assistant name</label><input name="ai_name" value="{{ old('ai_name', $widget->ai_name) }}" @input="pName = $event.target.value" class="{{ $in }}"></div>
                        <div>
                            <label class="{{ $lbl }}">Tone</label>
                            <select name="tone" class="{{ $in }}">
                                @foreach(['friendly' => 'Friendly', 'professional' => 'Professional', 'sales' => 'Sales', 'support' => 'Support'] as $val => $t)
                                <option value="{{ $val }}" @selected($widget->tone === $val)>{{ $t }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="{{ $lbl }}">Font</label>
                            <select name="font_family" @change="pFont = $event.target.value" class="{{ $in }}">
                                @foreach(["'Plus Jakarta Sans', system-ui, sans-serif" => 'Plus Jakarta Sans (default)', "'Poppins', sans-serif" => 'Poppins', "'Roboto', sans-serif" => 'Roboto', "'Open Sans', sans-serif" => 'Open Sans', "'DM Sans', sans-serif" => 'DM Sans', "system-ui, sans-serif" => 'System default', "'Georgia', serif" => 'Georgia (serif)', "'Courier New', monospace" => 'Monospace'] as $val => $t)
                                <option value="{{ $val }}" @selected($widget->font_family === $val)>{{ $t }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="sm:col-span-2 flex items-center gap-4 p-3 rounded-xl bg-white/[0.03] border border-white/5">
                            @if($widget->ai_avatar)
                                <img src="{{ asset('storage/' . $widget->ai_avatar) }}" class="w-12 h-12 rounded-xl object-cover" alt="">
                            @else
                                <div class="w-12 h-12 rounded-xl bg-white/5 grid place-items-center text-xl">🤖</div>
                            @endif
                            <div class="min-w-0">
                                <label class="{{ $lbl }} !mb-1">Assistant avatar</label>
                                <input type="file" name="ai_avatar" accept="image/png,image/jpeg" class="w-full max-w-full text-xs text-gray-400 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-white/10 file:text-white file:text-xs">
                                <p class="text-[11px] text-gray-500 mt-1">PNG or JPG, up to 2 MB. Shown in the chat header.</p>
                            </div>
                        </div>
                    </div>
                </section>

                {{-- Colours & theme --}}
                <section class="{{ $card }}">
                    <h3 class="text-base font-semibold text-white">Chat window</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Theme and colours of the conversation panel.</p>
                    <div class="mt-5 grid grid-cols-3 sm:grid-cols-6 gap-2.5">
                        @foreach(['dark' => ['Dark', '#0f0f13', '#2f5ee8'], 'light' => ['Light', '#ffffff', '#2f5ee8'], 'minimal' => ['Minimal', '#fafafa', '#111827'], 'bold' => ['Bold', '#1a0533', '#0891b2'], 'ocean' => ['Ocean', '#0c1e35', '#38bdf8'], 'nature' => ['Nature', '#0f1f0f', '#86efac']] as $val => [$t, $bgc, $acc])
                        <label class="cursor-pointer">
                            <input type="radio" name="theme" value="{{ $val }}" @checked(($widget->theme ?? 'dark') === $val) @change="pTheme = '{{ $val }}'; pBg = '{{ $bgc }}'; pText = '{{ in_array($val, ['light', 'minimal']) ? '#111827' : '#ffffff' }}'" class="peer sr-only">
                            <span class="block rounded-xl overflow-hidden ring-1 ring-slate-400/30 peer-checked:ring-2 peer-checked:ring-brand-500 transition">
                                <span class="block h-10" style="background: {{ $bgc }}"></span>
                                <span class="block h-2" style="background: {{ $acc }}"></span>
                            </span>
                            <span class="block mt-1 text-center text-[11px] text-gray-400">{{ $t }}</span>
                        </label>
                        @endforeach
                    </div>
                    <div class="mt-5 grid grid-cols-2 sm:grid-cols-4 gap-3">
                        @foreach(['primary_color' => 'Primary', 'secondary_color' => 'Secondary', 'text_color' => 'Text', 'bg_color' => 'Background'] as $field => $t)
                        <label class="flex items-center gap-2.5 p-2 rounded-xl bg-white/[0.03] border border-white/5 cursor-pointer" x-data="{ v: @js($widget->$field ?: '#000000') }">
                            <input type="color" name="{{ $field }}" x-model="v" class="w-9 h-9 rounded-lg bg-transparent cursor-pointer shrink-0"
                                   @if($field === 'primary_color') @input="pColor = v" @elseif($field === 'bg_color') @input="pBg = v" @elseif($field === 'text_color') @input="pText = v" @endif>
                            <span class="min-w-0"><span class="block text-xs font-semibold text-gray-300">{{ $t }}</span><span class="block text-[11px] font-mono text-gray-500 uppercase" x-text="v"></span></span>
                        </label>
                        @endforeach
                    </div>
                </section>

                {{-- Sizes on desktop and mobile --}}
                <section class="{{ $card }}" x-data="{ rec: @js(\App\Support\LauncherIcons::RECOMMENDED) }">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h3 class="text-base font-semibold text-white">Size on screens</h3>
                            <p class="text-xs text-gray-500 mt-0.5">Chat window and launcher size on computers and phones.</p>
                        </div>
                        <button type="button" @click="Object.assign(L, rec)" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-brand-500/10 text-brand-300 ring-1 ring-brand-500/30 hover:bg-brand-500/20">Use recommended sizes</button>
                    </div>
                    <div class="mt-5 grid grid-cols-1 md:grid-cols-2 gap-6">
                        {{-- Desktop --}}
                        <div class="space-y-4">
                            <p class="flex items-center gap-2 text-sm font-semibold text-white"><svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5h18v11H3zM8 20h8M12 16v4"/></svg>Desktop and tablet</p>
                            @foreach(['chat_w' => ['Chat width', 300, 520, 10], 'chat_h' => ['Chat height', 400, 760, 10]] as $k => [$t, $min, $max, $step])
                            <div>
                                <div class="flex justify-between text-[11px]"><span class="text-gray-400">{{ $t }}</span><span class="text-gray-300"><span x-text="L.{{ $k }} + 'px'"></span> <span x-show="+L.{{ $k }} === rec.{{ $k }}" class="ml-1 text-emerald-400">Recommended</span><span x-show="+L.{{ $k }} !== rec.{{ $k }}" class="ml-1 text-gray-500" x-text="'· recommended ' + rec.{{ $k }} + 'px'"></span></span></div>
                                <input type="range" name="launcher[{{ $k }}]" min="{{ $min }}" max="{{ $max }}" step="{{ $step }}" x-model="L.{{ $k }}" class="w-full accent-brand-500">
                            </div>
                            @endforeach
                            <p class="text-[11px] text-gray-500">Never taller or wider than the visitor's screen allows.</p>
                        </div>
                        {{-- Mobile --}}
                        <div class="space-y-4">
                            <p class="flex items-center gap-2 text-sm font-semibold text-white"><svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><rect x="7" y="2.5" width="10" height="19" rx="2"/><path d="M11 18.5h2"/></svg>Phones</p>
                            <div class="grid grid-cols-2 gap-1 p-1 rounded-xl bg-white/5 border border-white/10">
                                <label><input type="radio" name="launcher[m_mode]" value="full" x-model="L.m_mode" class="peer sr-only"><span class="{{ $seg }} block">Full screen <span class="opacity-75">(recommended)</span></span></label>
                                <label><input type="radio" name="launcher[m_mode]" value="custom" x-model="L.m_mode" class="peer sr-only"><span class="{{ $seg }} block">Custom</span></label>
                            </div>
                            <template x-if="L.m_mode === 'custom'">
                                <div class="space-y-4">
                                    @foreach(['m_w' => ['Chat width', 70, 100], 'm_h' => ['Chat height', 50, 100]] as $k => [$t, $min, $max])
                                    <div>
                                        <div class="flex justify-between text-[11px]"><span class="text-gray-400">{{ $t }} (% of screen)</span><span class="text-gray-300" x-text="L.{{ $k }} + '%'"></span></div>
                                        <input type="range" name="launcher[{{ $k }}]" min="{{ $min }}" max="{{ $max }}" x-model="L.{{ $k }}" class="w-full accent-brand-500">
                                    </div>
                                    @endforeach
                                </div>
                            </template>
                            <div>
                                <div class="flex justify-between text-[11px]"><span class="text-gray-400">Launcher size on phones</span><span class="text-gray-300"><span x-text="L.m_size + 'px'"></span> <span x-show="+L.m_size === rec.m_size" class="ml-1 text-emerald-400">Recommended</span></span></div>
                                <input type="range" name="launcher[m_size]" min="40" max="72" step="2" x-model="L.m_size" class="w-full accent-brand-500">
                            </div>
                            <p class="text-[11px] text-gray-500">Full screen gives the most room to type and read; 56 px is an easy tap target.</p>
                        </div>
                    </div>
                    {{-- Proportion sketch --}}
                    <div class="mt-5 flex items-end justify-center gap-8 p-4 rounded-xl bg-white/[0.03] border border-white/5" aria-hidden="true">
                        <div class="relative w-56 h-36 rounded-lg border-2 border-slate-500/40 bg-slate-500/10">
                            <div class="absolute right-2 bottom-2 rounded-md transition-all" :style="`width:${L.chat_w / 1440 * 224 * 1.6}px;height:${L.chat_h / 900 * 144 * .9}px;background:${pColor}55;border:1px solid ${pColor}`"></div>
                            <span class="absolute left-2 top-1.5 text-[10px] text-gray-500">1440 × 900</span>
                        </div>
                        <div class="relative w-16 h-32 rounded-xl border-2 border-slate-500/40 bg-slate-500/10 overflow-hidden">
                            <div class="absolute rounded-sm transition-all" :style="L.m_mode === 'full' ? `inset:0;background:${pColor}55` : `width:${L.m_w}%;height:${L.m_h}%;left:${(100 - L.m_w) / 2}%;bottom:2px;background:${pColor}55;border:1px solid ${pColor}`"></div>
                        </div>
                    </div>
                </section>

                {{-- Launcher --}}
                <section class="{{ $card }}">
                    <h3 class="text-base font-semibold text-white">Launcher button</h3>
                    <p class="text-xs text-gray-500 mt-0.5">The button visitors click to open the chat. Changes show in the preview.</p>

                    {{-- Icon --}}
                    <p class="{{ $lbl }} mt-5">Icon</p>
                    <div class="grid grid-cols-6 sm:grid-cols-11 gap-2">
                        @foreach(\App\Support\LauncherIcons::PRESETS as $key => [$t])
                        <label class="cursor-pointer" title="{{ $t }}">
                            <input type="radio" name="launcher_icon" value="{{ $key }}" x-model="icon" class="peer sr-only">
                            <span class="grid place-items-center aspect-square rounded-xl ring-1 ring-white/10 peer-checked:ring-2 peer-checked:ring-brand-500 transition" :style="`background:${bg()};color:${L.bg}`">
                                <span class="w-1/2 h-1/2 [&>svg]:w-full [&>svg]:h-full" x-html="svgs['{{ $key }}'].replaceAll('__C__', L.icon_color)"></span>
                            </span>
                        </label>
                        @endforeach
                        <label class="cursor-pointer" title="Upload your own">
                            <input type="radio" name="launcher_icon" value="custom" x-model="icon" class="peer sr-only">
                            <span class="grid place-items-center aspect-square rounded-xl ring-1 ring-dashed ring-white/20 peer-checked:ring-2 peer-checked:ring-brand-500 overflow-hidden bg-white/5">
                                <template x-if="upload"><img :src="upload" alt="" class="w-full h-full object-cover"></template>
                                <template x-if="!upload"><svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 16V4m0 0-4 4m4-4 4 4M4 16v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/></svg></template>
                            </span>
                        </label>
                    </div>
                    <div x-show="icon === 'custom'" x-cloak class="mt-3 p-3 rounded-xl bg-white/[0.03] border border-white/5">
                        <input type="file" name="launcher_icon_file" accept="image/png,image/jpeg"
                               @change="const f = $event.target.files[0]; if (!f) return;
                                        if (!['image/png','image/jpeg'].includes(f.type)) { alert('Please choose a JPG or PNG image.'); $event.target.value=''; return; }
                                        if (f.size > 512 * 1024) { alert('The image must be 512 KB or smaller.'); $event.target.value=''; return; }
                                        const img = new Image(); img.onload = () => { if (img.width !== img.height || img.width < 64 || img.width > 512) { alert('The image must be square, between 64×64 and 512×512 pixels. This one is ' + img.width + '×' + img.height + '.'); $event.target.value=''; } else { upload = img.src; } }; img.src = URL.createObjectURL(f);"
                               class="block w-full text-sm text-gray-400 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:bg-white/10 file:text-white">
                        <p class="mt-2 text-xs text-gray-500">{{ \App\Support\LauncherIcons::UPLOAD_HINT }}</p>
                        @error('launcher_icon_file')<p class="mt-1 text-xs text-rose-400">{{ $message }}</p>@enderror
                    </div>

                    <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-5">
                        {{-- Shape --}}
                        <div>
                            <p class="{{ $lbl }}">Shape</p>
                            <div class="flex gap-1 p-1 rounded-xl bg-white/5 border border-white/10">
                                @foreach(['circle' => 'Circle', 'rounded' => 'Rounded', 'square' => 'Square', 'custom' => 'Custom'] as $val => $t)
                                <label class="flex-1"><input type="radio" name="launcher_shape" value="{{ $val }}" x-model="shape" @change="pShape = shape" class="peer sr-only"><span class="{{ $seg }} block">{{ $t }}</span></label>
                                @endforeach
                            </div>
                            <div x-show="shape === 'custom'" x-cloak class="mt-3">
                                <div class="flex justify-between text-[11px] text-gray-500"><span>Corner radius</span><span x-text="L.radius + 'px'"></span></div>
                                <input type="range" name="launcher[radius]" min="0" max="48" x-model="L.radius" class="w-full accent-brand-500">
                            </div>
                        </div>
                        {{-- Size --}}
                        <div>
                            <div class="flex justify-between"><p class="{{ $lbl }}">Size</p><span class="text-[11px] text-gray-500" x-text="L.size + 'px'"></span></div>
                            <input type="range" name="launcher[size]" min="40" max="96" step="2" x-model="L.size" class="w-full accent-brand-500 mt-2">
                            <div class="flex justify-between text-[10px] text-gray-600"><span>Small</span><span>Large</span></div>
                        </div>
                        {{-- Background --}}
                        <div class="sm:col-span-2">
                            <p class="{{ $lbl }}">Background</p>
                            <div class="flex flex-wrap items-center gap-3">
                                <div class="flex gap-1 p-1 rounded-xl bg-white/5 border border-white/10 w-48">
                                    @foreach(['solid' => 'Solid', 'gradient' => 'Gradient'] as $val => $t)
                                    <label class="flex-1"><input type="radio" name="launcher[bg_type]" value="{{ $val }}" x-model="L.bg_type" class="peer sr-only"><span class="{{ $seg }} block">{{ $t }}</span></label>
                                    @endforeach
                                </div>
                                <label class="flex items-center gap-2 text-xs text-gray-400"><input type="color" name="launcher[bg]" x-model="L.bg" class="w-9 h-9 rounded-lg bg-transparent cursor-pointer"><span x-text="L.bg_type === 'gradient' ? 'From' : 'Colour'"></span></label>
                                <label x-show="L.bg_type === 'gradient'" class="flex items-center gap-2 text-xs text-gray-400"><input type="color" name="launcher[bg2]" x-model="L.bg2" class="w-9 h-9 rounded-lg bg-transparent cursor-pointer">To</label>
                                <div x-show="L.bg_type === 'gradient'" class="flex-1 min-w-[140px]">
                                    <div class="flex justify-between text-[11px] text-gray-500"><span>Angle</span><span x-text="L.angle + '°'"></span></div>
                                    <input type="range" name="launcher[angle]" min="0" max="360" step="5" x-model="L.angle" class="w-full accent-brand-500">
                                </div>
                            </div>
                        </div>
                        {{-- Icon colour --}}
                        <div>
                            <p class="{{ $lbl }}">Icon and label colour</p>
                            <label class="flex items-center gap-2.5"><input type="color" name="launcher[icon_color]" x-model="L.icon_color" class="w-9 h-9 rounded-lg bg-transparent cursor-pointer"><span class="text-[11px] font-mono text-gray-500 uppercase" x-text="L.icon_color"></span></label>
                        </div>
                        {{-- Border --}}
                        <div>
                            <div class="flex justify-between"><p class="{{ $lbl }}">Border</p><span class="text-[11px] text-gray-500" x-text="+L.border_width ? L.border_width + 'px' : 'None'"></span></div>
                            <div class="flex items-center gap-3">
                                <input type="range" name="launcher[border_width]" min="0" max="8" x-model="L.border_width" class="flex-1 accent-brand-500">
                                <input type="color" name="launcher[border_color]" x-model="L.border_color" class="w-9 h-9 rounded-lg bg-transparent cursor-pointer" :class="!+L.border_width && 'opacity-40'">
                            </div>
                        </div>
                        {{-- Shadow --}}
                        <div>
                            <p class="{{ $lbl }}">Shadow</p>
                            <div class="flex gap-1 p-1 rounded-xl bg-white/5 border border-white/10">
                                @foreach(['none' => 'None', 'soft' => 'Soft', 'medium' => 'Medium', 'strong' => 'Strong'] as $val => $t)
                                <label class="flex-1"><input type="radio" name="launcher[shadow]" value="{{ $val }}" x-model="L.shadow" class="peer sr-only"><span class="{{ $seg }} block">{{ $t }}</span></label>
                                @endforeach
                            </div>
                        </div>
                        {{-- Animation --}}
                        <div>
                            <p class="{{ $lbl }}">Attention effect</p>
                            <div class="grid grid-cols-3 gap-1 p-1 rounded-xl bg-white/5 border border-white/10">
                                @foreach(\App\Support\LauncherIcons::ANIMATIONS as $val => $t)
                                <label><input type="radio" name="launcher[animation]" value="{{ $val }}" x-model="L.animation" class="peer sr-only"><span class="{{ $seg }} block">{{ $t }}</span></label>
                                @endforeach
                            </div>
                        </div>
                        {{-- Label --}}
                        <div class="sm:col-span-2">
                            <label class="{{ $lbl }}">Text beside the icon <span class="font-normal text-gray-500">(optional, hidden on phones)</span></label>
                            <input name="launcher[label]" x-model="L.label" maxlength="40" placeholder="e.g. Chat with us" class="{{ $in }}">
                        </div>
                        {{-- Position --}}
                        <div class="sm:col-span-2 grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">
                            <div>
                                <p class="{{ $lbl }}">Position</p>
                                <div class="grid grid-cols-2 gap-1 p-1 rounded-xl bg-white/5 border border-white/10 w-28">
                                    @foreach(['top-left' => '↖', 'top-right' => '↗', 'bottom-left' => '↙', 'bottom-right' => '↘'] as $val => $t)
                                    <label title="{{ str_replace('-', ' ', ucfirst($val)) }}"><input type="radio" name="position" value="{{ $val }}" @checked(($widget->position ?? 'bottom-right') === $val) @change="pPosition = '{{ $val }}'" class="peer sr-only"><span class="{{ $seg }} block">{{ $t }}</span></label>
                                    @endforeach
                                </div>
                            </div>
                            <div>
                                <div class="flex justify-between text-[11px] text-gray-500"><span>From the side</span><span x-text="L.offset_x + 'px'"></span></div>
                                <input type="range" name="launcher[offset_x]" min="0" max="120" x-model="L.offset_x" class="w-full accent-brand-500">
                            </div>
                            <div>
                                <div class="flex justify-between text-[11px] text-gray-500"><span>From the edge</span><span x-text="L.offset_y + 'px'"></span></div>
                                <input type="range" name="launcher[offset_y]" min="0" max="120" x-model="L.offset_y" class="w-full accent-brand-500">
                            </div>
                        </div>
                    </div>
                </section>
            </div>

            {{-- Live preview --}}
            <aside class="xl:sticky xl:top-6 space-y-3">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Preview</p>
                <div class="relative h-[26rem] rounded-2xl overflow-hidden border border-white/10 bg-[linear-gradient(180deg,#f8fafc,#e2e8f0)]">
                    <div class="p-5 space-y-2.5" aria-hidden="true">
                        <div class="h-4 w-1/2 rounded bg-slate-300"></div>
                        <div class="h-2.5 w-5/6 rounded bg-slate-200"></div>
                        <div class="h-2.5 w-2/3 rounded bg-slate-200"></div>
                        <div class="grid grid-cols-2 gap-2 pt-3"><div class="h-16 rounded-lg bg-slate-200"></div><div class="h-16 rounded-lg bg-slate-200"></div></div>
                    </div>
                    {{-- Mini chat window: theme colours, name and font update as you type --}}
                    <div class="absolute w-52 rounded-xl overflow-hidden shadow-xl ring-1 ring-black/10 transition-all duration-200" :style="chatPos() + `background:${pBg};font-family:${pFont}`">
                        <div class="flex items-center gap-2 px-3 py-2.5" :style="`background:linear-gradient(135deg, ${pColor}, ${pColor}cc)`">
                            <span class="grid place-items-center w-6 h-6 rounded-full bg-white text-[11px] font-bold" :style="`color:${pColor}`" x-text="(pName || 'A').charAt(0).toUpperCase()"></span>
                            <span class="text-xs font-semibold text-white truncate" x-text="pName || 'Assistant'"></span>
                        </div>
                        <div class="p-2.5 space-y-1.5">
                            <p class="max-w-[85%] px-2.5 py-1.5 rounded-lg rounded-tl-sm text-[10px] leading-snug" :style="`background:${pText}14;color:${pText}`">Hi! How can I help today?</p>
                            <p class="ml-auto max-w-[75%] px-2.5 py-1.5 rounded-lg rounded-tr-sm text-[10px] text-white" :style="`background:${pColor}`">Do you have openings?</p>
                        </div>
                    </div>
                    <div class="absolute flex items-center justify-center transition-[width,height,border-radius,background,left,right,top,bottom] duration-200" :class="L.animation !== 'none' && 'pfx-anim-' + L.animation" :style="corner() + launcherStyle() + `;--pfx-ring:${L.bg}99`">
                        <span class="shrink-0 grid place-items-center [&>svg]:w-full [&>svg]:h-full overflow-hidden" :style="icon === 'custom' && upload ? `width:100%;height:100%;border-radius:inherit` : `width:${Math.round(L.size * .5)}px;height:${Math.round(L.size * .5)}px`" x-html="iconHtml()"></span>
                        <span x-show="L.label.trim()" class="text-sm font-semibold whitespace-nowrap" :style="`color:${L.icon_color};font-family:${pFont}`" x-text="L.label"></span>
                    </div>
                </div>
                <p class="text-[11px] text-gray-500">Updates live as you change settings. Gaps are shown at 60% scale. Save to publish them to your site.</p>
            </aside>
        </div>

        {{-- ── Behavior Tab ─────────────────────────────────────────────────── --}}
        <div x-show="tab === 'behavior'" x-transition class="bg-gray-900/50 border border-white/5 rounded-2xl p-6 space-y-6">
            <h3 class="text-base font-semibold text-white">Behavior</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1.5">Welcome Message</label>
                    <textarea name="welcome_message" rows="3" @input="pWelcome = $event.target.value" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-brand-500/50 transition resize-none">{{ old('welcome_message', $widget->welcome_message) }}</textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1.5">Input Placeholder</label>
                    <input name="placeholder_text" value="{{ old('placeholder_text', $widget->placeholder_text) }}" @input="pPlaceholder = $event.target.value" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-brand-500/50 transition">
                    <label class="block text-sm font-medium text-gray-300 mb-1.5 mt-4">Handoff Button Label</label>
                    <input name="handoff_button_label" value="{{ old('handoff_button_label', $widget->handoff_button_label ?? 'Talk to a human') }}" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-brand-500/50 transition">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1.5">Handoff Acknowledgment Message</label>
                <input name="handoff_acknowledgment" value="{{ old('handoff_acknowledgment', $widget->handoff_acknowledgment ?? 'Thanks! A team member will get back to you as soon as possible.') }}" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-brand-500/50 transition">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1.5">Suggested Quick Replies <span class="text-gray-500">(one per line, max 12)</span></label>
                <textarea name="suggested_replies_input" rows="4" placeholder="Pricing&#10;Book a demo&#10;Talk to sales&#10;View case studies"
                          class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-gray-500 focus:outline-none focus:border-brand-500/50 transition resize-none text-sm">{{ old('suggested_replies_input', collect($widget->suggested_replies ?? [])->implode("\n")) }}</textarea>
                <p class="mt-1 text-xs text-gray-500">Shown as tappable chips to guide visitors before they type.</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1.5">Conversation Starters <span class="text-gray-500">(one per line, max 4)</span></label>
                <textarea name="conversation_starters_input" rows="3" placeholder="How can you help my business?&#10;What does this cost?&#10;Can I see a demo?"
                          class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-gray-500 focus:outline-none focus:border-brand-500/50 transition resize-none text-sm">{{ old('conversation_starters_input', collect($widget->conversation_starters ?? [])->implode("\n")) }}</textarea>
                <p class="mt-1 text-xs text-gray-500">Large button prompts shown when the widget is first opened — helps visitors know where to start.</p>
            </div>

            {{-- Toggle Checkboxes --}}
            <div>
                <p class="text-sm font-medium text-gray-300 mb-3">Feature Toggles</p>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                    @foreach([
                        'auto_open'             => ['Auto Open on Load', '🚀'],
                        'show_on_mobile'        => ['Show on Mobile', '📱'],
                        'collect_email'         => ['Collect Email', '📧'],
                        'collect_phone'         => ['Collect Phone', '📞'],
                        'emoji_enabled'         => ['Enable Emojis', '😊'],
                        'typing_animation'      => ['Typing Animation', '⌨️'],
                        'message_streaming'     => ['Stream Responses', '⚡'],
                        'show_handoff_button'   => ['Human Handoff Button', '🙋'],
                        'csat_prompt_enabled'   => ['CSAT Rating Prompt', '⭐'],
                        'sound_enabled'         => ['Sound Notifications', '🔔'],
                        'show_branding'         => ['Show "Powered by" Branding', '🏷️'],
                        'offline_form_enabled'  => ['Offline Message Form', '📩'],
                    ] as $field => [$lbl, $icon])
                    <label class="flex items-center gap-3 p-3 bg-white/[0.02] hover:bg-white/[0.04] rounded-xl cursor-pointer transition">
                        <input type="checkbox" name="{{ $field }}" value="1" {{ old($field, $widget->$field) ? 'checked' : '' }}
                               class="w-4 h-4 rounded bg-white/5 border-white/20 text-brand-600 focus:ring-brand-500/30">
                        <span class="text-sm text-gray-300">{{ $icon }} {{ $lbl }}</span>
                    </label>
                    @endforeach
                </div>
            </div>

            <div x-data="{ autoOpen: {{ $widget->auto_open ? 'true' : 'false' }} }">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1.5">Auto-Open Delay (seconds)</label>
                        <input name="auto_open_delay" type="number" min="0" max="300" value="{{ old('auto_open_delay', $widget->auto_open_delay ?? 5) }}"
                               class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-brand-500/50 transition">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1.5">Offline Form Message</label>
                        <input name="offline_form_message" value="{{ old('offline_form_message', $widget->offline_form_message ?? "Leave us a message and we'll get back to you.") }}"
                               class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-brand-500/50 transition text-sm">
                    </div>
                </div>
            </div>
        </div>

        {{-- ── AI Provider Tab ──────────────────────────────────────────────── --}}
        <div x-show="tab === 'ai'" x-transition x-data="{ provider: '{{ old('ai_provider', $widget->ai_provider ?? 'openai') }}' }" class="bg-gray-900/50 border border-white/5 rounded-2xl p-6 space-y-6">
            <h3 class="text-base font-semibold text-white">AI Provider & Model</h3>

            {{-- Provider Cards --}}
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-3">AI Provider</label>
                @php
                    $pfxProviders = \App\Services\MultiProviderAiService::providers();
                    $pfxModels    = \App\Services\MultiProviderAiService::availableModels();
                    $pfxCurrent   = $widget->ai_provider ?? 'openai';
                @endphp
                <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
                    @foreach($pfxProviders as $providerVal => [$providerName, $models, $color])
                    <label class="cursor-pointer" @click="provider = '{{ $providerVal }}'">
                        <input type="radio" name="ai_provider" value="{{ $providerVal }}" {{ $pfxCurrent === $providerVal ? 'checked' : '' }} class="sr-only" x-model="provider">
                        <div :class="provider === '{{ $providerVal }}' ? 'border-brand-500 bg-brand-500/10' : 'border-white/10 bg-white/[0.02] hover:border-white/20'"
                             class="rounded-xl border-2 p-4 transition-all">
                            <span class="block w-2.5 h-2.5 rounded-full mb-2" style="background: {{ $color }}"></span>
                            <p class="text-sm font-semibold text-white">{{ $providerName }}</p>
                            <p class="text-[10px] text-gray-500 mt-1">{{ $models }}</p>
                        </div>
                    </label>
                    @endforeach
                </div>
            </div>

            {{-- Model selection per provider (only the visible one is submitted) --}}
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1.5">Model</label>
                @foreach($pfxModels as $providerVal => $options)
                <div x-show="provider === '{{ $providerVal }}'">
                    <select name="ai_model_{{ $providerVal }}" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-brand-500/50 transition">
                        @if($pfxCurrent === $providerVal && $widget->ai_model && ! isset($options[$widget->ai_model]))
                        <option value="{{ $widget->ai_model }}" selected>{{ $widget->ai_model }} (custom)</option>
                        @endif
                        @foreach($options as $val => $lbl)
                        <option value="{{ $val }}" {{ $pfxCurrent === $providerVal && $widget->ai_model === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>
                @endforeach
                <p class="text-[11px] text-gray-500 mt-2">If this provider fails, PoriFyx automatically retries on the backup provider set by your administrator.</p>
            </div>

            {{-- Provider info banners --}}
            <div x-show="provider === 'openrouter'" class="p-4 bg-brand-500/5 border border-brand-500/20 rounded-xl text-sm text-brand-300">
                🔀 <strong>OpenRouter</strong> gives one API key access to hundreds of models from every major lab. Requires an OPENROUTER_API_KEY.
            </div>
            <div x-show="provider === 'groq'" class="p-4 bg-accent-500/5 border border-accent-500/20 rounded-xl text-sm text-accent-300">
                ⚡ <strong>Groq</strong> delivers the fastest AI inference — typical response times under 0.5s. Great for high-volume, latency-sensitive deployments. Requires a GROQ_API_KEY in your .env file.
            </div>
            <div x-show="provider === 'anthropic'" class="p-4 bg-amber-500/5 border border-amber-500/20 rounded-xl text-sm text-amber-300">
                🧠 <strong>Anthropic Claude</strong> models excel at nuanced reasoning, long-context understanding, and following complex instructions. Requires an ANTHROPIC_API_KEY in your .env file.
            </div>
            <div x-show="provider === 'gemini'" class="p-4 bg-sky-500/5 border border-sky-500/20 rounded-xl text-sm text-sky-300">
                🔵 <strong>Google Gemini</strong> offers multimodal capabilities and competitive pricing. Requires a GEMINI_API_KEY in your .env file.
            </div>

            {{-- AI Tuning --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-4 border-t border-white/5">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1.5">
                        Temperature <span x-text="''" class="text-brand-400 font-mono text-xs">{{ $widget->temperature }}</span>
                    </label>
                    <input name="temperature" type="range" min="0" max="2" step="0.1" value="{{ $widget->temperature }}" class="w-full accent-brand-500"
                           oninput="this.parentElement.querySelector('.temp-val').textContent = this.value">
                    <div class="flex justify-between text-[10px] text-gray-500 mt-1"><span>0 — Precise</span><span class="temp-val text-brand-400">{{ $widget->temperature }}</span><span>2 — Creative</span></div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1.5">Response Length</label>
                    <select name="response_length" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-brand-500/50 transition">
                        @foreach(['short' => 'Short (≤256 tokens)', 'medium' => 'Medium (≤512 tokens)', 'long' => 'Long (≤1024 tokens)'] as $val => $lbl)
                        <option value="{{ $val }}" {{ $widget->response_length === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1.5">System Prompt</label>
                <textarea name="system_prompt" rows="6" placeholder="Describe your business, products, services, and how the AI should behave...&#10;&#10;Example: You are a support assistant for Acme Corp. We sell CRM software. Focus on helping users with onboarding, pricing, and feature questions."
                          class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-gray-500 focus:outline-none focus:border-brand-500/50 transition resize-none font-mono text-sm">{{ old('system_prompt', $widget->system_prompt) }}</textarea>
                <p class="mt-1 text-xs text-gray-500">The knowledge base context is automatically appended. This prompt adds extra personality and instructions.</p>
            </div>

            <label class="flex items-center gap-3 p-3 bg-amber-500/5 border border-amber-500/10 rounded-xl cursor-pointer">
                <input type="checkbox" name="strict_mode" value="1" {{ $widget->strict_mode ? 'checked' : '' }} class="w-4 h-4 rounded text-amber-600 focus:ring-amber-500/30">
                <div>
                    <span class="text-sm font-medium text-gray-300">🔒 Strict Mode</span>
                    <p class="text-xs text-gray-500 mt-0.5">Only answer questions from the knowledge base. Respond with the fallback message for anything else.</p>
                </div>
            </label>

            <label class="flex items-center gap-3 p-3 bg-sky-500/5 border border-sky-500/10 rounded-xl cursor-pointer">
                <input type="checkbox" name="auto_language_detection" value="1" {{ $widget->auto_language_detection ? 'checked' : '' }} class="w-4 h-4 rounded text-sky-500 focus:ring-sky-500/30">
                <div>
                    <span class="text-sm font-medium text-gray-300">🌐 Auto-Language Detection</span>
                    <p class="text-xs text-gray-500 mt-0.5">Automatically detect the visitor's language and respond in the same language.</p>
                </div>
            </label>
        </div>

        {{-- ── Smart Features Tab ───────────────────────────────────────────── --}}
        <div x-show="tab === 'smart'" x-transition class="bg-gray-900/50 border border-white/5 rounded-2xl p-6 space-y-6">
            <h3 class="text-base font-semibold text-white">Smart AI Features</h3>

            {{-- Smart Suggestions --}}
            <div class="p-4 bg-white/[0.02] border border-white/5 rounded-2xl space-y-4">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-semibold text-white">✨ Smart Follow-up Suggestions</p>
                        <p class="text-xs text-gray-500 mt-1">After each AI response, generate 2-4 contextual reply chips the visitor can tap. Increases engagement by 30-50%.</p>
                    </div>
                    <label class="flex-shrink-0 relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="smart_suggestions_enabled" value="1" {{ $widget->smart_suggestions_enabled ? 'checked' : '' }} class="sr-only peer">
                        <div class="w-11 h-6 bg-white/10 rounded-full peer peer-checked:bg-brand-600 transition-colors"></div>
                        <div class="absolute left-0.5 top-0.5 w-5 h-5 bg-white rounded-full transition-transform peer-checked:translate-x-5 shadow"></div>
                    </label>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-1.5">Number of suggestions (2-5)</label>
                    <input name="smart_suggestions_count" type="number" min="2" max="5" value="{{ $widget->smart_suggestions_count ?? 3 }}"
                           class="w-24 bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-brand-500/50 transition">
                </div>
            </div>

            {{-- CSAT --}}
            <div class="p-4 bg-white/[0.02] border border-white/5 rounded-2xl">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-semibold text-white">⭐ CSAT Rating Prompt</p>
                        <p class="text-xs text-gray-500 mt-1">Ask visitors to rate their chat experience after 2+ AI exchanges. Data feeds your analytics.</p>
                    </div>
                    <label class="flex-shrink-0 relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="csat_prompt_enabled" value="1" {{ $widget->csat_prompt_enabled ? 'checked' : '' }} class="sr-only peer">
                        <div class="w-11 h-6 bg-white/10 rounded-full peer peer-checked:bg-brand-600 transition-colors"></div>
                        <div class="absolute left-0.5 top-0.5 w-5 h-5 bg-white rounded-full transition-transform peer-checked:translate-x-5 shadow"></div>
                    </label>
                </div>
            </div>

            {{-- Lead qualification --}}
            <div class="p-4 bg-white/[0.02] border border-white/5 rounded-2xl">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-semibold text-white">🎯 AI Lead Qualification</p>
                        <p class="text-xs text-gray-500 mt-1">AI naturally gathers budget, timeline, pain points, and company info, then scores leads 0-100.</p>
                    </div>
                    <label class="flex-shrink-0 relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="lead_qualification_enabled" value="1" {{ $widget->lead_qualification_enabled ? 'checked' : '' }} class="sr-only peer">
                        <div class="w-11 h-6 bg-white/10 rounded-full peer peer-checked:bg-brand-600 transition-colors"></div>
                        <div class="absolute left-0.5 top-0.5 w-5 h-5 bg-white rounded-full transition-transform peer-checked:translate-x-5 shadow"></div>
                    </label>
                </div>
            </div>

            {{-- Collect info --}}
            <div class="p-4 bg-white/[0.02] border border-white/5 rounded-2xl">
                <p class="text-sm font-semibold text-white mb-3">📋 Visitor Info Collection</p>
                <div class="grid grid-cols-2 gap-3">
                    @foreach(['collect_email' => '📧 Email Address', 'collect_phone' => '📞 Phone Number', 'offline_form_enabled' => '📩 Offline Message Form', 'sound_enabled' => '🔔 Sound Notifications'] as $field => $lbl)
                    <label class="flex items-center gap-2 cursor-pointer p-2 rounded-lg hover:bg-white/5 transition">
                        <input type="checkbox" name="{{ $field }}" value="1" {{ $widget->$field ? 'checked' : '' }} class="w-4 h-4 rounded text-brand-600 focus:ring-brand-500/30">
                        <span class="text-sm text-gray-300">{{ $lbl }}</span>
                    </label>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- ── Proactive Messaging Tab ───────────────────────────────────────── --}}
        <div x-show="tab === 'proactive'" x-transition class="bg-gray-900/50 border border-white/5 rounded-2xl p-6 space-y-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="text-base font-semibold text-white">📣 Proactive Messaging</h3>
                    <p class="text-sm text-gray-500 mt-1">Automatically reach out to visitors based on their behavior — before they ask.</p>
                </div>
                <label class="flex-shrink-0 relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="proactive_enabled" value="1" {{ $widget->proactive_enabled ? 'checked' : '' }} class="sr-only peer" id="proactiveToggle">
                    <div class="w-11 h-6 bg-white/10 rounded-full peer peer-checked:bg-brand-600 transition-colors"></div>
                    <div class="absolute left-0.5 top-0.5 w-5 h-5 bg-white rounded-full transition-transform peer-checked:translate-x-5 shadow"></div>
                </label>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1.5">Proactive Message</label>
                    <textarea name="proactive_message" rows="3" placeholder="👋 Hi! Noticed you've been browsing for a while. Need any help finding what you're looking for?"
                              class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-gray-500 focus:outline-none focus:border-brand-500/50 transition resize-none text-sm">{{ old('proactive_message', $widget->proactive_message) }}</textarea>
                </div>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1.5">Trigger: Time on Page (seconds)</label>
                        <input name="proactive_trigger_seconds" type="number" min="5" max="300" value="{{ old('proactive_trigger_seconds', $widget->proactive_trigger_seconds ?? 30) }}"
                               class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-brand-500/50 transition">
                        <p class="mt-1 text-xs text-gray-500">Show message after visitor spends this many seconds on the page.</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1.5">Trigger: Page Count</label>
                        <input name="proactive_trigger_pages" type="number" min="1" max="20" value="{{ old('proactive_trigger_pages', $widget->proactive_trigger_pages ?? 2) }}"
                               class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-brand-500/50 transition">
                        <p class="mt-1 text-xs text-gray-500">Show after visitor views this many pages in the session.</p>
                    </div>
                </div>
            </div>

            <div class="p-4 bg-brand-500/5 border border-brand-500/10 rounded-xl text-sm text-brand-300">
                💡 <strong>Advanced triggers</strong> (exit intent, scroll depth, per-URL targeting) can be configured in the Proactive Messages manager under each widget's settings.
            </div>

            {{-- Stats if any proactive messages exist --}}
            @if($widget->proactiveMessages()->exists())
            <div class="pt-4 border-t border-white/5">
                <p class="text-sm font-medium text-gray-300 mb-3">Active Proactive Messages</p>
                <div class="space-y-2">
                    @foreach($widget->proactiveMessages()->where('is_active', true)->get() as $pm)
                    <div class="flex items-center justify-between p-3 bg-white/[0.02] rounded-xl text-sm">
                        <div class="min-w-0 flex-1">
                            <p class="text-white font-medium truncate">{{ $pm->name }}</p>
                            <p class="text-xs text-gray-500">{{ $pm->trigger_type }} · Shown: {{ $pm->shown_count }} · Clicked: {{ $pm->clicked_count }} ({{ $pm->getClickThroughRate() }}% CTR)</p>
                        </div>
                        <span class="ml-3 text-xs text-emerald-400 shrink-0">Active</span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        {{-- ── Notifications Tab ────────────────────────────────────────────── --}}
        <div x-show="tab === 'notifications'" x-transition class="bg-gray-900/50 border border-white/5 rounded-2xl p-6 space-y-6">
            <h3 class="text-base font-semibold text-white">🔔 Notification Integrations</h3>
            <p class="text-sm text-gray-500">Get instant alerts on Slack, Discord, or Email when leads qualify or visitors request handoff.</p>

            {{-- Notification triggers --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                @foreach(['notify_on_lead' => ['🔥 Notify on Lead Qualified', 'Send alert when AI qualifies a new lead'], 'notify_on_handoff' => ['🙋 Notify on Human Handoff', 'Send alert when visitor requests a human agent']] as $field => [$lbl, $desc])
                <label class="flex items-start gap-3 p-3 bg-white/[0.02] hover:bg-white/[0.04] rounded-xl cursor-pointer transition">
                    <input type="checkbox" name="{{ $field }}" value="1" {{ $widget->$field ? 'checked' : '' }} class="mt-0.5 w-4 h-4 rounded text-brand-600 focus:ring-brand-500/30">
                    <div>
                        <span class="text-sm font-medium text-gray-300">{{ $lbl }}</span>
                        <p class="text-xs text-gray-500">{{ $desc }}</p>
                    </div>
                </label>
                @endforeach
            </div>

            {{-- Slack --}}
            <div class="p-4 bg-white/[0.02] border border-white/5 rounded-2xl space-y-3">
                <div class="flex items-center gap-2 mb-2">
                    <div class="w-8 h-8 bg-[#4A154B]/80 rounded-lg flex items-center justify-center text-lg">💬</div>
                    <div>
                        <p class="text-sm font-semibold text-white">Slack</p>
                        <p class="text-xs text-gray-500">Post alerts to a Slack channel via Incoming Webhook</p>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-1.5">Slack Incoming Webhook URL</label>
                    <input name="slack_webhook_url" type="url" value="{{ old('slack_webhook_url', $widget->slack_webhook_url) }}"
                           placeholder="https://hooks.slack.com/services/T.../B.../..."
                           class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-white placeholder-gray-600 focus:outline-none focus:border-brand-500/50 transition font-mono text-sm">
                </div>
            </div>

            {{-- Discord --}}
            <div class="p-4 bg-white/[0.02] border border-white/5 rounded-2xl space-y-3">
                <div class="flex items-center gap-2 mb-2">
                    <div class="w-8 h-8 bg-[#5865F2]/80 rounded-lg flex items-center justify-center text-lg">🎮</div>
                    <div>
                        <p class="text-sm font-semibold text-white">Discord</p>
                        <p class="text-xs text-gray-500">Post alerts to a Discord channel via Webhook</p>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-1.5">Discord Webhook URL</label>
                    <input name="discord_webhook_url" type="url" value="{{ old('discord_webhook_url', $widget->discord_webhook_url) }}"
                           placeholder="https://discord.com/api/webhooks/..."
                           class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-white placeholder-gray-600 focus:outline-none focus:border-brand-500/50 transition font-mono text-sm">
                </div>
            </div>

            {{-- Email --}}
            <div class="p-4 bg-white/[0.02] border border-white/5 rounded-2xl space-y-3">
                <div class="flex items-center gap-2 mb-2">
                    <div class="w-8 h-8 bg-brand-500/20 rounded-lg flex items-center justify-center text-lg">📧</div>
                    <div>
                        <p class="text-sm font-semibold text-white">Email</p>
                        <p class="text-xs text-gray-500">Send email alerts to a specific address</p>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-1.5">Notification Email Address</label>
                    <input name="notification_email" type="email" value="{{ old('notification_email', $widget->notification_email) }}"
                           placeholder="alerts@yourcompany.com"
                           class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-white placeholder-gray-600 focus:outline-none focus:border-brand-500/50 transition text-sm">
                    <p class="mt-1 text-xs text-gray-500">Leave blank to use your account email.</p>
                </div>
            </div>

            {{-- Webhooks --}}
            <div class="p-4 bg-white/[0.02] border border-white/5 rounded-2xl space-y-3 pt-4 border-t border-white/5 mt-4">
                <p class="text-sm font-semibold text-white">⚡ Custom Webhooks</p>
                <p class="text-xs text-gray-500">POST JSON payloads to your own endpoint. Verify with HMAC SHA-256 (<code class="text-brand-400">X-PFX-Signature</code> header).</p>
                <label class="flex items-center gap-3 p-3 bg-white/[0.02] rounded-xl cursor-pointer hover:bg-white/[0.04] transition">
                    <input type="checkbox" name="webhook_enabled" value="1" {{ $widget->webhook_enabled ? 'checked' : '' }} class="w-4 h-4 rounded text-brand-600 focus:ring-brand-500/30">
                    <span class="text-sm text-gray-300">Enable webhooks</span>
                </label>
                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-1.5">Webhook URL</label>
                    <input name="webhook_url" type="url" value="{{ old('webhook_url', $widget->webhook_url) }}" placeholder="https://hooks.yourapp.com/pfx"
                           class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-white placeholder-gray-600 focus:outline-none focus:border-brand-500/50 transition font-mono text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-1.5">Signing Secret</label>
                    <input name="webhook_secret" type="password" value="{{ old('webhook_secret', $widget->webhook_secret) }}" placeholder="Leave blank to keep current" autocomplete="new-password"
                           class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-white placeholder-gray-600 focus:outline-none focus:border-brand-500/50 transition font-mono text-sm">
                </div>
                <div class="flex items-center gap-2">
                    <button type="submit" form="form-test-webhook" class="px-4 py-2 bg-white/5 hover:bg-white/10 border border-white/10 rounded-lg text-sm text-gray-300 hover:text-white transition">
                        Send test payload
                    </button>
                    <a href="{{ route('widgets.webhookLogs', $widget) }}" class="px-4 py-2 bg-brand-500/10 hover:bg-brand-500/20 border border-brand-500/20 rounded-lg text-sm text-brand-400 hover:text-brand-300 transition">
                        View Delivery Logs →
                    </a>
                </div>
            </div>
        </div>

        {{-- ── Lead Capture Tab ─────────────────────────────────────────────── --}}
        <div x-show="tab === 'lead'" x-transition class="bg-gray-900/50 border border-white/5 rounded-2xl p-6 space-y-5">
            <h3 class="text-base font-semibold text-white">Lead Capture & Qualification</h3>
            <label class="flex items-center gap-3 p-4 bg-brand-500/5 border border-brand-500/10 rounded-xl cursor-pointer">
                <input type="checkbox" name="lead_qualification_enabled" value="1" {{ $widget->lead_qualification_enabled ? 'checked' : '' }} class="w-4 h-4 rounded text-brand-600 focus:ring-brand-500/30">
                <div>
                    <span class="text-sm font-medium text-gray-300">Enable AI Lead Qualification</span>
                    <p class="text-xs text-gray-500">AI naturally gathers lead information and scores 0-100 every 5 messages.</p>
                </div>
            </label>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-2 p-4 bg-white/[0.02] rounded-xl text-xs text-gray-500">
                @foreach(['Contact info (name, email)', 'Company & role', 'Budget range', 'Timeline & urgency', 'Pain points', 'Service interest'] as $item)
                <span class="flex items-center gap-2"><span class="w-1.5 h-1.5 bg-brand-400 rounded-full shrink-0"></span>{{ $item }}</span>
                @endforeach
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <label class="flex items-center gap-3 p-3 bg-white/[0.02] rounded-xl cursor-pointer">
                    <input type="checkbox" name="collect_email" value="1" {{ $widget->collect_email ? 'checked' : '' }} class="w-4 h-4 rounded text-brand-600 focus:ring-brand-500/30">
                    <div><span class="text-sm text-gray-300">Collect Email</span><p class="text-xs text-gray-500">Ask visitor for email during chat</p></div>
                </label>
                <label class="flex items-center gap-3 p-3 bg-white/[0.02] rounded-xl cursor-pointer">
                    <input type="checkbox" name="collect_phone" value="1" {{ $widget->collect_phone ? 'checked' : '' }} class="w-4 h-4 rounded text-brand-600 focus:ring-brand-500/30">
                    <div><span class="text-sm text-gray-300">Collect Phone</span><p class="text-xs text-gray-500">Ask visitor for phone number</p></div>
                </label>
            </div>
        </div>

        {{-- ── Security Tab ─────────────────────────────────────────────────── --}}
        <div x-show="tab === 'security'" x-transition class="bg-gray-900/50 border border-white/5 rounded-2xl p-6 space-y-5">
            <h3 class="text-base font-semibold text-white">Security & Access Control</h3>
            <label class="flex items-center gap-3 p-3 bg-white/[0.02] rounded-xl cursor-pointer hover:bg-white/[0.04] transition">
                <input type="checkbox" name="domain_restriction" value="1" {{ $widget->domain_restriction ? 'checked' : '' }} class="w-4 h-4 rounded text-brand-600 focus:ring-brand-500/30">
                <div>
                    <span class="text-sm font-medium text-gray-300">Domain Restriction</span>
                    <p class="text-xs text-gray-500">Only allow this widget to load on specific domains.</p>
                </div>
            </label>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1.5">Allowed Domains <span class="text-gray-500">(one per line, wildcards supported)</span></label>
                <textarea name="allowed_domains" rows="4" placeholder="example.com&#10;*.example.com&#10;app.mysite.io"
                          class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-gray-500 focus:outline-none focus:border-brand-500/50 transition resize-none font-mono text-sm">{{ old('allowed_domains', is_array($widget->allowed_domains) ? implode("\n", $widget->allowed_domains) : '') }}</textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1.5">Rate Limit (messages/minute per visitor)</label>
                <input name="rate_limit" type="number" min="1" max="200" value="{{ old('rate_limit', $widget->rate_limit ?? 30) }}"
                       class="w-full md:w-48 bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-brand-500/50 transition">
            </div>
            <div class="p-4 bg-white/[0.02] rounded-xl">
                <p class="text-xs text-gray-400 font-medium mb-2">Widget Token</p>
                <code class="text-xs text-brand-400 break-all">{{ $widget->token }}</code>
                <div class="mt-3">
                    <button type="submit" form="form-regen-token" onclick="return confirm('Regenerate token? Existing embeds will stop working until updated.')" class="px-3 py-1.5 text-xs bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 rounded-lg transition">Regenerate Token</button>
                </div>
            </div>
        </div>

        {{-- ── A/B Testing Tab ──────────────────────────────────────────────── --}}
        <div x-show="tab === 'ab-testing'" x-transition class="bg-gray-900/50 border border-white/5 rounded-2xl p-6 space-y-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="text-base font-semibold text-white">🧪 A/B Testing</h3>
                    <p class="text-sm text-gray-500 mt-1">Test different widget configurations to optimize conversion rates.</p>
                </div>
                <a href="{{ route('widgets.abTests.create', $widget) }}" class="px-4 py-2 bg-brand-600 hover:bg-brand-500 text-white text-sm font-medium rounded-xl transition">
                    + New Test
                </a>
            </div>

            @if($widget->abTests->count() > 0)
                <div class="space-y-4">
                    @foreach($widget->abTests as $test)
                    <div class="p-4 bg-white/[0.02] border border-white/5 rounded-2xl">
                        <div class="flex items-start justify-between gap-4 mb-4">
                            <div class="min-w-0 flex-1">
                                <h4 class="text-sm font-semibold text-white">{{ $test->name }}</h4>
                                <p class="text-xs text-gray-500 mt-1">{{ $test->variant_a['welcome_message'] ?? 'Variant A' }} vs {{ $test->variant_b['welcome_message'] ?? 'Variant B' }}</p>
                                <div class="flex items-center gap-4 mt-2 text-xs">
                                    <span class="text-gray-400">Split: {{ intval($test->split_ratio * 100) }}% / {{ intval((1 - $test->split_ratio) * 100) }}%</span>
                                    <span class="text-{{ $test->is_active ? 'emerald' : 'gray' }}-400">{{ $test->is_active ? '● Active' : '○ Inactive' }}</span>
                                    @if($test->started_at)
                                    <span class="text-gray-400">Started {{ $test->started_at->diffForHumans() }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <button type="submit" form="form-ab-toggle-{{ $test->id }}" class="px-3 py-1.5 text-xs font-medium rounded-lg transition {{ $test->is_active ? 'bg-emerald-500/10 text-emerald-400 hover:bg-emerald-500/20' : 'bg-gray-500/10 text-gray-400 hover:bg-gray-500/20' }}">
                                    {{ $test->is_active ? 'Pause' : 'Resume' }}
                                </button>
                                <a href="{{ route('widgets.abTests.edit', [$widget, $test]) }}" class="px-3 py-1.5 text-xs bg-brand-500/10 text-brand-400 hover:bg-brand-500/20 rounded-lg transition">Edit</a>
                            </div>
                        </div>

                        {{-- Test Results --}}
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div class="text-center">
                                <p class="text-lg font-semibold text-white">{{ $test->impressions_a + $test->impressions_b }}</p>
                                <p class="text-xs text-gray-500">Total Impressions</p>
                            </div>
                            <div class="text-center">
                                <p class="text-lg font-semibold text-white">{{ $test->conversations_a + $test->conversations_b }}</p>
                                <p class="text-xs text-gray-500">Conversations</p>
                            </div>
                            <div class="text-center">
                                <p class="text-lg font-semibold text-white">{{ $test->leads_a + $test->leads_b }}</p>
                                <p class="text-xs text-gray-500">Leads</p>
                            </div>
                            <div class="text-center">
                                <p class="text-lg font-semibold text-{{ $test->getWinner() === 'a' ? 'emerald' : ($test->getWinner() === 'b' ? 'emerald' : 'gray') }}-400">
                                    {{ $test->getWinner() ? 'Variant ' . strtoupper($test->getWinner()) : 'No winner yet' }}
                                </p>
                                <p class="text-xs text-gray-500">Winner</p>
                            </div>
                        </div>

                        {{-- Conversion Rates --}}
                        <div class="mt-4 pt-4 border-t border-white/5">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <p class="text-sm font-medium text-gray-300 mb-2">Variant A</p>
                                    <div class="text-xs text-gray-500 space-y-1">
                                        <div>Impressions: {{ $test->impressions_a }}</div>
                                        <div>Conversations: {{ $test->conversations_a }}</div>
                                        <div>Leads: {{ $test->leads_a }}</div>
                                        <div>Conversion: {{ $test->getLeadConversionRate('a') }}%</div>
                                    </div>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-300 mb-2">Variant B</p>
                                    <div class="text-xs text-gray-500 space-y-1">
                                        <div>Impressions: {{ $test->impressions_b }}</div>
                                        <div>Conversations: {{ $test->conversations_b }}</div>
                                        <div>Leads: {{ $test->leads_b }}</div>
                                        <div>Conversion: {{ $test->getLeadConversionRate('b') }}%</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-12">
                    <div class="w-16 h-16 bg-brand-500/10 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-brand-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3.104v5.714a2.25 2.25 0 0 1-.659 1.591L5 14.5M9.75 3.104c.251.023.501.05.75.082m-.75-.082a24.301 24.301 0 0 0-4.5 0m4.5 0v5.714c0 .597.237 1.17.659 1.591L9.75 14.5M9.75 14.5l3-3m0 0 3 3m-3-3v-5.714a2.25 2.25 0 0 1 .659-1.591L16.5 3.104m0 0 .75.082M16.5 3.104c-.251.023-.501.05-.75.082"/>
                        </svg>
                    </div>
                    <h4 class="text-lg font-semibold text-white mb-2">No A/B Tests Yet</h4>
                    <p class="text-gray-500 mb-6">Create your first test to start optimizing your widget's performance.</p>
                    <a href="{{ route('widgets.abTests.create', $widget) }}" class="px-6 py-3 bg-brand-600 hover:bg-brand-500 text-white text-sm font-medium rounded-xl transition">
                        Create First Test
                    </a>
                </div>
            @endif

            <div class="p-4 bg-brand-500/5 border border-brand-500/10 rounded-xl text-sm text-brand-300">
                💡 <strong>How it works:</strong> Visitors are randomly assigned to variants based on their fingerprint. Track impressions, conversations, and leads to determine which variant performs better. Tests can be paused/resumed at any time.
            </div>
        </div>

        {{-- ── Languages Tab ────────────────────────────────────────────────── --}}
        <div x-show="tab === 'languages'" x-transition class="bg-gray-900/50 border border-white/5 rounded-2xl p-6 space-y-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="text-base font-semibold text-white">🌍 Multi-Language Support</h3>
                    <p class="text-sm text-gray-500 mt-1">Customize widget text for different languages. Leave fields empty to use defaults.</p>
                </div>
                <div class="text-xs text-gray-400">
                    <p>Supported: en, es, fr, de, it, pt, ru, ja, ko, zh</p>
                </div>
            </div>

            {{-- Language Tabs --}}
            <div x-data="{ langTab: 'en' }" class="space-y-6">
                <div class="flex items-center gap-1 bg-gray-800/50 border border-white/5 rounded-xl p-1 overflow-x-auto">
                    @foreach(['en' => '🇺🇸 English', 'es' => '🇪🇸 Spanish', 'fr' => '🇫🇷 French', 'de' => '🇩🇪 German', 'it' => '🇮🇹 Italian', 'pt' => '🇵🇹 Portuguese', 'ru' => '🇷🇺 Russian', 'ja' => '🇯🇵 Japanese', 'ko' => '🇰🇷 Korean', 'zh' => '🇨🇳 Chinese'] as $code => $label)
                    <button @click="langTab = '{{ $code }}'" :class="langTab === '{{ $code }}' ? 'bg-white/10 text-white' : 'text-gray-400 hover:text-white'"
                            class="px-3 py-2 text-xs font-medium rounded-lg transition whitespace-nowrap">{{ $label }}</button>
                    @endforeach
                </div>

                {{-- Welcome Messages --}}
                <div x-show="langTab === 'en'" x-transition>
                    <h4 class="text-sm font-semibold text-white mb-3">Welcome Messages</h4>
                    <div class="space-y-4">
                        @foreach(['en' => '🇺🇸 English', 'es' => '🇪🇸 Spanish', 'fr' => '🇫🇷 French', 'de' => '🇩🇪 German', 'it' => '🇮🇹 Italian', 'pt' => '🇵🇹 Portuguese', 'ru' => '🇷🇺 Russian', 'ja' => '🇯🇵 Japanese', 'ko' => '🇰🇷 Korean', 'zh' => '🇨🇳 Chinese'] as $code => $label)
                        <div>
                            <label class="block text-xs font-medium text-gray-300 mb-1">{{ $label }}</label>
                            <input name="welcome_messages[{{ $code }}]" value="{{ old('welcome_messages.' . $code, ($widget->welcome_messages[$code] ?? '')) }}"
                                   placeholder="Hi! How can I help you today?" class="w-full bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-white text-sm placeholder-gray-500 focus:outline-none focus:border-brand-500/50 transition">
                        </div>
                        @endforeach
                    </div>
                </div>

                {{-- Placeholder Texts --}}
                <div x-show="langTab === 'en'" x-transition>
                    <h4 class="text-sm font-semibold text-white mb-3">Placeholder Texts</h4>
                    <div class="space-y-4">
                        @foreach(['en' => '🇺🇸 English', 'es' => '🇪🇸 Spanish', 'fr' => '🇫🇷 French', 'de' => '🇩🇪 German', 'it' => '🇮🇹 Italian', 'pt' => '🇵🇹 Portuguese', 'ru' => '🇷🇺 Russian', 'ja' => '🇯🇵 Japanese', 'ko' => '🇰🇷 Korean', 'zh' => '🇨🇳 Chinese'] as $code => $label)
                        <div>
                            <label class="block text-xs font-medium text-gray-300 mb-1">{{ $label }}</label>
                            <input name="placeholder_texts[{{ $code }}]" value="{{ old('placeholder_texts.' . $code, ($widget->placeholder_texts[$code] ?? '')) }}"
                                   placeholder="Type a message..." class="w-full bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-white text-sm placeholder-gray-500 focus:outline-none focus:border-brand-500/50 transition">
                        </div>
                        @endforeach
                    </div>
                </div>

                {{-- Conversation Starters --}}
                <div x-show="langTab === 'en'" x-transition>
                    <h4 class="text-sm font-semibold text-white mb-3">Conversation Starters</h4>
                    <div class="space-y-4">
                        @foreach(['en' => '🇺🇸 English', 'es' => '🇪🇸 Spanish', 'fr' => '🇫🇷 French', 'de' => '🇩🇪 German', 'it' => '🇮🇹 Italian', 'pt' => '🇵🇹 Portuguese', 'ru' => '🇷🇺 Russian', 'ja' => '🇯🇵 Japanese', 'ko' => '🇰🇷 Korean', 'zh' => '🇨🇳 Chinese'] as $code => $label)
                        <div>
                            <label class="block text-xs font-medium text-gray-300 mb-1">{{ $label }} (one per line)</label>
                            <textarea name="conversation_starters_multi[{{ $code }}]" rows="3"
                                      placeholder="How can I help you today?&#10;Tell me about your products&#10;I need support"
                                      class="w-full bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-white text-sm placeholder-gray-500 focus:outline-none focus:border-brand-500/50 transition resize-none">{{ old('conversation_starters_multi.' . $code, (is_array($widget->conversation_starters_multi[$code] ?? null) ? implode("\n", $widget->conversation_starters_multi[$code]) : '')) }}</textarea>
                        </div>
                        @endforeach
                    </div>
                </div>

                {{-- Offline Form Messages --}}
                <div x-show="langTab === 'en'" x-transition>
                    <h4 class="text-sm font-semibold text-white mb-3">Offline Form Messages</h4>
                    <div class="space-y-4">
                        @foreach(['en' => '🇺🇸 English', 'es' => '🇪🇸 Spanish', 'fr' => '🇫🇷 French', 'de' => '🇩🇪 German', 'it' => '🇮🇹 Italian', 'pt' => '🇵🇹 Portuguese', 'ru' => '🇷🇺 Russian', 'ja' => '🇯🇵 Japanese', 'ko' => '🇰🇷 Korean', 'zh' => '🇨🇳 Chinese'] as $code => $label)
                        <div>
                            <label class="block text-xs font-medium text-gray-300 mb-1">{{ $label }}</label>
                            <input name="offline_form_messages[{{ $code }}]" value="{{ old('offline_form_messages.' . $code, ($widget->offline_form_messages[$code] ?? '')) }}"
                                   placeholder="We're currently offline. Leave a message and we'll get back to you soon." class="w-full bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-white text-sm placeholder-gray-500 focus:outline-none focus:border-brand-500/50 transition">
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="p-4 bg-brand-500/5 border border-brand-500/10 rounded-xl text-sm text-brand-300">
                🌍 <strong>Auto-detection:</strong> The widget automatically detects visitor language from browser settings. If a language isn't configured, it falls back to English defaults.
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3 mt-6">
            <button type="submit" class="px-6 py-2.5 bg-gradient-to-r from-brand-600 to-accent-600 hover:from-brand-500 hover:to-accent-500 text-white text-sm font-medium rounded-xl transition shadow-lg shadow-brand-500/20">
                Save Changes
            </button>
            <a href="{{ route('widgets.index') }}" class="px-4 py-2.5 text-sm text-gray-400 hover:text-white transition">Cancel</a>
            <button type="button" @click="tab = 'preview'" class="px-4 py-2.5 text-sm text-accent-400 hover:text-accent-300 transition ml-auto">🔍 Preview Changes</button>
        </div>
    </form>

    {{-- ── Auxiliary forms — kept outside main form to prevent nested-form breakage ── --}}
    <form id="form-test-webhook" method="POST" action="{{ route('widgets.testWebhook', $widget) }}">@csrf</form>
    <form id="form-regen-token" method="POST" action="{{ route('widgets.regenerateToken', $widget) }}">@csrf</form>
    @foreach($widget->abTests as $test)
    <form id="form-ab-toggle-{{ $test->id }}" method="POST" action="{{ route('widgets.abTests.toggle', [$widget, $test]) }}">@csrf @method('PATCH')</form>
    @endforeach

    {{-- ── Channels Tab (outside form – managed via API) ─────────────── --}}
    <div x-show="tab === 'channels'" x-transition
         x-data="channelsManager({{ $widget->id }}, @js($channelConfigurations->map(fn($c) => ['id'=>$c->id,'channel_type'=>$c->channel_type,'account_name'=>$c->account_name,'is_enabled'=>$c->is_enabled,'messages_today'=>$c->messages_today,'total_messages_sent'=>$c->total_messages_sent,'total_messages_received'=>$c->total_messages_received,'last_message_at'=>$c->last_message_at?->toIso8601String()])->values()), {{ $channelLimit }})"
         class="bg-gray-900/50 border border-white/5 rounded-2xl p-6 space-y-5">

        {{-- Header row --}}
        <div class="flex items-start justify-between gap-4">
            <div>
                <h3 class="text-base font-semibold text-white">📡 Channels</h3>
                <p class="text-sm text-gray-500 mt-0.5">Connect external messaging platforms. AI handles replies automatically on every channel.</p>
            </div>
            <div class="flex items-center gap-3">
                <div class="text-xs text-gray-500">
                    <span x-text="channels.length" class="text-white font-semibold"></span>
                    <span x-text="channelLimit === -1 ? ' / ∞' : ' / ' + channelLimit"></span> connected
                </div>
                <button @click="openAddModal()"
                        :disabled="channelLimit !== -1 && channels.length >= channelLimit"
                        class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 disabled:opacity-40 disabled:cursor-not-allowed text-white text-xs font-medium rounded-xl transition">
                    + Add Channel
                </button>
            </div>
        </div>

        {{-- Plan limit bar --}}
        <template x-if="channelLimit !== -1">
            <div class="w-full bg-white/5 rounded-full h-1.5">
                <div class="bg-emerald-500 h-1.5 rounded-full transition-all duration-500"
                     :style="`width: ${Math.min(channels.length / channelLimit * 100, 100)}%`"></div>
            </div>
        </template>

        {{-- Toast notification --}}
        <div x-show="toast.show" x-transition:leave="transition duration-300" x-transition:leave-end="opacity-0"
             class="p-3 rounded-xl text-sm flex items-center gap-2"
             :class="toast.type === 'success' ? 'bg-emerald-500/10 border border-emerald-500/20 text-emerald-400' : 'bg-rose-500/10 border border-rose-500/20 text-rose-400'">
            <span x-text="toast.type === 'success' ? '✓' : '✕'"></span>
            <span x-text="toast.message"></span>
        </div>

        {{-- Empty state --}}
        <div x-show="channels.length === 0" class="text-center py-12">
            <div class="text-4xl mb-3">📡</div>
            <p class="text-gray-400 font-medium">No channels connected yet</p>
            <p class="text-gray-600 text-sm mt-1">Connect WhatsApp, Telegram, Slack and more — AI replies automatically.</p>
            <button @click="openAddModal()" class="mt-5 px-5 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-medium rounded-xl transition">
                Connect Your First Channel
            </button>
        </div>

        {{-- Channels grid --}}
        <div x-show="channels.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <template x-for="ch in channels" :key="ch.id">
                <div class="bg-white/[0.03] border border-white/5 rounded-xl p-4 flex items-start justify-between gap-3 hover:border-white/10 transition">
                    <div class="flex items-start gap-3 flex-1 min-w-0">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center text-xl shrink-0"
                             :style="`background: ${channelMeta[ch.channel_type]?.color}20`"
                             x-text="channelMeta[ch.channel_type]?.emoji || '📡'"></div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <p class="text-sm font-semibold text-white" x-text="ch.account_name || channelMeta[ch.channel_type]?.name"></p>
                                <span class="px-1.5 py-0.5 text-[10px] rounded-full"
                                      :class="ch.is_enabled ? 'bg-emerald-500/15 text-emerald-400' : 'bg-gray-500/15 text-gray-500'"
                                      x-text="ch.is_enabled ? 'Active' : 'Disabled'"></span>
                            </div>
                            <p class="text-xs text-gray-500 mt-0.5" x-text="channelMeta[ch.channel_type]?.name"></p>
                            <div class="flex items-center gap-3 mt-2 text-[10px] text-gray-600">
                                <span>↑ <span x-text="ch.total_messages_sent"></span></span>
                                <span>↓ <span x-text="ch.total_messages_received"></span></span>
                                <span x-show="ch.last_message_at">Last: <span x-text="ch.last_message_at ? new Date(ch.last_message_at).toLocaleDateString() : '—'"></span></span>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-1 shrink-0">
                        {{-- Webhook copy --}}
                        <button @click="copyWebhook(ch.channel_type)" title="Copy webhook URL"
                                class="p-1.5 text-gray-500 hover:text-gray-300 hover:bg-white/5 rounded-lg transition">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244" /></svg>
                        </button>
                        {{-- Toggle --}}
                        <button @click="toggleChannel(ch)" title="Toggle enabled"
                                class="p-1.5 rounded-lg transition"
                                :class="ch.is_enabled ? 'text-emerald-400 hover:bg-emerald-500/10' : 'text-gray-500 hover:bg-white/5'">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M5.636 5.636a9 9 0 1 0 12.728 0M12 3v9" /></svg>
                        </button>
                        {{-- Edit --}}
                        <button @click="openEditModal(ch)" title="Edit"
                                class="p-1.5 text-gray-500 hover:text-white hover:bg-white/5 rounded-lg transition">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125" /></svg>
                        </button>
                        {{-- Test --}}
                        <button @click="testChannel(ch)" :disabled="testing === ch.id" title="Test connection"
                                class="p-1.5 text-gray-500 hover:text-brand-400 hover:bg-brand-500/10 rounded-lg transition disabled:opacity-40">
                            <svg class="w-3.5 h-3.5" :class="testing === ch.id ? 'animate-spin' : ''" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.990" /></svg>
                        </button>
                        {{-- Delete --}}
                        <button @click="deleteChannel(ch)" title="Disconnect"
                                class="p-1.5 text-gray-500 hover:text-rose-400 hover:bg-rose-500/10 rounded-lg transition">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                        </button>
                    </div>
                </div>
            </template>
        </div>

        {{-- Webhook reference table --}}
        <div x-show="channels.length > 0" class="mt-2 p-4 bg-white/[0.02] border border-white/5 rounded-xl">
            <p class="text-xs font-semibold text-gray-400 mb-2">Webhook URLs (configure in each platform)</p>
            <div class="space-y-1">
                <template x-for="ch in channels.filter(c => channelMeta[c.channel_type]?.webhookPath)" :key="ch.id">
                    <div class="flex items-center gap-2 text-xs">
                        <span class="text-gray-500 w-20 shrink-0" x-text="channelMeta[ch.channel_type]?.name + ':'"></span>
                        <code class="text-brand-300 font-mono truncate flex-1" x-text="webhookBase + channelMeta[ch.channel_type]?.webhookPath"></code>
                        <button @click="copyWebhook(ch.channel_type)" class="text-gray-600 hover:text-gray-300 shrink-0">
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 0 1-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 0 1 1.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 0 0-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 0 1-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 0 0-3.375-3.375h-1.5a1.125 1.125 0 0 1-1.125-1.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H9.75" /></svg>
                        </button>
                    </div>
                </template>
            </div>
        </div>

        {{-- Add Channel Modal --}}
        <div x-show="showAdd" x-transition class="fixed inset-0 z-50 flex items-center justify-center p-4"
             style="background: rgba(0,0,0,0.7)" @click.self="showAdd = false">
            <div class="bg-gray-900 border border-white/10 rounded-2xl w-full max-w-lg shadow-2xl overflow-hidden" @click.stop>

                {{-- Modal Header --}}
                <div class="px-6 py-4 border-b border-white/5 flex items-center justify-between">
                    <div>
                        <h4 class="text-base font-semibold text-white" x-text="addStep === 1 ? 'Connect a Channel' : 'Configure ' + (channelMeta[selectedType]?.name || '')"></h4>
                        <p class="text-xs text-gray-500 mt-0.5" x-show="addStep === 1">Choose a messaging platform to connect</p>
                        <p class="text-xs text-gray-500 mt-0.5" x-show="addStep === 2">Enter your credentials to connect this channel</p>
                    </div>
                    <button @click="showAdd = false" class="text-gray-500 hover:text-white">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                {{-- Step 1: Channel Type Selection --}}
                <div x-show="addStep === 1" class="p-6">
                    <div class="grid grid-cols-3 gap-2">
                        <template x-for="[type, meta] in Object.entries(channelMeta)" :key="type">
                            <button @click="selectType(type)"
                                    :disabled="channels.some(c => c.channel_type === type)"
                                    class="flex flex-col items-center gap-1.5 p-3 rounded-xl border transition disabled:opacity-30 disabled:cursor-not-allowed"
                                    :class="channels.some(c => c.channel_type === type) ? 'border-white/5 bg-white/[0.02]' : 'border-white/10 bg-white/[0.03] hover:border-white/20 hover:bg-white/5'">
                                <span class="text-2xl" x-text="meta.emoji"></span>
                                <span class="text-xs text-gray-300 font-medium text-center leading-tight" x-text="meta.name"></span>
                                <span x-show="channels.some(c => c.channel_type === type)" class="text-[10px] text-gray-600">Connected</span>
                            </button>
                        </template>
                    </div>
                </div>

                {{-- Step 2: Credentials Form --}}
                <div x-show="addStep === 2" class="p-6 space-y-4">
                    {{-- Back button --}}
                    <button @click="addStep = 1; selectedType = ''" class="flex items-center gap-1 text-xs text-gray-400 hover:text-white transition">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/></svg>
                        Back
                    </button>

                    <div>
                        <label class="block text-xs font-medium text-gray-400 mb-1">Display Name</label>
                        <input x-model="addForm.account_name" type="text"
                               :placeholder="'e.g. My ' + (channelMeta[selectedType]?.name || '') + ' Bot'"
                               class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-white text-sm placeholder-gray-600 focus:outline-none focus:border-brand-500/50 transition">
                        <p class="text-[10px] text-gray-600 mt-1">A friendly name to identify this channel</p>
                    </div>

                    <template x-if="selectedType">
                        <div class="space-y-3">
                            <template x-for="field in channelMeta[selectedType]?.fields || []" :key="field.key">
                                <div>
                                    <label class="block text-xs font-medium text-gray-400 mb-1" x-text="field.label"></label>
                                    <input :type="field.showPlain ? 'text' : field.type"
                                           x-model="addForm.credentials[field.key]"
                                           :placeholder="field.placeholder"
                                           class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-white text-sm font-mono placeholder-gray-600 focus:outline-none focus:border-brand-500/50 transition">
                                </div>
                            </template>
                        </div>
                    </template>

                    <template x-if="channelMeta[selectedType]?.webhookNote">
                        <div class="p-3 bg-brand-500/5 border border-brand-500/10 rounded-xl text-xs text-brand-300">
                            <strong>Webhook:</strong> <span x-text="channelMeta[selectedType]?.webhookNote"></span>
                        </div>
                    </template>

                    <div class="flex gap-2 pt-1">
                        <button @click="saveChannel()" :disabled="saving"
                                class="flex-1 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-500 disabled:opacity-50 text-white text-sm font-medium rounded-xl transition flex items-center justify-center gap-2">
                            <svg x-show="saving" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            <span x-text="saving ? 'Connecting...' : 'Connect Channel'"></span>
                        </button>
                        <button @click="showAdd = false" class="px-4 py-2.5 bg-white/5 hover:bg-white/10 text-gray-300 text-sm rounded-xl transition">Cancel</button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Edit Channel Modal --}}
        <div x-show="showEdit" x-transition class="fixed inset-0 z-50 flex items-center justify-center p-4"
             style="background: rgba(0,0,0,0.7)" @click.self="showEdit = false">
            <div class="bg-gray-900 border border-white/10 rounded-2xl w-full max-w-lg shadow-2xl overflow-hidden" @click.stop>
                <div class="px-6 py-4 border-b border-white/5 flex items-center justify-between">
                    <h4 class="text-base font-semibold text-white">Edit Channel</h4>
                    <button @click="showEdit = false" class="text-gray-500 hover:text-white">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-400 mb-1">Display Name</label>
                        <input x-model="editForm.account_name" type="text"
                               class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-white text-sm focus:outline-none focus:border-brand-500/50 transition">
                    </div>
                    <template x-if="editChannel">
                        <div class="space-y-3">
                            <p class="text-xs text-gray-500">Update credentials (leave blank to keep existing):</p>
                            <template x-for="field in channelMeta[editChannel?.channel_type]?.fields || []" :key="field.key">
                                <div>
                                    <label class="block text-xs font-medium text-gray-400 mb-1" x-text="field.label"></label>
                                    <input :type="field.type" x-model="editForm.credentials[field.key]"
                                           :placeholder="field.placeholder"
                                           class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-white text-sm font-mono placeholder-gray-600 focus:outline-none focus:border-brand-500/50 transition">
                                </div>
                            </template>
                        </div>
                    </template>
                    <div class="flex items-center gap-2 p-3 bg-white/[0.02] rounded-xl">
                        <input type="checkbox" x-model="editForm.is_enabled" id="editEnabled" class="rounded bg-white/5 border-white/20 text-emerald-600">
                        <label for="editEnabled" class="text-sm text-gray-300">Channel Active</label>
                    </div>
                    <div class="flex gap-2">
                        <button @click="updateChannel()" :disabled="saving"
                                class="flex-1 px-4 py-2.5 bg-brand-600 hover:bg-brand-500 disabled:opacity-50 text-white text-sm font-medium rounded-xl transition">
                            <span x-text="saving ? 'Saving...' : 'Save Changes'"></span>
                        </button>
                        <button @click="showEdit = false" class="px-4 py-2.5 bg-white/5 hover:bg-white/10 text-gray-300 text-sm rounded-xl transition">Cancel</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Live Preview Panel (outside form) ───────────────────────────── --}}
    <div x-show="tab === 'preview'" x-transition class="mt-0">

        {{-- Preview controls --}}
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-base font-semibold text-white">Live Widget Preview</h3>
                <p class="text-xs text-gray-500 mt-0.5">Changes reflect in real-time as you edit the Appearance & Behavior tabs.</p>
            </div>
            <div class="flex items-center gap-2">
                <button @click="pChatOpen = !pChatOpen"
                    class="px-3 py-1.5 text-xs bg-white/5 hover:bg-white/10 text-gray-300 rounded-xl transition">
                    <span x-text="pChatOpen ? '✕ Close Chat' : '💬 Open Chat'"></span>
                </button>
                <button @click="tab = 'appearance'"
                    class="px-3 py-1.5 text-xs bg-brand-500/10 hover:bg-brand-500/20 text-brand-400 rounded-xl transition">
                    ← Back to Edit
                </button>
            </div>
        </div>

        {{-- Browser mockup --}}
        <div class="relative bg-gray-900 rounded-2xl border border-white/10 overflow-hidden shadow-2xl" style="height: 640px;">

            {{-- Browser chrome bar --}}
            <div class="bg-gray-950 px-4 py-2.5 flex items-center gap-3 border-b border-white/5">
                <div class="flex gap-1.5 shrink-0">
                    <div class="w-3 h-3 rounded-full bg-rose-500/70"></div>
                    <div class="w-3 h-3 rounded-full bg-amber-500/70"></div>
                    <div class="w-3 h-3 rounded-full bg-emerald-500/70"></div>
                </div>
                <div class="flex-1 bg-white/5 border border-white/5 rounded-lg px-3 py-1 text-xs text-gray-500 text-center flex items-center justify-center gap-1.5">
                    <svg class="w-3 h-3 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 1a4.5 4.5 0 0 0-4.5 4.5V9H5a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-6a2 2 0 0 0-2-2h-.5V5.5A4.5 4.5 0 0 0 10 1Zm3 8V5.5a3 3 0 1 0-6 0V9h6Z" clip-rule="evenodd" /></svg>
                    yourwebsite.com
                </div>
                <div class="w-16 shrink-0"></div>
            </div>

            {{-- Simulated web page background --}}
            <div class="absolute inset-0 top-11 overflow-hidden bg-gradient-to-br from-slate-100 to-slate-50">

                {{-- Fake page skeleton --}}
                <div class="p-8 select-none pointer-events-none opacity-60">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-32 h-6 bg-slate-300 rounded-md"></div>
                        <div class="h-6 w-px bg-slate-300"></div>
                        <div class="w-24 h-5 bg-slate-200 rounded-md"></div>
                        <div class="w-20 h-5 bg-slate-200 rounded-md ml-auto"></div>
                        <div class="w-16 h-5 bg-slate-200 rounded-md"></div>
                        <div class="w-20 h-6 bg-slate-300 rounded-lg"></div>
                    </div>
                    <div class="max-w-lg">
                        <div class="h-10 bg-slate-800 rounded-xl w-3/4 mb-3"></div>
                        <div class="h-4 bg-slate-300 rounded w-full mb-2"></div>
                        <div class="h-4 bg-slate-300 rounded w-5/6 mb-2"></div>
                        <div class="h-4 bg-slate-300 rounded w-2/3 mb-6"></div>
                        <div class="flex gap-3">
                            <div class="h-10 w-32 bg-slate-800 rounded-xl"></div>
                            <div class="h-10 w-28 bg-slate-200 rounded-xl"></div>
                        </div>
                    </div>
                    <div class="grid grid-cols-3 gap-4 mt-8">
                        <div class="h-28 bg-white rounded-2xl shadow-sm border border-slate-200"></div>
                        <div class="h-28 bg-white rounded-2xl shadow-sm border border-slate-200"></div>
                        <div class="h-28 bg-white rounded-2xl shadow-sm border border-slate-200"></div>
                    </div>
                </div>

                {{-- Widget container — positioned by pPosition --}}
                <div class="absolute inset-4 pointer-events-none">
                    <div
                        class="absolute flex flex-col gap-3 pointer-events-auto"
                        :class="{
                            'bottom-0 right-0 items-end': pPosition === 'bottom-right',
                            'bottom-0 left-0 items-start': pPosition === 'bottom-left',
                            'top-0 right-0 items-end': pPosition === 'top-right',
                            'top-0 left-0 items-start': pPosition === 'top-left',
                        }">

                        {{-- Chat window --}}
                        <div x-show="pChatOpen" x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                             x-transition:leave="transition ease-in duration-150"
                             x-transition:leave-start="opacity-100 scale-100"
                             x-transition:leave-end="opacity-0 scale-95"
                             class="w-72 rounded-2xl shadow-2xl overflow-hidden border border-black/10 flex flex-col"
                             :style="`background: ${pBg}; color: ${pText}; font-family: ${pFont}`"
                             style="max-height: 420px;">

                            {{-- Header --}}
                            <div class="px-4 py-3 flex items-center justify-between shrink-0" :style="`background: ${pColor}`">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-full bg-white/20 flex items-center justify-center text-sm">🤖</div>
                                    <div>
                                        <p class="text-sm font-semibold text-white leading-none" x-text="pName || 'AI Assistant'"></p>
                                        <p class="text-xs text-white/70 mt-0.5">● Online</p>
                                    </div>
                                </div>
                                <button @click="pChatOpen = false" class="text-white/60 hover:text-white transition">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                                </button>
                            </div>

                            {{-- Messages --}}
                            <div class="flex-1 overflow-y-auto px-3 py-3 space-y-2.5">
                                <div class="flex gap-2 items-end">
                                    <div class="w-6 h-6 rounded-full flex items-center justify-center text-xs shrink-0 mb-0.5" :style="`background: ${pColor}25`">🤖</div>
                                    <div class="max-w-[80%] rounded-2xl rounded-bl-sm px-3 py-2 text-xs leading-relaxed"
                                         :style="`background: ${pColor}18; color: ${pText}`"
                                         x-text="pWelcome || 'Hi! How can I help you today?'"></div>
                                </div>
                                <div class="flex gap-2 items-end justify-end">
                                    <div class="max-w-[80%] rounded-2xl rounded-br-sm px-3 py-2 text-xs text-white leading-relaxed"
                                         :style="`background: ${pColor}`">Hello! I have a question about pricing.</div>
                                </div>
                                <div class="flex gap-2 items-end">
                                    <div class="w-6 h-6 rounded-full flex items-center justify-center text-xs shrink-0 mb-0.5" :style="`background: ${pColor}25`">🤖</div>
                                    <div class="max-w-[80%] rounded-2xl rounded-bl-sm px-3 py-2 text-xs leading-relaxed"
                                         :style="`background: ${pColor}18; color: ${pText}`">Of course! I'd be happy to help. What would you like to know?</div>
                                </div>
                                <div class="flex gap-2 items-end">
                                    <div class="w-6 h-6 rounded-full flex items-center justify-center text-xs shrink-0 mb-0.5" :style="`background: ${pColor}25`">🤖</div>
                                    <div class="rounded-2xl rounded-bl-sm px-3 py-2.5" :style="`background: ${pColor}18`">
                                        <div class="flex gap-1 items-center h-3">
                                            <div class="w-1.5 h-1.5 rounded-full animate-bounce" :style="`background: ${pColor}`"></div>
                                            <div class="w-1.5 h-1.5 rounded-full animate-bounce" :style="`background: ${pColor}; animation-delay: 0.15s`"></div>
                                            <div class="w-1.5 h-1.5 rounded-full animate-bounce" :style="`background: ${pColor}; animation-delay: 0.3s`"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Input area --}}
                            <div class="px-3 pb-3 pt-2 shrink-0 border-t" :style="`border-color: ${pText}15`">
                                <div class="flex items-center gap-2 rounded-xl px-3 py-2 border" :style="`background: ${pText}08; border-color: ${pText}15`">
                                    <span class="flex-1 text-xs opacity-40" :style="`color: ${pText}`" x-text="pPlaceholder || 'Type a message...'"></span>
                                    <div class="w-7 h-7 rounded-lg flex items-center justify-center text-white shrink-0" :style="`background: ${pColor}`">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5"/></svg>
                                    </div>
                                </div>
                                <p class="text-center mt-1.5" :style="`color: ${pText}50; font-size: 9px`">Powered by <span x-text="appName"></span></p>
                            </div>
                        </div>

                        {{-- Launcher button --}}
                        <button @click="pChatOpen = !pChatOpen"
                            :class="pShape === 'circle' ? 'rounded-full' : (pShape === 'rounded' ? 'rounded-2xl' : 'rounded-xl')"
                            :style="`background: ${pColor}; box-shadow: 0 8px 32px ${pColor}60`"
                            class="w-14 h-14 flex items-center justify-center text-white shadow-2xl transition-all duration-200 hover:scale-110 active:scale-95">
                            <svg x-show="!pChatOpen" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z"/>
                            </svg>
                            <svg x-show="pChatOpen" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

            </div>{{-- end simulated page --}}
        </div>{{-- end browser mockup --}}

        {{-- Config summary chips --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-4">
            <div class="p-3 bg-white/[0.03] rounded-xl border border-white/5">
                <p class="text-xs text-gray-500 mb-1">Primary Color</p>
                <div class="flex items-center gap-2">
                    <div class="w-5 h-5 rounded-md border border-white/20 shrink-0" :style="`background: ${pColor}`"></div>
                    <span class="text-xs font-mono text-white" x-text="pColor"></span>
                </div>
            </div>
            <div class="p-3 bg-white/[0.03] rounded-xl border border-white/5">
                <p class="text-xs text-gray-500 mb-1">Position</p>
                <p class="text-sm font-medium text-white capitalize" x-text="pPosition.replace(/-/g, ' ')"></p>
            </div>
            <div class="p-3 bg-white/[0.03] rounded-xl border border-white/5">
                <p class="text-xs text-gray-500 mb-1">Launcher Shape</p>
                <p class="text-sm font-medium text-white capitalize" x-text="pShape"></p>
            </div>
            <div class="p-3 bg-white/[0.03] rounded-xl border border-white/5">
                <p class="text-xs text-gray-500 mb-1">Theme</p>
                <p class="text-sm font-medium text-white capitalize" x-text="pTheme"></p>
            </div>
        </div>

        <p class="text-xs text-gray-600 mt-3 text-center">This is a visual preview only. Save your changes to apply them to the live widget.</p>
    </div>{{-- end preview panel --}}
</div>

@push('scripts')
<script>
// Sync color hex display with color picker
document.querySelectorAll('input[type=color]').forEach(function(picker) {
    picker.addEventListener('input', function() {
        var text = this.nextElementSibling;
        if (text) text.value = this.value;
    });
});
// Sync AI model hidden field based on provider selection
document.querySelectorAll('input[name=ai_provider]').forEach(function(radio) {
    radio.addEventListener('change', function() {
        // Show correct model select - handled by Alpine x-show
    });
});
</script>

<script>
function channelsManager(widgetId, initialChannels, channelLimit) {
    return {
        widgetId,
        channels: initialChannels,
        channelLimit,
        showAdd: false,
        showEdit: false,
        addStep: 1,
        selectedType: '',
        addForm: { account_name: '', credentials: {} },
        editForm: { account_name: '', credentials: {}, is_enabled: true },
        editChannel: null,
        saving: false,
        testing: null,
        toast: { show: false, message: '', type: 'success' },
        webhookBase: window.location.origin,

        channelMeta: {
            whatsapp:  { name: 'WhatsApp',       emoji: '💬', color: '#25D366', webhookPath: '/api/webhooks/whatsapp',  fields: [{key:'access_token',label:'Access Token',type:'password',placeholder:'EAAxxxxx...'},{key:'phone_number_id',label:'Phone Number ID',type:'text',placeholder:'123456789'},{key:'business_account_id',label:'Business Account ID',type:'text',placeholder:'987654321'}], webhookNote: 'Set URL in Meta Developers → WhatsApp → Configuration → Webhook' },
            telegram:  { name: 'Telegram',        emoji: '✈️', color: '#0088cc', webhookPath: '/api/webhooks/telegram',  fields: [{key:'bot_token',label:'Bot Token',type:'password',placeholder:'1234567890:ABCdef...'}], webhookNote: 'Webhook is configured automatically when you test the connection.' },
            facebook:  { name: 'Facebook',        emoji: '📘', color: '#1877F2', webhookPath: '/api/webhooks/facebook',  fields: [{key:'page_access_token',label:'Page Access Token',type:'password',placeholder:'EAAxxxxx...'},{key:'page_id',label:'Page ID',type:'text',placeholder:'1234567890'},{key:'app_secret',label:'App Secret',type:'password',placeholder:'abc123...'}], webhookNote: 'Set URL in Meta Developers → Messenger → Settings → Webhooks' },
            instagram: { name: 'Instagram',       emoji: '📸', color: '#E1306C', webhookPath: '/api/webhooks/instagram', fields: [{key:'access_token',label:'Access Token',type:'password',placeholder:'EAAxxxxx...'},{key:'instagram_account_id',label:'Instagram Account ID',type:'text',placeholder:'1234567890'}], webhookNote: 'Set URL in Meta Developers → Instagram → Webhooks' },
            slack:     { name: 'Slack',            emoji: '💼', color: '#4A154B', webhookPath: '/api/webhooks/slack',     fields: [{key:'bot_token',label:'Bot Token',type:'password',placeholder:'xoxb-...'},{key:'channel_id',label:'Channel ID',type:'text',placeholder:'C0XXXXXXXXX'}], webhookNote: 'Configure in Slack App → Event Subscriptions → Request URL' },
            discord:   { name: 'Discord',          emoji: '🎮', color: '#5865F2', webhookPath: '/api/webhooks/discord',   fields: [{key:'bot_token',label:'Bot Token',type:'password',placeholder:'MTxxxxxxxx...'},{key:'guild_id',label:'Server ID',type:'text',placeholder:'1234567890'},{key:'channel_id',label:'Channel ID',type:'text',placeholder:'9876543210'}], webhookNote: 'Configure in Discord Developer Portal → Bot → Interactions Endpoint URL' },
            sms:       { name: 'SMS (Twilio)',     emoji: '📱', color: '#F22F46', webhookPath: '/api/webhooks/sms',       fields: [{key:'account_sid',label:'Account SID',type:'text',placeholder:'ACxxxxxxxx...'},{key:'auth_token',label:'Auth Token',type:'password',placeholder:'xxxxxxxx...'},{key:'from_number',label:'From Number',type:'text',placeholder:'+15551234567'}], webhookNote: 'Set URL in Twilio Console → Phone Numbers → Messaging → Webhook' },
            twitter:   { name: 'Twitter / X',     emoji: '🐦', color: '#000000', webhookPath: '/api/webhooks/twitter',   fields: [{key:'api_key',label:'API Key',type:'text',placeholder:'xxxxx...'},{key:'api_secret',label:'API Secret',type:'password',placeholder:'xxxxx...'},{key:'access_token',label:'Access Token',type:'text',placeholder:'xxxxx...'},{key:'access_token_secret',label:'Access Token Secret',type:'password',placeholder:'xxxxx...'}], webhookNote: 'Set URL in Twitter Developer Portal → Account Activity API → Webhooks' },
            email:     { name: 'Email',            emoji: '📧', color: '#2f5ee8', webhookPath: null,                      fields: [{key:'sender_email',label:'Sender Email',type:'email',placeholder:'support@yoursite.com'},{key:'sender_name',label:'Sender Name',type:'text',placeholder:'Your Company'}], webhookNote: 'No webhook needed — email is sent via your configured mail server.' },
        },

        openAddModal() {
            this.addStep = 1;
            this.selectedType = '';
            this.addForm = { account_name: '', credentials: {} };
            this.showAdd = true;
        },

        selectType(type) {
            this.selectedType = type;
            this.addForm.credentials = {};
            (this.channelMeta[type]?.fields || []).forEach(f => { this.addForm.credentials[f.key] = ''; });
            this.addForm.account_name = '';
            this.addStep = 2;
        },

        openEditModal(ch) {
            this.editChannel = ch;
            this.editForm = {
                account_name: ch.account_name || '',
                credentials: {},
                is_enabled: ch.is_enabled,
            };
            (this.channelMeta[ch.channel_type]?.fields || []).forEach(f => { this.editForm.credentials[f.key] = ''; });
            this.showEdit = true;
        },

        async saveChannel() {
            if (!this.addForm.account_name.trim()) { this.showToast('Display name is required.', 'error'); return; }
            this.saving = true;
            try {
                const res = await fetch(`/api/channels/${this.widgetId}/configure`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
                    body: JSON.stringify({ channel_type: this.selectedType, account_name: this.addForm.account_name, credentials: this.addForm.credentials, is_enabled: true }),
                });
                const data = await res.json();
                if (data.success) {
                    this.channels.push(data.data);
                    this.showAdd = false;
                    this.showToast('Channel connected successfully!', 'success');
                } else {
                    this.showToast(data.message || 'Failed to connect channel.', 'error');
                }
            } catch (e) { this.showToast('Network error. Please try again.', 'error'); }
            this.saving = false;
        },

        async updateChannel() {
            this.saving = true;
            const payload = { account_name: this.editForm.account_name, is_enabled: this.editForm.is_enabled };
            const nonEmpty = Object.fromEntries(Object.entries(this.editForm.credentials).filter(([,v]) => v));
            if (Object.keys(nonEmpty).length) payload.credentials = nonEmpty;
            try {
                const res = await fetch(`/api/channels/${this.editChannel.id}`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
                    body: JSON.stringify(payload),
                });
                const data = await res.json();
                if (data.success) {
                    const idx = this.channels.findIndex(c => c.id === this.editChannel.id);
                    if (idx !== -1) Object.assign(this.channels[idx], data.data);
                    this.showEdit = false;
                    this.showToast('Channel updated.', 'success');
                } else {
                    this.showToast(data.message || 'Failed to update.', 'error');
                }
            } catch (e) { this.showToast('Network error.', 'error'); }
            this.saving = false;
        },

        async toggleChannel(ch) {
            try {
                const res = await fetch(`/api/channels/${ch.id}`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
                    body: JSON.stringify({ is_enabled: !ch.is_enabled }),
                });
                const data = await res.json();
                if (data.success) {
                    ch.is_enabled = !ch.is_enabled;
                    this.showToast(ch.is_enabled ? 'Channel enabled.' : 'Channel disabled.', 'success');
                }
            } catch (e) { this.showToast('Network error.', 'error'); }
        },

        async deleteChannel(ch) {
            if (!confirm(`Disconnect ${this.channelMeta[ch.channel_type]?.name}? This cannot be undone.`)) return;
            try {
                const res = await fetch(`/api/channels/${ch.id}`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
                });
                const data = await res.json();
                if (data.success) {
                    this.channels = this.channels.filter(c => c.id !== ch.id);
                    this.showToast('Channel disconnected.', 'success');
                }
            } catch (e) { this.showToast('Network error.', 'error'); }
        },

        async testChannel(ch) {
            this.testing = ch.id;
            try {
                const res = await fetch(`/api/channels/${ch.id}/test`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
                });
                const data = await res.json();
                this.showToast(data.message || (data.success ? 'Connection successful!' : 'Test failed.'), data.success ? 'success' : 'error');
            } catch (e) { this.showToast('Network error.', 'error'); }
            this.testing = null;
        },

        copyWebhook(type) {
            const path = this.channelMeta[type]?.webhookPath;
            if (!path) { this.showToast('No webhook URL for this channel.', 'error'); return; }
            navigator.clipboard.writeText(this.webhookBase + path).then(() => this.showToast('Webhook URL copied!', 'success'));
        },

        showToast(message, type = 'success') {
            this.toast = { show: true, message, type };
            setTimeout(() => { this.toast.show = false; }, 4000);
        },
    };
}
</script>
@endpush
@endsection
