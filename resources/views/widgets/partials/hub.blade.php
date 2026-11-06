@php
    $kb = $widget->activeKnowledgeBase;
    $sources = $kb ? $kb->trainingSources()->count() : 0;
    $ready = $kb ? $kb->trainingSources()->where('status', 'completed')->count() : 0;
    $tested = \Illuminate\Support\Facades\Cache::get('pfx:tested:' . $widget->id);
    $steps = [
        ['Train with real knowledge', $ready > 0, $ready ? "Indexed {$ready} of {$sources} sources." : 'Add your website, files or Q&A.', route('widgets.knowledge.index', $widget)],
        ['Test in the Playground', (bool) $tested, $tested ? 'Tested ' . \Carbon\Carbon::parse($tested)->diffForHumans() . '.' : 'Chat with it before visitors do.', route('widgets.playground', $widget)],
        ['Go live', (bool) $widget->is_active, $widget->is_active ? 'Live and ready on your site.' : 'Switch it on and add the embed code.', route('widgets.embed', $widget)],
    ];
    $done = collect($steps)->where(1, true)->count();
    $card = 'group rounded-2xl border border-white/5 bg-gray-900/50 p-4 hover:border-brand-500/40 hover:bg-white/[0.03] transition';
    $snippet = '<script src="' . url('/widget.js') . '" data-widget-id="' . $widget->uuid . '" async></script>';
    $core = [
        ['📚', 'Knowledge base', 'Add URLs, sitemaps or files; see what was indexed.', route('widgets.knowledge.index', $widget)],
        ['💬', 'Conversations', 'Every visitor session: what they asked, what the AI said.', route('conversations.index', ['widget_id' => $widget->id])],
        ['🎯', 'Leads', 'Leads captured by this widget, scored and ready.', route('leads.index', ['widget_id' => $widget->id])],
        ['▶️', 'Playground', 'Chat with it for real (does not count toward billing).', route('widgets.playground', $widget)],
    ];
    $grow = [
        ['✨', 'Answers & gaps', 'Hand-written answers and questions it could not answer.', route('widgets.answers', $widget)],
        ['🧭', 'Engagement', 'Behaviour triggers, AI notice, action cards, hours.', route('widgets.engagement', $widget)],
        ['🔀', 'Chat flows', 'Step-by-step conversations on a canvas.', route('widgets.flows', $widget)],
        ['📝', 'Lead form', 'Fields and when to ask for details.', route('widgets.leadform', $widget)],
        ['🎨', 'Appearance', 'Colours, launcher, bubbles, sizes with live preview.', route('widgets.edit', $widget) . '#appearance'],
        ['🧠', 'AI behaviour', 'Prompt, tone, confidence and reply when unsure.', route('widgets.edit', $widget) . '#ai'],
        ['📅', 'Bookings', 'Services, team diaries, calendar sync.', route('widgets.booking', $widget)],
        ['🔌', 'AI tools', 'WooCommerce orders, coupons and MCP servers.', route('widgets.tools', $widget)],
        ['🧪', 'A/B tests', 'Test greetings and styles on a slice of visitors.', route('widgets.edit', $widget) . '#ab-testing'],
        ['📖', 'Help centre', 'Public articles visitors can search.', route('widgets.help', $widget)],
        ['🔗', 'Integrations', 'CRM, calendars, channels and alerts.', route('integrations.index', ['widget' => $widget->id])],
    ];
@endphp
<div class="grid xl:grid-cols-[minmax(0,1fr)_360px] gap-6 mb-8">
    <div class="space-y-6 min-w-0">
        <section class="rounded-2xl border border-white/5 bg-gray-900/50 overflow-hidden">
            <div class="p-5 sm:p-6 flex flex-col sm:flex-row sm:items-center gap-4">
                <div class="flex-1">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Widget workspace</p>
                    <p class="mt-1 text-sm text-gray-400">Train it, review what it knows, test it, then go live.</p>
                    <div class="mt-3 flex flex-wrap gap-2 text-[11px] font-semibold">
                        <span class="px-2 py-0.5 rounded-full {{ $widget->is_active ? 'bg-emerald-500/15 text-emerald-400' : 'bg-white/5 text-gray-400' }}">{{ $widget->is_active ? '● Live' : '○ Off' }}</span>
                        <span class="px-2 py-0.5 rounded-full bg-white/5 text-gray-300">{{ round(($widget->min_confidence ?? config('ai.response.confidence_threshold', 0.5)) * 100) }}% confidence threshold</span>
                        <span class="px-2 py-0.5 rounded-full bg-white/5 text-gray-300">{{ $ready }}/{{ $sources }} indexed</span>
                    </div>
                </div>
                <div class="sm:w-56 rounded-xl border border-white/5 bg-white/[0.03] p-4">
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-500">Setup progress</p>
                    <p class="mt-1 text-2xl font-bold text-white">{{ $done }}/3 <span class="text-xs font-normal text-gray-400">{{ $done === 3 ? 'Ready' : 'In progress' }}</span></p>
                    <div class="mt-2 h-1.5 rounded-full bg-white/10"><div class="h-full rounded-full bg-brand-500" style="width: {{ $done / 3 * 100 }}%"></div></div>
                </div>
            </div>
            <div class="grid sm:grid-cols-3 border-t border-white/5">
                @foreach($steps as $i => [$t, $ok, $d, $u])
                <a href="{{ $u }}" class="flex gap-3 p-4 {{ $i ? 'sm:border-l border-t sm:border-t-0 border-white/5' : '' }} hover:bg-white/[0.02]">
                    <span class="grid place-items-center w-7 h-7 shrink-0 rounded-full text-xs font-bold {{ $ok ? 'bg-emerald-500 text-white' : 'bg-white/10 text-gray-400' }}">{{ $ok ? '✓' : $i + 1 }}</span>
                    <span><span class="block text-sm font-semibold text-white">{{ $t }}</span><span class="block text-xs text-gray-500">{{ $d }}</span></span>
                </a>
                @endforeach
            </div>
            <div class="border-t border-white/5 p-4 sm:px-6 flex flex-col sm:flex-row sm:items-center gap-3">
                <div class="flex-1"><p class="text-sm font-semibold text-white">Review live conversations</p><p class="text-xs text-gray-500">Watch real visitor sessions and keep improving the answers.</p></div>
                <a href="{{ route('conversations.index', ['widget_id' => $widget->id]) }}" class="shrink-0 px-4 py-2 rounded-xl bg-white text-gray-900 text-xs font-bold text-center">Review live conversations →</a>
            </div>
        </section>

        <section>
            <h3 class="mb-3 text-sm font-semibold text-white">Core workflow</h3>
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                @foreach($core as [$i, $t, $d, $u])
                <a href="{{ $u }}" class="{{ $card }}"><span class="text-xl">{{ $i }}</span><span class="mt-2 block text-sm font-semibold text-white">{{ $t }}</span><span class="mt-1 block text-xs text-gray-500">{{ $d }}</span></a>
                @endforeach
            </div>
        </section>
        <section>
            <h3 class="mb-3 text-sm font-semibold text-white">Optimise and grow</h3>
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                @foreach($grow as [$i, $t, $d, $u])
                <a href="{{ $u }}" class="{{ $card }}"><span class="text-xl">{{ $i }}</span><span class="mt-2 block text-sm font-semibold text-white">{{ $t }}</span><span class="mt-1 block text-xs text-gray-500">{{ $d }}</span></a>
                @endforeach
            </div>
        </section>
    </div>

    <aside class="space-y-4">
        <section class="rounded-2xl border border-white/5 bg-gray-900/50 p-5">
            <p class="text-sm font-semibold text-white">Launch snapshot</p>
            <div class="mt-3 grid grid-cols-2 gap-3">
                <div class="rounded-xl bg-white/[0.03] p-3"><p class="text-[11px] text-gray-500">Knowledge</p><p class="text-lg font-bold text-white">{{ $ready }}/{{ $sources }}</p></div>
                <div class="rounded-xl bg-white/[0.03] p-3"><p class="text-[11px] text-gray-500">Publishing</p><p class="text-lg font-bold {{ $widget->is_active ? 'text-emerald-400' : 'text-gray-400' }}">{{ $widget->is_active ? 'Live' : 'Off' }}</p></div>
                <div class="rounded-xl bg-white/[0.03] p-3"><p class="text-[11px] text-gray-500">Confidence</p><p class="text-lg font-bold text-white">{{ round(($widget->min_confidence ?? config('ai.response.confidence_threshold', 0.6)) * 100) }}%</p></div>
                <div class="rounded-xl bg-white/[0.03] p-3"><p class="text-[11px] text-gray-500">Open gaps</p><p class="text-lg font-bold text-white">{{ \App\Models\KnowledgeGap::where('widget_id', $widget->id)->where('status', 'open')->count() }}</p></div>
            </div>
        </section>
        <section class="rounded-2xl border border-emerald-500/20 bg-emerald-500/[0.04] p-5" x-data="{ copied: false }">
            <div class="flex items-center justify-between"><p class="text-sm font-semibold text-white">Embed on your site</p>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $widget->is_active ? 'bg-emerald-500/15 text-emerald-400' : 'bg-white/5 text-gray-400' }}">{{ $widget->is_active ? '● Live' : 'Off' }}</span></div>
            <p class="mt-1 text-xs text-gray-500">Paste before &lt;/body&gt; on every page that should show the widget.</p>
            <pre class="mt-3 rounded-xl bg-black/40 border border-white/10 p-3 text-[11px] text-emerald-300 whitespace-pre-wrap break-all">{{ $snippet }}</pre>
            <button type="button" @click="navigator.clipboard.writeText(@js($snippet)); copied = true; setTimeout(() => copied = false, 2000)" class="mt-2 px-3 py-1.5 rounded-lg bg-white/5 hover:bg-white/10 text-xs font-semibold text-white" x-text="copied ? 'Copied!' : 'Copy snippet'">Copy snippet</button>
        </section>
        <section class="rounded-2xl border border-white/5 bg-gray-900/50 p-4">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-500">Loader URL</p>
            <p class="mt-1 text-xs text-gray-200 break-all font-mono">{{ url('/widget.js') }}</p>
            <p class="mt-1 text-[11px] text-gray-500">Widget ID: <span class="font-mono text-gray-300">{{ $widget->uuid }}</span></p>
        </section>
        <section class="rounded-2xl border border-white/5 bg-gray-900/50 p-5">
            <p class="text-sm font-semibold text-white">Behaviour and presentation</p>
            @foreach([['Voice and prompt', $widget->system_prompt ? 'Custom instructions are guiding the AI.' : 'Using the default instructions.', 'Settings', route('widgets.edit', $widget) . '#ai'], ['Appearance and feel', 'Colours, launcher, persona and bubbles with live preview.', 'Customise', route('widgets.edit', $widget) . '#appearance'], ['Leads and conversations', 'Jump into live sessions and captured leads.', 'Inbox', route('conversations.index', ['widget_id' => $widget->id])]] as [$t, $d, $l, $u])
            <div class="mt-3 pt-3 border-t border-white/5 flex gap-3"><div class="flex-1"><p class="text-sm font-medium text-gray-200">{{ $t }}</p><p class="text-xs text-gray-500">{{ $d }}</p></div><a href="{{ $u }}" class="shrink-0 text-xs font-semibold text-brand-400">{{ $l }} ↗</a></div>
            @endforeach
        </section>
        <section class="rounded-2xl border border-white/5 bg-gray-900/50 p-5">
            <p class="text-sm font-semibold text-white">System prompt</p>
            <p class="mt-2 text-xs text-gray-400 whitespace-pre-line line-clamp-6">{{ $widget->system_prompt ?: 'No custom instructions yet.' }}</p>
            <a href="{{ route('widgets.edit', $widget) }}#ai" class="mt-2 inline-block text-xs font-semibold text-brand-400">Edit →</a>
        </section>
    </aside>
</div>
