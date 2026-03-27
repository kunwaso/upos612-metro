<?php

namespace Modules\Operations\app\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryTransaction extends Model
{
    protected $fillable = [
        'organization_id', 'txn_type', 'txn_no', 'txn_date', 'warehouse_id',
        'reference_type', 'reference_id', 'voucher_id', 'status',
    ];

    protected $casts = [
        'txn_date' => 'date',
    ];
}
