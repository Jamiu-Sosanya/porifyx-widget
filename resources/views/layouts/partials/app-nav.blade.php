{{-- Customer app navigation: floating sidebar on desktop, slide-in drawer on mobile (admins use layouts.admin). --}}
@php
    $isAdmin = auth()->user()->isAdmin();
    $i = fn ($d) => '<svg class="w-[18px] h-[18px] shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="' . $d . '"/></svg>';
    $is = fn (...$p) => request()->routeIs(...$p);
    // [url, label, active, icon, children: [url, label, active]]
    $firstWidget = $isAdmin ? null : auth()->user()->widgets()->value('id');
    // While editing a widget, its sections (and related pages) show under "Widgets".
    $openWidget = request()->route('widget');
    $widgetMenu = [];
    if ($openWidget instanceof \App\Models\Widget && ! $isAdmin) {
        $onEdit = $is('widgets.edit');
        foreach (['appearance' => 'Appearance', 'behavior' => 'Behaviour', 'ai' => 'AI behaviour', 'smart' => 'Smart features', 'proactive' => 'Proactive', 'notifications' => 'Notifications', 'lead' => 'Lead capture', 'ab-testing' => 'A/B testing', 'security' => 'Security', 'languages' => 'Languages', 'channels' => 'Channels', 'preview' => 'Live preview'] as $hash => $label) {
            $widgetMenu[] = [($onEdit ? '' : route('widgets.edit', $openWidget)) . '#' . $hash, $label, null];
        }
        $widgetMenu[] = [route('widgets.knowledge.index', $openWidget), 'Knowledge base', $is('widgets.knowledge.*')];
        $widgetMenu[] = [route('widgets.answers', $openWidget), 'Answers & gaps', $is('widgets.answers')];
        $widgetMenu[] = [route('widgets.engagement', $openWidget), 'Engagement', $is('widgets.engagement')];
        $widgetMenu[] = [route('widgets.flows', $openWidget), 'Chat flows', $is('widgets.flows')];
        $widgetMenu[] = [route('widgets.leadform', $openWidget), 'Lead form', $is('widgets.leadform')];
        $widgetMenu[] = [route('widgets.help', $openWidget), 'Help centre', $is('widgets.help')];
        $widgetMenu[] = [route('widgets.tools', $openWidget), 'AI tools', $is('widgets.tools')];
        $widgetMenu[] = [route('widgets.playground', $openWidget), 'Playground', $is('widgets.playground')];
        $widgetMenu[] = [route('widgets.embed', $openWidget), 'Embed code', $is('widgets.embed')];
    }
    $groups = $isAdmin
        ? [
            'Platform' => [
                [route('admin.dashboard'), 'Dashboard', $is('admin.dashboard'), 'M4 5h7v7H4zM13 5h7v4h-7zM13 11h7v8h-7zM4 14h7v5H4z', []],
                [route('admin.users'), 'Users', $is('admin.users*'), 'M16 19v-1a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v1M9.5 10a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7zM21 19v-1a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75', []],
                [route('admin.widgets'), 'Widgets', $is('admin.widgets*'), 'M8 10h8M8 14h5M5 4h14a1 1 0 0 1 1 1v11a1 1 0 0 1-1 1h-6l-5 4v-4H5a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1z', []],
                [route('admin.conversations'), 'Conversations', $is('admin.conversations*'), 'M7 8h10M7 12h6m-9 8 3-3h11a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v14z', []],
                [route('admin.reports'), 'Reports', $is('admin.reports*'), 'M4 20V10m6 10V4m6 16v-7m4 7H2', []],
                [route('admin.inbox'), 'Inbox', $is('admin.inbox*'), 'M3 13h4l2 3h6l2-3h4M3 13l2.5-8h13L21 13v6H3z', []],
            ],
            'Business' => [
                [route('admin.payments'), 'Payments', $is('admin.payments*'), 'M3 10h18M5 6h14a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2zm2 8h2m4 0h2', []],
                [route('admin.plans'), 'Plans', $is('admin.plans*'), 'M3 8h18M5 5h14a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2zm2 10h4', []],
                [route('admin.channels'), 'Channels', $is('admin.channels*'), 'M8.3 15a5.3 5.3 0 0 1 7.4 0M5.1 11.9a9.7 9.7 0 0 1 13.8 0M12 19h.01', []],
                [route('admin.broadcasts'), 'Broadcasts', $is('admin.broadcasts*'), 'M3 11v2a1 1 0 0 0 1 1h2l5 4V6L6 10H4a1 1 0 0 0-1 1zm13-3a5 5 0 0 1 0 8', []],
                [route('admin.consent'), 'Cookie consent', $is('admin.consent*'), 'M12 3l8 4v5c0 5-3.5 8-8 9-4.5-1-8-4-8-9V7l8-4zm-3 9 2 2 4-4', []],
            ],
            'System' => [
                [route('admin.system'), 'System', $is('admin.system*'), 'M4 6h16M4 12h16M4 18h16', []],
                [route('admin.search'), 'Search', $is('admin.search*'), 'M21 21l-4.35-4.35M10.5 18a7.5 7.5 0 1 1 0-15 7.5 7.5 0 0 1 0 15z', []],
                [route('admin.monitor'), 'Widget monitor', $is('admin.monitor*'), 'M3 12h4l3-8 4 16 3-8h4', []],
                [route('admin.marketing'), 'Marketing site', $is('admin.marketing*'), 'M4 5h16v10H4zM8 19h8M12 15v4', []],
                [route('admin.security'), 'Security & performance', $is('admin.security*'), 'M12 2 4 5v6c0 5 3.4 9.7 8 11 4.6-1.3 8-6 8-11V5l-8-3zm0 7v4m0 3h.01', []],
                [route('admin.privacy'), 'Privacy & GDPR', $is('admin.privacy*'), 'M12 3l8 4v5c0 5-3.5 8-8 9-4.5-1-8-4-8-9V7l8-4zm-3 9 2 2 4-4', []],
                [route('admin.translations'), 'Translations', $is('admin.translations*'), 'M3 5h12M9 3v2m1.05 9.5A18 18 0 0 1 6.4 9M12.5 18h7M15 21l5-10 5 10M4 15c2.5-1.3 4.6-3.3 6-6', []],
                [route('admin.changelog'), 'Changelog', $is('admin.changelog*'), 'M9 12h6m-6 4h6M7 4h10a2 2 0 0 1 2 2v14l-4-2-3 2-3-2-4 2V6a2 2 0 0 1 2-2z', []],
                [route('admin.activity'), 'Activity log', $is('admin.activity*'), 'M12 6v6l4 2m5-2a9 9 0 1 1-18 0 9 9 0 0 1 18 0z', []],
                [route('admin.settings'), 'Settings', $is('admin.settings*', 'admin.profile*'), 'M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm8-3a8 8 0 0 0-.2-1.7l2-1.6-2-3.4-2.4 1a8 8 0 0 0-2.9-1.7L14 2h-4l-.5 2.6a8 8 0 0 0-2.9 1.7l-2.4-1-2 3.4 2 1.6a8 8 0 0 0 0 3.4l-2 1.6 2 3.4 2.4-1a8 8 0 0 0 2.9 1.7L10 22h4l.5-2.6a8 8 0 0 0 2.9-1.7l2.4 1 2-3.4-2-1.6c.1-.6.2-1.1.2-1.7z', []],
            ],
        ]
        : [
            'Workspace' => [
                [route('dashboard'), 'Overview', $is('dashboard'), 'M4 5h7v7H4zM13 5h7v4h-7zM13 11h7v8h-7zM4 14h7v5H4z', []],
                [route('widgets.index'), 'Widgets', $is('widgets.*') && ! $is('widgets.booking*'), 'M8 10h8M8 14h5M5 4h14a1 1 0 0 1 1 1v11a1 1 0 0 1-1 1h-6l-5 4v-4H5a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1z', $widgetMenu],
                [route('conversations.index'), 'Conversations', $is('conversations.*'), 'M7 8h10M7 12h6m-9 8 3-3h11a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v14z', []],
                [route('tickets.index'), 'Tickets', $is('tickets.*'), 'M15 5v2m0 4v2m0 4v2M5 5h14a2 2 0 0 1 2 2v3a2 2 0 0 0 0 4v3a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-3a2 2 0 0 0 0-4V7a2 2 0 0 1 2-2z', []],
                [route('leads.index'), 'Leads', $is('leads.*'), 'M16 19v-1a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v1M9.5 10a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7zM21 19v-1a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75', []],
                [route('appointments.index'), 'Bookings', $is('appointments.*', 'widgets.booking*'), 'M8 2v3m8-3v3M3.5 9h17M5 4h14a2 2 0 0 1 2 2v13a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z', array_filter([
                    [route('appointments.index'), 'Agenda', $is('appointments.*') && request('view') !== 'week'],
                    [route('appointments.index', ['view' => 'week']), 'Week view', $is('appointments.*') && request('view') === 'week'],
                    $firstWidget ? [route('widgets.booking', $firstWidget), 'Booking setup', $is('widgets.booking*')] : null,
                ])],
            ],
            'Grow' => [
                [route('integrations.index'), 'Integrations', $is('integrations.*'), 'M14 7h3a3 3 0 0 1 0 6h-3M10 17H7a3 3 0 0 1 0-6h3M8 12h8', []],
                [route('analytics.index'), 'Analytics', $is('analytics.*'), 'M4 20V10m6 10V4m6 16v-7m4 7H2', []],
                [route('flows.index'), 'Chat flows', $is('flows.index') || $is('widgets.flows'), 'M6 3v6m0 0a3 3 0 1 0 0 6m0-6a3 3 0 1 1 0 6m0 0v6M18 9a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm0 0c0 6-12 3-12 9', []],
                [route('automations.index'), 'Automations', $is('automations.*'), 'M13 2 4 14h7l-1 8 9-12h-7l1-8z', array_merge([[route('automations.index'), 'All automations', $is('automations.index')]], array_map(fn ($t) => [route('automations.create', ['name' => $t[0], 'trigger' => $t[1], 'action' => $t[2], 'keywords' => $t[3] ?: null]), $t[0], $is('automations.create') && request('name') === $t[0]], \App\Models\Automation::TEMPLATES))],
                [route('settings.profile'), 'Settings', $is('settings.*'), 'M10.3 4.3a1.7 1.7 0 0 1 3.4 0l.2.9a1.7 1.7 0 0 0 2.5 1l.8-.4a1.7 1.7 0 0 1 2.4 2.4l-.4.8a1.7 1.7 0 0 0 1 2.5l.9.2a1.7 1.7 0 0 1 0 3.4l-.9.2a1.7 1.7 0 0 0-1 2.5l.4.8a1.7 1.7 0 0 1-2.4 2.4l-.8-.4a1.7 1.7 0 0 0-2.5 1l-.2.9a1.7 1.7 0 0 1-3.4 0l-.2-.9a1.7 1.7 0 0 0-2.5-1l-.8.4a1.7 1.7 0 0 1-2.4-2.4l.4-.8a1.7 1.7 0 0 0-1-2.5l-.9-.2a1.7 1.7 0 0 1 0-3.4l.9-.2a1.7 1.7 0 0 0 1-2.5l-.4-.8a1.7 1.7 0 0 1 2.4-2.4l.8.4a1.7 1.7 0 0 0 2.5-1l.2-.9zM12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6z', [
                    [route('settings.profile'), 'Profile', $is('settings.profile*', 'settings.password*')],
                    [route('settings.notifications'), 'Notifications', $is('settings.notifications*')],
                    [route('settings.twoFactor'), 'Security', $is('settings.twoFactor*')],
                    [route('settings.team'), 'Team', $is('settings.team*')],
                    [route('settings.billing'), 'Billing', $is('settings.billing*')],
                    [route('settings.cannedResponses'), 'Canned responses', $is('settings.cannedResponses*')],
                    [route('settings.apiKeys'), 'API keys', $is('settings.apiKeys*')],
                    [route('settings.developer'), 'Webhooks', $is('settings.developer*')],
                ]],
            ],
        ];
    $plan = $isAdmin ? null : rescue(fn () => auth()->user()->subscription?->plan?->name, null, false);
@endphp

{{-- Mobile top bar --}}
<header class="pfx-appbar lg:hidden sticky top-0 z-40 h-16 px-4 flex items-center gap-3 border-b border-white/[0.06] bg-[#070b16]/90 backdrop-blur-xl">
    <button @click="sidebarOpen = true" class="p-2 -ml-2 rounded-lg text-gray-400 hover:text-white hover:bg-white/5" aria-label="Open menu">
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" /></svg>
    </button>
    <a href="{{ route('dashboard') }}" class="flex items-center gap-2">@include('layouts.partials.logo', ['size' => 30])</a>
    <div class="ml-auto flex items-center gap-1">@include('layouts.partials.topbar-actions')</div>
</header>

<div x-show="sidebarOpen" x-transition.opacity x-cloak @click="sidebarOpen = false" class="lg:hidden fixed inset-0 z-40 bg-black/60 backdrop-blur-sm"></div>

{{-- Sidebar --}}
<aside class="pfx-sidebar fixed z-50 inset-y-0 left-0 w-72 p-3 -translate-x-full lg:translate-x-0 duration-300"
       :class="sidebarOpen && 'translate-x-0'" x-init="requestAnimationFrame(() => $el.classList.add('transition-transform'))">
    <div class="h-full flex flex-col rounded-3xl border border-white/[0.07] bg-gradient-to-b from-[#0d1428] to-[#080c18] shadow-2xl shadow-black/40 overflow-hidden">
        <div class="px-5 pt-5 pb-4 flex items-center justify-between">
            <a href="{{ $isAdmin ? route('admin.dashboard') : route('dashboard') }}" class="flex items-center gap-2.5 min-w-0">
                @include('layouts.partials.logo', ['size' => 34, 'tone' => 'white'])
            </a>
            <button @click="sidebarOpen = false" class="lg:hidden p-1.5 rounded-lg text-gray-500 hover:text-white" aria-label="Close menu">✕</button>
        </div>

        @unless($isAdmin)
        <div class="px-4">
            <a href="{{ route('widgets.create') }}" class="flex items-center justify-center gap-2 w-full py-2.5 rounded-2xl bg-gradient-to-r from-brand-600 to-accent-500 text-white text-sm font-semibold shadow-lg shadow-brand-600/25 hover:brightness-110 transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                New widget
            </a>
        </div>
        @endunless

        {{-- Keeps its scroll position between pages so the clicked item stays in view. --}}
        <nav id="pfx-nav" class="flex-1 overflow-y-auto px-3 py-4 space-y-5 custom-scrollbar"
             x-data @scroll.debounce.100ms="sessionStorage.setItem('pfx-nav-scroll', $el.scrollTop)">
            @foreach($groups as $group => $items)
            <div>
                <p class="px-3 mb-1.5 text-[10px] font-bold uppercase tracking-[0.16em] text-gray-500">{{ $group }}</p>
                <div class="space-y-0.5">
                    @foreach($items as [$url, $label, $on, $icon, $children])
                    <div x-data="{ open: {{ $on ? 'true' : 'false' }} }">
                        <div class="relative flex items-center">
                            <a href="{{ $url }}" @if($on && ! $children) aria-current="page" @endif
                               class="group relative flex-1 flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition {{ $on ? 'pfx-nav-on bg-white/[0.07] text-white' : 'text-gray-400 hover:text-white hover:bg-white/[0.04]' }}">
                                @if($on)<span class="absolute left-0 top-2 bottom-2 w-[3px] rounded-r-full bg-gradient-to-b from-brand-400 to-accent-400"></span>@endif
                                <span class="{{ $on ? 'text-accent-300' : 'text-gray-500 group-hover:text-gray-300' }}">{!! $i($icon) !!}</span>
                                {{ $label }}
                            </a>
                            @if($children)
                            <button type="button" @click="open = !open" class="absolute right-1.5 p-1.5 rounded-lg text-gray-500 hover:text-white hover:bg-white/5" :aria-expanded="open" aria-label="Show {{ $label }} pages">
                                <svg class="w-4 h-4 transition-transform {{ $on ? 'rotate-90' : '' }}" :class="open && 'rotate-90'" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m9 5 7 7-7 7"/></svg>
                            </button>
                            @endif
                        </div>
                        @if($children)
                        <div x-show="open" x-collapse @if(! $on) x-cloak @endif class="mt-0.5 ml-[1.35rem] pl-3 border-l border-white/[0.08] space-y-0.5">
                            @foreach($children as [$curl, $clabel, $con])
                            @if($con === null)
                            <a href="{{ $curl }}" x-data="{ h: location.hash || '#appearance' }" @hashchange.window="h = location.hash"
                               :aria-current="h === '{{ strstr($curl, '#') }}' && 'page'"
                               :class="h === '{{ strstr($curl, '#') }}' ? 'text-white bg-white/[0.06] font-semibold' : 'text-gray-400 hover:text-white hover:bg-white/[0.04]'"
                               class="block px-3 py-2 rounded-lg text-[13px] transition">{{ $clabel }}</a>
                            @else
                            <a href="{{ $curl }}" @if($con) aria-current="page" @endif
                               class="block px-3 py-2 rounded-lg text-[13px] transition {{ $con ? 'text-white bg-white/[0.06] font-semibold' : 'text-gray-400 hover:text-white hover:bg-white/[0.04]' }}">{{ $clabel }}</a>
                            @endif
                            @endforeach
                        </div>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
            @endforeach
        </nav>
        <script>
            (function () {
                var n = document.getElementById('pfx-nav');
                n.scrollTop = +sessionStorage.getItem('pfx-nav-scroll') || 0;
                var on = n.querySelector('[aria-current=page]');
                if (on) {
                    var r = on.getBoundingClientRect(), b = n.getBoundingClientRect();
                    if (r.top < b.top || r.bottom > b.bottom) n.scrollTop += r.top - b.top - b.height / 2 + r.height / 2;
                }
            })();
        </script>

        <div class="p-3 border-t border-white/[0.06]">
            @php $pfxReal = \App\Support\Workspace::realUser(); $pfxWs = $pfxReal && ! $isAdmin ? \App\Support\Workspace::for($pfxReal) : []; @endphp
            @if(! $isAdmin)
            <div x-data="{ open: false }" class="relative mb-2">
                <button type="button" @click="open = !open" class="w-full flex items-center gap-2 px-3 py-2 rounded-xl bg-white/[0.04] hover:bg-white/[0.07] text-left">
                    <span class="grid place-items-center w-6 h-6 rounded-md bg-accent-500/20 text-accent-300 text-[11px] font-bold">{{ mb_strtoupper(mb_substr(auth()->user()->company_name ?: auth()->user()->name, 0, 1)) }}</span>
                    <span class="flex-1 min-w-0"><span class="block text-xs font-semibold text-white truncate">{{ auth()->user()->company_name ?: auth()->user()->name }}</span>
                        <span class="block text-[10px] text-gray-500">{{ app()->bound('pfx.workspace_role') ? \App\Support\Workspace::roleLabel(app('pfx.workspace_role')) : 'Your workspace' }}</span></span>
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M8 9l4-4 4 4M16 15l-4 4-4-4"/></svg>
                </button>
                <div x-show="open" x-cloak @click.outside="open = false" class="absolute bottom-full mb-2 left-0 right-0 rounded-xl border border-white/10 bg-[#0d1a33] p-1.5 shadow-2xl z-10">
                    @foreach($pfxWs as $ws)
                    <form method="POST" action="{{ route('workspaces.switch') }}">@csrf<input type="hidden" name="workspace" value="{{ $ws['id'] }}">
                        <button class="w-full flex items-center justify-between gap-2 px-2.5 py-2 rounded-lg text-left text-xs hover:bg-white/5 {{ $ws['id'] === auth()->id() ? 'text-white font-semibold' : 'text-gray-300' }}">
                            <span class="truncate">{{ $ws['name'] }}</span><span class="shrink-0 text-[10px] text-gray-500">{{ \App\Support\Workspace::roleLabel($ws['role']) }}</span>
                        </button>
                    </form>
                    @endforeach
                    <form method="POST" action="{{ route('workspaces.store') }}" class="mt-1 pt-1.5 border-t border-white/5 flex gap-1.5">@csrf
                        <input name="name" required maxlength="80" placeholder="New workspace name" class="flex-1 min-w-0 bg-white/5 border border-white/10 rounded-lg px-2 py-1.5 text-xs text-white">
                        <button class="px-2.5 rounded-lg bg-brand-500 text-white text-xs font-semibold">+</button>
                    </form>
                </div>
            </div>
            @endif
            @if($plan)
            <a href="{{ route('settings.billing') }}" class="block mb-2 px-3 py-2.5 rounded-xl bg-brand-500/10 border border-brand-500/20 text-xs">
                <span class="text-gray-400">Plan</span> <span class="font-semibold text-white">{{ $plan }}</span>
            </a>
            @endif
            <div class="flex items-center gap-3 px-2 py-1.5">
                <img src="{{ ($pfxReal ?? auth()->user())->avatarUrl() }}" alt="" class="w-9 h-9 rounded-xl object-cover shrink-0">
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold text-white truncate">{{ ($pfxReal ?? auth()->user())->name }}</p>
                    <p class="text-xs text-gray-500 truncate">{{ ($pfxReal ?? auth()->user())->email }}</p>
                </div>
            </div>
        </div>
    </div>
</aside>
