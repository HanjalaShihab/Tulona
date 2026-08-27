<?php

namespace App\Jobs;

use App\Connectors\Generator\ManualAffiliateLinkGenerator;
use App\Contracts\Merchant\AffiliateLinkGenerator;
use App\Models\AffiliateGenerationRun;
use App\Models\AffiliateOffer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * §23 BULK AFFILIATE LINK GENERATION — walks pending offers for a merchant and
 * invokes the registered generator. Runs as a queue job so it never blocks an
 * HTTP request. Each generation attempt is recorded in the generation history,
 * and live progress (processed / generated / failed) is written to the run row
 * that powers the admin progress bar.
 */
class ProcessAffiliateGenerations implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 900;

    public function __construct(public ?int $merchantId = null, public ?int $runId = null)
    {
        $this->onQueue('imports');
    }

    public function handle(AffiliateLinkGenerator $generator): void
    {
        $run = $this->runId ? AffiliateGenerationRun::find($this->runId) : null;
        if ($run) {
            $run->update(['status' => 'processing', 'started_at' => now()]);
        }

        // The only registered generator requires a per-offer URL that a bulk run
        // never has. Running it here would only record a failure for every offer
        // and loop on each retry — so fail fast and surface that bulk generation
        // needs an automated (non-manual) generator (§23).
        if ($generator instanceof ManualAffiliateLinkGenerator) {
            Log::warning('Bulk affiliate generation skipped: the configured generator requires manual per-offer URLs, which a bulk run cannot supply.');

            if ($run) {
                $run->update(['status' => 'completed', 'completed_at' => now()]);
            }

            return;
        }

        $query = AffiliateOffer::whereIn('status', ['pending', 'failed', 'invalid']);

        if ($this->merchantId !== null) {
            $query->where('merchant_id', $this->merchantId);
        }

        $total = (clone $query)->count();
        $processed = 0;
        $generated = 0;
        $failed = 0;

        if ($run) {
            $run->update(['total' => $total]);
        }

        $query->chunkById(100, function ($offers) use ($generator, $run, &$processed, &$generated, &$failed): void {
            foreach ($offers as $offer) {
                try {
                    $generator->generate($offer); // records a generation history row
                    if ($offer->status === 'manual' || $offer->status === 'generated') {
                        $generated++;
                    }
                } catch (\Throwable $e) {
                    $failed++;
                    $offer->update(['status' => 'failed', 'last_error' => $e->getMessage()]);
                    Log::warning('Bulk affiliate generation failed', ['offer' => $offer->id, 'error' => $e->getMessage()]);
                }

                $processed++;
            }

            // Live progress for the admin progress bar (single UPDATE each chunk).
            if ($run) {
                $run->update([
                    'processed' => $processed,
                    'generated' => $generated,
                    'failed' => $failed,
                ]);
            }
        });

        if ($run) {
            $run->update([
                'processed' => $processed,
                'generated' => $generated,
                'failed' => $failed,
                'status' => 'completed',
                'completed_at' => now(),
            ]);
        }

        Log::info('Bulk affiliate generation finished', [
            'total' => $total, 'generated' => $generated, 'failed' => $failed,
        ]);
    }
}
