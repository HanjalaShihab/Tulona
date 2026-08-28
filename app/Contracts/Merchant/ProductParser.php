<?php

namespace App\Contracts\Merchant;

/**
 * Parses raw source data (CSV bytes, JSON payload, feed response) into raw rows.
 * Parser output feeds ProductNormalizer → ProductImporter (§56).
 */
interface ProductParser
{
    /**
     * @return iterable<array<string, mixed>> raw rows
     */
    public function parse(string $raw, array $config): iterable;

    /** True when this parser can handle the merchant's configured import method. */
    public function supports(string $method): bool;
}
