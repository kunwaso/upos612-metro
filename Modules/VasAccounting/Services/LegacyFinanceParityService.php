<?php

namespace Modules\VasAccounting\Services;

class LegacyFinanceParityService
{
    public function __construct(protected CutoverParityService $cutoverParityService)
    {
    }

    public function build(int $businessId, ?string $period = null, array $branchIds = []): array
    {
        return $this->cutoverParityService->build($businessId, $period, $branchIds);
    }
}
