<?php

namespace Modules\Aichat\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;

class SearchQuoteWizardContactsRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check() && auth()->user()->can('aichat.quote_wizard.use');
    }

    public function rules()
    {
        return [
            'q' => ['nullable', 'string', 'max:120'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:25'],
        ];
    }
}
