<?php

namespace App\Connectors;

use App\Connectors\Parser\UrlProductParser;
use App\Contracts\Merchant\AffiliateLinkGenerator;
use App\Contracts\Merchant\CategoryMapper;
use App\Contracts\Merchant\MerchantConnector;
use App\Contracts\Merchant\ProductImporter;
use App\Contracts\Merchant\ProductNormalizer;
use App\Contracts\Merchant\ProductParser;
use App\Models\Merchant;

/**
 * URL/feed scraping connector (§14, §31). Same normalizer/importer pipeline as
 * the generic connector but the parser is URL-aware (JSON/CSV sniffing).
 * Registered as 'url' in config/merchants.php.
 */
class UrlConnector implements MerchantConnector
{
    public function __construct(
        protected UrlProductParser $parser,
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
        return in_array($merchant->product_import_method, ['url', 'scrape', 'html', 'api', 'feed'], true);
    }
}
