<?php

namespace Modules\VasAccounting\Contracts;

interface EInvoiceAdapterInterface
{
    public function issue(array $payload, array $trace = []): array;

    public function cancel(array $payload, array $trace = []): array;

    public function correct(array $payload, array $trace = []): array;

    public function replace(array $payload, array $trace = []): array;

    public function syncStatus(array $payload, array $trace = []): array;

    public function capabilities(): array;
}
