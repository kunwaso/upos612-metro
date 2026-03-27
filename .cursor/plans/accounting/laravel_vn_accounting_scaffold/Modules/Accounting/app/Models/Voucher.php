<?php

namespace Modules\Accounting\app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Voucher extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id', 'branch_id', 'period_id', 'voucher_type', 'voucher_no',
        'voucher_date', 'posting_date', 'document_date', 'document_no', 'description',
        'currency_code', 'exchange_rate', 'total_amount', 'status', 'source_module',
        'source_id', 'workflow_status', 'created_by', 'approved_by', 'posted_by',
        'posted_at', 'reversed_voucher_id',
    ];

    protected $casts = [
        'voucher_date' => 'date',
        'posting_date' => 'date',
        'document_date' => 'date',
        'posted_at' => 'datetime',
        'exchange_rate' => 'decimal:6',
        'total_amount' => 'decimal:2',
    ];

    public function lines(): HasMany
    {
        return $this->hasMany(VoucherLine::class);
    }
}
