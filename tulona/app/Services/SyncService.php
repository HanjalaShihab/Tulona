<?php

namespace App\Services;

use App\Models\Merchant;
use App\Models\Product;
use App\Models\SyncLog;
use App\Services\Feeds\MerchantConfig;
use App\Services\Feeds\MerchantFeedProvider;
use Illuminate\Support\Facades\Log;

/**
 * Scheduled merchant synchronization (§26). Failures never break the site:
 * last known valid data is kept, the failure is logged and surfaced (§65).
 */
class SyncService
{
    public function __construct(protected MerchantFeedProvider $provider) {}

    public function sync(Merchant $merchant): SyncLog
    {
        $log = SyncLog::create(['merchant_id' => $merchant->id, 'status' => 'running', 'started_at' => now()]);

        try {
            $config = new MerchantConfig(
                slug: $merchant->slug,
                networkSlug: $merchant->network?->slug,
                feedConfig: $merchant->feed_config ?? [],
                credentials: $this->credentialsFor($merchant),
            );

            $updated = 0;
            $failed = 0;

            foreach ($this->provider->fetch($config) as $item) {
                try {
                    $product = Product::where('gtin', $item['gtin'] ?? '')
                        ->where('gtin', '!=', '')
                        ->first();
                    if (! $product && ! empty($item['model_number'])) {
                        $product = Product::where('model_number', $item['model_number'])->first();
                    }
                    if (! $product) {
                        continue; // never auto-create unmatched products without review (§24)
                    }
                    $offer = $product->offers()->updateOrCreate(
                        ['merchant_id' => $merchant->id],
                        [
                            'affiliate_url' => $item['external_url'] ?? $product->offers()->where('merchant_id', $merchant->id)->value('affiliate_url') ?? '',
                            'current_price' => $item['price'],
                            'currency' => $item['currency'],
                            'availability' => $item['availability'],
                            'source' => 'api',
                            'last_synced_at' => now(),
                        ]
                    );
                    app(PriceTrackingService::class)->recordPrice($offer, $item['price']);
                    $updated++;
                } catch (\Throwable $e) {
                    $failed++;
                }
            }

            $log->update(['status' => 'success', 'items_updated' => $updated, 'items_failed' => $failed, 'finished_at' => now()]);
            $merchant->update(['last_synced_at' => now(), 'sync_status' => 'success']);
        } catch (\Throwable $e) {
            Log::error('Merchant sync failed', ['merchant' => $merchant->slug, 'error' => $e->getMessage()]);
            $log->update(['status' => 'failed', 'message' => $e->getMessage(), 'finished_at' => now()]);
            $merchant->update(['sync_status' => 'failed']); // old data untouched (§65)
        }

        return $log;
    }

    protected function credentialsFor(Merchant $merchant): array
    {
        $key = strtoupper($merchant->slug);

        return [
            'key' => config("services.feeds.{$merchant->slug}.key") ?? config("services.feeds.{$merchant->slug}.key"),
            'secret' => config("services.feeds.{$merchant->slug}.secret") ?? config("services.feeds.{$merchant->slug}.secret"),
        ];
    }
}
