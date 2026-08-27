<?php

namespace App\Contracts\Merchant;

use App\Models\Merchant;

/**
 * A complete, pluggable merchant connector (§4, §56).
 * Combines parser + normalizer + matcher + category mapping + affiliate link generation.
 * Connectors are registered in config/merchants.php keyed by Merchant::connector_type.
 */
interface MerchantConnector
{
    public function parser(): ProductParser;

    public function normalizer(): ProductNormalizer;

    public function importer(): ProductImporter;

    public function categoryMapper(): CategoryMapper;

    public function affiliateLinkGenerator(): AffiliateLinkGenerator;

    public function supports(Merchant $merchant): bool;
}
