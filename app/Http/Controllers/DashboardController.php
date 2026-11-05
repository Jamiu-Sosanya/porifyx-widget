<?php

namespace App\Http\Controllers;

use App\Models\AnalyticsDaily;
use App\Models\AiInsight;
use App\Models\Conversation;
use App\Models\Lead;
use App\Models\Message;
use App\Models\Widget;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $period = $request->get('period', '30');
        $startDate = now()->subDays((int) $period);
        $prevStart = now()->subDays((int) $period * 2);
        $prevEnd   = now()->subDays((int) $period);

        $widgetIds = $user->widgets()->pluck('id');

        // Current period stats
        $totalWidgets       = $user->widgets()->count();
        $periodConversations = Conversation::whereIn('widget_id', $widgetIds)->where('created_at', '>=', $startDate)->count();
        $periodLeads        = Lead::where('user_id', $user->id)->where('created_at', '>=', $startDate)->count();
        $periodMessages     = Message::whereHas('conversation', fn ($q) => $q->whereIn('widget_id', $widgetIds))
                                ->where('created_at', '>=', $startDate)->count();

        // Previous period stats (for % change)
        $prevConversations = Conversation::whereIn('widget_id', $widgetIds)
            ->whereBetween('created_at', [$prevStart, $prevEnd])->count();
        $prevLeads = Lead::where('user_id', $user->id)
            ->whereBetween('created_at', [$prevStart, $prevEnd])->count();
        $prevMessages = Message::whereHas('conversation', fn ($q) => $q->whereIn('widget_id', $widgetIds))
            ->whereBetween('created_at', [$prevStart, $prevEnd])->count();

        $calcChange = fn ($curr, $prev) => $prev > 0 ? round((($curr - $prev) / $prev) * 100, 1) : ($curr > 0 ? 100 : 0);
        $convChange  = $calcChange($periodConversations, $prevConversations);
        $leadChange  = $calcChange($periodLeads, $prevLeads);
        $msgChange   = $calcChange($periodMessages, $prevMessages);

        // Sentiment average (current period)
        $avgSentiment = Message::whereHas('conversation', fn ($q) => $q->whereIn('widget_id', $widgetIds))
            ->where('created_at', '>=', $startDate)
            ->whereNotNull('sentiment_score')
            ->avg('sentiment_score');
        $avgSentiment = $avgSentiment !== null ? round((float) $avgSentiment, 2) : null;

        // Sentiment label
        $sentimentLabel = match (true) {
            $avgSentiment === null        => 'No Data',
            $avgSentiment >= 0.5          => 'Very Positive',
            $avgSentiment >= 0.15         => 'Positive',
            $avgSentiment >= -0.15        => 'Neutral',
            $avgSentiment >= -0.5         => 'Negative',
            default                       => 'Very Negative',
        };
        $sentimentColor = match (true) {
            $avgSentiment === null        => 'gray',
            $avgSentiment >= 0.15         => 'emerald',
            $avgSentiment >= -0.15        => 'amber',
            default                       => 'rose',
        };

        // Top widgets by conversations in period
        $topWidgets = $user->widgets()
            ->select('widgets.*')
            ->withCount([
                'conversations as period_conversations' => fn ($q) => $q->where('created_at', '>=', $startDate),
                'leads as period_leads'                  => fn ($q) => $q->where('created_at', '>=', $startDate),
            ])
            ->orderByDesc('period_conversations')
            ->take(5)
            ->get();

        // Recent conversations
        $recentConversations = Conversation::whereIn('widget_id', $widgetIds)
            ->with(['widget', 'visitor', 'lastMessage'])
            ->orderBy('last_message_at', 'desc')
            ->take(10)
            ->get();

        // Recent leads
        $recentLeads = Lead::where('user_id', $user->id)
            ->with(['widget', 'conversation'])
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        // Activity feed: last 8 conversations + 8 new leads merged and sorted
        $activityConversations = Conversation::whereIn('widget_id', $widgetIds)
            ->with(['visitor', 'widget'])
            ->orderBy('created_at', 'desc')
            ->take(8)->get()
            ->map(fn ($c) => [
                'type'    => 'conversation',
                'icon'    => 'chat',
                'color'   => 'brand',
                'title'   => 'New conversation',
                'desc'    => ($c->visitor?->name ?? $c->visitor?->email ?? 'Visitor') . ' on ' . ($c->widget?->name ?? '—'),
                'time'    => $c->created_at,
                'url'     => route('conversations.show', $c),
            ]);

        $activityLeads = Lead::where('user_id', $user->id)
            ->with(['widget'])
            ->orderBy('created_at', 'desc')
            ->take(8)->get()
            ->map(fn ($l) => [
                'type'  => 'lead',
                'icon'  => 'user',
                'color' => 'accent',
                'title' => 'Lead captured',
                'desc'  => ($l->name ?? $l->email ?? 'Unknown') . ' — ' . ucfirst($l->temperature ?? 'cold'),
                'time'  => $l->created_at,
                'url'   => route('leads.show', $l),
            ]);

        $activityFeed = $activityConversations->concat($activityLeads)
            ->sortByDesc('time')->take(10)->values();

        // Charts data
        $chartData = AnalyticsDaily::where('user_id', $user->id)
            ->where('date', '>=', $startDate)
            ->orderBy('date')
            ->get()
            ->groupBy(fn ($item) => $item->date->format('Y-m-d'));

        $chartLabels = [];
        $chartConversations = [];
        $chartMessages = [];
        $chartLeads = [];

        for ($i = (int) $period; $i >= 0; $i--) {
            $date    = now()->subDays($i)->format('Y-m-d');
            $dayData = $chartData->get($date, collect());

            $chartLabels[]        = now()->subDays($i)->format('M d');
            $chartConversations[] = $dayData->sum('conversations');
            $chartMessages[]      = $dayData->sum('messages');
            $chartLeads[]         = $dayData->sum('leads');
        }

        // Hot leads
        $hotLeads = Lead::where('user_id', $user->id)->where('temperature', 'hot')->where('status', 'new')->count();

        // Unread conversations
        $unreadConversations = Conversation::whereIn('widget_id', $widgetIds)->where('is_read', false)->count();

        // Subscription info
        $subscription  = $user->subscription;
        $plan          = $subscription?->plan;
        $messagesUsed  = $user->getUsageThisMonth('messages');
        $leadsUsed     = $user->getUsageThisMonth('leads');
        $messagesLimit = $plan?->messages_per_month ?? 0;
        $leadsLimit    = $plan?->leads_per_month ?? 0;

        // Widgets list
        $widgets = $user->widgets()->withCount(['conversations', 'leads'])->get();

        // AI Insights
        $insights = AiInsight::where('user_id', $user->id)
            ->orderBy('importance', 'desc')
            ->take(5)
            ->get();

        // Recent Conversation Summaries
        $conversationSummaries = Conversation::whereIn('widget_id', $widgetIds)
            ->whereNotNull('summary')
            ->with(['visitor', 'widget', 'lead'])
            ->orderBy('summary_generated_at', 'desc')
            ->take(6)
            ->get()
            ->map(function ($conv) {
                return [
                    'id'              => $conv->id,
                    'uuid'            => $conv->uuid,
                    'visitor_name'    => $conv->visitor?->name ?? $conv->visitor?->email ?? 'Visitor',
                    'widget_name'     => $conv->widget?->name ?? '—',
                    'summary'         => $conv->summary,
                    'action_items'    => $conv->action_items ?? [],
                    'sentiment'       => $conv->avg_sentiment,
                    'quality_grade'   => $conv->lead?->quality_grade,
                    'engagement_score' => $conv->lead?->engagement_score,
                    'message_count'   => $conv->message_count ?? 0,
                    'status'          => $conv->status,
                    'generated_at'    => $conv->summary_generated_at,
                    'url'             => route('conversations.show', $conv),
                ];
            });

        $totalConversations = Conversation::whereIn('widget_id', $widgetIds)->count();
        $totalLeads         = Lead::where('user_id', $user->id)->count();
        $totalMessages      = $user->widgets()->sum('total_messages');

        return view('dashboard.index', compact(
            'totalWidgets', 'totalConversations', 'totalLeads', 'totalMessages',
            'periodConversations', 'periodLeads', 'periodMessages',
            'convChange', 'leadChange', 'msgChange',
            'avgSentiment', 'sentimentLabel', 'sentimentColor',
            'topWidgets', 'activityFeed',
            'recentConversations', 'recentLeads', 'conversationSummaries',
            'chartLabels', 'chartConversations', 'chartMessages', 'chartLeads',
            'hotLeads', 'unreadConversations', 'subscription', 'plan',
            'messagesUsed', 'leadsUsed', 'messagesLimit', 'leadsLimit',
            'insights', 'widgets', 'period'
        ));
    }

    /**
     * Show all notifications.
     */
    public function notifications(Request $request)
    {
        $notifications = $request->user()->notifications()
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('notifications.index', compact('notifications'));
    }

    /**
     * Mark a notification as read.
     */
    public function markNotificationRead(Request $request, $notification)
    {
        $notification = $request->user()->notifications()->findOrFail($notification);
        $notification->markAsRead();

        return back()->with('success', 'Notification marked as read.');
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllNotificationsRead(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('success', 'All notifications marked as read.');
    }
}
