<?php

namespace Modules\Finance\app\Models;

use Illuminate\Database\Eloquent\Model;

class SalesInvoice extends Model
{
    protected $fillable = [
        'organization_id', 'branch_id', 'customer_id', 'invoice_no', 'invoice_date',
        'due_date', 'currency_code', 'exchange_rate', 'untaxed_amount', 'tax_amount',
        'total_amount', 'status', 'e_invoice_status', 'voucher_id',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'exchange_rate' => 'decimal:6',
        'untaxed_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];
}
