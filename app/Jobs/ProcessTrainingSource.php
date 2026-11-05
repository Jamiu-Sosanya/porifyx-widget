<?php

namespace App\Jobs;

use App\Models\TrainingSource;
use App\Services\TrainingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessTrainingSource implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;
    public int $tries = 3;

    public function __construct(
        public TrainingSource $source,
    ) {}

    public function handle(TrainingService $trainingService): void
    {
        $trainingService->processSource($this->source);
    }

    public function failed(\Throwable $exception): void
    {
        $this->source->update([
            'status'        => 'failed',
            'error_message' => $exception->getMessage(),
        ]);
    }
}
