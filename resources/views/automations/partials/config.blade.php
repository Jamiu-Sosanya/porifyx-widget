@php
    $tc = old('trigger_conditions', $automation->trigger_conditions ?? []);
    $ac = old('action_config', $automation->action_config ?? []);
    $in = 'w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white';
    $agents = \App\Models\User::whereIn('id', \App\Models\TeamMember::where('owner_id', auth()->id())->where('status', 'active')->pluck('user_id')->push(auth()->id()))->get(['id', 'name']);
@endphp
<div class="space-y-3">
    <div x-show="trigger === 'keyword_detected'" x-cloak class="p-4 bg-white/[0.02] rounded-xl">
        <label class="block text-sm font-medium text-gray-300 mb-1">Keywords (comma separated)</label>
        <input name="trigger_conditions[keywords]" value="{{ $tc['keywords'] ?? '' }}" placeholder="refund, cancel, speak to manager" class="{{ $in }}">
        <p class="mt-1 text-xs text-gray-500">Runs when a visitor message contains any of these.</p>
    </div>
    <div x-show="action === 'assign_priority'" x-cloak class="p-4 bg-white/[0.02] rounded-xl">
        <label class="block text-sm font-medium text-gray-300 mb-1">Priority</label>
        <select name="action_config[priority]" class="{{ $in }}">@foreach(['high' => 'High', 'medium' => 'Medium', 'low' => 'Low'] as $k => $l)<option value="{{ $k }}" @selected(($ac['priority'] ?? 'high') === $k)>{{ $l }}</option>@endforeach</select>
    </div>
    <div x-show="action === 'assign_agent'" x-cloak class="p-4 bg-white/[0.02] rounded-xl">
        <label class="block text-sm font-medium text-gray-300 mb-1">Assign the conversation to</label>
        <select name="action_config[agent_id]" class="{{ $in }}">@foreach($agents as $a)<option value="{{ $a->id }}" @selected(($ac['agent_id'] ?? null) == $a->id)>{{ $a->name }}</option>@endforeach</select>
    </div>
    <div x-show="action === 'send_telegram'" x-cloak class="p-4 bg-white/[0.02] rounded-xl grid sm:grid-cols-2 gap-3">
        <label class="text-xs text-gray-400">Bot token (from @BotFather)<input name="action_config[bot_token]" value="{{ $ac['bot_token'] ?? '' }}" class="mt-1 {{ $in }}"></label>
        <label class="text-xs text-gray-400">Chat ID<input name="action_config[chat_id]" value="{{ $ac['chat_id'] ?? '' }}" class="mt-1 {{ $in }}"></label>
    </div>
    <div x-show="action === 'add_to_crm'" x-cloak class="p-4 bg-white/[0.02] rounded-xl">
        <label class="block text-sm font-medium text-gray-300 mb-1">CRM webhook URL (Zapier, Make, HubSpot workflow…)</label>
        <input name="action_config[url]" type="url" value="{{ $ac['url'] ?? '' }}" placeholder="https://hooks.zapier.com/…" class="{{ $in }}">
        <p class="mt-1 text-xs text-gray-500">Receives the lead and conversation as signed JSON (X-PoriFyx-Signature).</p>
    </div>
</div>
