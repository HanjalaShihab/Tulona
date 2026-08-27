<?php

namespace App\Contracts\Merchant;

use App\Models\Merchant;

/**
 * Applies the pipeline steps in order: PARSING → NORMALIZATION → MATCHING → PERSIST.
 * Produces a preview (`dryRun`) or performs the import (§16 preview, §31 scraping).
 */
interface ProductImporter
{
    /**
     * @param  iterable<array<string, mixed>>  $rows  normalized (or raw) rows
     * @return array{matched: int, created: int, updated: int, skipped: int, errors: int}
     */
    public function import(Merchant $merchant, iterable $rows, array $config, bool $dryRun = false): array;
}
