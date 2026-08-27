<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportItem extends Model
{
    protected $fillable = [
        'import_batch_id', 'source_identifier', 'raw_data', 'normalized_data',
        'product_id', 'offer_id', 'match_type', 'status', 'error', 'processed_at',
    ];

    protected $casts = [
        'raw_data' => 'array',
        'normalized_data' => 'array',
        'processed_at' => 'datetime',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ImportBatch::class, 'import_batch_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }
}
