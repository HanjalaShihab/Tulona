<?php

use App\Connectors\GenericConnector;
use App\Connectors\UrlConnector;

return [

    /*
    |--------------------------------------------------------------------------
    | Merchant Connector Registry (§4, §56)
    |--------------------------------------------------------------------------
    |
    | Each Merchant has a `connector_type` (startech, daraz, ryans, generic…).
    | CSV/feed-first connectors live here; URL scraper connectors plug in later
    | without touching core. Unknown/legacy merchants fall back to GenericConnector.
    |
    */

    'connectors' => [
        'generic' => GenericConnector::class,
        'url' => UrlConnector::class,
    ],

    /*
    | Default method used when a merchant has none configured.
    */
    'default_method' => 'csv',

    /*
    | Row-shape expectations for the CSV/feed parser. Keys are the canonical
    | normalized fields; csv_column_map maps raw headers onto them.
    */
    'mapping' => [
        'required' => ['name', 'category_slug', 'merchant_slug', 'price', 'currency'],
        'columns' => [
            'name', 'category_slug', 'brand_slug', 'merchant_slug', 'price',
            'original_price', 'currency', 'affiliate_url', 'external_url',
            'availability', 'gtin', 'model_number', 'sku', 'description',
        ],
    ],
];
