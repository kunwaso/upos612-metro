<?php

namespace Modules\Mailbox\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMailboxAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('mailbox.manage_accounts');
    }

    public function rules(): array
    {
        return [
            'display_name' => ['nullable', 'string', 'max:120'],
            'email_address' => ['required', 'email:rfc', 'max:191'],
            'sender_name' => ['nullable', 'string', 'max:120'],
            'imap_host' => ['required', 'string', 'max:191'],
            'imap_port' => ['required', 'integer', 'between:1,65535'],
            'imap_encryption' => ['nullable', 'in:ssl,tls,starttls,none'],
            'imap_username' => ['required', 'string', 'max:191'],
            'imap_password' => ['required', 'string', 'max:500'],
            'imap_inbox_folder' => ['nullable', 'string', 'max:120'],
            'imap_sent_folder' => ['nullable', 'string', 'max:120'],
            'imap_trash_folder' => ['nullable', 'string', 'max:120'],
            'smtp_host' => ['required', 'string', 'max:191'],
            'smtp_port' => ['required', 'integer', 'between:1,65535'],
            'smtp_encryption' => ['nullable', 'in:ssl,tls,starttls,none'],
            'smtp_username' => ['required', 'string', 'max:191'],
            'smtp_password' => ['required', 'string', 'max:500'],
            'sync_enabled' => ['nullable', 'boolean'],
        ];
    }
}
