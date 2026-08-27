<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Merchant extends Model
{
    protected $fillable = [
        'affiliate_network_id', 'name', 'slug', 'connector_type', 'product_import_method',
        'affiliate_link_method', 'affiliate_enabled', 'logo_path', 'description', 'website_url',
        'country', 'region', 'currencies', 'base_affiliate_url', 'tracking_template',
        'feed_config', 'configuration', 'commission_note', 'status', 'last_synced_at', 'sync_status',
        'terms_notes', 'seo_title', 'seo_description',
    ];

    protected $casts = [
        'currencies' => 'array',
        'feed_config' => 'array',
        'configuration' => 'array',
        'last_synced_at' => 'datetime',
        'affiliate_enabled' => 'boolean',
    ];

    public function network(): BelongsTo
    {
        return $this->belongsTo(AffiliateNetwork::class, 'affiliate_network_id');
    }

    public function offers(): HasMany
    {
        return $this->hasMany(Offer::class);
    }

    /** §19 affiliate offers for this merchant's listings. */
    public function affiliateOffers(): HasMany
    {
        return $this->hasMany(AffiliateOffer::class);
    }

    /** §20 manual/automated link generation attempts for this merchant. */
    public function affiliateLinkGenerations(): HasMany
    {
        return $this->hasMany(AffiliateLinkGeneration::class);
    }

    public function importBatches(): HasMany
    {
        return $this->hasMany(ImportBatch::class);
    }

    public function clicks(): HasMany
    {
        return $this->hasMany(Click::class);
    }

    public function syncLogs(): HasMany
    {
        return $this->hasMany(SyncLog::class);
    }
}
