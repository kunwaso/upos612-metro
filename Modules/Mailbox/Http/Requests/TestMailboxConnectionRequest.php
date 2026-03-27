<?php

namespace Modules\Mailbox\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TestMailboxConnectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('mailbox.manage_accounts');
    }

    public function rules(): array
    {
        $passwordRules = $this->filled('existing_account_id')
            ? ['nullable', 'string', 'max:500']
            : ['required', 'string', 'max:500'];

        return [
            'existing_account_id' => ['nullable', 'integer', 'min:1'],
            'email_address' => ['required', 'email:rfc', 'max:191'],
            'imap_host' => ['required', 'string', 'max:191'],
            'imap_port' => ['required', 'integer', 'between:1,65535'],
            'imap_encryption' => ['nullable', 'in:ssl,tls,starttls,none'],
            'imap_username' => ['required', 'string', 'max:191'],
            'imap_password' => $passwordRules,
            'imap_inbox_folder' => ['nullable', 'string', 'max:120'],
            'imap_sent_folder' => ['nullable', 'string', 'max:120'],
            'imap_trash_folder' => ['nullable', 'string', 'max:120'],
            'smtp_host' => ['required', 'string', 'max:191'],
            'smtp_port' => ['required', 'integer', 'between:1,65535'],
            'smtp_encryption' => ['nullable', 'in:ssl,tls,starttls,none'],
            'smtp_username' => ['required', 'string', 'max:191'],
            'smtp_password' => $passwordRules,
        ];
    }
}
