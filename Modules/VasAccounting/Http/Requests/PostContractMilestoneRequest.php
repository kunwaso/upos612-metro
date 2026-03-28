<?php

namespace Modules\VasAccounting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PostContractMilestoneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('vas_accounting.contracts.manage');
    }

    public function rules(): array
    {
        return [
            'posted_at' => ['nullable', 'date'],
        ];
    }
}
