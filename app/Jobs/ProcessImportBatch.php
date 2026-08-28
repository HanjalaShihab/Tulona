<?php

namespace App\Jobs;

use App\Models\ImportBatch;
use App\Services\ImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/** Heavy import work always runs on the queue, never inside HTTP requests (§53). */
class ProcessImportBatch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 900;

    public function __construct(public ImportBatch $batch)
    {
        $this->onQueue('imports');
    }

    public function handle(ImportService $service): void
    {
        try {
            $service->process($this->batch);
        } catch (\Throwable $e) {
            Log::error('Import batch job failed', ['batch' => $this->batch->id, 'error' => $e->getMessage()]);
            $this->batch->update(['status' => 'failed']);

            throw $e;
        }
    }
}
