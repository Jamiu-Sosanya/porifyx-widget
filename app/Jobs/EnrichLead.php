<?php

namespace App\Jobs;

use App\Models\Lead;
use App\Services\LeadEnrichmentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class EnrichLead implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 2;

    public function __construct(public int $leadId) {}

    public function handle(LeadEnrichmentService $svc): void
    {
        if ($lead = Lead::find($this->leadId)) $svc->enrich($lead);
    }
}
