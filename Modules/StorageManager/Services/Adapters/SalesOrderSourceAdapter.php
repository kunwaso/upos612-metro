<?php

namespace Modules\StorageManager\Services\Adapters;

class SalesOrderSourceAdapter extends AbstractTransactionSourceAdapter
{
    public function supportedSourceType(): string
    {
        return 'sales_order';
    }

    protected function type(): string
    {
        return 'sales_order';
    }
}
