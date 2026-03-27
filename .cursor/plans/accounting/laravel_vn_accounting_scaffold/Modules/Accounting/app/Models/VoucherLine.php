<?php

namespace Modules\Accounting\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoucherLine extends Model
{
    protected $fillable = [
        'voucher_id', 'line_no', 'description', 'debit_account_id', 'credit_account_id',
        'amount', 'amount_fc', 'currency_code', 'customer_id', 'vendor_id', 'employee_id',
        'department_id', 'cost_center_id', 'project_id', 'item_id', 'warehouse_id',
        'asset_id', 'tax_code_id', 'due_date', 'reference_type', 'reference_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'amount_fc' => 'decimal:2',
        'due_date' => 'date',
    ];

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class);
    }
}
