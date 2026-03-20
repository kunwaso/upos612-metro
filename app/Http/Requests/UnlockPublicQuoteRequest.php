<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UnlockPublicQuoteRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    protected function prepareForValidation()
    {
        if ($this->resolvedUnlockInputMode() !== 'otp') {
            return;
        }

        $length = $this->resolvedOtpLength();
        $parts = [];
        for ($i = 1; $i <= $length; $i++) {
            $parts[] = (string) $this->input('code_'.$i, '');
        }

        $this->merge([
            'password' => implode('', $parts),
        ]);
    }

    public function rules()
    {
        $passwordRules = ['required', 'string', 'max:255'];

        if ($this->resolvedUnlockInputMode() === 'otp') {
            $len = $this->resolvedOtpLength();
            if (config('product.public_quote_unlock.otp_digits_only', true)) {
                $passwordRules[] = 'regex:/^\d{'.$len.'}$/';
            } else {
                $passwordRules[] = 'regex:/^[a-zA-Z0-9]{'.$len.'}$/';
            }
        }

        return [
            'password' => $passwordRules,
        ];
    }

    protected function resolvedUnlockInputMode(): string
    {
        $mode = strtolower((string) config('product.public_quote_unlock.input_mode', 'password'));

        return in_array($mode, ['password', 'otp'], true) ? $mode : 'password';
    }

    protected function resolvedOtpLength(): int
    {
        return min(32, max(1, (int) config('product.public_quote_unlock.otp_length', 6)));
    }
}
