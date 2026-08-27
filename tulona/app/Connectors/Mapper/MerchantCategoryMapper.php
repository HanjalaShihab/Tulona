<?php

namespace App\Connectors\Mapper;

use App\Contracts\Merchant\CategoryMapper;
use App\Models\Category;
use App\Models\Merchant;

/**
 * Maps a merchant's category string → canonical category slug.
 * Priority: explicit merchant `configuration.category_mapping` hash ↦
 * exact/matched slug, then fuzzy slug lookup. Returns null when unmapped.
 */
class MerchantCategoryMapper implements CategoryMapper
{
    public function canonicalSlug(?string $merchantCategory, Merchant $merchant): ?string
    {
        $key = $merchantCategory === null ? '' : trim(mb_strtolower($merchantCategory));
        if ($key === '') {
            return null;
        }

        $mapping = $merchant->configuration['category_mapping'] ?? [];
        if (is_array($mapping)) {
            foreach ($mapping as $k => $slug) {
                if (mb_strtolower((string) $k) === $key) {
                    return $this->whenExists((string) $slug);
                }
            }
        }

        $slug = str($key)->slug()->toString();

        return $this->whenExists($slug);
    }

    protected function whenExists(string $slug): ?string
    {
        return Category::where('slug', $slug)->first()?->slug;
    }
}
