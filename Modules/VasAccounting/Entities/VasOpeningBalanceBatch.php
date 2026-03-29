<?php

namespace Modules\VasAccounting\Entities;

class VasOpeningBalanceBatch extends BaseVasModel
{
    protected $table = 'vas_opening_balance_batches';

    protected $casts = [
        'meta' => 'array',
    ];
}
