<?php

namespace Modules\VasAccounting\Contracts;

interface PayrollBridgeInterface
{
    public function buildVoucherPayload(array $payrollData, array $context = []): array;
}
