<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LandingPage extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title', 'slug', 'excerpt', 'status', 'sections', 'published_at',
        'seo_title', 'seo_description', 'canonical_url',
    ];

    protected $casts = ['sections' => 'array', 'published_at' => 'datetime'];

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'landing_page_product')
            ->withPivot('sort_order')
            ->orderBy('pivot_sort_order');
    }

    public function comparisons(): BelongsToMany
    {
        return $this->belongsToMany(Comparison::class, 'landing_page_comparison')
            ->withPivot('sort_order')
            ->orderBy('pivot_sort_order');
    }

    public function scopePublished(Builder $q): Builder
    {
        return $q->where('status', 'published')->whereNotNull('published_at');
    }
}
