<?php

namespace Modules\VasAccounting\Contracts;

interface SourceDocumentAdapterInterface
{
    public function loadSourceDocument(int $sourceId, array $context = []);

    public function toVoucherPayload($sourceDocument, array $context = []): array;
}
