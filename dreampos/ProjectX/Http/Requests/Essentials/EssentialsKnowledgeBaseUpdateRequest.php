<?php

namespace Modules\ProjectX\Http\Requests\Essentials;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\ProjectX\Http\Requests\Essentials\Concerns\AuthorizesEssentialsRequest;

class EssentialsKnowledgeBaseUpdateRequest extends FormRequest
{
    use AuthorizesEssentialsRequest;

    public function authorize()
    {
        return $this->hasEssentialsAccess();
    }

    public function rules()
    {
        $business_id = $this->businessId();

        return [
            'title' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'share_with' => ['nullable', Rule::in(['public', 'private', 'only_with'])],
            'user_ids' => ['nullable', 'array'],
            'user_ids.*' => [
                'integer',
                Rule::exists('users', 'id')->where(function ($query) use ($business_id) {
                    $query->where('business_id', $business_id);
                }),
            ],
        ];
    }
}
