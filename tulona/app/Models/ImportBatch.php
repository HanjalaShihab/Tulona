<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImportBatch extends Model
{
    protected $fillable = [
        'filename', 'type', 'source_type', 'source_url', 'category_slug', 'status', 'total_rows',
        'imported_count', 'created_count', 'updated_count', 'skipped_count',
        'failed_count', 'merchant_id', 'created_by', 'completed_at',
    ];

    protected $casts = ['completed_at' => 'datetime'];

    public function errors(): HasMany
    {
        return $this->hasMany(ImportError::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ImportItem::class);
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
