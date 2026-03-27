<?php

namespace Modules\Mailbox\Tests\Unit;

use Modules\Mailbox\Entities\MailboxAccount;
use Tests\TestCase;

class MailboxAccountModelTest extends TestCase
{
    public function test_sensitive_mailbox_fields_are_encrypted_via_casts()
    {
        $account = new MailboxAccount();
        $account->encrypted_access_token = 'access-token-secret';
        $account->encrypted_refresh_token = 'refresh-token-secret';
        $account->encrypted_imap_password = 'imap-secret';
        $account->encrypted_smtp_password = 'smtp-secret';

        $attributes = $account->getAttributes();

        $this->assertNotSame('access-token-secret', $attributes['encrypted_access_token']);
        $this->assertNotSame('refresh-token-secret', $attributes['encrypted_refresh_token']);
        $this->assertNotSame('imap-secret', $attributes['encrypted_imap_password']);
        $this->assertNotSame('smtp-secret', $attributes['encrypted_smtp_password']);
        $this->assertSame('access-token-secret', $account->encrypted_access_token);
        $this->assertSame('refresh-token-secret', $account->encrypted_refresh_token);
        $this->assertSame('imap-secret', $account->encrypted_imap_password);
        $this->assertSame('smtp-secret', $account->encrypted_smtp_password);
    }
}
