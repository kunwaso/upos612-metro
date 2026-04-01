<?php

namespace Modules\VasAccounting\Http\Requests;

class TreasuryReconciliationActionRequest extends FinanceDocumentActionRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'finance_document_id' => ['sometimes', 'integer', 'min:1'],
            'finance_open_item_id' => ['nullable', 'integer', 'min:1'],
        ]);
    }
}
