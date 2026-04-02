<?php

namespace Modules\VasAccounting\Tests\Unit;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Modules\VasAccounting\Jobs\DispatchExpenseApprovalEscalationJob;
use Modules\VasAccounting\Services\WorkflowApproval\ExpenseApprovalEscalationDispatchService;
use RuntimeException;
use Tests\TestCase;

class DispatchExpenseApprovalEscalationJobTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function test_handle_delegates_to_dispatch_service(): void
    {
        $service = Mockery::mock(ExpenseApprovalEscalationDispatchService::class);
        $service->shouldReceive('handleDispatch')
            ->once()
            ->with(10, 20, 30, 40, 'Escalate now');

        $job = new DispatchExpenseApprovalEscalationJob(10, 20, 30, 40, 'Escalate now');

        $job->handle($service);
    }

    public function test_failed_delegates_to_dispatch_failure_handler(): void
    {
        $service = Mockery::mock(ExpenseApprovalEscalationDispatchService::class);
        $service->shouldReceive('handleDispatchFailure')
            ->once()
            ->withArgs(function (
                int $documentId,
                int $approvalInstanceId,
                int $approvalStepId,
                int $actorId,
                ?string $reason,
                RuntimeException $exception
            ): bool {
                return $documentId === 11
                    && $approvalInstanceId === 21
                    && $approvalStepId === 31
                    && $actorId === 41
                    && $reason === 'Dispatch escalation'
                    && $exception->getMessage() === 'Queue transport unavailable';
            });

        app()->instance(ExpenseApprovalEscalationDispatchService::class, $service);

        $job = new DispatchExpenseApprovalEscalationJob(11, 21, 31, 41, 'Dispatch escalation');

        $job->failed(new RuntimeException('Queue transport unavailable'));
    }
}
