<?php

namespace Modules\StorageManager\Contracts;

use Modules\StorageManager\Entities\StorageDocument;

interface WarehousePostingAdapterInterface
{
    public function adapterKey(): string;

    public function isAvailable(int $businessId): bool;

    public function sync(StorageDocument $document, int $userId): array;

    public function reconcile(StorageDocument $document): array;
}
