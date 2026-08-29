<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An editable draft created from an uploaded products CSV. Each row becomes a
 * draft the admin reviews, edits and posts one-by-one (Product Generator).
 * The raw CSV fields are kept in `data`; the publish step lives in
 * ProductPublishService so a posted draft becomes a real product + offer.
 */
class ProductDraft extends Model
{
    protected $fillable = [
        'data', 'merchant_id', 'created_by', 'status', 'error',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Convenience: a human-friendly name for the draft row. */
    public function productName(): string
    {
        return trim((string) ($this->data['name'] ?? ''));
    }
}
