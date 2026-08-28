<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'category_id', 'brand_id', 'name', 'slug', 'sku', 'model_number', 'gtin',
        'product_type', 'short_description', 'description', 'summary_editorial',
        'pros', 'cons', 'rating', 'pricing_model', 'has_free_plan', 'platforms',
        'is_featured', 'is_trending', 'is_top_selling', 'is_editors_pick', 'is_best_value',
        'is_budget_pick', 'is_premium_pick', 'popularity_score', 'clicks_count', 'status',
    ];

    protected $casts = [
        'pros' => 'array', 'cons' => 'array', 'platforms' => 'array',
        'has_free_plan' => 'boolean', 'rating' => 'float',
        'is_featured' => 'boolean', 'is_trending' => 'boolean', 'is_top_selling' => 'boolean',
        'is_editors_pick' => 'boolean', 'is_best_value' => 'boolean',
        'is_budget_pick' => 'boolean', 'is_premium_pick' => 'boolean',
    ];

    /** §7/§39 live products only — replaces the old 'active' filter. */
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function attributes(): HasMany
    {
        return $this->hasMany(ProductAttribute::class);
    }

    public function offers(): HasMany
    {
        return $this->hasMany(Offer::class);
    }

    public function affiliateOffers(): HasMany
    {
        return $this->hasMany(AffiliateOffer::class);
    }

    public function comparisons(): BelongsToMany
    {
        return $this->belongsToMany(Comparison::class, 'comparison_product')
            ->withPivot(['sort_order', 'editorial_notes', 'pick_label'])
            ->orderBy('pivot_sort_order');
    }

    public function landingPages(): BelongsToMany
    {
        return $this->belongsToMany(LandingPage::class, 'landing_page_product')
            ->withPivot('sort_order');
    }

    public function activeOffers(): HasMany
    {
        return $this->hasMany(Offer::class)->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('deal_expires_at')->orWhere('deal_expires_at', '>', now());
            });
    }

    public function priceDrops(): HasMany
    {
        return $this->hasMany(PriceDropEvent::class);
    }

    public function articles()
    {
        return $this->belongsToMany(Article::class, 'article_product')->withPivot(['blurb', 'pick_label', 'sort_order'])->orderBy('pivot_sort_order');
    }

    public function deals(): HasMany
    {
        return $this->hasMany(Deal::class);
    }

    public function clicks(): HasMany
    {
        return $this->hasMany(Click::class);
    }

    /** Best (cheapest available) offer using transparent ranking (§90). */
    public function bestOffer(): ?Offer
    {
        return $this->activeOffers()->whereNotNull('current_price')
            ->orderByRaw("CASE availability WHEN 'in_stock' THEN 0 WHEN 'preorder' THEN 1 WHEN 'unknown' THEN 2 ELSE 3 END")
            ->orderBy('current_price')->first();
    }

    public function priceRange(): array
    {
        $q = $this->activeOffers()->whereNotNull('current_price');

        return ['min' => (clone $q)->min('current_price'), 'max' => $q->max('current_price')];
    }
}
