<?php

namespace Modules\VasAccounting\Services;

use InvalidArgumentException;
use Modules\VasAccounting\Contracts\SourceDocumentAdapterInterface;

class SourceDocumentAdapterManager
{
    public function resolve(string $sourceType): SourceDocumentAdapterInterface
    {
        $adapterClass = config("vasaccounting.source_document_adapters.{$sourceType}");

        if (! is_string($adapterClass) || ! class_exists($adapterClass)) {
            throw new InvalidArgumentException("No VAS source adapter registered for [{$sourceType}].");
        }

        $adapter = app($adapterClass);
        if (! $adapter instanceof SourceDocumentAdapterInterface) {
            throw new InvalidArgumentException("Configured VAS source adapter [{$adapterClass}] is invalid.");
        }

        return $adapter;
    }
}
