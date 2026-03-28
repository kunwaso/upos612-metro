<?php

namespace Modules\VasAccounting\Contracts;

interface EInvoiceAdapterInterface
{
    public function issue(array $payload): array;

    public function cancel(array $payload): array;

    public function correct(array $payload): array;

    public function replace(array $payload): array;

    public function syncStatus(array $payload): array;
}
