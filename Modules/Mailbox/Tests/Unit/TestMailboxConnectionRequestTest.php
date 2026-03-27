<?php

namespace Modules\Mailbox\Tests\Unit;

use Illuminate\Support\Facades\Validator;
use Modules\Mailbox\Http\Requests\TestMailboxConnectionRequest;
use Tests\TestCase;

class TestMailboxConnectionRequestTest extends TestCase
{
    public function test_new_connection_requires_passwords()
    {
        $payload = $this->basePayload();

        $request = TestMailboxConnectionRequest::create('/mailbox/accounts/imap/test', 'POST', $payload);
        $validator = Validator::make($payload, $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('imap_password', $validator->errors()->messages());
        $this->assertArrayHasKey('smtp_password', $validator->errors()->messages());
    }

    public function test_existing_connection_allows_blank_passwords()
    {
        $payload = $this->basePayload() + [
            'existing_account_id' => 7,
            'imap_password' => '',
            'smtp_password' => '',
        ];

        $request = TestMailboxConnectionRequest::create('/mailbox/accounts/imap/test', 'POST', $payload);
        $validator = Validator::make($payload, $request->rules());

        $this->assertTrue($validator->passes(), json_encode($validator->errors()->messages()));
    }

    protected function basePayload(): array
    {
        return [
            'email_address' => 'sales@example.com',
            'imap_host' => 'imap.example.com',
            'imap_port' => 993,
            'imap_encryption' => 'ssl',
            'imap_username' => 'sales@example.com',
            'imap_inbox_folder' => 'INBOX',
            'imap_sent_folder' => 'Sent',
            'imap_trash_folder' => 'Trash',
            'smtp_host' => 'smtp.example.com',
            'smtp_port' => 465,
            'smtp_encryption' => 'ssl',
            'smtp_username' => 'sales@example.com',
        ];
    }
}
