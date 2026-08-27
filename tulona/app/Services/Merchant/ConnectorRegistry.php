<?php

namespace App\Services\Merchant;

use App\Connectors\GenericConnector;
use App\Contracts\Merchant\MerchantConnector;
use App\Models\Merchant;

/**
 * Resolves a Merchant to its registered connector (§4).
 * Legacy/unknown connector_type falls back to the generic CSV/feed connector.
 */
class ConnectorRegistry
{
    protected array $map;

    public function __construct(?array $connectors = null)
    {
        $this->map = $connectors ?? config('merchants.connectors', []);
    }

    public function resolve(Merchant $merchant): MerchantConnector
    {
        $type = $merchant->connector_type ?: 'generic';
        $class = $this->map[$type] ?? $this->map['generic'] ?? GenericConnector::class;

        $connector = is_object($class) ? $class : app($class);

        if (! $connector->supports($merchant)) {
            $connector = app($this->map['generic'] ?? GenericConnector::class);
        }

        return $connector;
    }

    public function get(string $connectorType): MerchantConnector
    {
        $class = $this->map[$connectorType] ?? $this->map['generic'] ?? GenericConnector::class;

        return app($class);
    }
}
