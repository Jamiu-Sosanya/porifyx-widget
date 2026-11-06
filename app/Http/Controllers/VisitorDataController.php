<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Lead;
use App\Models\OfflineMessage;
use App\Models\Ticket;
use App\Models\Visitor;
use Illuminate\Http\Request;

/** GDPR requests for one visitor: download everything held about them, or erase it. */
class VisitorDataController extends Controller
{
    private function visitor(Request $request, Visitor $visitor): Visitor
    {
        abort_unless($visitor->widget && $visitor->widget->user_id === $request->user()->id, 403);

        return $visitor;
    }

    public function export(Request $request, Visitor $visitor)
    {
        $v = $this->visitor($request, $visitor);
        $email = $v->email;
        $data = [
            'exported_at'   => now()->toIso8601String(),
            'business'      => $v->widget->name,
            'visitor'       => $v->only(['name', 'email', 'phone', 'country', 'city', 'created_at']),
            'conversations' => $v->conversations()->with(['messages' => fn ($q) => $q->select('id', 'conversation_id', 'role', 'content', 'created_at')])->get(['id', 'uuid', 'status', 'created_at'])
                ->map(fn ($c) => ['started' => $c->created_at, 'status' => $c->status, 'messages' => $c->messages->map(fn ($m) => ['from' => $m->role, 'text' => $m->content, 'at' => $m->created_at])]),
            'leads'         => Lead::where('visitor_id', $v->id)->orWhere(fn ($q) => $email ? $q->where('widget_id', $v->widget_id)->where('email', $email) : $q->whereRaw('0=1'))
                ->get(['name', 'email', 'phone', 'company', 'status', 'form_data', 'created_at']),
            'bookings'      => $email ? Appointment::where('widget_id', $v->widget_id)->where('email', $email)->get(['reference', 'service_name', 'starts_at', 'status', 'name', 'email', 'phone', 'notes']) : [],
            'messages_left' => $email ? OfflineMessage::where('widget_id', $v->widget_id)->where('email', $email)->get(['name', 'email', 'message', 'created_at']) : [],
            'tickets'       => $email ? Ticket::where('user_id', $request->user()->id)->where('requester_email', $email)->with('messages:id,ticket_id,type,body,created_at')->get(['id', 'subject', 'status', 'created_at']) : [],
        ];
        \App\Support\Audit::log('gdpr.export', $v, ['email' => $email]);

        return response()->streamDownload(fn () => print(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)), 'visitor-data-' . $v->id . '.json', ['Content-Type' => 'application/json']);
    }

    public function erase(Request $request, Visitor $visitor)
    {
        $v = $this->visitor($request, $visitor);
        $email = $v->email;
        $widgetId = $v->widget_id;

        foreach ($v->conversations as $c) {
            $c->messages()->delete();
            $c->delete();
        }
        Lead::where('visitor_id', $v->id)->delete();
        if ($email) {
            Lead::where('widget_id', $widgetId)->where('email', $email)->delete();
            OfflineMessage::where('widget_id', $widgetId)->where('email', $email)->delete();
            // Bookings keep the slot (for the business's records) but lose personal details.
            Appointment::where('widget_id', $widgetId)->where('email', $email)->update(['name' => 'Erased', 'email' => 'erased@invalid', 'phone' => null, 'notes' => null, 'answers' => null]);
            Ticket::where('user_id', $request->user()->id)->where('requester_email', $email)->get()->each->delete();
        }
        \App\Support\Audit::log('gdpr.erase', null, ['visitor_id' => $v->id, 'widget_id' => $widgetId]);
        $v->delete();

        return redirect()->route('conversations.index')->with('success', 'All data for this visitor was erased.');
    }
}
