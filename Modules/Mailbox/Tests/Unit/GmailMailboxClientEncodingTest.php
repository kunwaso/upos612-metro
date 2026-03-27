<?php

namespace Modules\Mailbox\Tests\Unit;

use Modules\Mailbox\Services\GmailMailboxClient;
use Tests\TestCase;

class GmailMailboxClientEncodingTest extends TestCase
{
    public function test_decode_base64_url_handles_missing_padding()
    {
        $client = new class extends GmailMailboxClient
        {
            public function decodeForTest(string $data): string
            {
                return $this->decodeBase64Url($data);
            }
        };

        $encoded = rtrim(strtr(base64_encode('hello mailbox'), '+/', '-_'), '=');

        $this->assertSame('hello mailbox', $client->decodeForTest($encoded));
    }
}
