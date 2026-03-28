<?php

namespace Modules\VasAccounting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCutoverSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cutover_settings.legacy_routes_mode' => ['required', 'string', Rule::in(['observe', 'redirect', 'disabled'])],
            'cutover_settings.parallel_run_status' => ['required', 'string', Rule::in(['not_started', 'in_progress', 'ready', 'cutover_complete'])],
            'cutover_settings.parallel_run_notes' => ['nullable', 'string', 'max:1000'],
            'rollout_settings.status' => ['required', 'string', Rule::in(['pilot', 'staged', 'full'])],
            'rollout_settings.target_go_live_date' => ['nullable', 'date'],
            'rollout_settings.support_owner' => ['nullable', 'string', 'max:120'],
            'rollout_settings.training_notes' => ['nullable', 'string', 'max:1000'],
            'rollout_settings.enabled_branch_ids' => ['nullable', 'array'],
            'rollout_settings.enabled_branch_ids.*' => ['integer'],
            'rollout_settings.rollout_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
