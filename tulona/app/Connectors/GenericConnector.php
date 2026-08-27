<?php

namespace App\Connectors;

use App\Contracts\Merchant\AffiliateLinkGenerator;
use App\Contracts\Merchant\CategoryMapper;
use App\Contracts\Merchant\MerchantConnector;
use App\Contracts\Merchant\ProductImporter;
use App\Contracts\Merchant\ProductNormalizer;
use App\Contracts\Merchant\ProductParser;
use App\Models\Merchant;

/**
 * Default merchant connector for CSV/feed-first data (§56).
 * Used for legacy/unconfigured merchants so the pipeline is always exercisable.
 */
class GenericConnector implements MerchantConnector
{
    public function __construct(
        protected ProductParser $parser,
        protected ProductNormalizer $normalizer,
        protected ProductImporter $importer,
        protected CategoryMapper $categoryMapper,
        protected AffiliateLinkGenerator $affiliateLinkGenerator,
    ) {}

    public function parser(): ProductParser
    {
        return $this->parser;
    }

    public function normalizer(): ProductNormalizer
    {
        return $this->normalizer;
    }

    public function importer(): ProductImporter
    {
        return $this->importer;
    }

    public function categoryMapper(): CategoryMapper
    {
        return $this->categoryMapper;
    }

    public function affiliateLinkGenerator(): AffiliateLinkGenerator
    {
        return $this->affiliateLinkGenerator;
    }

    public function supports(Merchant $merchant): bool
    {
        return in_array($merchant->product_import_method, ['csv', 'feed', 'api'], true);
    }
}
