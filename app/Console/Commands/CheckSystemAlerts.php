<?php

namespace App\Console\Commands;

use App\Services\AlertingService;
use Illuminate\Console\Command;

class CheckSystemAlerts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'alerts:check {--type= : Specific alert type to check (conversations, leads, ab-tests, all)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for system anomalies and send alerts';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $alertingService = new AlertingService();
        $type = $this->option('type') ?? 'all';

        $this->info("Checking system alerts...");

        switch ($type) {
            case 'conversations':
                $this->info("Checking conversation anomalies...");
                $alertingService->checkConversationAnomalies();
                break;
            case 'leads':
                $this->info("Checking lead quality anomalies...");
                $alertingService->checkLeadQualityAnomalies();
                break;
            case 'ab-tests':
                $this->info("Checking A/B test anomalies...");
                $alertingService->checkAbTestAnomalies();
                break;
            case 'all':
            default:
                $this->info("Running all alert checks...");
                $alertingService->runAllChecks();
                break;
        }

        $this->info("Alert checking completed.");
    }
}
