<?php

namespace Modules\Essentials\Http\Requests;

use App\Utils\ModuleUtil;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TranslateTranscriptRequest extends FormRequest
{
    public function authorize(): bool
    {
        $business_id = $this->session()->get('user.business_id');
        $moduleUtil = app(ModuleUtil::class);

        return auth()->user()->can('superadmin')
            || $moduleUtil->hasThePermissionInSubscription($business_id, 'essentials_module');
    }

    public function rules(): array
    {
        $languageKeys = array_keys((array) config('constants.langs', []));

        return [
            'text' => ['required', 'string', 'max:20000'],
            'source_language' => ['required', 'string', Rule::in($languageKeys)],
            'target_language' => ['required', 'string', Rule::in($languageKeys)],
        ];
    }

    public function messages(): array
    {
        return [
            'text.required'            => __('essentials::lang.translation_text_required'),
            'source_language.required' => __('essentials::lang.language_required'),
            'source_language.in'       => __('essentials::lang.language_invalid'),
            'target_language.required' => __('essentials::lang.language_required'),
            'target_language.in'       => __('essentials::lang.language_invalid'),
        ];
    }
}
