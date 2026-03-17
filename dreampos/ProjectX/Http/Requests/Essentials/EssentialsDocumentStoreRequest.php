<?php

namespace Modules\ProjectX\Http\Requests\Essentials;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\ProjectX\Http\Requests\Essentials\Concerns\AuthorizesEssentialsRequest;

class EssentialsDocumentStoreRequest extends FormRequest
{
    use AuthorizesEssentialsRequest;

    public function authorize()
    {
        return $this->hasEssentialsAccess();
    }

    public function rules()
    {
        return [
            'type' => ['required', Rule::in(['document', 'memos'])],
            'name' => ['required'],
            'description' => ['nullable', 'string'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $type = (string) $this->input('type');
            $name = $this->input('name');

            if ($type === 'document' && ! $this->hasFile('name')) {
                $validator->errors()->add('name', __('validation.required', ['attribute' => 'name']));
            }

            if ($type === 'memos' && ! is_string($name)) {
                $validator->errors()->add('name', __('validation.string', ['attribute' => 'name']));
            }
        });
    }
}
