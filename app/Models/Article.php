<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Article extends Model
{
    protected $fillable = [
        'title', 'slug', 'type', 'excerpt', 'content', 'featured_image', 'category_id',
        'author', 'status', 'published_at', 'seo_title', 'seo_description',
        'canonical_url', 'og_image', 'faqs', 'selection_criteria',
    ];

    protected $casts = ['faqs' => 'array', 'selection_criteria' => 'array', 'published_at' => 'datetime'];

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'article_product')
            ->withPivot(['blurb', 'pick_label', 'sort_order'])
            ->orderBy('pivot_sort_order');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function scopePublished(Builder $q): Builder
    {
        return $q->where('status', 'published')->whereNotNull('published_at');
    }
}
