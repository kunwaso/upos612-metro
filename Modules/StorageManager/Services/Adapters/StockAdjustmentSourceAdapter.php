<?php

namespace Modules\StorageManager\Services\Adapters;

class StockAdjustmentSourceAdapter extends AbstractTransactionSourceAdapter
{
    public function supportedSourceType(): string
    {
        return 'stock_adjustment';
    }

    protected function type(): string
    {
        return 'stock_adjustment';
    }
}
