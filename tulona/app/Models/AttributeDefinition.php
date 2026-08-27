<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttributeDefinition extends Model
{
    protected $table = 'attribute_definitions';

    protected $fillable = ['category_id', 'name', 'key', 'data_type', 'unit', 'options', 'is_filterable', 'sort_order'];

    protected $casts = ['options' => 'array', 'is_filterable' => 'boolean'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
