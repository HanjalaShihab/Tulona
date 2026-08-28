<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AffiliateLinkGeneration extends Model
{
    protected $fillable = [
        'affiliate_offer_id', 'product_id', 'merchant_id', 'method', 'status',
        'input_url', 'generated_url', 'error', 'initiated_by', 'metadata', 'processed_at',
    ];

    protected $casts = ['metadata' => 'array', 'processed_at' => 'datetime'];

    public function affiliateOffer(): BelongsTo
    {
        return $this->belongsTo(AffiliateOffer::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function initiator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }
}
