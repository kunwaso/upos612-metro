<?php

namespace Modules\Aichat\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;

class ListQuoteWizardLocationsRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check() && auth()->user()->can('aichat.quote_wizard.use');
    }

    public function rules()
    {
        return [];
    }
}
