<?php

namespace Modules\ProjectX\Http\Requests\Essentials;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\ProjectX\Http\Requests\Essentials\Concerns\AuthorizesEssentialsRequest;

class EssentialsKnowledgeBaseStoreRequest extends FormRequest
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
            'kb_type' => ['nullable', Rule::in(['knowledge_base', 'section', 'article'])],
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('essentials_kb', 'id')->where(function ($query) use ($business_id) {
                    $query->where('business_id', $business_id);
                }),
            ],
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
