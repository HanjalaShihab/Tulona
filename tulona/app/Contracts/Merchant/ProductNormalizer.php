<?php

namespace App\Contracts\Merchant;

/**
 * Normalization step of the import pipeline (§56: "NORMALIZATION").
 * Converts one raw scraped/parsed row into a canonical intermediate shape
 * (normalized field names, cleaned prices, normalized availability).
 */
interface ProductNormalizer
{
    /**
     * @param  array<string, mixed>  $raw  raw row from parser or scraper
     * @param  array<string, mixed>  $config  merchant connector configuration
     * @return array<string, mixed> normalized intermediate row (see ProductImporter contract)
     */
    public function normalize(array $raw, array $config): array;
}
