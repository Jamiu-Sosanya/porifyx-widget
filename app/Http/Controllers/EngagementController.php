<?php

namespace App\Http\Controllers;

use App\Models\ProactiveMessage;
use App\Models\Widget;
use App\Support\Engagement;
use Illuminate\Http\Request;

/** Behaviour triggers, AI notice, hidden pages, action cards and voice input for one widget. */
class EngagementController extends Controller
{
    public const TRIGGERS = [
        'time_on_page' => ['Time on page', 'seconds'],
        'scroll_depth' => ['Scroll depth', '% of page'],
        'idle'         => ['Visitor idle', 'seconds'],
        'exit_intent'  => ['Exit intent (desktop)', null],
    ];

    public function show(Widget $widget)
    {
        $this->authorize('update', $widget);

        return view('widgets.engagement', [
            'widget'   => $widget,
            's'        => Engagement::settings($widget),
            'triggers' => ProactiveMessage::where('widget_id', $widget->id)->latest()->get(),
        ]);
    }

    public function save(Request $request, Widget $widget)
    {
        $this->authorize('update', $widget);
        $d = $request->validate([
            'ai_notice.title'        => 'nullable|string|max:120',
            'ai_notice.text'         => 'nullable|string|max:600',
            'ai_notice.decline_text' => 'nullable|string|max:300',
            'ai_notice.privacy_url'  => 'nullable|url:http,https|max:255',
            'hidden_paths'           => 'nullable|string|max:2000',
            'cards'                  => 'nullable|array|max:12',
            'cards.*.title'          => 'nullable|string|max:80',
            'cards.*.text'           => 'nullable|string|max:160',
            'cards.*.button'         => 'nullable|string|max:30',
            'cards.*.url'            => 'nullable|url:http,https|max:255',
            'cards.*.keywords'       => 'nullable|string|max:200',
        ]);

        $notice = array_merge(Engagement::DEFAULTS['ai_notice'], array_filter($d['ai_notice'] ?? [], fn ($v) => $v !== null));
        $notice['enabled'] = $request->boolean('ai_notice.enabled');
        $notice['privacy_url'] = $d['ai_notice']['privacy_url'] ?? '';

        $widget->update([
            'voice_input_enabled' => $request->boolean('voice_input_enabled'),
            'engagement' => [
                'ai_notice'    => $notice,
                'hidden_paths' => array_values(array_filter(array_map('trim', preg_split('/\R/', (string) ($d['hidden_paths'] ?? ''))))),
                'cards'        => array_values(array_filter($d['cards'] ?? [], fn ($c) => filled($c['title'] ?? null) && filled($c['keywords'] ?? null))),
            ],
        ]);

        return back()->with('success', 'Engagement settings saved.');
    }

    public function storeTrigger(Request $request, Widget $widget)
    {
        $this->authorize('update', $widget);
        $d = $this->trigger($request);
        ProactiveMessage::create($d + ['widget_id' => $widget->id, 'is_active' => true]);
        $widget->update(['proactive_enabled' => true]);

        return back()->with('success', 'Trigger added.');
    }

    public function updateTrigger(Request $request, Widget $widget, ProactiveMessage $trigger)
    {
        $this->authorize('update', $widget);
        abort_unless($trigger->widget_id === $widget->id, 404);
        $trigger->update($this->trigger($request) + ['is_active' => $request->boolean('is_active')]);

        return back()->with('success', 'Trigger updated.');
    }

    public function destroyTrigger(Widget $widget, ProactiveMessage $trigger)
    {
        $this->authorize('update', $widget);
        abort_unless($trigger->widget_id === $widget->id, 404);
        $trigger->delete();

        return back()->with('success', 'Trigger deleted.');
    }

    private function trigger(Request $request): array
    {
        $d = $request->validate([
            'name'               => 'nullable|string|max:80',
            'trigger_type'       => 'required|in:' . implode(',', array_keys(self::TRIGGERS)),
            'trigger_value'      => 'nullable|integer|min:0|max:3600',
            'message'            => 'required|string|max:500',
            'target_url_pattern' => 'nullable|string|max:200',
            'quick_replies'      => 'nullable|string|max:300',
        ]);
        $d['quick_replies'] = array_values(array_filter(array_map('trim', explode(',', (string) ($d['quick_replies'] ?? '')))));
        $d['trigger_value'] = (int) ($d['trigger_value'] ?? 0);
        $d['name'] = ($d['name'] ?? null) ?: self::TRIGGERS[$d['trigger_type']][0];

        return $d;
    }
}
