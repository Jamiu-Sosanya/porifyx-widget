<?php

namespace App\Jobs;

use App\Models\AnalyticsDaily;
use App\Models\Conversation;
use App\Models\Lead;
use App\Models\Message;
use App\Models\User;
use App\Models\Widget;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AggregateAnalytics implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public ?string $date = null,
    ) {
        $this->date = $date ?? now()->subDay()->format('Y-m-d');
    }

    public function handle(): void
    {
        $date = $this->date;

        $users = User::where('role', 'user')->where('is_active', true)->get();

        foreach ($users as $user) {
            $widgets = $user->widgets()->get();

            foreach ($widgets as $widget) {
                $conversations = Conversation::where('widget_id', $widget->id)
                    ->whereDate('created_at', $date)->count();
                $messages = Message::whereHas('conversation', fn ($q) => $q->where('widget_id', $widget->id))
                    ->whereDate('created_at', $date)->count();
                $leads = Lead::where('widget_id', $widget->id)
                    ->whereDate('created_at', $date)->count();
                $aiResponses = Message::whereHas('conversation', fn ($q) => $q->where('widget_id', $widget->id))
                    ->where('role', 'ai')->whereDate('created_at', $date)->count();
                $fallbacks = Message::whereHas('conversation', fn ($q) => $q->where('widget_id', $widget->id))
                    ->where('is_fallback', true)->whereDate('created_at', $date)->count();
                $resolved = Conversation::where('widget_id', $widget->id)
                    ->where('status', 'resolved')->whereDate('resolved_at', $date)->count();

                AnalyticsDaily::updateOrCreate(
                    ['user_id' => $user->id, 'widget_id' => $widget->id, 'date' => $date],
                    [
                        'conversations'   => $conversations,
                        'messages'        => $messages,
                        'leads'           => $leads,
                        'ai_responses'    => $aiResponses,
                        'fallbacks'       => $fallbacks,
                        'resolution_rate' => $conversations > 0 ? round(($resolved / $conversations) * 100, 1) : 0,
                    ]
                );
            }
        }
    }
}
