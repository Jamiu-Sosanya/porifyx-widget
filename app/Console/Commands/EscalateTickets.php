<?php

namespace App\Console\Commands;

use App\Mail\TicketMail;
use App\Models\Ticket;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/** Open tickets waiting longer than the owner's escalation time get a higher priority and an email to the team. */
class EscalateTickets extends Command
{
    protected $signature = 'tickets:escalate';
    protected $description = 'Escalate tickets with no reply within the owner\'s escalation time';

    public function handle(): int
    {
        $n = 0;
        Ticket::where('status', 'open')->whereNull('merged_into_id')->whereNull('escalated_at')->with('owner', 'assignee')
            ->chunkById(200, function ($tickets) use (&$n) {
                foreach ($tickets as $t) {
                    $hours = (int) ((is_array($t->owner->support_settings) ? $t->owner->support_settings : [])['escalate_hours'] ?? 24);
                    $waitingSince = $t->last_customer_reply_at ?? $t->created_at;
                    if ($hours === 0 || ($t->last_agent_reply_at && $t->last_agent_reply_at->gt($waitingSince)) || $waitingSince->gt(now()->subHours($hours))) continue;

                    $t->update(['escalated_at' => now(), 'priority' => $t->priority === 'high' || $t->priority === 'urgent' ? 'urgent' : 'high']);
                    $t->log('event', "Escalated: no reply for {$hours} hours.");
                    $to = $t->assignee?->email ?? $t->owner->email;
                    if ($to) Mail::to($to)->send(new TicketMail($t, 'owner_escalated'));
                    $n++;
                }
            });
        $this->info("Escalated {$n} ticket(s).");

        return self::SUCCESS;
    }
}
