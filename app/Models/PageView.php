<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PageView extends Model
{
    public $timestamps = false;

    protected $fillable = ['path', 'referrer_page', 'ip_hash', 'user_agent_family', 'viewed_at'];

    protected $casts = ['viewed_at' => 'datetime'];
}
