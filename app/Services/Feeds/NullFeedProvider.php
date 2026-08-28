<?php

namespace App\Services\Feeds;

use App\Services\Feeds\MerchantFeedProvider as Provider;

/**
 * Default provider used until real merchant APIs are configured.
 * Returns nothing — synchronizing with it simply updates sync timestamps,
 * keeping the pipeline exercised end-to-end without fabricating data (§45).
 */
class NullFeedProvider implements Provider
{
    public function fetch(array $feedConfig): iterable
    {
        return [];
    }

    public function supports(MerchantConfig $config): bool
    {
        return true;
    }
}
