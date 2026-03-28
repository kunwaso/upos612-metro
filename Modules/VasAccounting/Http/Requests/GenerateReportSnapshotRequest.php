<?php

namespace Modules\VasAccounting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateReportSnapshotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'report_key' => ['required', 'string', 'max:80'],
            'snapshot_name' => ['nullable', 'string', 'max:150'],
            'period_id' => ['nullable', 'integer'],
        ];
    }
}
