<?php

namespace Modules\VasAccounting\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\VasAccounting\Services\WorkflowApproval\ExpenseApprovalEscalationDispatchService;
use Throwable;

class DispatchExpenseApprovalEscalationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public int $documentId,
        public int $approvalInstanceId,
        public int $approvalStepId,
        public int $actorId,
        public ?string $reason = null
    ) {
        $this->afterCommit();
    }

    public function handle(ExpenseApprovalEscalationDispatchService $dispatchService): void
    {
        $dispatchService->handleDispatch(
            $this->documentId,
            $this->approvalInstanceId,
            $this->approvalStepId,
            $this->actorId,
            $this->reason
        );
    }

    public function failed(Throwable $exception): void
    {
        app(ExpenseApprovalEscalationDispatchService::class)->handleDispatchFailure(
            $this->documentId,
            $this->approvalInstanceId,
            $this->approvalStepId,
            $this->actorId,
            $this->reason,
            $exception
        );
    }
}
