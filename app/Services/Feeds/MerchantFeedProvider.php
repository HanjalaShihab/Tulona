<?php

namespace App\Services\Feeds;

/**
 * Contract every merchant feed provider implements (§81).
 * Real providers are plugged in per-merchant once official API access exists;
 * until then the NullFeedProvider keeps synchronization harmless.
 */
interface MerchantFeedProvider
{
    /**
     * Fetch normalized product/offer updates from an official API or feed.
     *
     * @return iterable<array{name: string, external_product_id?: ?string, external_url?: ?string,
     *     price: ?float, original_price: ?float, currency: string,
     *     availability: string, gtin?: ?string, model_number?: ?string}>
     */
    public function fetch(array $feedConfig): iterable;

    public function supports(MerchantConfig $config): bool;
}
