<?php

namespace Modules\VasAccounting\Entities;

class VasTaxCode extends BaseVasModel
{
    protected $table = 'vas_tax_codes';

    protected $casts = [
        'meta' => 'array',
        'is_active' => 'boolean',
        'is_system' => 'boolean',
        'rate' => 'decimal:4',
    ];
}
