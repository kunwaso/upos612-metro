<?php

namespace Modules\VasAccounting\Services;

use InvalidArgumentException;
use Modules\VasAccounting\Contracts\BankStatementImportAdapterInterface;

class BankStatementImportAdapterManager
{
    public function resolve(?string $provider = null): BankStatementImportAdapterInterface
    {
        $provider = $provider ?: 'manual';
        $adapterClass = config("vasaccounting.bank_statement_import_adapters.{$provider}");

        if (! is_string($adapterClass) || ! class_exists($adapterClass)) {
            throw new InvalidArgumentException("No VAS bank statement import adapter registered for [{$provider}].");
        }

        $adapter = app($adapterClass);

        if (! $adapter instanceof BankStatementImportAdapterInterface) {
            throw new InvalidArgumentException("Configured VAS bank statement import adapter [{$adapterClass}] is invalid.");
        }

        return $adapter;
    }
}
