<?php

namespace Modules\Mailbox\Http\Requests;

class UpdateMailboxAccountRequest extends StoreMailboxAccountRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        $rules['imap_password'] = ['nullable', 'string', 'max:500'];
        $rules['smtp_password'] = ['nullable', 'string', 'max:500'];

        return $rules;
    }
}
