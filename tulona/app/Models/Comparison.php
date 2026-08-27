<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Comparison extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title', 'slug', 'introduction', 'description', 'verdict', 'notes', 'cta_text',
        'status', 'featured', 'merchant_order', 'specifications_shown', 'published_at',
        'seo_title', 'seo_description', 'canonical_url',
    ];

    protected $casts = [
        'featured' => 'boolean',
        'merchant_order' => 'array',
        'specifications_shown' => 'array',
        'published_at' => 'datetime',
    ];

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'comparison_product')
            ->withPivot(['sort_order', 'editorial_notes', 'pick_label'])
            ->orderBy('pivot_sort_order');
    }

    public function offers(): BelongsToMany
    {
        return $this->belongsToMany(Offer::class, 'comparison_offer')
            ->withPivot(['is_hidden', 'override_price', 'override_availability', 'override_warranty', 'override_shipping', 'sort_order'])
            ->with(['merchant', 'product'])
            ->orderBy('pivot_sort_order');
    }

    public function scopePublished(Builder $q): Builder
    {
        return $q->where('status', 'published')->whereNotNull('published_at');
    }

    public function scopeFeatured(Builder $q): Builder
    {
        return $q->where('featured', true);
    }
}
