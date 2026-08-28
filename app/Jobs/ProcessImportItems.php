<?php

namespace App\Jobs;

use App\Models\ImportBatch;
use App\Services\Scraping\StagedImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Imports confirmed staged items from a URL scrape batch §16 ("Import",
 * "Import All"). Runs item-by-item so partial failures never abort the batch.
 */
class ProcessImportItems implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 900;

    public function __construct(
        public ImportBatch $batch,
        public ?array $itemIds = null, // null = import all staged (non-error) items
    ) {
        $this->onQueue('imports');
    }

    public function handle(StagedImportService $service): void
    {
        try {
            $service->process($this->batch, $this->itemIds);
        } catch (\Throwable $e) {
            Log::error('Staged import job failed', ['batch' => $this->batch->id, 'error' => $e->getMessage()]);
            $this->batch->update(['status' => 'failed']);

            throw $e;
        }
    }
}
