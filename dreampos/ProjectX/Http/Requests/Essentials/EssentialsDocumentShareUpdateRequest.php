<?php

namespace Modules\ProjectX\Http\Requests\Essentials;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\ProjectX\Http\Requests\Essentials\Concerns\AuthorizesEssentialsRequest;

class EssentialsDocumentShareUpdateRequest extends FormRequest
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
            'document_id' => [
                'required',
                'integer',
                Rule::exists('essentials_documents', 'id')->where(function ($query) use ($business_id) {
                    $query->where('business_id', $business_id);
                }),
            ],
            'user' => ['nullable', 'array'],
            'user.*' => [
                'integer',
                Rule::exists('users', 'id')->where(function ($query) use ($business_id) {
                    $query->where('business_id', $business_id);
                }),
            ],
            'role' => ['nullable', 'array'],
            'role.*' => ['integer', Rule::exists('roles', 'id')],
        ];
    }
}
