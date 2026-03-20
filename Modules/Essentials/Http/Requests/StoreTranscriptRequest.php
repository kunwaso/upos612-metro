<?php

namespace Modules\Essentials\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Utils\ModuleUtil;

class StoreTranscriptRequest extends FormRequest
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
        return [
            'title'          => ['nullable', 'string', 'max:255'],
            'audio'          => [
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
            'audio.required_without'          => __('essentials::lang.audio_required'),
            'recorded_audio.required_without' => __('essentials::lang.audio_required'),
            'audio.mimes'                     => __('essentials::lang.audio_invalid_format'),
            'audio.max'                       => __('essentials::lang.audio_too_large'),
            'recorded_audio.max'              => __('essentials::lang.audio_too_large'),
        ];
    }
}
