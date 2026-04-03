<?php

namespace Modules\StorageManager\Contracts;

interface SourceDocumentAdapterInterface
{
    public function supportedSourceType(): string;

    public function load(int $businessId, int $sourceId);

    public function summarize($sourceDocument): array;
}
