<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceDropEvent extends Model
{
    public $timestamps = false;

    protected $fillable = ['product_id', 'offer_id', 'previous_price', 'current_price', 'drop_amount', 'drop_percent', 'currency', 'occurred_at'];

    protected $casts = ['occurred_at' => 'datetime'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }
}
