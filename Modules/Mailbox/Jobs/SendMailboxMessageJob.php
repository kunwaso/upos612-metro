<?php

namespace Modules\Mailbox\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Mailbox\Utils\MailboxSendUtil;

class SendMailboxMessageJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $businessId;

    public int $userId;

    public array $payload;

    public $queue = 'mailbox-send';

    public function __construct(int $businessId, int $userId, array $payload)
    {
        $this->businessId = $businessId;
        $this->userId = $userId;
        $this->payload = $payload;
    }

    public function handle(MailboxSendUtil $sendUtil): void
    {
        $sendUtil->handleSend($this->businessId, $this->userId, $this->payload);
    }
}
