<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Data freshness threshold (§64)
    |--------------------------------------------------------------------------
    | Offers older than this many hours show "price may be outdated".
    */
    'freshness_hours' => env('TULONA_FRESHNESS_HOURS', 72),

    // Minimum genuine discount (%) to qualify as a deal — no fake discounts (§45)
    'deal_threshold_percent' => 5,
];
