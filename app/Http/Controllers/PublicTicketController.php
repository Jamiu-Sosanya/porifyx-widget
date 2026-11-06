<?php

namespace App\Http\Controllers;

use App\Mail\TicketMail;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

/** Customer-facing ticket page reached from the emailed link (no account). */
class PublicTicketController extends Controller
{
    private function ticket(int $id, string $token): Ticket
    {
        $t = Ticket::findOrFail($id);
        abort_unless(hash_equals($t->publicToken(), $token), 404);

        return $t->merged_into_id ? $t->mergedInto : $t;
    }

    public function show(int $id, string $token)
    {
        $t = $this->ticket($id, $token);
        $t->load(['messages' => fn ($q) => $q->whereIn('type', ['customer', 'reply']), 'widget']);

        return view('tickets.public', ['ticket' => $t, 'token' => $t->publicToken()]);
    }

    public function reply(Request $request, int $id, string $token)
    {
        $t = $this->ticket($id, $token);
        $d = $request->validate(['body' => 'required|string|max:10000']);
        $t->log('customer', $d['body']);
        $t->update(['last_customer_reply_at' => now(), 'status' => in_array($t->status, ['solved', 'closed', 'pending']) ? 'open' : $t->status]);

        $to = $t->assignee?->email ?? $t->owner->email;
        if ($to) Mail::to($to)->send(new TicketMail($t, 'owner_customer', $d['body']));

        return redirect()->route('tickets.public', [$t->id, $t->publicToken()])->with('success', 'Thanks, your reply was sent.');
    }
}
