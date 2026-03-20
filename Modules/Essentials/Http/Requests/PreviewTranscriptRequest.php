<?php

namespace Modules\Essentials\Http\Requests;

use App\Utils\ModuleUtil;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PreviewTranscriptRequest extends FormRequest
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
            'source_language' => ['required', 'string', Rule::in($languageKeys)],
            'target_language' => ['required', 'string', Rule::in($languageKeys)],
            'audio' => [
                'required_without:recorded_audio',
                'file',
                'mimes:mp3,wav,m4a,webm,mpeg,ogg,flac',
                'max:25600',
            ],
            'recorded_audio' => [
                'required_without:audio',
                'file',
                'max:25600',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'source_language.required'        => __('essentials::lang.language_required'),
            'source_language.in'              => __('essentials::lang.language_invalid'),
            'target_language.required'        => __('essentials::lang.language_required'),
            'target_language.in'              => __('essentials::lang.language_invalid'),
            'audio.required_without'          => __('essentials::lang.audio_required'),
            'recorded_audio.required_without' => __('essentials::lang.audio_required'),
            'audio.mimes'                     => __('essentials::lang.audio_invalid_format'),
            'audio.max'                       => __('essentials::lang.audio_too_large'),
            'recorded_audio.max'              => __('essentials::lang.audio_too_large'),
        ];
    }
}
