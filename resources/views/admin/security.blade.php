@extends('layouts.admin')
@section('title', 'Security & performance')
@section('page-title', 'Security & performance')
@section('content')
@php
    $field = 'w-full bg-white/5 border border-white/10 rounded-xl px-3.5 py-2.5 text-sm text-white';
    $card = 'rounded-2xl border border-white/5 bg-gray-900/50 p-5';
    $dot = ['ok' => 'bg-emerald-400', 'warn' => 'bg-amber-400', 'fail' => 'bg-rose-500'];
    $mb = fn ($b) => $b === null || $b === false ? '–' : ($b >= 1073741824 ? round($b / 1073741824, 1) . ' GB' : round($b / 1048576, 1) . ' MB');
    $types = ['login_failed' => 'Failed logins', 'lockout' => 'Lockouts', 'auto_ban' => 'Auto-bans', 'blocked' => 'Blocked requests', 'bad_bot' => 'Scanner bots'];
@endphp
<div x-data="{ tab: '{{ request('tab', 'overview') }}' }" class="space-y-6">
    <div class="flex flex-wrap gap-2">
        @foreach(['overview' => 'Overview', 'firewall' => 'Firewall', 'login' => 'Login protection', 'events' => 'Security events', 'performance' => 'Performance', 'maintenance' => 'Maintenance'] as $k => $l)
        <button type="button" @click="tab = '{{ $k }}'" :class="tab === '{{ $k }}' ? 'bg-white text-gray-900' : 'bg-white/5 text-gray-300 hover:bg-white/10'" class="px-4 py-2 rounded-xl text-sm font-semibold">{{ $l }}</button>
        @endforeach
    </div>

    {{-- Overview --}}
    <div x-show="tab === 'overview'" class="grid xl:grid-cols-[320px_minmax(0,1fr)] gap-6">
        <div class="space-y-4">
            <section class="{{ $card }} text-center">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Security score</p>
                <p class="mt-2 text-5xl font-extrabold {{ $score >= 85 ? 'text-emerald-400' : ($score >= 60 ? 'text-amber-300' : 'text-rose-400') }}">{{ $score }}</p>
                <div class="mt-3 h-2 rounded-full bg-white/10"><div class="h-full rounded-full {{ $score >= 85 ? 'bg-emerald-400' : ($score >= 60 ? 'bg-amber-300' : 'bg-rose-500') }}" style="width: {{ $score }}%"></div></div>
            </section>
            <section class="{{ $card }}">
                <p class="text-sm font-semibold text-white">Last 24 hours</p>
                <ul class="mt-3 space-y-2 text-sm">
                    @foreach($types as $k => $l)<li class="flex justify-between"><span class="text-gray-400">{{ $l }}</span><span class="font-semibold text-white">{{ $stats[$k] ?? 0 }}</span></li>@endforeach
                </ul>
            </section>
            <section class="{{ $card }}">
                <p class="text-sm font-semibold text-white">Speed</p>
                <ul class="mt-3 space-y-2 text-sm">
                    <li class="flex justify-between"><span class="text-gray-400">Database</span><span class="text-white">{{ $perf['db_ms'] ?? '–' }} ms</span></li>
                    <li class="flex justify-between"><span class="text-gray-400">Cache</span><span class="text-white">{{ $perf['cache_ms'] }} ms</span></li>
                    <li class="flex justify-between"><span class="text-gray-400">OPcache</span><span class="{{ $perf['opcache'] ? 'text-emerald-400' : 'text-amber-300' }}">{{ $perf['opcache'] ? 'On · ' . $perf['opcache_hit'] . '% hits' : 'Off' }}</span></li>
                    <li class="flex justify-between"><span class="text-gray-400">Queue</span><span class="text-white">{{ $perf['pending'] }} waiting · {{ $perf['failed'] }} failed</span></li>
                </ul>
            </section>
        </div>
        <section class="{{ $card }}">
            <p class="text-sm font-semibold text-white">Security checklist</p>
            <ul class="mt-3 divide-y divide-white/5">
                @foreach($checks as [$label, $state, $detail, $fix])
                <li class="py-3 flex gap-3">
                    <span class="mt-1.5 w-2.5 h-2.5 shrink-0 rounded-full {{ $dot[$state] }}"></span>
                    <div class="min-w-0 flex-1"><p class="text-sm font-medium text-gray-100">{{ $label }}</p><p class="text-xs text-gray-500 break-words">{{ $detail }}</p></div>
                    @if($state !== 'ok')<p class="text-xs text-amber-200/80 max-w-[45%] text-right">{{ $fix }}</p>@endif
                </li>
                @endforeach
            </ul>
        </section>
    </div>

    {{-- Firewall + login settings share one form --}}
    <form method="POST" action="{{ route('admin.security.save') }}" x-show="tab === 'firewall' || tab === 'login'" class="space-y-6">
        @csrf @method('PUT')
        <div x-show="tab === 'firewall'" class="grid lg:grid-cols-2 gap-6">
            <section class="{{ $card }} space-y-3">
                <p class="text-sm font-semibold text-white">Blocked IPs</p>
                <p class="text-xs text-gray-500">One per line. Single addresses or ranges (CIDR), e.g. 203.0.113.7 or 198.51.100.0/24. Blocked visitors get "Access denied" on the whole site, the API and the widget.</p>
                <textarea name="blocked_ips" rows="8" class="{{ $field }} font-mono">{{ implode("\n", $s['blocked_ips']) }}</textarea>
                <p class="text-xs text-gray-500">Your IP: <span class="font-mono text-gray-300">{{ request()->ip() }}</span></p>
            </section>
            <section class="{{ $card }} space-y-3">
                <p class="text-sm font-semibold text-white">Always allowed</p>
                <p class="text-xs text-gray-500">These are never blocked or auto-banned. Add your office or your own IP.</p>
                <textarea name="allowed_ips" rows="4" class="{{ $field }} font-mono">{{ implode("\n", $s['allowed_ips']) }}</textarea>
                @foreach([['block_bad_bots', 'Block hacking tools', 'Stops known scanners (sqlmap, Nikto, WPScan, Nuclei and others).'], ['headers', 'Security headers', 'Blocks clickjacking and content sniffing; limits referrer data. The chat widget stays embeddable.'], ['hsts', 'Force HTTPS (HSTS)', 'Browsers refuse plain HTTP for a year. Only turn on once HTTPS works everywhere.']] as [$n, $t, $d])
                <label class="flex gap-3 rounded-xl bg-white/[0.03] p-3"><input type="checkbox" name="{{ $n }}" value="1" @checked($s[$n]) class="mt-1 rounded"><span><span class="block text-sm text-white">{{ $t }}</span><span class="block text-xs text-gray-500">{{ $d }}</span></span></label>
                @endforeach
            </section>
        </div>
        <div x-show="tab === 'login'" class="grid lg:grid-cols-2 gap-6">
            <section class="{{ $card }} space-y-3">
                <p class="text-sm font-semibold text-white">Brute-force protection</p>
                <div class="grid grid-cols-2 gap-3">
                    <label class="text-xs text-gray-400">Lock after (failed tries)<input type="number" name="login_max" value="{{ $s['login_max'] }}" min="3" max="50" class="{{ $field }} mt-1"></label>
                    <label class="text-xs text-gray-400">Lock for (minutes)<input type="number" name="login_decay" value="{{ $s['login_decay'] }}" min="1" max="1440" class="{{ $field }} mt-1"></label>
                </div>
                <label class="flex gap-3 rounded-xl bg-white/[0.03] p-3"><input type="checkbox" name="auto_ban" value="1" @checked($s['auto_ban']) class="mt-1 rounded"><span><span class="block text-sm text-white">Auto-ban repeat attackers</span><span class="block text-xs text-gray-500">Ban an IP from the whole site after several lockouts in an hour.</span></span></label>
                <div class="grid grid-cols-2 gap-3">
                    <label class="text-xs text-gray-400">Ban after (lockouts per hour)<input type="number" name="ban_after" value="{{ $s['ban_after'] }}" min="1" max="20" class="{{ $field }} mt-1"></label>
                    <label class="text-xs text-gray-400">Ban for (minutes)<input type="number" name="ban_minutes" value="{{ $s['ban_minutes'] }}" min="5" max="43200" class="{{ $field }} mt-1"></label>
                </div>
            </section>
            <section class="{{ $card }} space-y-3">
                <p class="text-sm font-semibold text-white">Admin accounts</p>
                <label class="flex gap-3 rounded-xl bg-white/[0.03] p-3"><input type="checkbox" name="force_admin_2fa" value="1" @checked($s['force_admin_2fa']) class="mt-1 rounded"><span><span class="block text-sm text-white">Require two-factor for admins</span><span class="block text-xs text-gray-500">Admins without 2FA are sent to set it up before they can use the admin area.</span></span></label>
                <p class="text-xs text-gray-500">Failed admin and customer logins are both protected and logged under Security events.</p>
            </section>
        </div>
        <div class="flex justify-end"><button class="px-5 py-2.5 rounded-xl bg-brand-500 hover:bg-brand-400 text-white text-sm font-semibold">Save security settings</button></div>
    </form>

    {{-- Events --}}
    <div x-show="tab === 'events'" class="grid xl:grid-cols-[minmax(0,1fr)_300px] gap-6">
        <section class="{{ $card }} !p-0 overflow-hidden">
            <form method="GET" class="p-4 flex flex-wrap gap-2 border-b border-white/5"><input type="hidden" name="tab" value="events">
                <select name="type" class="bg-white/5 border border-white/10 rounded-lg px-3 py-1.5 text-xs text-white"><option value="">All events</option>@foreach($types as $k => $l)<option value="{{ $k }}" @selected(request('type') === $k)>{{ $l }}</option>@endforeach</select>
                <input name="ip" value="{{ request('ip') }}" placeholder="Filter by IP" class="bg-white/5 border border-white/10 rounded-lg px-3 py-1.5 text-xs text-white">
                <button class="px-3 py-1.5 rounded-lg bg-white/10 text-xs text-white">Filter</button>
            </form>
            <div class="overflow-x-auto"><table class="w-full text-xs">
                <thead class="text-gray-500 text-left"><tr><th class="px-4 py-2">When</th><th class="px-4 py-2">Event</th><th class="px-4 py-2">IP</th><th class="px-4 py-2">Email / detail</th><th></th></tr></thead>
                <tbody class="divide-y divide-white/5">
                @forelse($events as $e)
                <tr><td class="px-4 py-2 text-gray-400 whitespace-nowrap">{{ \Carbon\Carbon::parse($e->created_at)->diffForHumans(short: true) }}</td>
                    <td class="px-4 py-2 text-gray-200">{{ $types[$e->type] ?? $e->type }}</td>
                    <td class="px-4 py-2 font-mono text-gray-300">{{ $e->ip }}</td>
                    <td class="px-4 py-2 text-gray-400 max-w-xs truncate">{{ $e->email ?: $e->detail }}</td>
                    <td class="px-4 py-2">@if($e->ip)<form method="POST" action="{{ route('admin.security.ip') }}">@csrf<input type="hidden" name="ip" value="{{ $e->ip }}"><input type="hidden" name="do" value="block"><button class="text-rose-300 hover:text-rose-200">Block</button></form>@endif</td></tr>
                @empty
                <tr><td colspan="5" class="px-4 py-10 text-center text-gray-500">No security events yet.</td></tr>
                @endforelse
                </tbody></table></div>
        </section>
        <section class="{{ $card }}">
            <p class="text-sm font-semibold text-white">Most active IPs (7 days)</p>
            <ul class="mt-3 space-y-2 text-xs">
                @forelse($topIps as $t)
                <li class="flex items-center gap-2"><span class="font-mono text-gray-300 flex-1 truncate">{{ $t->ip }}</span><span class="text-gray-500">{{ $t->c }}</span>
                    <form method="POST" action="{{ route('admin.security.ip') }}">@csrf<input type="hidden" name="ip" value="{{ $t->ip }}">
                        @if(\App\Support\Security::isBanned($t->ip))<input type="hidden" name="do" value="unban"><button class="text-emerald-300">Unblock</button>@else<input type="hidden" name="do" value="block"><button class="text-rose-300">Block</button>@endif</form></li>
                @empty<li class="text-gray-500">Nothing yet.</li>@endforelse
            </ul>
        </section>
    </div>

    {{-- Performance --}}
    <div x-show="tab === 'performance'" class="grid lg:grid-cols-3 gap-6">
        <section class="{{ $card }}">
            <p class="text-sm font-semibold text-white">Server</p>
            <ul class="mt-3 space-y-2 text-sm">
                @foreach(['PHP' => $perf['php'], 'Laravel' => $perf['laravel'], 'Memory limit' => $perf['memory_limit'], 'Peak memory (this page)' => $perf['peak'] . ' MB', 'Max execution' => $perf['max_exec'] . 's', 'Upload limit' => $perf['upload'], 'Free disk' => $mb($perf['disk_free'])] as $k => $v)
                <li class="flex justify-between gap-2"><span class="text-gray-400">{{ $k }}</span><span class="text-white">{{ $v }}</span></li>
                @endforeach
            </ul>
        </section>
        <section class="{{ $card }}">
            <p class="text-sm font-semibold text-white">App caches</p>
            <ul class="mt-3 space-y-2 text-sm">
                @foreach(['Config cached' => $perf['config_cached'], 'Routes cached' => $perf['routes_cached'], 'OPcache' => $perf['opcache']] as $k => $v)
                <li class="flex justify-between"><span class="text-gray-400">{{ $k }}</span><span class="{{ $v ? 'text-emerald-400' : 'text-amber-300' }}">{{ $v ? 'Yes' : 'No' }}</span></li>
                @endforeach
                @foreach(['Compiled templates' => $perf['views'], 'Cache store' => $perf['cache'] . ' · ' . $perf['cache_ms'] . ' ms', 'Sessions' => $perf['session'], 'Queue' => $perf['queue'] . ' · ' . $perf['pending'] . ' waiting'] as $k => $v)
                <li class="flex justify-between gap-2"><span class="text-gray-400">{{ $k }}</span><span class="text-white">{{ $v }}</span></li>
                @endforeach
            </ul>
            @if(! $perf['config_cached'])<p class="mt-3 text-xs text-amber-200/80">Tip: run "Speed up app" in Maintenance after deploying.</p>@endif
        </section>
        <section class="{{ $card }}">
            <p class="text-sm font-semibold text-white">Database · {{ $perf['db'] }}</p>
            <p class="text-xs text-gray-500">{{ $perf['db_ms'] }} ms per query @if($perf['db_size']) · {{ $mb($perf['db_size']) }} @endif</p>
            <ul class="mt-3 space-y-2 text-sm">
                @foreach($perf['tables'] as $tb => $n)<li class="flex justify-between"><span class="text-gray-400 font-mono text-xs">{{ $tb }}</span><span class="text-white">{{ number_format($n) }}</span></li>@endforeach
            </ul>
            <p class="mt-3 text-xs text-gray-500">Logs {{ $mb($perf['logs']) }} · uploads {{ $mb($perf['uploads']) }}</p>
        </section>
    </div>

    {{-- Maintenance --}}
    <div x-show="tab === 'maintenance'" class="grid sm:grid-cols-2 xl:grid-cols-3 gap-4">
        @foreach([
            ['optimize', 'Speed up app', 'Cache config, routes and events. Run after each deploy.', false],
            ['views', 'Pre-compile templates', 'First page loads get faster.', false],
            ['clear', 'Clear all caches', 'Use after changing .env or when something looks stale.', false],
            ['db', 'Optimise database', 'Compacts tables and refreshes query statistics.', false],
            ['logs', 'Trim log files', 'Deletes logs older than 7 days and empties logs over 5 MB.', false],
            ['prune', 'Prune old records', 'Resolved monitor events (30 days), activity (180 days), security events (90 days).', true],
            ['retry', 'Retry failed jobs', 'Puts every failed background job back in the queue.', false],
            ['flush_failed', 'Delete failed jobs', 'Removes the failed job list for good.', true],
            ['sessions', 'Sign everyone out', 'Ends every session, yours included. Use after a suspected breach.', true],
        ] as [$a, $t, $d, $danger])
        <form method="POST" action="{{ route('admin.security.action') }}" class="{{ $card }} flex flex-col" @if($danger) onsubmit="return confirm('{{ $t }}? This cannot be undone.')" @endif>@csrf
            <input type="hidden" name="action" value="{{ $a }}">
            <p class="text-sm font-semibold text-white">{{ $t }}</p><p class="mt-1 text-xs text-gray-500 flex-1">{{ $d }}</p>
            <button class="mt-4 self-start px-4 py-2 rounded-lg text-xs font-semibold {{ $danger ? 'bg-rose-500/15 text-rose-300 hover:bg-rose-500/25' : 'bg-white/10 text-white hover:bg-white/15' }}">Run</button>
        </form>
        @endforeach
    </div>
</div>
@endsection
