<?php

namespace Modules\Mailbox\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendMailboxMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('mailbox.send');
    }

    public function rules(): array
    {
        return [
            'account_id' => ['required', 'integer', 'exists:mailbox_accounts,id'],
            'to' => ['required', 'array', 'min:1'],
            'to.*' => ['required', 'email:rfc'],
            'cc' => ['nullable', 'array'],
            'cc.*' => ['nullable', 'email:rfc'],
            'bcc' => ['nullable', 'array'],
            'bcc.*' => ['nullable', 'email:rfc'],
            'subject' => ['nullable', 'string', 'max:500'],
            'body_html' => ['nullable', 'string'],
            'reply_message_id' => ['nullable', 'integer', 'exists:mailbox_messages,id'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'max:10240'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'to' => $this->normalizeAddresses($this->input('to')),
            'cc' => $this->normalizeAddresses($this->input('cc')),
            'bcc' => $this->normalizeAddresses($this->input('bcc')),
        ]);
    }

    protected function normalizeAddresses($value): array
    {
        if (is_string($value)) {
            $value = explode(',', $value);
        }

        return collect((array) $value)
            ->map(function ($item) {
                return trim((string) $item);
            })
            ->filter()
            ->values()
            ->all();
    }
}
