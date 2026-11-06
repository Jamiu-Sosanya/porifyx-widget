<?php

namespace App\Http\Controllers;

use App\Models\Automation;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AutomationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $automations = Automation::where('user_id', $user->id)
            ->with('widget')
            ->withCount('logs')
            ->latest()
            ->paginate(20);

        $widgets = $user->widgets()->get();

        return view('automations.index', compact('automations', 'widgets'));
    }

    public function create()
    {
        $widgets = request()->user()->widgets()->get();

        $triggers = [
            'new_lead'         => ['name' => 'New Lead Captured', 'icon' => 'user-plus', 'description' => 'When a new lead is captured from a conversation'],
            'chat_started'     => ['name' => 'Chat Started', 'icon' => 'chat-bubble-left', 'description' => 'When a visitor starts a new conversation'],
            'chat_completed'   => ['name' => 'Chat Completed', 'icon' => 'check-circle', 'description' => 'When a conversation is marked as resolved'],
            'ai_fallback'      => ['name' => 'AI Fallback', 'icon' => 'exclamation-triangle', 'description' => 'When the AI cannot answer a question'],
            'hot_lead'         => ['name' => 'Hot Lead Detected', 'icon' => 'fire', 'description' => 'When a lead is scored as hot (80+)'],
            'keyword_detected' => ['name' => 'Keyword Detected', 'icon' => 'magnifying-glass', 'description' => 'When specific keywords appear in conversations'],
            'time_based'       => ['name' => 'Scheduled', 'icon' => 'clock', 'description' => 'Run on a schedule (daily, weekly)'],
        ];

        $actions = [
            'send_email'      => ['name' => 'Send Email', 'icon' => 'envelope', 'description' => 'Send an email notification'],
            'send_telegram'   => ['name' => 'Send Telegram', 'icon' => 'paper-airplane', 'description' => 'Send a Telegram notification'],
            'tag_lead'        => ['name' => 'Tag Lead', 'icon' => 'tag', 'description' => 'Add tags to the lead'],
            'assign_priority' => ['name' => 'Set Priority', 'icon' => 'flag', 'description' => 'Set lead priority level'],
            'assign_agent'    => ['name' => 'Assign Agent', 'icon' => 'user', 'description' => 'Assign conversation to a team member'],
            'webhook'         => ['name' => 'Send Webhook', 'icon' => 'globe-alt', 'description' => 'Send data to an external URL'],
            'add_to_crm'      => ['name' => 'Add to CRM', 'icon' => 'building-office', 'description' => 'Push lead data to your CRM'],
        ];

        return view('automations.create', compact('widgets', 'triggers', 'actions'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'               => 'required|string|max:255',
            'description'        => 'nullable|string|max:1000',
            'widget_id'          => ['nullable', Rule::exists('widgets', 'id')->where('user_id', $request->user()->id)],
            'trigger'            => 'required|in:new_lead,chat_started,chat_completed,ai_fallback,hot_lead,keyword_detected,time_based',
            'trigger_conditions' => 'nullable|array',
            'action'             => 'required|in:send_email,send_telegram,tag_lead,assign_priority,assign_agent,webhook,add_to_crm',
            'action_config'      => 'nullable|array',
        ]);

        if (! $request->user()->canUse('automations')) {
            return back()->with('error', 'Your plan does not include more automations. Upgrade to add more.');
        }

        $request->user()->automations()->create($validated);

        return redirect()->route('automations.index')->with('success', 'Automation created!');
    }

    public function edit(Automation $automation)
    {
        abort_unless((int) $automation->user_id === (int) request()->user()->id, 403);

        $widgets = request()->user()->widgets()->get();

        $triggers = [
            'new_lead'         => ['name' => 'New Lead Captured',    'description' => 'When a new lead is captured from a conversation'],
            'chat_started'     => ['name' => 'Chat Started',         'description' => 'When a visitor starts a new conversation'],
            'chat_completed'   => ['name' => 'Chat Completed',       'description' => 'When a conversation is marked as resolved'],
            'ai_fallback'      => ['name' => 'AI Fallback',          'description' => 'When the AI cannot answer a question'],
            'hot_lead'         => ['name' => 'Hot Lead Detected',    'description' => 'When a lead is scored as hot (80+)'],
            'keyword_detected' => ['name' => 'Keyword Detected',     'description' => 'When specific keywords appear in conversations'],
            'time_based'       => ['name' => 'Scheduled',            'description' => 'Run on a schedule (daily, weekly)'],
        ];

        $actions = [
            'send_email'      => ['name' => 'Send Email',       'description' => 'Send an email notification'],
            'send_telegram'   => ['name' => 'Send Telegram',    'description' => 'Send a Telegram notification'],
            'tag_lead'        => ['name' => 'Tag Lead',         'description' => 'Add tags to the lead'],
            'assign_priority' => ['name' => 'Set Priority',     'description' => 'Set lead priority level'],
            'assign_agent'    => ['name' => 'Assign Agent',     'description' => 'Assign conversation to a team member'],
            'webhook'         => ['name' => 'Send Webhook',     'description' => 'Send data to an external URL'],
            'add_to_crm'      => ['name' => 'Add to CRM',       'description' => 'Push lead data to your CRM'],
        ];

        return view('automations.edit', compact('automation', 'widgets', 'triggers', 'actions'));
    }

    public function update(Request $request, Automation $automation)
    {
        abort_unless((int) $automation->user_id === (int) $request->user()->id || $request->user()->isAdmin(), 403);

        $validated = $request->validate([
            'name'               => 'required|string|max:255',
            'description'        => 'nullable|string|max:1000',
            'widget_id'          => ['nullable', Rule::exists('widgets', 'id')->where('user_id', $request->user()->id)],
            'trigger'            => 'required|string',
            'trigger_conditions' => 'nullable|array',
            'action'             => 'required|string',
            'action_config'      => 'nullable|array',
            'is_active'          => 'nullable|boolean',
        ]);

        $automation->update($validated);

        return back()->with('success', 'Automation updated.');
    }

    public function toggleActive(Automation $automation)
    {
        abort_unless((int) $automation->user_id === (int) request()->user()->id, 403);

        $automation->update(['is_active' => !$automation->is_active]);

        return back()->with('success', $automation->is_active ? 'Automation activated.' : 'Automation paused.');
    }

    public function destroy(Automation $automation)
    {
        abort_unless((int) $automation->user_id === (int) request()->user()->id, 403);

        $automation->delete();

        return redirect()->route('automations.index')->with('success', 'Automation deleted.');
    }
}
