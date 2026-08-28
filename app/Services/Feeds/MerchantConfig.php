<?php

namespace App\Services\Feeds;

/** Read-only view of a merchant's configuration passed to providers. */
class MerchantConfig
{
    public function __construct(
        public readonly string $slug,
        public readonly ?string $networkSlug,
        public readonly array $feedConfig,
        public readonly array $credentials, // from env — never DB-stored secrets
    ) {}
}
