<?php

namespace Modules\VasAccounting\Contracts;

use Modules\VasAccounting\Application\DTOs\DocumentTraceView;

interface DocumentTraceServiceInterface
{
    public function linkDocumentToEvent(int $documentId, int $eventId): void;

    public function traceDocument(int $documentId): DocumentTraceView;
}
