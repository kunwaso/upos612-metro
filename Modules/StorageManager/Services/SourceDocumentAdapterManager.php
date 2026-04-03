<?php

namespace Modules\StorageManager\Services;

use InvalidArgumentException;
use Modules\StorageManager\Contracts\SourceDocumentAdapterInterface;

class SourceDocumentAdapterManager
{
    public function resolve(string $sourceType): SourceDocumentAdapterInterface
    {
        $adapterClass = config("storagemanager.source_document_adapters.{$sourceType}");

        if (! is_string($adapterClass) || ! class_exists($adapterClass)) {
            throw new InvalidArgumentException("No StorageManager source adapter registered for [{$sourceType}].");
        }

        $adapter = app($adapterClass);
        if (! $adapter instanceof SourceDocumentAdapterInterface) {
            throw new InvalidArgumentException("Configured StorageManager source adapter [{$adapterClass}] is invalid.");
        }

        return $adapter;
    }
}
