<?php

namespace App\Http\Controllers;

use App\Models\AnalyticsDaily;
use App\Models\AiInsight;
use App\Models\ChannelConfiguration;
use App\Models\Conversation;
use App\Models\Lead;
use App\Models\Message;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $period   = $request->get('period', '30');
        $widgetId = $request->get('widget_id');
        $startDate = now()->subDays((int) $period);

        $widgetIds = $user->widgets()->pluck('id');
        $filteredWidgetIds = $widgetId ? collect([$widgetId]) : $widgetIds;

        // Core analytics daily
        $query = AnalyticsDaily::where('user_id', $user->id)->where('date', '>=', $startDate);
        if ($widgetId) {
            $query->where('widget_id', $widgetId);
        }
        $analytics = $query->orderBy('date')->get();
        // Live numbers (the daily summary table can lag or be empty, so charts read the real tables)
        $sqlite = Conversation::query()->getConnection()->getDriverName() === 'sqlite';
        $perDay = fn ($q, $col = 'created_at') => $q->selectRaw("DATE($col) as d, COUNT(*) as c")->groupBy('d')->pluck('c', 'd');
        $convQ = fn () => Conversation::whereIn('widget_id', $filteredWidgetIds)->where('created_at', '>=', $startDate);
        $msgQ = fn () => Message::whereHas('conversation', fn ($q) => $q->whereIn('widget_id', $filteredWidgetIds))->where('created_at', '>=', $startDate);
        $leadQ = fn () => Lead::where('user_id', $user->id)->when($widgetId, fn ($q) => $q->where('widget_id', $widgetId))->where('created_at', '>=', $startDate);
        $cDay = $perDay($convQ()); $mDay = $perDay($msgQ()); $lDay = $perDay($leadQ());
        $vDay = $convQ()->selectRaw('DATE(created_at) as d, COUNT(DISTINCT visitor_id) as c')->groupBy('d')->pluck('c', 'd');

        $labels = []; $conversations = []; $messages = []; $leads = []; $visitors = [];
        for ($i = (int) $period; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $labels[]        = now()->subDays($i)->format('M d');
            $conversations[] = (int) ($cDay[$date] ?? 0);
            $messages[]      = (int) ($mDay[$date] ?? 0);
            $leads[]         = (int) ($lDay[$date] ?? 0);
            $visitors[]      = (int) ($vDay[$date] ?? 0);
        }

        $totalConversations = array_sum($conversations);
        $totalMessages      = array_sum($messages);
        $totalLeads         = array_sum($leads);
        $totalVisitors      = (int) $convQ()->distinct()->count('visitor_id');
        $avgResolution      = $totalConversations ? round($convQ()->where('status', 'resolved')->count() / $totalConversations * 100, 1) : 0;
        $avgResponseTime    = (float) ($convQ()->whereNotNull('first_response_at')->get(['created_at', 'first_response_at'])
            ->avg(fn ($c) => max(0, $c->created_at->diffInMilliseconds($c->first_response_at))) ?? 0);
        $totalAiResponses   = $msgQ()->where('role', 'assistant')->count();
        $totalFallbacks     = $msgQ()->where('role', 'assistant')->where('is_fallback', true)->count();
        $aiAccuracy         = $totalAiResponses > 0 ? round((1 - ($totalFallbacks / $totalAiResponses)) * 100, 1) : 0;
        // ── Sentiment trend (per day) ────────────────────────────────────────────
        $sentimentRaw = Message::whereHas('conversation', fn ($q) => $q->whereIn('widget_id', $filteredWidgetIds))
            ->where('created_at', '>=', $startDate)
            ->whereNotNull('sentiment_score')
            ->selectRaw('DATE(created_at) as day, AVG(sentiment_score) as avg_sentiment, COUNT(*) as msg_count')
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->keyBy('day');

        $sentimentLabels = [];
        $sentimentData   = [];
        for ($i = (int) $period; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $sentimentLabels[] = now()->subDays($i)->format('M d');
            $sentimentData[]   = $sentimentRaw->has($date)
                ? round((float) $sentimentRaw[$date]->avg_sentiment, 3)
                : null;
        }

        // Overall avg sentiment
        $overallSentiment = Message::whereHas('conversation', fn ($q) => $q->whereIn('widget_id', $filteredWidgetIds))
            ->where('created_at', '>=', $startDate)
            ->whereNotNull('sentiment_score')
            ->avg('sentiment_score');
        $overallSentiment = $overallSentiment !== null ? round((float) $overallSentiment, 2) : null;

        // Sentiment distribution buckets
        $sentimentBuckets = [
            'very_positive' => 0, 'positive' => 0, 'neutral' => 0,
            'negative' => 0, 'very_negative' => 0,
        ];
        Message::whereHas('conversation', fn ($q) => $q->whereIn('widget_id', $filteredWidgetIds))
            ->where('created_at', '>=', $startDate)
            ->whereNotNull('sentiment_score')
            ->selectRaw('sentiment_label, COUNT(*) as cnt')
            ->groupBy('sentiment_label')
            ->get()
            ->each(function ($row) use (&$sentimentBuckets) {
                $key = str_replace(' ', '_', strtolower($row->sentiment_label ?? ''));
                if (isset($sentimentBuckets[$key])) {
                    $sentimentBuckets[$key] = $row->cnt;
                }
            });

        // ── CSAT trend (per day) ─────────────────────────────────────────────────
        $csatRaw = Conversation::whereIn('widget_id', $filteredWidgetIds)
            ->where('csat_submitted_at', '>=', $startDate)
            ->whereNotNull('csat_score')
            ->selectRaw('DATE(csat_submitted_at) as day, AVG(csat_score) as avg_csat, COUNT(*) as cnt')
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->keyBy('day');

        $csatLabels = [];
        $csatData   = [];
        for ($i = (int) $period; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $csatLabels[] = now()->subDays($i)->format('M d');
            $csatData[]   = $csatRaw->has($date) ? round((float) $csatRaw[$date]->avg_csat, 2) : null;
        }
        $avgCsat = Conversation::whereIn('widget_id', $filteredWidgetIds)
            ->whereNotNull('csat_score')
            ->where('csat_submitted_at', '>=', $startDate)
            ->avg('csat_score');
        $avgCsat = $avgCsat ? round((float) $avgCsat, 2) : null;
        $totalCsatResponses = Conversation::whereIn('widget_id', $filteredWidgetIds)
            ->whereNotNull('csat_score')->where('csat_submitted_at', '>=', $startDate)->count();

        // ── Heatmap: conversations by hour and weekday ───────────────────────────
        $heatmapRaw = Conversation::whereIn('widget_id', $filteredWidgetIds)
            ->where('created_at', '>=', $startDate)
            ->selectRaw(Conversation::query()->getConnection()->getDriverName() === 'sqlite'
                ? "CAST(strftime('%H', created_at) AS INTEGER) as hour, CAST(strftime('%w', created_at) AS INTEGER) + 1 as dow, COUNT(*) as cnt"
                : 'HOUR(created_at) as hour, DAYOFWEEK(created_at) as dow, COUNT(*) as cnt')
            ->groupBy('hour', 'dow')
            ->get();

        // dow: 1=Sun, 2=Mon … 7=Sat. We want Mon-Sun (0-6 index).
        $heatmap = array_fill(0, 7, array_fill(0, 24, 0));
        foreach ($heatmapRaw as $row) {
            $dayIdx  = ($row->dow + 5) % 7; // Mon=0 … Sun=6
            $heatmap[$dayIdx][(int) $row->hour] = (int) $row->cnt;
        }
        $heatmapMax = max(1, max(array_map(fn ($row) => max($row), $heatmap)));

        // ── Device distribution ──────────────────────────────────────────────────
        $devices = $analytics->pluck('device_distribution')->filter()
            ->reduce(function ($carry, $dist) {
                foreach ($dist as $device => $count) {
                    $carry[$device] = ($carry[$device] ?? 0) + $count;
                }
                return $carry;
            }, []);
        if (empty($devices)) {
            $devices = \App\Models\Visitor::whereIn('id', $convQ()->select('visitor_id'))->whereNotNull('device_type')
                ->selectRaw('device_type, COUNT(*) as c')->groupBy('device_type')->pluck('c', 'device_type')->all();
        }
        arsort($devices);

        // ── Language distribution ────────────────────────────────────────────────
        $languages = $analytics->pluck('language_distribution')->filter()
            ->reduce(function ($carry, $dist) {
                foreach ($dist as $lang => $count) {
                    $carry[$lang] = ($carry[$lang] ?? 0) + $count;
                }
                return $carry;
            }, []);
        arsort($languages);

        // Live language detection from messages table
        if (empty($languages)) {
            $convQ()
                ->whereNotNull('detected_language')
                ->selectRaw('detected_language, COUNT(*) as cnt')
                ->groupBy('detected_language')
                ->orderByDesc('cnt')
                ->take(10)
                ->get()
                ->each(function ($row) use (&$languages) {
                    $languages[$row->detected_language] = $row->cnt;
                });
        }

        // ── Top topics ───────────────────────────────────────────────────────────
        $topTopics = $analytics->pluck('top_topics')->filter()->flatten(1)
            ->groupBy('topic')
            ->map(fn ($items) => $items->sum('count'))
            ->sortDesc()
            ->take(10);
        if ($topTopics->isEmpty()) {
            $topTopics = \App\Models\KnowledgeGap::whereIn('widget_id', $filteredWidgetIds)->where('last_asked_at', '>=', $startDate)
                ->orderByDesc('times_asked')->take(10)->pluck('times_asked', 'question');
        }

        // ── Funnel ───────────────────────────────────────────────────────────────
        $funnel = [
            'visitors'      => $totalVisitors,
            'conversations' => $totalConversations,
            'leads'         => $totalLeads,
            'hot_leads'     => Lead::where('user_id', $user->id)->where('temperature', 'hot')->where('created_at', '>=', $startDate)->count(),
            'won'           => Lead::where('user_id', $user->id)->where('status', 'won')->where('created_at', '>=', $startDate)->count(),
        ];

        // ── AI Insights ──────────────────────────────────────────────────────────
        $insights = AiInsight::where('user_id', $user->id)
            ->when($widgetId, fn ($q) => $q->where('widget_id', $widgetId))
            ->orderBy('importance', 'desc')->orderBy('created_at', 'desc')
            ->take(15)->get();

        // ── Channel analytics ─────────────────────────────────────────────────
        $channelConfigs = ChannelConfiguration::whereIn('widget_id', $filteredWidgetIds)->get();

        $channelStats = $channelConfigs
            ->groupBy('channel_type')
            ->map(fn ($configs, $type) => [
                'count'       => $configs->count(),
                'active'      => $configs->where('is_enabled', true)->count(),
                'sent'        => $configs->sum('total_messages_sent'),
                'received'    => $configs->sum('total_messages_received'),
                'today'       => $configs->sum('messages_today'),
                'last_active' => $configs->max('last_message_at'),
            ])
            ->sortByDesc(fn ($s) => $s['sent'] + $s['received']);

        $totalChannelMessages = $channelConfigs->sum('total_messages_sent') + $channelConfigs->sum('total_messages_received');
        $activeChannels       = $channelConfigs->where('is_enabled', true)->count();

        $widgets = $user->widgets()->get();
        $report = \App\Support\AnalyticsReport::build($user, max(1, (int) $period), $widgetId ? (int) $widgetId : null);
        $reportSettings = array_merge(['enabled' => false, 'days' => 7, 'every' => 7, 'recipients' => [$user->email], 'next_at' => null], (array) $user->report_settings);

        return view('analytics.index', compact('report', 'reportSettings',
            'labels', 'conversations', 'messages', 'leads', 'visitors',
            'totalConversations', 'totalMessages', 'totalLeads', 'totalVisitors',
            'avgResolution', 'avgResponseTime', 'aiAccuracy', 'totalFallbacks',
            'topTopics', 'devices', 'languages', 'insights', 'funnel',
            'sentimentLabels', 'sentimentData', 'overallSentiment', 'sentimentBuckets',
            'csatLabels', 'csatData', 'avgCsat', 'totalCsatResponses',
            'heatmap', 'heatmapMax',
            'channelStats', 'totalChannelMessages', 'activeChannels',
            'widgets', 'period', 'widgetId'
        ));
    }

    /** Scheduled email reports: which period, how often, to whom. */
    public function saveReports(Request $request)
    {
        $d = $request->validate([
            'days' => 'required|in:' . implode(',', array_keys(\App\Support\AnalyticsReport::PERIODS)),
            'every' => 'required|integer|in:1,2,3,7,14,30',
            'recipients' => 'required|string|max:1000',
        ]);
        $emails = collect(preg_split('/[\s,;]+/', $d['recipients']))->filter()->unique()->take(10)->values();
        if ($emails->contains(fn ($e) => ! filter_var($e, FILTER_VALIDATE_EMAIL))) return back()->withErrors(['recipients' => 'Check the email addresses.']);
        $request->user()->update(['report_settings' => [
            'enabled' => $request->boolean('enabled'), 'days' => (int) $d['days'], 'every' => (int) $d['every'], 'recipients' => $emails->all(),
            'next_at' => now()->addDays((int) $d['every'])->startOfDay()->addHours(8)->toIso8601String(),
        ]]);

        if ($request->boolean('send_now')) {
            \Illuminate\Support\Facades\Mail::to($emails->all())->send(new \App\Mail\AnalyticsReportMail($request->user(), \App\Support\AnalyticsReport::build($request->user(), (int) $d['days'])));
            return back()->with('success', 'Report settings saved and a report was sent now.');
        }

        return back()->with('success', $request->boolean('enabled') ? 'Email reports scheduled.' : 'Email reports turned off.');
    }

    public function export(Request $request)
    {
        $user = $request->user();
        $days = max(1, min(365, (int) $request->get('period', 30)));
        $ids = $request->filled('widget_id') ? [(int) $request->get('widget_id')] : $user->widgets()->pluck('id')->all();
        $rows = \App\Models\Conversation::whereIn('widget_id', $ids)->where('created_at', '>=', now()->subDays($days))
            ->selectRaw('date(created_at) as d, count(*) as conversations, sum(message_count) as messages')->groupBy('d')->orderBy('d')->get()->keyBy('d');
        $leads = \App\Models\Lead::where('user_id', $user->id)->whereIn('widget_id', $ids)->where('created_at', '>=', now()->subDays($days))
            ->selectRaw('date(created_at) as d, count(*) as c')->groupBy('d')->pluck('c', 'd');

        return response()->streamDownload(function () use ($days, $rows, $leads) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['date', 'conversations', 'messages', 'leads']);
            for ($i = $days; $i >= 0; $i--) {
                $d = now()->subDays($i)->toDateString();
                fputcsv($out, [$d, (int) ($rows[$d]->conversations ?? 0), (int) ($rows[$d]->messages ?? 0), (int) ($leads[$d] ?? 0)]);
            }
            fclose($out);
        }, 'analytics-' . now()->toDateString() . '.csv', ['Content-Type' => 'text/csv']);
    }
}