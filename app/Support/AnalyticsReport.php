<?php

namespace App\Support;

use App\Models\Conversation;
use App\Models\Lead;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/** Period summary with comparison to the previous period (analytics page + emailed reports). */
class AnalyticsReport
{
    public const PERIODS = [1 => 'Last 24 hours', 2 => 'Last 48 hours', 3 => 'Last 72 hours', 7 => 'Last 7 days', 14 => 'Last 14 days', 30 => 'Last 30 days', 90 => 'Last 90 days'];

    public static function build(User $u, int $days, ?int $widgetId = null): array
    {
        $widgetIds = $widgetId ? [$widgetId] : $u->widgets()->pluck('id')->all();
        $now = now();
        $from = $now->copy()->subDays($days);
        $prev = $from->copy()->subDays($days);

        $count = function ($from, $to) use ($u, $widgetIds) {
            $conv = Conversation::whereIn('widget_id', $widgetIds)->whereBetween('created_at', [$from, $to]);
            $convIds = (clone $conv)->pluck('id');

            return [
                'conversations' => (clone $conv)->count(),
                'engaged'       => (clone $conv)->where('message_count', '>', 1)->count(),
                'messages'      => Message::whereIn('conversation_id', $convIds)->count(),
                'ai_replies'    => Message::whereIn('conversation_id', $convIds)->where('role', 'ai')->count(),
                'fallbacks'     => Message::whereIn('conversation_id', $convIds)->where('role', 'ai')->where('is_fallback', true)->count(),
                'leads'         => Lead::where('user_id', $u->id)->whereIn('widget_id', $widgetIds)->whereBetween('created_at', [$from, $to])->count(),
                'hot_leads'     => Lead::where('user_id', $u->id)->whereIn('widget_id', $widgetIds)->where('temperature', 'hot')->whereBetween('created_at', [$from, $to])->count(),
                'bookings'      => DB::table('appointments')->whereIn('widget_id', $widgetIds)->whereBetween('created_at', [$from, $to])->count(),
                'handoffs'      => (clone $conv)->whereIn('status', ['open', 'human_takeover', 'waiting'])->count(),
                'tickets'       => DB::table('tickets')->where('user_id', $u->id)->whereBetween('created_at', [$from, $to])->count(),
            ];
        };
        $cur = $count($from, $now);
        $old = $count($prev, $from);
        $kpis = [];
        foreach ($cur as $k => $v) {
            $kpis[$k] = ['value' => $v, 'previous' => $old[$k], 'change' => $old[$k] ? (int) round(($v - $old[$k]) / $old[$k] * 100) : ($v ? 100 : 0)];
        }
        $conv = Conversation::whereIn('widget_id', $widgetIds)->where('created_at', '>=', $from);

        return [
            'days' => $days, 'from' => $from, 'to' => $now, 'kpis' => $kpis,
            'conversion' => $cur['conversations'] ? round($cur['leads'] / $cur['conversations'] * 100, 1) : 0,
            'answered'   => $cur['ai_replies'] ? round(($cur['ai_replies'] - $cur['fallbacks']) / $cur['ai_replies'] * 100) : 100,
            'top_pages'  => (clone $conv)->whereNotNull('current_page_url')->selectRaw('current_page_url as url, count(*) as c')->groupBy('current_page_url')->orderByDesc('c')->limit(8)->get(),
            'gaps'       => DB::table('knowledge_gaps')->whereIn('widget_id', $widgetIds)->where('status', 'open')->orderByDesc('times_asked')->limit(5)->get(['question', 'times_asked']),
            'pipeline'   => Lead::where('user_id', $u->id)->whereIn('widget_id', $widgetIds)->selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status'),
            'per_widget' => Conversation::whereIn('widget_id', $widgetIds)->where('created_at', '>=', $from)->selectRaw('widget_id, count(*) as c')->groupBy('widget_id')->with('widget:id,name')->get()
                ->map(fn ($r) => ['name' => $r->widget?->name ?? '—', 'c' => $r->c])->sortByDesc('c')->values(),
        ];
    }
}
