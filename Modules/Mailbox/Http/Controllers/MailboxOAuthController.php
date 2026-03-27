<?php

namespace Modules\Mailbox\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Modules\Mailbox\Jobs\SyncMailboxAccountJob;
use Modules\Mailbox\Services\GmailMailboxClient;
use Modules\Mailbox\Utils\MailboxAccountUtil;

class MailboxOAuthController extends Controller
{
    public function redirect(GmailMailboxClient $gmailClient)
    {
        return $gmailClient->redirectResponse();
    }

    public function callback(Request $request, GmailMailboxClient $gmailClient, MailboxAccountUtil $accountUtil)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $userId = (int) auth()->id();

        try {
            $socialiteUser = $gmailClient->getUserFromCallback();
            $account = $accountUtil->storeGmailAccount($businessId, $userId, $socialiteUser, [
                'email_address' => $socialiteUser->getEmail(),
            ]);

            $profile = $gmailClient->getProfile($account);
            $account = $accountUtil->storeGmailAccount($businessId, $userId, $socialiteUser, $profile);
            SyncMailboxAccountJob::dispatch((int) $account->id);

            return redirect()->route('mailbox.accounts.index')->with('status', ['success' => true, 'msg' => __('mailbox::lang.account_connected')]);
        } catch (\Throwable $exception) {
            Log::warning('Mailbox Gmail connect failed: ' . $exception->getMessage(), [
                'user_id' => auth()->id(),
                'business_id' => $request->session()->get('user.business_id'),
            ]);

            return redirect()->route('mailbox.accounts.index')->with('status', ['success' => false, 'msg' => __('mailbox::lang.gmail_connect_failed')]);
        }
    }
}
