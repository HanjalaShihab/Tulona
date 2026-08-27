<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AffiliateConversion extends Model
{
    public $timestamps = false;

    protected $fillable = ['merchant_id', 'network', 'product_id', 'external_order_ref', 'commission_amount', 'currency', 'status', 'converted_at', 'imported_at'];

    protected $casts = ['converted_at' => 'datetime', 'imported_at' => 'datetime'];

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
