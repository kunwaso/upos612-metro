<?php

namespace Modules\StorageManager\Services\Adapters;

class PurchaseSourceAdapter extends AbstractTransactionSourceAdapter
{
    public function supportedSourceType(): string
    {
        return 'purchase';
    }

    protected function type(): string
    {
        return 'purchase';
    }
}
