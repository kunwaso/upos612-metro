<?php

namespace Modules\Mailbox\Tests\Unit;

use Modules\Mailbox\Services\ImapMailboxClient;
use Tests\TestCase;

class ImapMailboxClientTest extends TestCase
{
    public function test_provider_message_id_round_trips_folder_and_uid()
    {
        $client = new ImapMailboxClient();

        $encoded = $client->encodeProviderMessageId('INBOX/Sales', 42);
        [$folder, $uid] = $client->decodeProviderMessageId($encoded);

        $this->assertSame('INBOX/Sales', $folder);
        $this->assertSame(42, $uid);
    }

    public function test_invalid_provider_message_id_returns_empty_values()
    {
        $client = new ImapMailboxClient();

        [$folder, $uid] = $client->decodeProviderMessageId('not-a-valid-id');

        $this->assertSame('', $folder);
        $this->assertSame(0, $uid);
    }
}
