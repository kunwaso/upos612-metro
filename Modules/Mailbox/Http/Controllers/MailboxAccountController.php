<?php

namespace Modules\Mailbox\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Mailbox\Entities\MailboxAccount;
use Modules\Mailbox\Http\Requests\StoreMailboxAccountRequest;
use Modules\Mailbox\Http\Requests\TestMailboxConnectionRequest;
use Modules\Mailbox\Http\Requests\UpdateMailboxAccountRequest;
use Modules\Mailbox\Jobs\SyncMailboxAccountJob;
use Modules\Mailbox\Utils\MailboxAccountUtil;

class MailboxAccountController extends Controller
{
    public function index(Request $request, MailboxAccountUtil $accountUtil)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $userId = (int) auth()->id();
        $accounts = $accountUtil->listAccountsForOwner($businessId, $userId);

        return view('mailbox::accounts.index', compact('accounts'));
    }

    public function testConnection(TestMailboxConnectionRequest $request, MailboxAccountUtil $accountUtil)
    {
        try {
            $businessId = (int) $request->session()->get('user.business_id');
            $userId = (int) auth()->id();
            $payload = $request->validated();

            if (! empty($payload['existing_account_id'])) {
                $existingAccount = $accountUtil->getAccountForOwner($businessId, $userId, (int) $payload['existing_account_id']);
                $payload = $this->mergeExistingCredentials($payload, $existingAccount);
            }

            $result = $accountUtil->testImapConnection($payload);

            return response()->json([
                'success' => true,
                'message' => __('mailbox::lang.connection_successful'),
                'data' => $result,
            ]);
        } catch (\Throwable $exception) {
            return response()->json([
                'success' => false,
                'message' => __('mailbox::lang.connection_failed', ['message' => $exception->getMessage()]),
            ], 422);
        }
    }

    public function store(StoreMailboxAccountRequest $request, MailboxAccountUtil $accountUtil)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $userId = (int) auth()->id();

        try {
            $payload = $request->validated();
            $accountUtil->testImapConnection($payload);
            $accountUtil->storeImapAccount($businessId, $userId, $payload);
        } catch (\Throwable $exception) {
            return redirect()
                ->back()
                ->withInput()
                ->with('status', ['success' => false, 'msg' => __('mailbox::lang.connection_failed', ['message' => $exception->getMessage()])]);
        }

        return redirect()->route('mailbox.accounts.index')->with('status', ['success' => true, 'msg' => __('mailbox::lang.account_connected')]);
    }

    public function update(UpdateMailboxAccountRequest $request, int $account, MailboxAccountUtil $accountUtil)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $userId = (int) auth()->id();
        $existingAccount = $accountUtil->getAccountForOwner($businessId, $userId, $account);

        try {
            $payload = $this->mergeExistingCredentials($request->validated(), $existingAccount);
            $accountUtil->testImapConnection($payload);
            $accountUtil->storeImapAccount($businessId, $userId, $payload, $existingAccount);
        } catch (\Throwable $exception) {
            return redirect()
                ->back()
                ->withInput()
                ->with('status', ['success' => false, 'msg' => __('mailbox::lang.connection_failed', ['message' => $exception->getMessage()])]);
        }

        return redirect()->route('mailbox.accounts.index')->with('status', ['success' => true, 'msg' => __('mailbox::lang.account_updated')]);
    }

    public function destroy(Request $request, int $account, MailboxAccountUtil $accountUtil)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $userId = (int) auth()->id();
        $existingAccount = $accountUtil->getAccountForOwner($businessId, $userId, $account);
        $accountUtil->disconnectAccount($existingAccount);

        return redirect()->route('mailbox.accounts.index')->with('status', ['success' => true, 'msg' => __('mailbox::lang.account_removed')]);
    }

    public function sync(Request $request, int $account, MailboxAccountUtil $accountUtil)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $userId = (int) auth()->id();
        $existingAccount = $accountUtil->getAccountForOwner($businessId, $userId, $account);
        SyncMailboxAccountJob::dispatch((int) $existingAccount->id);

        return redirect()->route('mailbox.accounts.index')->with('status', ['success' => true, 'msg' => __('mailbox::lang.sync_queued')]);
    }

    protected function mergeExistingCredentials(array $payload, MailboxAccount $account): array
    {
        if (trim((string) ($payload['imap_password'] ?? '')) === '') {
            $payload['imap_password'] = (string) $account->encrypted_imap_password;
        }

        if (trim((string) ($payload['smtp_password'] ?? '')) === '') {
            $payload['smtp_password'] = (string) $account->encrypted_smtp_password;
        }

        foreach ([
            'email_address',
            'imap_inbox_folder',
            'imap_sent_folder',
            'imap_trash_folder',
        ] as $field) {
            if (empty($payload[$field]) && ! empty($account->{$field})) {
                $payload[$field] = (string) $account->{$field};
            }
        }

        unset($payload['existing_account_id']);

        return $payload;
    }
}
