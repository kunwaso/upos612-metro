<?php

namespace Modules\VasAccounting\Contracts;

use Modules\VasAccounting\Application\DTOs\ActionContext;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceMatchException;

interface ProcurementDiscrepancyServiceInterface
{
    public function takeOwnership(FinanceMatchException $exception, ActionContext $context): FinanceMatchException;

    public function assignOwner(FinanceMatchException $exception, int $ownerId, ActionContext $context): FinanceMatchException;

    public function resolve(FinanceMatchException $exception, string $resolutionNote, ActionContext $context): FinanceMatchException;
}
