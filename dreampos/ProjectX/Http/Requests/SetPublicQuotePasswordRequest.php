<?php

namespace Modules\ProjectX\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SetPublicQuotePasswordRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check() && auth()->user()->can('projectx.quote.edit');
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'remove_password' => $this->boolean('remove_password'),
        ]);

        if (is_string($this->input('password'))) {
            $password = trim((string) $this->input('password'));
            $this->merge([
                'password' => $password === '' ? null : $password,
            ]);
        }

        if (is_string($this->input('password_confirmation'))) {
            $passwordConfirmation = trim((string) $this->input('password_confirmation'));
            $this->merge([
                'password_confirmation' => $passwordConfirmation === '' ? null : $passwordConfirmation,
            ]);
        }
    }

    public function rules()
    {
        return [
            'remove_password' => ['nullable', 'boolean'],
            'password' => [
                Rule::requiredIf(function () {
                    return ! $this->boolean('remove_password');
                }),
                'nullable',
                'string',
                'min:6',
                'max:255',
                'confirmed',
            ],
            'password_confirmation' => [
                Rule::requiredIf(function () {
                    return ! $this->boolean('remove_password');
                }),
                'nullable',
                'string',
                'min:6',
                'max:255',
            ],
        ];
    }
}
