<?php

namespace App\Jobs;

use App\Contracts\Merchant\AffiliateLinkGenerator;
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
 * HTTP request. Each generation attempt is recorded in the generation history.
 */
class ProcessAffiliateGenerations implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 900;

    public function __construct(public ?int $merchantId = null)
    {
        $this->onQueue('imports');
    }

    public function handle(AffiliateLinkGenerator $generator): void
    {
        $query = AffiliateOffer::whereIn('status', ['pending', 'failed', 'invalid']);

        if ($this->merchantId !== null) {
            $query->where('merchant_id', $this->merchantId);
        }

        $total = (clone $query)->count();
        $generated = 0;
        $failed = 0;

        $query->chunkById(100, function ($offers) use ($generator, &$generated, &$failed): void {
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
            }
        });

        Log::info('Bulk affiliate generation finished', [
            'total' => $total, 'generated' => $generated, 'failed' => $failed,
        ]);
    }
}
