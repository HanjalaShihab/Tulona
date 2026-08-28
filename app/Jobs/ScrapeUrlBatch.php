<?php

namespace App\Jobs;

use App\Models\ImportBatch;
use App\Services\Scraping\UrlScrapeService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * §15 SCRAPER MUST BE QUEUE BASED — the merchant product-list URL is fetched,
 * parsed, normalised and matched out-of-band. Produces a staged preview of
 * import_items (§16) that admin then confirms (Import All / Selected / Cancel).
 */
class ScrapeUrlBatch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 900;

    public function __construct(public ImportBatch $batch)
    {
        $this->onQueue('imports');
    }

    public function handle(UrlScrapeService $service): void
    {
        try {
            $service->scrape($this->batch);
        } catch (\Throwable $e) {
            Log::error('URL scrape job failed', ['batch' => $this->batch->id, 'error' => $e->getMessage()]);
            $this->batch->update([
                'status' => 'failed',
                'failed_count' => ($this->batch->failed_count ?? 0) + 1,
                'completed_at' => now(),
            ]);

            throw $e;
        }
    }
}
