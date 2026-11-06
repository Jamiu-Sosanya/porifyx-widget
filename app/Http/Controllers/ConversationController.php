<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Services\AiChatService;
use App\Services\ConversationSummaryService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ConversationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $widgetIds = $user->widgets()->pluck('id');

        $query = Conversation::whereIn('widget_id', $widgetIds)
            ->with(['widget', 'visitor', 'lastMessage', 'assignedAgent'])
            ->withCount('messages'); // real count; the stored message_count column is not always updated

        // Filters
        if ($tag = trim((string) $request->get('tag'))) {
            $query->where('tags', 'like', '%"' . str_replace(['%', '_', '"'], '', $tag) . '"%');
        }
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }
        if ($widgetId = $request->get('widget_id')) {
            $query->where('widget_id', $widgetId);
        }
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('visitor', fn ($v) => $v->where('email', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%"))
                  ->orWhereHas('messages', fn ($m) => $m->where('content', 'like', "%{$search}%"));
            });
        }
        if ($request->get('unread')) {
            $query->where('is_read', false);
        }
        if ($request->get('starred')) {
            $query->where('is_starred', true);
        }

        $conversations = $query->orderBy('last_message_at', 'desc')->paginate(25);

        $widgets = $user->widgets()->get();

        // Stats
        $stats = [
            'total'    => Conversation::whereIn('widget_id', $widgetIds)->count(),
            'active'   => Conversation::whereIn('widget_id', $widgetIds)->whereIn('status', ['ai_active', 'human_active', 'open'])->count(),
            'unread'   => Conversation::whereIn('widget_id', $widgetIds)->where('is_read', false)->count(),
            'resolved' => Conversation::whereIn('widget_id', $widgetIds)->where('status', 'resolved')->count(),
        ];

        return view('conversations.index', compact('conversations', 'widgets', 'stats'));
    }

    public function export(Request $request): StreamedResponse
    {
        $user = $request->user();
        $widgetIds = $user->widgets()->pluck('id');

        $query = Conversation::whereIn('widget_id', $widgetIds)
            ->with(['widget', 'visitor']);

        if ($tag = trim((string) $request->get('tag'))) {
            $query->where('tags', 'like', '%"' . str_replace(['%', '_', '"'], '', $tag) . '"%');
        }
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }
        if ($widgetId = $request->get('widget_id')) {
            $query->where('widget_id', $widgetId);
        }
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('visitor', fn ($v) => $v->where('email', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%"))
                    ->orWhereHas('messages', fn ($m) => $m->where('content', 'like', "%{$search}%"));
            });
        }
        if ($request->get('unread')) {
            $query->where('is_read', false);
        }
        if ($request->get('starred')) {
            $query->where('is_starred', true);
        }

        $filename = 'conversations-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['UUID', 'Widget', 'Status', 'Visitor name', 'Visitor email', 'Messages', 'CSAT', 'Last activity', 'Created']);

            $query->orderBy('last_message_at', 'desc')->chunk(200, function ($rows) use ($handle) {
                foreach ($rows as $c) {
                    fputcsv($handle, [
                        $c->uuid,
                        $c->widget?->name ?? '',
                        $c->status,
                        $c->visitor?->name ?? '',
                        $c->visitor?->email ?? '',
                        $c->message_count,
                        $c->csat_score ?? '',
                        $c->last_message_at?->format('Y-m-d H:i') ?? '',
                        $c->created_at->format('Y-m-d H:i'),
                    ]);
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function tags(Request $request, Conversation $conversation)
    {
        $this->authorizeConversation($conversation);
        $d = $request->validate(['tags' => 'nullable|string|max:300']);
        $tags = collect(explode(',', (string) ($d['tags'] ?? '')))->map(fn ($t) => mb_substr(trim(strip_tags($t)), 0, 30))->filter()->unique()->take(10)->values()->all();
        $conversation->update(['tags' => $tags]);

        return back()->with('success', 'Tags saved.');
    }

    public function show(Conversation $conversation)
    {
        $this->authorizeConversation($conversation);

        $conversation->load(['widget', 'visitor', 'assignedAgent', 'lead']);
        $messages = $conversation->messages()
            ->with('agent')
            ->orderBy('created_at', 'asc')
            ->get();

        // Mark as read
        if (!$conversation->is_read) {
            $conversation->update(['is_read' => true]);
            $conversation->messages()->where('is_read', false)->update(['is_read' => true]);
        }

        // Reconstruct session timeline from available data points
        $timeline = $this->buildSessionTimeline($conversation, $messages);

        // Prior conversations for this visitor
        $priorConversations = $conversation->visitor_id
            ? \App\Models\Conversation::where('visitor_id', $conversation->visitor_id)
                ->where('id', '!=', $conversation->id)
                ->latest()
                ->limit(5)
                ->get(['id', 'uuid', 'status', 'created_at', 'message_count'])
            : collect();

        return view('conversations.show', compact('conversation', 'messages', 'timeline', 'priorConversations'));
    }

    public function sendMessage(Request $request, Conversation $conversation)
    {
        $this->authorizeConversation($conversation);

        $validated = $request->validate([
            'content' => 'required|string|max:5000',
        ]);

        // Send as agent
        $message = $conversation->messages()->create([
            'role'     => 'agent',
            'content'  => $validated['content'],
            'agent_id' => $request->user()->id,
        ]);

        // Switch to human mode if AI was active
        if ($conversation->isAiActive()) {
            $conversation->switchToHuman($request->user());
        }

        $conversation->update(['last_message_at' => now(), 'message_count' => $conversation->messages()->count()]);

        return back()->with('success', 'Message sent.');
    }

    public function toggleStar(Conversation $conversation)
    {
        $this->authorizeConversation($conversation);

        $conversation->update(['is_starred' => !$conversation->is_starred]);

        return back();
    }

    public function resolve(Conversation $conversation)
    {
        $this->authorizeConversation($conversation);

        $conversation->update([
            'status'      => 'resolved',
            'resolved_at' => now(),
        ]);

        // Generate AI summary and action items
        $summaryService = new ConversationSummaryService();
        if ($summaryService->shouldGenerateSummary($conversation)) {
            $summaryService->generateSummary($conversation);
        }

        return back()->with('success', 'Conversation marked as resolved.');
    }

    public function takeOver(Request $request, Conversation $conversation)
    {
        $this->authorizeConversation($conversation);

        $conversation->switchToHuman($request->user());

        return back()->with('success', 'You have taken over this conversation.');
    }

    public function switchToAi(Conversation $conversation)
    {
        $this->authorizeConversation($conversation);

        $conversation->switchToAi();

        return back()->with('success', 'Conversation switched back to AI.');
    }

    public function markAsSpam(Conversation $conversation)
    {
        $this->authorizeConversation($conversation);

        $conversation->update(['status' => 'spam']);

        return back()->with('success', 'Conversation marked as spam.');
    }

    public function destroy(Conversation $conversation)
    {
        $this->authorizeConversation($conversation);

        $conversation->delete();

        return redirect()->route('conversations.index')->with('success', 'Conversation deleted.');
    }

    public function agentAssist(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorizeConversation($conversation);

        $aiService = app(AiChatService::class);
        $suggestion = $aiService->agentAssist($conversation);

        return response()->json(['suggestion' => $suggestion]);
    }

    public function addLabel(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorizeConversation($conversation);

        $validated = $request->validate(['label' => 'required|string|max:50']);
        $labels = $conversation->labels ?? [];
        if (!in_array($validated['label'], $labels)) {
            $labels[] = $validated['label'];
            $conversation->update(['labels' => $labels]);
        }

        return response()->json(['labels' => $conversation->fresh()->labels]);
    }

    public function removeLabel(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorizeConversation($conversation);

        $validated = $request->validate(['label' => 'required|string|max:50']);
        $labels = array_values(array_filter($conversation->labels ?? [], fn ($l) => $l !== $validated['label']));
        $conversation->update(['labels' => $labels]);

        return response()->json(['labels' => $labels]);
    }

    protected function authorizeConversation(Conversation $conversation): void
    {
        $user = request()->user();
        $widgetIds = $user->widgets()->pluck('id');
        abort_unless($widgetIds->contains($conversation->widget_id) || $user->isAdmin(), 403);
    }

    protected function buildSessionTimeline(\App\Models\Conversation $conversation, \Illuminate\Support\Collection $messages): array
    {
        $events = [];

        $events[] = [
            'at'    => $conversation->created_at,
            'icon'  => '💬',
            'color' => 'brand',
            'label' => 'Conversation started',
            'meta'  => $conversation->current_page_url
                ? 'Page: ' . parse_url($conversation->current_page_url, PHP_URL_PATH)
                : null,
        ];

        if ($conversation->first_response_at) {
            $events[] = [
                'at'    => $conversation->first_response_at,
                'icon'  => '🤖',
                'color' => 'accent',
                'label' => 'AI first responded',
                'meta'  => null,
            ];
        }

        $systemMessages = $messages->where('role', 'system');
        foreach ($systemMessages as $sm) {
            $events[] = [
                'at'    => $sm->created_at,
                'icon'  => '⚙️',
                'color' => 'gray',
                'label' => $sm->content,
                'meta'  => null,
            ];
        }

        if ($conversation->lead) {
            $events[] = [
                'at'    => $conversation->lead->created_at,
                'icon'  => '🔥',
                'color' => 'amber',
                'label' => 'Lead qualified',
                'meta'  => $conversation->lead->score ? "Score: {$conversation->lead->score}" : null,
            ];
        }

        if ($conversation->csat_submitted_at) {
            $events[] = [
                'at'    => $conversation->csat_submitted_at,
                'icon'  => '⭐',
                'color' => 'amber',
                'label' => "CSAT rating: {$conversation->csat_score}/5",
                'meta'  => $conversation->csat_comment ? "\"{$conversation->csat_comment}\"" : null,
            ];
        }

        if ($conversation->resolved_at) {
            $events[] = [
                'at'    => $conversation->resolved_at,
                'icon'  => '✅',
                'color' => 'emerald',
                'label' => 'Conversation resolved',
                'meta'  => null,
            ];
        }

        usort($events, fn ($a, $b) => $a['at'] <=> $b['at']);

        return $events;
    }
}
