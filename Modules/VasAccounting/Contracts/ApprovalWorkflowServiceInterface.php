<?php

namespace Modules\VasAccounting\Contracts;

use Modules\VasAccounting\Application\DTOs\ActionContext;
use Modules\VasAccounting\Application\DTOs\ApprovalStateView;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceDocument;
use Modules\VasAccounting\Domain\WorkflowApproval\Models\FinanceApprovalInstance;

interface ApprovalWorkflowServiceInterface
{
    public function start(FinanceDocument $document, ActionContext $context): FinanceApprovalInstance;

    public function approve(FinanceDocument $document, ActionContext $context): FinanceApprovalInstance;

    public function reject(FinanceDocument $document, ActionContext $context): FinanceApprovalInstance;

    public function escalate(FinanceDocument $document, ActionContext $context): FinanceApprovalInstance;

    public function currentState(FinanceDocument $document): ApprovalStateView;
}
