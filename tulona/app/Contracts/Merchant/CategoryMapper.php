<?php

namespace App\Contracts\Merchant;

use App\Models\Merchant;

/**
 * Maps a merchant's category name/structure to a canonical Category slug (§32 matching).
 * Implementations read the merchant's `configuration.category_mapping` plus rules.
 */
interface CategoryMapper
{
    public function canonicalSlug(?string $merchantCategory, Merchant $merchant): ?string;
}
