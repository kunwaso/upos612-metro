<?php

namespace Modules\VasAccounting\Entities;

use App\Product;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VasInventoryDocumentLine extends BaseVasModel
{
    protected $table = 'vas_inventory_document_lines';

    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_cost' => 'decimal:4',
        'amount' => 'decimal:4',
        'meta' => 'array',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(VasInventoryDocument::class, 'inventory_document_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
