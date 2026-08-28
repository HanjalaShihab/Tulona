<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AffiliateNetwork extends Model
{
    protected $fillable = ['name', 'slug', 'website_url', 'api_config', 'notes'];

    protected $casts = ['api_config' => 'array'];

    public function merchants(): HasMany
    {
        return $this->hasMany(Merchant::class);
    }
}
