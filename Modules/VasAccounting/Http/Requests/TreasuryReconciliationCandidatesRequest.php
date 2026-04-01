<?php

namespace Modules\VasAccounting\Http\Requests;

class TreasuryReconciliationCandidatesRequest extends TreasuryReconciliationActionRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'limit' => ['nullable', 'integer', 'min:1', 'max:20'],
        ]);
    }
}
