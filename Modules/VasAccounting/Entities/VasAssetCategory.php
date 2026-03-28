<?php

namespace Modules\VasAccounting\Entities;

class VasAssetCategory extends BaseVasModel
{
    protected $table = 'vas_asset_categories';

    protected $casts = [
        'meta' => 'array',
        'is_active' => 'boolean',
        'default_useful_life_months' => 'integer',
    ];
}
