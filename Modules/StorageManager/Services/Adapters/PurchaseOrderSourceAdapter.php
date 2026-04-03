<?php

namespace Modules\StorageManager\Services\Adapters;

class PurchaseOrderSourceAdapter extends AbstractTransactionSourceAdapter
{
    public function supportedSourceType(): string
    {
        return 'purchase_order';
    }

    protected function type(): string
    {
        return 'purchase_order';
    }
}
