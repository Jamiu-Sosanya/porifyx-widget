<?php

namespace App\Http\Controllers;

use App\Mail\TicketMail;
use App\Models\Conversation;
use App\Models\TeamMember;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

/** Support tickets: created by hand or from a chat, replied to by email, with notes, assignment, escalation and merging. */
class TicketController extends Controller
{
    public function index(Request $request)
    {
        $u = $request->user();
        $q = Ticket::where(fn ($w) => $w->where('user_id', $u->id)->orWhere('assigned_to', $u->id))->whereNull('merged_into_id')->with('assignee');
        $status = $request->get('status', 'active');
        if ($status === 'active') $q->whereIn('status', ['open', 'pending']);
        elseif (isset(Ticket::STATUSES[$status])) $q->where('status', $status);
        if ($request->get('mine')) $q->where('assigned_to', $u->id);
        if ($s = trim((string) $request->get('q'))) {
            $q->where(fn ($w) => $w->where('subject', 'like', "%{$s}%")->orWhere('requester_email', 'like', "%{$s}%")
                ->orWhere('id', (int) preg_replace('/\D/', '', $s) - 1000));
        }

        return view('tickets.index', [
            'tickets'  => $q->orderByRaw("case priority when 'urgent' then 0 when 'high' then 1 when 'normal' then 2 else 3 end")->latest('updated_at')->paginate(25)->withQueryString(),
            'counts'   => Ticket::where('user_id', $u->id)->whereNull('merged_into_id')->selectRaw('status, count(*) c')->groupBy('status')->pluck('c', 'status'),
            'status'   => $status,
            'settings' => $this->settings($u),
        ]);
    }

    public function create(Request $request)
    {
        $conv = $request->filled('conversation') ? Conversation::where('user_id', $request->user()->id)->where('uuid', $request->get('conversation'))->first() : null;

        return view('tickets.create', ['conversation' => $conv, 'widgets' => $request->user()->widgets()->get(['id', 'name'])]);
    }

    public function store(Request $request)
    {
        $u = $request->user();
        $d = $request->validate([
            'subject'          => 'required|string|max:200',
            'requester_name'   => 'nullable|string|max:120',
            'requester_email'  => 'required|email|max:190',
            'message'          => 'required|string|max:10000',
            'priority'         => 'required|in:' . implode(',', array_keys(Ticket::PRIORITIES)),
            'widget_id'        => 'nullable|integer',
            'conversation_uuid' => 'nullable|string|max:64',
            'notify'           => 'nullable|boolean',
        ]);
        $conv = filled($d['conversation_uuid'] ?? null) ? Conversation::where('user_id', $u->id)->where('uuid', $d['conversation_uuid'])->first() : null;
        $widgetId = $conv?->widget_id ?? $u->widgets()->whereKey($d['widget_id'] ?? 0)->value('id');

        $t = Ticket::create([
            'user_id' => $u->id, 'widget_id' => $widgetId, 'conversation_id' => $conv?->id,
            'subject' => $d['subject'], 'requester_name' => $d['requester_name'] ?? null, 'requester_email' => mb_strtolower($d['requester_email']),
            'priority' => $d['priority'], 'status' => 'open', 'last_customer_reply_at' => now(),
        ]);
        $t->log('customer', $d['message']);
        if ($conv) $t->log('event', 'Created from a chat conversation.', $u->id);
        if ($request->boolean('notify')) Mail::to($t->requester_email)->send(new TicketMail($t, 'created', $d['message']));

        return redirect()->route('tickets.show', $t)->with('success', "Ticket {$t->number} created.");
    }

    public function show(Request $request, Ticket $ticket)
    {
        abort_unless($ticket->canBeSeenBy($request->user()), 403);
        if ($ticket->merged_into_id) return redirect()->route('tickets.show', $ticket->merged_into_id)->with('success', "{$ticket->number} was merged into this ticket.");
        $ticket->load('messages.user', 'conversation', 'assignee');

        return view('tickets.show', ['ticket' => $ticket, 'agents' => $this->agents($ticket->owner)]);
    }

    public function reply(Request $request, Ticket $ticket)
    {
        abort_unless($ticket->canBeSeenBy($request->user()), 403);
        $d = $request->validate(['body' => 'required|string|max:10000', 'type' => 'required|in:reply,note', 'status' => 'nullable|in:' . implode(',', array_keys(Ticket::STATUSES))]);

        $ticket->log($d['type'], $d['body'], $request->user()->id);
        if ($d['type'] === 'reply') {
            Mail::to($ticket->requester_email)->send(new TicketMail($ticket, 'reply', $d['body']));
            $ticket->update(['last_agent_reply_at' => now(), 'escalated_at' => null, 'status' => $d['status'] ?? 'pending']);
        } elseif (! empty($d['status'])) {
            $ticket->update(['status' => $d['status']]);
        }

        return back()->with('success', $d['type'] === 'reply' ? 'Reply sent to ' . $ticket->requester_email . '.' : 'Note added.');
    }

    public function update(Request $request, Ticket $ticket)
    {
        abort_unless($ticket->canBeSeenBy($request->user()), 403);
        $agents = $this->agents($ticket->owner)->pluck('id')->all();
        $d = $request->validate([
            'status'      => 'nullable|in:' . implode(',', array_keys(Ticket::STATUSES)),
            'priority'    => 'nullable|in:' . implode(',', array_keys(Ticket::PRIORITIES)),
            'assigned_to' => ['nullable', 'integer', \Illuminate\Validation\Rule::in($agents)],
        ]);
        $changes = [];
        foreach (['status' => Ticket::STATUSES, 'priority' => Ticket::PRIORITIES] as $k => $labels) {
            if (! empty($d[$k]) && $d[$k] !== $ticket->$k) $changes[] = ucfirst($k) . ' → ' . $labels[$d[$k]];
        }
        if ($request->has('assigned_to') && (int) $request->assigned_to !== (int) $ticket->assigned_to) {
            $changes[] = 'Assigned to ' . (User::find($d['assigned_to'] ?? 0)?->name ?? 'nobody');
            $ticket->assigned_to = $d['assigned_to'] ?? null;
        }
        $ticket->fill(array_filter(['status' => $d['status'] ?? null, 'priority' => $d['priority'] ?? null]))->save();
        if ($changes) $ticket->log('event', implode(' · ', $changes), $request->user()->id);

        return back()->with('success', 'Ticket updated.');
    }

    /** Merge this ticket into another one (by number, e.g. TK-1004): its messages move over. */
    public function merge(Request $request, Ticket $ticket)
    {
        abort_unless($ticket->canBeSeenBy($request->user()), 403);
        $d = $request->validate(['into' => 'required|string|max:20']);
        $target = Ticket::where('user_id', $ticket->user_id)->whereNull('merged_into_id')->find((int) preg_replace('/\D/', '', $d['into']) - 1000);
        if (! $target || $target->id === $ticket->id) return back()->withErrors(['into' => 'Ticket not found.']);

        $ticket->messages()->update(['ticket_id' => $target->id]);
        $target->log('event', "Merged {$ticket->number} ({$ticket->subject}) into this ticket.", $request->user()->id);
        $ticket->update(['merged_into_id' => $target->id, 'status' => 'closed']);
        $target->touch();

        return redirect()->route('tickets.show', $target)->with('success', "{$ticket->number} merged into {$target->number}.");
    }

    public function settings(User $u): array
    {
        return array_merge(['escalate_hours' => 24], is_array($u->support_settings) ? $u->support_settings : []);
    }

    public function saveSettings(Request $request)
    {
        $d = $request->validate(['escalate_hours' => 'required|integer|min:0|max:720']);
        $request->user()->update(['support_settings' => array_merge($this->settings($request->user()), $d)]);

        return back()->with('success', 'Escalation saved.');
    }

    /** Owner plus active team members (people who can be assigned). */
    private function agents(User $owner)
    {
        $ids = TeamMember::where('owner_id', $owner->id)->where('status', 'active')->whereNotNull('user_id')->pluck('user_id')->push($owner->id);

        return User::whereIn('id', $ids)->orderBy('name')->get(['id', 'name', 'email']);
    }
}
