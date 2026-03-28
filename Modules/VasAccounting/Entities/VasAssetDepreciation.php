<?php

namespace Modules\VasAccounting\Entities;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VasAssetDepreciation extends BaseVasModel
{
    protected $table = 'vas_asset_depreciations';

    protected $casts = [
        'depreciation_date' => 'date',
        'amount' => 'decimal:4',
        'posted_at' => 'datetime',
        'meta' => 'array',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(VasFixedAsset::class, 'fixed_asset_id');
    }
}
