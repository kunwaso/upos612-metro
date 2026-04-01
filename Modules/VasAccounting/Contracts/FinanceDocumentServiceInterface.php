<?php

namespace Modules\VasAccounting\Contracts;

use Modules\VasAccounting\Application\DTOs\ActionContext;
use Modules\VasAccounting\Application\DTOs\DocumentCreateData;
use Modules\VasAccounting\Application\DTOs\DocumentUpdateData;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceDocument;

interface FinanceDocumentServiceInterface
{
    public function create(DocumentCreateData $data): FinanceDocument;

    public function update(int $documentId, DocumentUpdateData $data): FinanceDocument;

    public function submit(int $documentId, ActionContext $context): FinanceDocument;

    public function approve(int $documentId, ActionContext $context): FinanceDocument;

    public function match(int $documentId, ActionContext $context): FinanceDocument;

    public function fulfill(int $documentId, ActionContext $context): FinanceDocument;

    public function close(int $documentId, ActionContext $context): FinanceDocument;

    public function cancel(int $documentId, ActionContext $context): FinanceDocument;

    public function reverse(int $documentId, ActionContext $context): FinanceDocument;
}
