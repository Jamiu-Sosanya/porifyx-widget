<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\LeadNote;
use Illuminate\Http\Request;

class LeadController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Lead::where('user_id', $user->id)
            ->with(['widget', 'conversation', 'visitor']);

        if ($temperature = $request->get('temperature')) {
            $query->where('temperature', $temperature);
        }
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }
        if ($widgetId = $request->get('widget_id')) {
            $query->where('widget_id', $widgetId);
        }
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('company', 'like', "%{$search}%");
            });
        }

        // Only known columns: the sort value comes from the URL.
        $sortBy = in_array($request->get('sort'), ['created_at', 'score', 'name', 'last_engagement_at'], true) ? $request->get('sort') : 'created_at';
        $sortDir = $request->get('dir') === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortBy, $sortDir);

        $boardLeads = (clone $query)->limit(300)->get(); // board shows every column at once
        $leads = $query->paginate(25);
        $pipeline = \App\Support\LeadPipeline::settings($user);

        // Stats
        $stats = [
            'total' => Lead::where('user_id', $user->id)->count(),
            'hot'   => Lead::where('user_id', $user->id)->where('temperature', 'hot')->count(),
            'warm'  => Lead::where('user_id', $user->id)->where('temperature', 'warm')->count(),
            'cold'  => Lead::where('user_id', $user->id)->where('temperature', 'cold')->count(),
            'new'   => Lead::where('user_id', $user->id)->where('status', 'new')->count(),
            'won'   => Lead::where('user_id', $user->id)->where('status', 'won')->count(),
        ];

        $widgets = $user->widgets()->get();

        return view('leads.index', compact('leads', 'stats', 'widgets', 'boardLeads', 'pipeline'));
    }

    public function show(Lead $lead)
    {
        $this->authorizeLead($lead);

        $lead->load(['widget', 'conversation.messages', 'visitor', 'leadNotes.user']);

        return view('leads.show', compact('lead'));
    }

    public function update(Request $request, Lead $lead)
    {
        $this->authorizeLead($lead);

        $validated = $request->validate([
            'status'      => 'nullable|in:' . implode(',', \App\Support\LeadPipeline::keys($request->user())),
            'priority'    => 'nullable|in:high,medium,low',
            'temperature' => 'nullable|in:hot,warm,cold',
            'score'       => 'nullable|integer|min:0|max:100',
            'notes'       => 'nullable|string|max:5000',
            'tags'        => 'nullable|string',
        ]);

        if (isset($validated['tags'])) {
            $validated['tags'] = array_filter(array_map('trim', explode(',', $validated['tags'])));
        }

        $lead->update(array_filter($validated, fn ($v) => $v !== null));

        if ($request->expectsJson()) return response()->json(['ok' => true, 'status' => $lead->status]);

        return back()->with('success', 'Lead updated.');
    }

    /** Board columns (lead statuses) and Hot/Warm thresholds. */
    public function pipeline(Request $request)
    {
        $d = $request->validate([
            'statuses'         => 'required|array|min:2|max:12',
            'statuses.*.label' => 'required|string|max:30',
            'statuses.*.color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'statuses.*.key'   => 'nullable|string|max:40',
            'warm'             => 'required|integer|min:1|max:99',
            'hot'              => 'required|integer|min:2|max:100|gt:warm',
        ]);
        $seen = [];
        $statuses = [];
        foreach ($d['statuses'] as $i => $st) {
            $key = \App\Support\LeadForm::key(($st['key'] ?? null) ?: $st['label']);
            while (isset($seen[$key])) $key .= '_' . $i;
            $seen[$key] = true;
            $statuses[] = ['key' => $key, 'label' => $st['label'], 'color' => strtolower($st['color'])];
        }
        $user = $request->user();
        $user->update(['lead_settings' => ['statuses' => $statuses, 'warm' => $d['warm'], 'hot' => $d['hot']]]);
        // Leads in a removed column move to the first one.
        Lead::where('user_id', $user->id)->whereNotIn('status', array_keys($seen))->update(['status' => $statuses[0]['key']]);

        return back()->with('success', 'Pipeline saved.');
    }

    public function addNote(Request $request, Lead $lead)
    {
        $this->authorizeLead($lead);

        $validated = $request->validate(['content' => 'required|string|max:5000']);

        $lead->leadNotes()->create([
            'user_id' => $request->user()->id,
            'content' => $validated['content'],
        ]);

        return back()->with('success', 'Note added.');
    }

    public function export(Request $request)
    {
        $user = $request->user();
        $leads = Lead::where('user_id', $user->id)->with(['widget'])->get();

        $csv = "Name,Email,Phone,Company,Score,Temperature,Status,Widget,Created\n";
        foreach ($leads as $lead) {
            $csv .= implode(',', [
                '"' . ($lead->name ?? '') . '"',
                '"' . ($lead->email ?? '') . '"',
                '"' . ($lead->phone ?? '') . '"',
                '"' . ($lead->company ?? '') . '"',
                $lead->score,
                $lead->temperature,
                $lead->status,
                '"' . ($lead->widget?->name ?? '') . '"',
                $lead->created_at->format('Y-m-d H:i'),
            ]) . "\n";
        }

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="leads-' . now()->format('Y-m-d') . '.csv"',
        ]);
    }

    public function destroy(Lead $lead)
    {
        $this->authorizeLead($lead);
        $lead->delete();
        return redirect()->route('leads.index')->with('success', 'Lead deleted.');
    }

    protected function authorizeLead(Lead $lead): void
    {
        abort_unless((int) $lead->user_id === (int) request()->user()->id || request()->user()->isAdmin(), 403);
    }
}
