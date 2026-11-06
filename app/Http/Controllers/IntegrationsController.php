<?php

namespace App\Http\Controllers;

use App\Models\CalendarConnection;
use App\Models\ChannelConfiguration;
use App\Models\CrmConnection;
use App\Support\AiTools;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** One place to see and connect every integration, per widget. */
class IntegrationsController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $widgets = $user->widgets()->get(['id', 'name', 'uuid', 'slack_webhook_url', 'discord_webhook_url', 'webhook_enabled', 'webhook_url', 'tools', 'notification_email']);
        $w = $widgets->firstWhere('id', $request->integer('widget')) ?? $widgets->first();
        if (! $w) return redirect()->route('widgets.create')->with('error', 'Create a widget first, then connect your tools.');

        $crm = CrmConnection::where('widget_id', $w->id)->get()->keyBy('provider');
        $cal = CalendarConnection::where('widget_id', $w->id)->get()->groupBy('provider');
        $channels = ChannelConfiguration::where('widget_id', $w->id)->where('is_enabled', true)->get()->keyBy('channel_type');
        $tools = AiTools::settings($w);
        $on = fn ($v, $label = 'Connected') => $v ? ['on', $label] : ['off', 'Not connected'];
        $booking = route('widgets.booking', $w) . '#integrations';
        $edit = fn ($hash) => route('widgets.edit', $w) . '#' . $hash;

        $groups = [
            'Website & store' => [
                ['WordPress', '🧩', 'Plugin adds the widget and syncs posts and pages into the knowledge base.', ['info', 'Plugin'], route('widgets.embed', $w)],
                ['WooCommerce', '🛒', 'Order status and one-time discount codes inside the chat.', $on($tools['woo']['url'] && ($tools['woo']['orders'] || $tools['woo']['coupons'])), route('widgets.tools', $w)],
                ['Shopify, Wix, Squarespace, Webflow', '🌐', 'Paste one line of code; step-by-step guides.', ['info', 'Guide'], route('widgets.embed', $w)],
            ],
            'CRM' => collect(['hubspot' => ['HubSpot', '🟠'], 'pipedrive' => ['Pipedrive', '🟢'], 'gohighlevel' => ['GoHighLevel', '🔷'], 'zoho' => ['Zoho CRM', '🔴'], 'salesforce' => ['Salesforce', '☁️']])
                ->map(fn ($m, $k) => [$m[0], $m[1], 'Leads and bookings create contacts and deals automatically.', $crm->has($k) ? ($crm[$k]->last_error ? ['warn', 'Error: check'] : ($crm[$k]->is_active ? ['on', 'Connected'] : ['off', 'Paused'])) : ['off', 'Not connected'], $booking])->values()->all(),
            'Calendars' => [
                ['Google Calendar', '📅', 'Real availability and events for bookings.', $on($cal->has('google'), $cal->get('google')?->count() . ' calendar(s)'), $booking],
                ['Microsoft Outlook', '📆', 'Outlook / Microsoft 365 calendars for bookings.', $on($cal->has('microsoft'), $cal->get('microsoft')?->count() . ' calendar(s)'), $booking],
            ],
            'Messaging channels' => collect(['whatsapp' => ['WhatsApp', '💬'], 'telegram' => ['Telegram', '✈️'], 'messenger' => ['Messenger', '💙'], 'instagram' => ['Instagram', '📸'], 'sms' => ['SMS', '📱'], 'email' => ['Email', '✉️'], 'slack' => ['Slack app', '#️⃣'], 'discord' => ['Discord bot', '🎮']])
                ->map(fn ($m, $k) => [$m[0], $m[1], 'Answer visitors on ' . $m[0] . ' with the same assistant.', $on($channels->has($k)), $edit('channels')])->values()->all(),
            'Team alerts' => [
                ['Slack alerts', '🔔', 'New leads and handoffs posted to a Slack channel.', $on(filled($w->slack_webhook_url)), $edit('notifications')],
                ['Discord alerts', '🔔', 'New leads and handoffs posted to Discord.', $on(filled($w->discord_webhook_url)), $edit('notifications')],
                ['Email alerts', '📧', 'Lead and handoff emails to your team.', $on(filled($w->notification_email), $w->notification_email ?: 'Connected'), $edit('notifications')],
                ['Support inbox email', '📥', 'Customer emails become tickets (Mailgun, Postmark, SendGrid).', $on(filled(($user->support_settings ?? [])['inbound_address'] ?? null)), route('tickets.index') . '#email'],
            ],
            'Automation & developers' => [
                ['Webhooks', '🪝', 'Signed events (lead captured, handoff, feedback) to any URL; works with Zapier and Make.', $on($w->webhook_enabled && $w->webhook_url), $edit('notifications')],
                ['Automations', '⚡', 'If-this-then-that rules for leads and chats.', ['info', DB::table('automations')->where('user_id', $user->id)->count() . ' rule(s)'], route('automations.index')],
                ['MCP servers', '🔌', 'Let the assistant call your own systems mid-conversation.', $on(count($tools['mcp']) > 0, count($tools['mcp']) . ' server(s)'), route('widgets.tools', $w)],
                ['REST API', '🧑‍💻', 'API keys for bookings, leads and more.', $on(DB::table('api_keys')->where('user_id', $user->id)->exists(), 'Keys created'), route('settings.apiKeys')],
            ],
        ];

        return view('integrations.index', compact('widgets', 'w', 'groups'));
    }
}
