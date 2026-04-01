<?php

namespace Modules\VasAccounting\Contracts;

use Modules\VasAccounting\Application\DTOs\ActionContext;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceAccountingEvent;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceDocument;

interface OpenItemServiceInterface
{
    public function resolveDocumentProfile(FinanceDocument $document): ?array;

    public function syncPostedDocument(FinanceDocument $document, FinanceAccountingEvent $event, ActionContext $context): void;

    public function reverseDocument(FinanceDocument $document, FinanceAccountingEvent $reversalEvent, ActionContext $context): void;
}
