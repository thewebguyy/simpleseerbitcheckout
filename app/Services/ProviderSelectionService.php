<?php

declare(strict_types=1);

namespace App\Services;

use App\Providers\PaymentProviderInterface;
use App\Providers\ProviderRegistry;
use RuntimeException;

final class ProviderSelectionService
{
    private array $config;

    public function __construct()
    {
        $this->config = require CONFIG_PATH . '/providers.php';
    }

    public function select(string $country, string $currency, ?string $forceProvider = null): ?PaymentProviderInterface
    {
        if ($forceProvider !== null) {
            return ProviderRegistry::get($forceProvider);
        }

        $eligible = ProviderRegistry::supports($currency, $country);
        
        if (empty($eligible)) {
            return null; // No provider available for this region/currency
        }

        // Sort by priority (lowest number wins)
        $ranked = [];
        foreach ($eligible as $name => $provider) {
            $ranked[$name] = $this->config[$name]['priority'] ?? 999;
        }

        asort($ranked);
        
        $winnerName = array_key_first($ranked);
        return ProviderRegistry::get($winnerName);
    }
}
