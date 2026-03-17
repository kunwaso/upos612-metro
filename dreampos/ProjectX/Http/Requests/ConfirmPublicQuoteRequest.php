<?php

namespace Modules\ProjectX\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmPublicQuoteRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'signature' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    if (! is_string($value) || ! preg_match('/^data:image\/(png|jpeg);base64,/', $value)) {
                        $fail(__('projectx::lang.quote_signature_invalid'));

                        return;
                    }

                    $encoded = substr($value, strpos($value, ',') + 1);
                    $decoded = base64_decode($encoded, true);

                    if ($decoded === false) {
                        $fail(__('projectx::lang.quote_signature_invalid'));

                        return;
                    }

                    $maxBytes = 2 * 1024 * 1024;
                    if (strlen($decoded) > $maxBytes) {
                        $fail(__('projectx::lang.quote_signature_too_large'));
                    }
                },
            ],
        ];
    }
}

