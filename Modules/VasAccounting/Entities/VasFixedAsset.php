<?php

namespace Modules\VasAccounting\Entities;

use App\BusinessLocation;
use App\Contact;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VasFixedAsset extends BaseVasModel
{
    protected $table = 'vas_fixed_assets';

    protected $casts = [
        'acquisition_date' => 'date',
        'capitalization_date' => 'date',
        'disposed_at' => 'date',
        'original_cost' => 'decimal:4',
        'salvage_value' => 'decimal:4',
        'monthly_depreciation' => 'decimal:4',
        'meta' => 'array',
        'useful_life_months' => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(VasAssetCategory::class, 'asset_category_id');
    }

    public function businessLocation(): BelongsTo
    {
        return $this->belongsTo(BusinessLocation::class, 'business_location_id');
    }

    public function vendorContact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'vendor_contact_id');
    }

    public function disposalVoucher(): BelongsTo
    {
        return $this->belongsTo(VasVoucher::class, 'disposal_voucher_id');
    }

    public function depreciations(): HasMany
    {
        return $this->hasMany(VasAssetDepreciation::class, 'fixed_asset_id');
    }
}
