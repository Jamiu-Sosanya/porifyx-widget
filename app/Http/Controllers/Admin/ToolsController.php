<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Lead;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Widget;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Platform admin: widget error monitor, global search and the product changelog. */
class ToolsController extends Controller
{
    public function monitor(Request $request)
    {
        $window = ['24h' => 1, '7d' => 7, '30d' => 30][$request->get('window', '7d')] ?? 7;
        $q = DB::table('widget_events')->leftJoin('widgets', 'widgets.id', '=', 'widget_events.widget_id')
            ->select('widget_events.*', 'widgets.name as widget_name')->where('widget_events.created_at', '>=', now()->subDays($window));
        if (isset(\App\Support\WidgetEvents::TYPES[$request->get('type')])) $q->where('widget_events.type', $request->get('type'));
        if (in_array($request->get('severity'), ['error', 'warning'], true)) $q->where('severity', $request->get('severity'));
        if ($request->get('status') === 'open') $q->whereNull('resolved_at');
        if ($request->get('status') === 'resolved') $q->whereNotNull('resolved_at');
        $day = DB::table('widget_events')->where('created_at', '>=', now()->subDay());

        return view('admin.monitor', [
            'events' => $q->latest('widget_events.id')->limit(200)->get(),
            'stats'  => [
                'open' => DB::table('widget_events')->whereNull('resolved_at')->count(),
                'provider_down' => (clone $day)->where('type', 'provider_down')->count(),
                'reply_failed' => (clone $day)->where('type', 'reply_failed')->count(),
                'failover' => (clone $day)->where('type', 'failover')->count(),
                'client' => (clone $day)->whereIn('type', ['config_failed', 'voice_failed', 'client_freeze'])->count(),
                'total' => (clone $day)->count(),
            ],
            'byWidget' => DB::table('widget_events')->join('widgets', 'widgets.id', '=', 'widget_events.widget_id')->whereNull('resolved_at')
                ->selectRaw('widgets.name, count(*) as c')->groupBy('widgets.name')->orderByDesc('c')->limit(6)->get(),
        ]);
    }

    public function resolveEvents(Request $request)
    {
        $ids = array_map('intval', (array) $request->input('ids', []));
        $q = DB::table('widget_events')->whereNull('resolved_at');
        if (! $request->boolean('all')) $q->whereIn('id', $ids);
        $n = $q->update(['resolved_at' => now()]);

        return back()->with('success', "{$n} event(s) marked resolved.");
    }

    public function search(Request $request)
    {
        $q = mb_substr(trim((string) $request->get('q')), 0, 100);
        $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $q) . '%';
        $r = $q === '' ? null : [
            'Users'         => User::where(fn ($x) => $x->where('name', 'like', $like)->orWhere('email', 'like', $like))->limit(10)->get()->map(fn ($u) => [$u->name, $u->email, route('admin.users.show', $u)]),
            'Widgets'       => Widget::with('user')->where(fn ($x) => $x->where('name', 'like', $like)->orWhere('uuid', $q))->limit(10)->get()->map(fn ($w) => [$w->name, $w->user?->email, route('admin.widgets', ['search' => $w->name])]),
            'Leads'         => Lead::with('user')->where(fn ($x) => $x->where('email', 'like', $like)->orWhere('name', 'like', $like))->limit(10)->get()->map(fn ($l) => [$l->name ?: $l->email, 'Lead of ' . $l->user?->email, null]),
            'Conversations' => Conversation::with('visitor', 'widget')->whereHas('visitor', fn ($x) => $x->where('email', 'like', $like)->orWhere('name', 'like', $like))->limit(10)->get()->map(fn ($c) => [$c->visitor?->name ?: $c->visitor?->email, $c->widget?->name, route('admin.conversations', ['search' => $c->visitor?->email])]),
            'Tickets'       => Ticket::with('owner')->where(fn ($x) => $x->where('subject', 'like', $like)->orWhere('requester_email', 'like', $like))->limit(10)->get()->map(fn ($t) => [$t->number . ' ' . $t->subject, $t->owner?->email, null]),
        ];

        return view('admin.search', ['q' => $q, 'results' => $r]);
    }

    public function marketing()
    {
        return view('admin.marketing', [
            'current' => \App\Support\MarketingTheme::current(), 'hero' => \App\Support\MarketingTheme::hero(),
            'guarantee' => (string) \App\Models\SystemSetting::get('money_back_text', ''),
        ]);
    }

    public function saveMarketing(Request $request)
    {
        $d = $request->validate([
            'marketing_theme' => 'required|in:' . implode(',', array_keys(\App\Support\MarketingTheme::THEMES)),
            'hero_title' => 'nullable|string|max:120', 'hero_highlight' => 'nullable|string|max:80', 'hero_subtitle' => 'nullable|string|max:400', 'money_back_text' => 'nullable|string|max:160',
        ]);
        foreach ($d as $k => $v) {
            \App\Models\SystemSetting::updateOrCreate(['key' => $k], ['label' => ucfirst(str_replace('_', ' ', $k)), 'type' => 'string', 'group' => $k === 'money_back_text' ? 'payment' : 'branding', 'value' => (string) $v]);
        }
        \Illuminate\Support\Facades\Cache::flush();
        \App\Support\Audit::log('marketing.updated', null, ['theme' => $d['marketing_theme']]);

        return back()->with('success', 'Marketing site updated.');
    }

    public function changelog()
    {
        return view('admin.changelog', ['entries' => DB::table('changelog_entries')->latest('id')->get()]);
    }

    public function storeChangelog(Request $request)
    {
        $d = $request->validate(['title' => 'required|string|max:200', 'tag' => 'required|in:new,improved,fixed', 'body' => 'required|string|max:5000']);
        DB::table('changelog_entries')->insert($d + ['published_at' => $request->boolean('publish') ? now() : null, 'created_at' => now(), 'updated_at' => now()]);
        \App\Support\Audit::log('changelog.created', null, ['title' => $d['title']]);

        return back()->with('success', 'Changelog entry saved.');
    }

    public function toggleChangelog(int $id)
    {
        $e = DB::table('changelog_entries')->find($id);
        abort_unless($e, 404);
        DB::table('changelog_entries')->where('id', $id)->update(['published_at' => $e->published_at ? null : now(), 'updated_at' => now()]);

        return back()->with('success', $e->published_at ? 'Unpublished.' : 'Published.');
    }

    public function destroyChangelog(int $id)
    {
        DB::table('changelog_entries')->where('id', $id)->delete();

        return back()->with('success', 'Deleted.');
    }
}
