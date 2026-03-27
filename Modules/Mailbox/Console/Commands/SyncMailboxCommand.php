<?php

namespace Modules\Mailbox\Console\Commands;

use Illuminate\Console\Command;
use Modules\Mailbox\Entities\MailboxAccount;
use Modules\Mailbox\Jobs\SyncMailboxAccountJob;

class SyncMailboxCommand extends Command
{
    protected $signature = 'mailbox:sync {account_id? : Optional mailbox account id}';

    protected $description = 'Dispatch sync jobs for active mailbox accounts.';

    public function handle(): int
    {
        $accountId = $this->argument('account_id');
        $query = MailboxAccount::query()->syncable();

        if (! empty($accountId)) {
            $query->where('id', (int) $accountId);
        }

        $accounts = $query->get();
        foreach ($accounts as $account) {
            SyncMailboxAccountJob::dispatch((int) $account->id);
        }

        $this->info('Dispatched ' . $accounts->count() . ' mailbox sync job(s).');

        return self::SUCCESS;
    }
}
