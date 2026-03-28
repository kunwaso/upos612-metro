<?php

namespace Modules\VasAccounting\Services;

use InvalidArgumentException;
use Modules\VasAccounting\Contracts\EInvoiceAdapterInterface;

class EInvoiceAdapterManager
{
    public function resolve(?string $provider = null): EInvoiceAdapterInterface
    {
        $provider = $provider ?: 'sandbox';
        $adapterClass = config("vasaccounting.einvoice_adapters.{$provider}");

        if (! is_string($adapterClass) || ! class_exists($adapterClass)) {
            throw new InvalidArgumentException("No VAS e-invoice adapter registered for [{$provider}].");
        }

        $adapter = app($adapterClass);
        if (! $adapter instanceof EInvoiceAdapterInterface) {
            throw new InvalidArgumentException("Configured VAS e-invoice adapter [{$adapterClass}] is invalid.");
        }

        return $adapter;
    }
}
