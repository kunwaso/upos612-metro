<?php

namespace Modules\VasAccounting\Services;

use InvalidArgumentException;
use Modules\VasAccounting\Contracts\PayrollBridgeInterface;

class PayrollBridgeManager
{
    public function resolve(?string $provider = null): PayrollBridgeInterface
    {
        $provider = $provider ?: 'essentials';
        $adapterClass = config("vasaccounting.payroll_bridge_adapters.{$provider}");

        if (! is_string($adapterClass) || ! class_exists($adapterClass)) {
            throw new InvalidArgumentException("No VAS payroll bridge adapter registered for [{$provider}].");
        }

        $adapter = app($adapterClass);

        if (! $adapter instanceof PayrollBridgeInterface) {
            throw new InvalidArgumentException("Configured VAS payroll bridge adapter [{$adapterClass}] is invalid.");
        }

        return $adapter;
    }
}
