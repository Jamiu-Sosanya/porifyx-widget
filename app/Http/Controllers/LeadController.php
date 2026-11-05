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

        $sortBy = $request->get('sort', 'created_at');
        $sortDir = $request->get('dir', 'desc');
        $query->orderBy($sortBy, $sortDir);

        $leads = $query->paginate(25);

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

        return view('leads.index', compact('leads', 'stats', 'widgets'));
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
            'status'      => 'nullable|in:new,contacted,qualified,proposal,won,lost',
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

        return back()->with('success', 'Lead updated.');
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
