<?php

namespace Modules\Mailbox\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Mailbox\Entities\MailboxAccount;
use Modules\Mailbox\Utils\MailboxSyncUtil;

class SyncMailboxAccountJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $accountId;

    public function __construct(int $accountId)
    {
        $this->accountId = $accountId;
        $this->onQueue('mailbox-sync');
    }

    public function handle(MailboxSyncUtil $syncUtil): void
    {
        $account = MailboxAccount::query()->where('id', $this->accountId)->first();
        if (! $account || ! $account->is_active || ! $account->sync_enabled) {
            return;
        }

        $syncUtil->syncAccount($account);
    }
}
