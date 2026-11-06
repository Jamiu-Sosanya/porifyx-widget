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
    public function monitor()
    {
        $since = now()->subDays(7);

        return view('admin.monitor', [
            'byWidget' => DB::table('widget_events')->join('widgets', 'widgets.id', '=', 'widget_events.widget_id')
                ->where('widget_events.created_at', '>=', $since)
                ->selectRaw('widgets.id, widgets.name, count(*) as errors, max(widget_events.created_at) as last_at')
                ->groupBy('widgets.id', 'widgets.name')->orderByDesc('errors')->limit(50)->get(),
            'recent' => DB::table('widget_events')->join('widgets', 'widgets.id', '=', 'widget_events.widget_id')
                ->select('widget_events.*', 'widgets.name as widget_name')->latest('widget_events.id')->limit(100)->get(),
        ]);
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
