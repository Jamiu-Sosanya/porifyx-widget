<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Audit;
use App\Support\Security;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class SecurityController extends Controller
{
    public function index(Request $request)
    {
        $checks = Security::checks();
        $events = DB::table('security_events')
            ->when($request->get('type'), fn ($q, $t) => $q->where('type', $t))
            ->when($request->get('ip'), fn ($q, $ip) => $q->where('ip', $ip))
            ->latest('id')->limit(100)->get();
        $since = now()->subDay();
        $stats = DB::table('security_events')->where('created_at', '>=', $since)->selectRaw('type, COUNT(*) c')->groupBy('type')->pluck('c', 'type');
        $topIps = DB::table('security_events')->where('created_at', '>=', now()->subDays(7))->whereNotNull('ip')
            ->selectRaw('ip, COUNT(*) c')->groupBy('ip')->orderByDesc('c')->limit(8)->get();

        return view('admin.security', [
            'checks' => $checks, 'score' => Security::score($checks), 's' => Security::settings(),
            'events' => $events, 'stats' => $stats, 'topIps' => $topIps, 'perf' => $this->perf(),
        ]);
    }

    public function save(Request $request)
    {
        $d = $request->validate([
            'login_max' => 'required|integer|between:3,50', 'login_decay' => 'required|integer|between:1,1440',
            'ban_after' => 'required|integer|between:1,20', 'ban_minutes' => 'required|integer|between:5,43200',
            'blocked_ips' => 'nullable|string|max:20000', 'allowed_ips' => 'nullable|string|max:5000',
        ]);
        $list = fn ($v) => array_values(array_unique(array_filter(array_map('trim', preg_split('/[\s,]+/', (string) $v)),
            fn ($ip) => filter_var(explode('/', $ip)[0], FILTER_VALIDATE_IP))));
        $blocked = $list($d['blocked_ips'] ?? '');
        if (Security::ipIn((string) $request->ip(), $blocked) && ! Security::ipIn((string) $request->ip(), $list($d['allowed_ips'] ?? ''))) {
            return back()->with('error', 'That list would block your own IP (' . $request->ip() . '). Remove it or add it to the allow list.');
        }
        Security::save([
            'headers' => $request->boolean('headers'), 'hsts' => $request->boolean('hsts'), 'block_bad_bots' => $request->boolean('block_bad_bots'),
            'force_admin_2fa' => $request->boolean('force_admin_2fa'), 'auto_ban' => $request->boolean('auto_ban'),
            'login_max' => (int) $d['login_max'], 'login_decay' => (int) $d['login_decay'], 'ban_after' => (int) $d['ban_after'], 'ban_minutes' => (int) $d['ban_minutes'],
            'blocked_ips' => $blocked, 'allowed_ips' => $list($d['allowed_ips'] ?? ''),
        ]);
        Audit::log('security.settings', null, ['blocked' => count($blocked)]);

        return back()->with('success', 'Security settings saved.');
    }

    /** Quick actions from the events table. */
    public function ip(Request $request)
    {
        $d = $request->validate(['ip' => 'required|ip', 'do' => 'required|in:block,unban']);
        $s = Security::settings();
        if ($d['do'] === 'block') {
            if ($d['ip'] === $request->ip()) return back()->with('error', 'You cannot block your own IP.');
            Security::save(['blocked_ips' => array_values(array_unique([...$s['blocked_ips'], $d['ip']]))]);
        } else {
            Cache::forget('pfx:ban:' . $d['ip']);
            Security::save(['blocked_ips' => array_values(array_diff($s['blocked_ips'], [$d['ip']]))]);
        }
        Audit::log('security.ip_' . $d['do'], null, ['ip' => $d['ip']]);

        return back()->with('success', $d['do'] === 'block' ? "Blocked {$d['ip']}." : "Unblocked {$d['ip']}.");
    }

    public function action(Request $request)
    {
        $a = $request->validate(['action' => 'required|in:optimize,clear,views,db,logs,retry,flush_failed,prune,sessions,events'])['action'];
        $run = function (string $cmd, string $msg, array $args = []) { Artisan::call($cmd, $args); return $msg; };
        try {
            $msg = match ($a) {
                'optimize' => $run('optimize', 'Config, routes and events cached. Clear caches after changing .env.'),
                'clear' => $run('optimize:clear', 'All caches cleared.'),
                'views' => $run('view:cache', 'Templates pre-compiled.'),
                'db' => $this->optimizeDb(),
                'logs' => $this->trimLogs(),
                'retry' => $run('queue:retry', 'Failed jobs queued again.', ['id' => ['all']]),
                'flush_failed' => $run('queue:flush', 'Failed jobs deleted.'),
                'prune' => $this->prune(),
                'sessions' => $this->signOutEveryone(),
                'events' => DB::table('security_events')->where('created_at', '<', now()->subDays(30))->delete() . ' old security events deleted.',
            };
        } catch (\Throwable $e) {
            return back()->with('error', 'Could not finish: ' . mb_substr($e->getMessage(), 0, 200));
        }
        Audit::log('performance.' . $a);

        return back()->with('success', $msg);
    }

    private function optimizeDb(): string
    {
        $driver = DB::getDriverName();
        if ($driver === 'sqlite') { if (DB::transactionLevel() === 0) DB::statement('VACUUM'); DB::statement('ANALYZE'); return 'Database compacted and statistics refreshed.'; }
        if ($driver === 'mysql' || $driver === 'mariadb') {
            $tables = array_map(fn ($t) => '`' . str_replace('`', '', $t) . '`', array_column(DB::select('SHOW TABLES'), array_key_first((array) (DB::select('SHOW TABLES')[0] ?? []))));
            foreach (array_chunk($tables, 20) as $chunk) DB::statement('OPTIMIZE TABLE ' . implode(',', $chunk));

            return count($tables) . ' tables optimised.';
        }

        return 'Nothing to do for ' . $driver . '.';
    }

    private function trimLogs(): string
    {
        $n = 0;
        foreach (File::glob(storage_path('logs/*.log')) as $f) {
            if (filemtime($f) < now()->subDays(7)->timestamp) { File::delete($f); $n++; }
            elseif (filesize($f) > 5 * 1024 * 1024) { File::put($f, ''); $n++; }
        }

        return "{$n} log file(s) removed or emptied.";
    }

    private function prune(): string
    {
        $n = 0;
        $n += DB::table('widget_events')->whereNotNull('resolved_at')->where('created_at', '<', now()->subDays(30))->delete();
        if (\Illuminate\Support\Facades\Schema::hasTable('activity_log')) $n += DB::table('activity_log')->where('created_at', '<', now()->subDays(180))->delete();
        $n += DB::table('security_events')->where('created_at', '<', now()->subDays(90))->delete();

        return "{$n} old monitoring and log rows deleted.";
    }

    private function signOutEveryone(): string
    {
        if (config('session.driver') === 'database') DB::table(config('session.table', 'sessions'))->delete();
        elseif (config('session.driver') === 'file') File::cleanDirectory(storage_path('framework/sessions'));
        else return 'Your session driver (' . config('session.driver') . ') must be cleared on the server.';
        DB::table('users')->update(['remember_token' => null]);

        return 'Everyone has been signed out, including you.';
    }

    private function perf(): array
    {
        $t = microtime(true); try { DB::select('select 1'); $dbMs = round((microtime(true) - $t) * 1000, 1); } catch (\Throwable) { $dbMs = null; }
        $t = microtime(true); Cache::put('pfx:ping', 1, 10); Cache::get('pfx:ping'); $cacheMs = round((microtime(true) - $t) * 1000, 1);
        $op = function_exists('opcache_get_status') ? @opcache_get_status(false) : false;
        $size = function (string $dir) { $s = 0; if (is_dir($dir)) foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)) as $f) $s += $f->getSize(); return $s; };
        $dbSize = DB::getDriverName() === 'sqlite' ? @filesize(config('database.connections.sqlite.database')) : null;
        $tables = [];
        foreach (['conversations', 'messages', 'leads', 'visitors', 'knowledge_chunks', 'widget_events', 'activity_log', 'security_events', 'jobs', 'failed_jobs'] as $tb) {
            try { $tables[$tb] = DB::table($tb)->count(); } catch (\Throwable) {}
        }
        arsort($tables);

        return [
            'php' => PHP_VERSION, 'laravel' => app()->version(), 'memory_limit' => ini_get('memory_limit'), 'max_exec' => ini_get('max_execution_time'),
            'upload' => ini_get('upload_max_filesize'), 'peak' => round(memory_get_peak_usage(true) / 1048576, 1),
            'opcache' => $op && ($op['opcache_enabled'] ?? false), 'opcache_hit' => $op ? round($op['opcache_statistics']['opcache_hit_rate'] ?? 0, 1) : null,
            'db' => DB::getDriverName(), 'db_ms' => $dbMs, 'db_size' => $dbSize, 'cache' => config('cache.default'), 'cache_ms' => $cacheMs,
            'queue' => config('queue.default'), 'session' => config('session.driver'),
            'config_cached' => app()->configurationIsCached(), 'routes_cached' => app()->routesAreCached(),
            'views' => count(File::glob(storage_path('framework/views/*.php'))),
            'pending' => rescue(fn () => DB::table('jobs')->count(), 0, false), 'failed' => rescue(fn () => DB::table('failed_jobs')->count(), 0, false),
            'logs' => $size(storage_path('logs')), 'uploads' => $size(storage_path('app')), 'disk_free' => @disk_free_space(base_path()),
            'tables' => $tables,
        ];
    }
}
