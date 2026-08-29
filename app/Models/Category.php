<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = ['parent_id', 'name', 'slug', 'description', 'icon', 'sort_order', 'is_active', 'seo_title', 'seo_description', 'intro_content'];

    protected $casts = ['is_active' => 'boolean'];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function attributeDefinitions(): HasMany
    {
        return $this->hasMany(AttributeDefinition::class);
    }

    public function descendantsAndSelf(): array
    {
        $ids = [$this->id];
        $queue = $this->children()->pluck('id')->all();

        while (! empty($queue)) {
            $batch = self::whereIn('id', $queue)->pluck('id')->all();
            $ids = array_merge($ids, $batch);
            $queue = self::whereIn('parent_id', $batch)->whereNotIn('id', $ids)->pluck('id')->all();
        }

        return $ids;
    }

    public function getBreadcrumbAttribute(): array
    {
        $crumbs = [];
        $cat = $this;
        while ($cat) {
            array_unshift($crumbs, $cat);
            $cat = $cat->parent;
        }

        return $crumbs;
    }

    public static function nestedTree(): Collection
    {
        $all = static::orderBy('sort_order')->orderBy('name')->get();
        $children = $all->groupBy('parent_id');

        $attach = function ($items) use (&$attach, $children) {
            return $items->map(function ($category) use (&$attach, $children) {
                $category->setRelation('children', $attach($children->get($category->id, collect())));

                return $category;
            });
        };

        return $attach($children->get(null, collect()));
    }

    /**
     * Data for the cascading category picker: parent list, id lookups and
     * child lists keyed by parent id.
     *
     * @return array{parents: \Illuminate\Support\Collection, byId: array, byParentId: array}
     */
    public static function cascadeData(): array
    {
        $parents = static::whereNull('parent_id')->orderBy('sort_order')->orderBy('name')->get(['id', 'name']);

        $byId = [];
        $byParentId = [];
        static::whereNotNull('parent_id')->orderBy('sort_order')->orderBy('name')
            ->get(['id', 'name', 'parent_id'])
            ->each(function ($child) use (&$byId, &$byParentId) {
                $byId[(int) $child->id] = $child;
                $byParentId[(int) $child->parent_id][] = $child;
            });

        return compact('parents', 'byId', 'byParentId');
    }
}
