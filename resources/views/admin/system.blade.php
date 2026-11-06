@extends('layouts.admin')
@section('title', 'Admin: System Health')
@section('page-title', 'System Health')
@section('content')
{{-- Go-live health checks --}}
<section class="mb-6 rounded-2xl border border-white/5 bg-gray-900/50 p-5">
    @php $bad = collect($checks)->where(1, 'fail')->count(); $warn = collect($checks)->where(1, 'warn')->count(); @endphp
    <div class="flex flex-wrap items-center justify-between gap-2">
        <h3 class="text-sm font-semibold text-white">Site health</h3>
        <span class="px-2.5 py-1 rounded-full text-xs font-semibold {{ $bad ? 'bg-rose-500/15 text-rose-400' : ($warn ? 'bg-amber-500/15 text-amber-400' : 'bg-emerald-500/15 text-emerald-400') }}">
            {{ $bad ? $bad . ' problem' . ($bad > 1 ? 's' : '') : ($warn ? $warn . ' warning' . ($warn > 1 ? 's' : '') : 'All good') }}
        </span>
    </div>
    <ul class="mt-4 grid sm:grid-cols-2 xl:grid-cols-3 gap-2">
        @foreach($checks as [$label, $state, $detail])
        <li class="flex items-start gap-2.5 rounded-xl border border-white/5 bg-white/[0.02] p-3">
            <span class="mt-0.5 grid place-items-center w-5 h-5 shrink-0 rounded-full text-[11px] font-bold {{ $state === 'ok' ? 'bg-emerald-500/15 text-emerald-400' : ($state === 'warn' ? 'bg-amber-500/15 text-amber-400' : 'bg-rose-500/15 text-rose-400') }}">{{ $state === 'ok' ? '✓' : '!' }}</span>
            <span class="min-w-0"><span class="block text-sm text-white">{{ $label }}</span><span class="block text-xs text-gray-500 break-words">{{ $detail }}</span></span>
        </li>
        @endforeach
    </ul>
</section>

<div x-data="{
    artisanCmd: '',
    artisanOutput: '',
    artisanLoading: false,
    artisanOk: null,
    logsOutput: '',
    logsLoading: false,
    logsLines: 100,
    showLogs: false,

    async runArtisan() {
        if (!this.artisanCmd) return;
        this.artisanLoading = true;
        this.artisanOutput  = '';
        this.artisanOk      = null;
        try {
            const fd = new FormData();
            fd.append('command', this.artisanCmd);
            fd.append('_token', document.querySelector('meta[name=csrf-token]').content);
            const res  = await fetch('{{ route('admin.system.artisan') }}', { method: 'POST', body: fd });
            const data = await res.json();
            this.artisanOutput = data.output;
            this.artisanOk     = data.ok;
        } catch (e) {
            this.artisanOutput = 'Request failed: ' + e.message;
            this.artisanOk     = false;
        } finally {
            this.artisanLoading = false;
        }
    },

    async fetchLogs() {
        this.logsLoading = true;
        this.showLogs    = true;
        try {
            const res  = await fetch('{{ route('admin.system.logs') }}?lines=' + this.logsLines);
            const data = await res.json();
            this.logsOutput = data.log;
            this.$nextTick(() => {
                const el = document.getElementById('log-output');
                if (el) el.scrollTop = el.scrollHeight;
            });
        } catch (e) {
            this.logsOutput = 'Failed to load logs: ' + e.message;
        } finally {
            this.logsLoading = false;
        }
    }
}">

@if(session('success'))
<div class="mb-5 p-4 bg-emerald-500/10 border border-emerald-500/20 rounded-2xl text-sm text-emerald-400 flex gap-2 items-center">
    <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd" /></svg>
    {{ session('success') }}
</div>
@endif
@if(session('error'))
<div class="mb-5 p-4 bg-rose-500/10 border border-rose-500/20 rounded-2xl text-sm text-rose-400">{{ session('error') }}</div>
@endif

{{-- Health overview grid --}}
<div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-3 mb-6">
    @php
    $indicators = [
        ['Environment',   $health['app_env'],        $health['app_env'] === 'production' ? 'emerald' : 'amber'],
        ['Debug Mode',    $health['debug_mode'] ? 'ON ⚠' : 'Off',  $health['debug_mode'] ? 'rose' : 'emerald'],
        ['PHP',           $health['php_version'],    'brand'],
        ['Laravel',       $health['laravel_version'],'brand'],
        ['Cache Driver',  $health['cache_driver'],   'sky'],
        ['Queue Driver',  $health['queue_driver'],   'sky'],
        ['DB Size',       $health['db_size_mb'].' MB','accent'],
        ['Free Storage',  $health['storage_free'],   'teal'],
        ['Memory Limit',  $health['memory_limit'],   'gray'],
        ['Max Exec',      $health['max_exec_time'],  'gray'],
        ['OPcache',       $health['opcache'] ? 'Enabled' : 'Disabled', $health['opcache'] ? 'emerald' : 'amber'],
        ['Upload Max',    $health['upload_max'],     'gray'],
    ];
    @endphp
    @foreach($indicators as [$label, $value, $color])
    <div class="bg-gray-900/50 border border-white/5 rounded-xl p-4">
        <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">{{ $label }}</p>
        <p class="text-sm font-semibold text-{{ $color }}-400">{{ $value }}</p>
    </div>
    @endforeach
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-6">

    {{-- Queue status --}}
    <div class="bg-gray-900/50 border border-white/5 rounded-2xl p-6">
        <h3 class="text-sm font-semibold text-white mb-4">Queue Status</h3>
        <div class="space-y-3 mb-5">
            <div class="flex items-center justify-between py-2.5 border-b border-white/[0.04]">
                <span class="text-sm text-gray-400">Pending jobs</span>
                <span class="text-lg font-bold {{ $health['queue_pending'] > 0 ? 'text-amber-400' : 'text-emerald-400' }}">
                    {{ number_format($health['queue_pending']) }}
                </span>
            </div>
            <div class="flex items-center justify-between py-2.5 border-b border-white/[0.04]">
                <span class="text-sm text-gray-400">Failed jobs</span>
                <span class="text-lg font-bold {{ $health['queue_failed'] > 0 ? 'text-rose-400' : 'text-emerald-400' }}">
                    {{ number_format($health['queue_failed']) }}
                </span>
            </div>
        </div>
        <div class="space-y-2">
            @if($health['queue_failed'] > 0)
            <form method="POST" action="{{ route('admin.system.retryJobs') }}">
                @csrf
                <button type="submit" class="w-full px-4 py-2 bg-amber-500/10 hover:bg-amber-500/20 text-amber-400 text-xs font-medium rounded-xl transition">
                    ↻ Retry All Failed Jobs
                </button>
            </form>
            <form method="POST" action="{{ route('admin.system.flushJobs') }}"
                  onsubmit="return confirm('Delete all failed jobs? This cannot be undone.')">
                @csrf
                <button type="submit" class="w-full px-4 py-2 bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 text-xs font-medium rounded-xl transition">
                    🗑 Flush All Failed Jobs
                </button>
            </form>
            @else
            <div class="text-center py-4">
                <p class="text-xs text-emerald-400">✓ No failed jobs — queue is healthy</p>
            </div>
            @endif
        </div>

        @if($recentFailed->isNotEmpty())
        <div class="mt-4 pt-4 border-t border-white/5">
            <p class="text-xs text-gray-500 mb-3 font-semibold uppercase tracking-wider">Recent Failures</p>
            <div class="space-y-2 max-h-48 overflow-y-auto custom-scrollbar">
                @foreach($recentFailed as $job)
                <div class="bg-rose-500/5 border border-rose-500/10 rounded-lg p-2.5">
                    <p class="text-xs text-rose-300 font-mono truncate">{{ class_basename(json_decode($job->payload)->displayName ?? 'Job') }}</p>
                    <p class="text-[10px] text-gray-600 mt-0.5">{{ \Carbon\Carbon::parse($job->failed_at)->diffForHumans() }}</p>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    {{-- Cache management --}}
    <div class="bg-gray-900/50 border border-white/5 rounded-2xl p-6">
        <h3 class="text-sm font-semibold text-white mb-4">Cache Management</h3>
        <div class="space-y-2">
            @foreach([
                ['all',     'Clear Everything',    'Clears all caches at once', 'rose'],
                ['config',  'Config Cache',        'php artisan config:clear',  'amber'],
                ['routes',  'Route Cache',         'php artisan route:clear',   'amber'],
                ['views',   'View Cache',          'php artisan view:clear',    'amber'],
                ['app',     'Application Cache',   'Cache::flush()',            'brand'],
            ] as [$type, $label, $desc, $color])
            <form method="POST" action="{{ route('admin.system.clearCache') }}">
                @csrf
                <input type="hidden" name="type" value="{{ $type }}">
                <button type="submit"
                        class="w-full px-4 py-3 bg-white/[0.03] hover:bg-{{ $color }}-500/10 border border-white/5 hover:border-{{ $color }}-500/20 rounded-xl transition text-left group">
                    <p class="text-sm font-medium text-gray-300 group-hover:text-white">{{ $label }}</p>
                    <p class="text-xs text-gray-600 font-mono mt-0.5">{{ $desc }}</p>
                </button>
            </form>
            @endforeach
        </div>
    </div>

    {{-- Database stats --}}
    <div class="bg-gray-900/50 border border-white/5 rounded-2xl p-6">
        <h3 class="text-sm font-semibold text-white mb-4">Database Row Counts</h3>
        <div class="space-y-0 divide-y divide-white/[0.04]">
            @foreach($tableStats as $table => $count)
            <div class="flex items-center justify-between py-2.5">
                <span class="text-sm text-gray-400 font-mono">{{ $table }}</span>
                <span class="text-sm font-semibold text-white">{{ number_format($count) }}</span>
            </div>
            @endforeach
        </div>
        <div class="mt-4 pt-4 border-t border-white/5">
            <p class="text-xs text-gray-500">Total DB size: <strong class="text-white">{{ $health['db_size_mb'] }} MB</strong></p>
        </div>
    </div>
</div>

{{-- PHP Extensions --}}
<div class="bg-gray-900/50 border border-white/5 rounded-2xl p-6 mb-6">
    <h3 class="text-sm font-semibold text-white mb-4">PHP Extensions</h3>
    <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 gap-2">
        @foreach($extStatus as $ext => $loaded)
        <div class="flex items-center gap-2 px-3 py-2 rounded-lg {{ $loaded ? 'bg-emerald-500/5 border border-emerald-500/10' : 'bg-rose-500/5 border border-rose-500/10' }}">
            <div class="w-1.5 h-1.5 rounded-full {{ $loaded ? 'bg-emerald-400' : 'bg-rose-400' }}"></div>
            <span class="text-xs font-mono {{ $loaded ? 'text-emerald-300' : 'text-rose-300' }}">{{ $ext }}</span>
        </div>
        @endforeach
    </div>
</div>

{{-- Artisan runner --}}
<div class="bg-gray-900/50 border border-white/5 rounded-2xl p-6 mb-6">
    <h3 class="text-sm font-semibold text-white mb-1">Artisan Command Runner</h3>
    <p class="text-xs text-gray-500 mb-4">Run safe, pre-approved Artisan commands directly from the admin panel.</p>

    <div class="flex gap-3 mb-3">
        <select x-model="artisanCmd" class="flex-1 bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white font-mono focus:outline-none focus:border-brand-500/50 transition">
            <option value="">— Select a command —</option>
            @foreach([
                'cache:clear', 'config:clear', 'config:cache',
                'route:clear', 'route:cache', 'view:clear', 'view:cache',
                'queue:restart', 'storage:link', 'optimize:clear', 'optimize',
            ] as $cmd)
            <option value="{{ $cmd }}">php artisan {{ $cmd }}</option>
            @endforeach
        </select>
        <button type="button" @click="runArtisan()" :disabled="!artisanCmd || artisanLoading"
                class="px-5 py-2.5 bg-brand-600 hover:bg-brand-500 disabled:opacity-40 disabled:cursor-not-allowed text-white text-sm font-medium rounded-xl transition flex items-center gap-2">
            <svg x-show="!artisanLoading" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.347a1.125 1.125 0 0 1 0 1.972l-11.54 6.347a1.125 1.125 0 0 1-1.667-.986V5.653Z" /></svg>
            <svg x-show="artisanLoading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
            <span x-text="artisanLoading ? 'Running…' : 'Run'"></span>
        </button>
    </div>

    <div x-show="artisanOutput" x-transition class="bg-black/40 border rounded-xl p-4"
         :class="artisanOk ? 'border-emerald-500/20' : 'border-rose-500/20'">
        <p class="text-[10px] font-semibold uppercase tracking-wider mb-2"
           :class="artisanOk ? 'text-emerald-400' : 'text-rose-400'"
           x-text="artisanOk ? '✓ Success' : '✗ Error'"></p>
        <pre class="text-xs text-gray-300 font-mono whitespace-pre-wrap" x-text="artisanOutput"></pre>
    </div>
</div>

{{-- Log viewer --}}
<div class="bg-gray-900/50 border border-white/5 rounded-2xl p-6">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h3 class="text-sm font-semibold text-white">Application Logs</h3>
            <p class="text-xs text-gray-500 mt-0.5">storage/logs/laravel.log — last N lines</p>
        </div>
        <div class="flex items-center gap-3">
            <select x-model="logsLines" class="bg-white/5 border border-white/10 rounded-lg px-3 py-1.5 text-xs text-white focus:outline-none transition">
                <option value="50">50 lines</option>
                <option value="100" selected>100 lines</option>
                <option value="200">200 lines</option>
                <option value="500">500 lines</option>
            </select>
            <button type="button" @click="fetchLogs()" :disabled="logsLoading"
                    class="px-4 py-1.5 bg-brand-600 hover:bg-brand-500 disabled:opacity-40 text-white text-xs font-medium rounded-lg transition flex items-center gap-1.5">
                <svg x-show="!logsLoading" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>
                <svg x-show="logsLoading" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                <span x-text="logsLoading ? 'Loading…' : 'Load Logs'"></span>
            </button>
        </div>
    </div>

    <div x-show="showLogs" x-transition>
        <div id="log-output"
             class="bg-black/50 border border-white/5 rounded-xl p-4 h-80 overflow-y-auto custom-scrollbar font-mono text-[11px] leading-relaxed whitespace-pre-wrap text-gray-400">
            <span x-show="logsLoading" class="text-gray-600">Loading logs…</span>
            <span x-show="!logsLoading" x-text="logsOutput"></span>
        </div>
        <div class="flex justify-end mt-2">
            <button type="button" @click="navigator.clipboard.writeText(logsOutput)"
                    class="text-xs text-gray-500 hover:text-gray-300 transition">Copy to clipboard</button>
        </div>
    </div>

    <div x-show="!showLogs" class="text-center py-6">
        <p class="text-xs text-gray-600">Click "Load Logs" to view the application log file.</p>
    </div>
</div>

</div>
@endsection
