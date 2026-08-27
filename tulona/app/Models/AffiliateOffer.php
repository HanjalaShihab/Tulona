<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AffiliateOffer extends Model
{
    protected $fillable = [
        'offer_id', 'product_id', 'merchant_id', 'normal_product_url', 'affiliate_url',
        'tracking_identifier', 'commission_rate', 'commission_type', 'commission_eligible',
        'status', 'generation_method', 'generated_at', 'last_verified_at', 'last_error', 'metadata',
    ];

    protected $casts = [
        'commission_eligible' => 'boolean',
        'commission_rate' => 'float',
        'generated_at' => 'datetime',
        'last_verified_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function generations(): HasMany
    {
        return $this->hasMany(AffiliateLinkGeneration::class);
    }
}
