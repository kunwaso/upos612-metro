<?php

namespace Modules\VasAccounting\Services;

use InvalidArgumentException;
use Modules\VasAccounting\Contracts\TaxExportAdapterInterface;

class TaxExportAdapterManager
{
    public function resolve(?string $provider = null): TaxExportAdapterInterface
    {
        $provider = $provider ?: 'local';
        $adapterClass = config("vasaccounting.tax_export_adapters.{$provider}");

        if (! is_string($adapterClass) || ! class_exists($adapterClass)) {
            throw new InvalidArgumentException("No VAS tax export adapter registered for [{$provider}].");
        }

        $adapter = app($adapterClass);

        if (! $adapter instanceof TaxExportAdapterInterface) {
            throw new InvalidArgumentException("Configured VAS tax export adapter [{$adapterClass}] is invalid.");
        }

        return $adapter;
    }
}
