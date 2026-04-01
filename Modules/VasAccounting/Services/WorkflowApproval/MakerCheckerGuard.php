<?php

namespace Modules\VasAccounting\Services\WorkflowApproval;

use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceDocument;
use RuntimeException;

class MakerCheckerGuard
{
    public function assertCanApprove(FinanceDocument $document, int $approverUserId): void
    {
        if (! config('vasaccounting.approval_defaults.finance_document_defaults.maker_checker', true)) {
            return;
        }

        if ((int) $document->submitted_by === $approverUserId && $approverUserId > 0) {
            throw new RuntimeException('Maker-checker control prevents the document submitter from approving the same finance document.');
        }
    }
}
