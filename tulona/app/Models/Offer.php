<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Offer extends Model
{
    protected $fillable = [
        'product_id', 'merchant_id', 'external_product_id', 'external_url', 'affiliate_url',
        'current_price', 'original_price', 'currency', 'availability', 'shipping_info',
        'deal_expires_at', 'source', 'status', 'last_synced_at',
    ];

    protected $casts = ['last_synced_at' => 'datetime', 'deal_expires_at' => 'datetime'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function affiliateOffer(): HasOne
    {
        return $this->hasOne(AffiliateOffer::class);
    }

    /** Effective tracked-outbound URL: affiliate offer takes precedence over legacy column (§19). */
    public function resolvedAffiliateUrl(): ?string
    {
        return $this->affiliateOffer?->affiliate_url ?: $this->affiliate_url;
    }

    public function priceHistory(): HasMany
    {
        return $this->hasMany(PriceHistory::class);
    }

    public function priceDropEvents(): HasMany
    {
        return $this->hasMany(PriceDropEvent::class);
    }

    public function deals(): HasMany
    {
        return $this->hasMany(Deal::class);
    }

    /** Real discount % from source prices; null when unverifiable. */
    public function discountPercent(): ?float
    {
        if (! $this->original_price || ! $this->current_price || $this->original_price <= $this->current_price) {
            return null;
        }

        return round((1 - $this->current_price / $this->original_price) * 100, 1);
    }

    public function isStale(int $thresholdHours): bool
    {
        $t = $this->last_synced_at ?? $this->updated_at;

        return ! $t || $t->diffInHours(now()) > $thresholdHours;
    }
}
