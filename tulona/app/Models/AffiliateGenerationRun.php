<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Tracks a bulk affiliate-link generation run for live progress UI (§23). */
class AffiliateGenerationRun extends Model
{
    protected $fillable = [
        'merchant_id', 'status', 'total', 'processed', 'generated', 'failed',
        'created_by', 'started_at', 'completed_at',
    ];

    protected $casts = ['started_at' => 'datetime', 'completed_at' => 'datetime'];

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
