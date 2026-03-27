<?php

namespace Modules\Mailbox\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Mailbox\Http\Requests\SendMailboxMessageRequest;
use Modules\Mailbox\Utils\MailboxMessageUtil;
use Modules\Mailbox\Utils\MailboxSendUtil;

class MailboxSendController extends Controller
{
    public function create(Request $request, MailboxMessageUtil $messageUtil)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $userId = (int) auth()->id();
        $accounts = $messageUtil->accountsForOwner($businessId, $userId);
        $replyMessage = null;
        $selectedAccountId = $request->filled('account_id') ? (int) $request->input('account_id') : null;

        if ($request->filled('reply_message_id')) {
            $replyMessage = $messageUtil->getMessageForOwner($businessId, $userId, (int) $request->input('reply_message_id'));
            $selectedAccountId = (int) $replyMessage->mailbox_account_id;
        }

        return view('mailbox::inbox.compose', compact('accounts', 'replyMessage', 'selectedAccountId'));
    }

    public function store(SendMailboxMessageRequest $request, MailboxSendUtil $sendUtil)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $userId = (int) auth()->id();
        $sendUtil->dispatchSend($businessId, $userId, $request->validated() + [
            'attachments' => $request->file('attachments', []),
        ]);

        if ($request->filled('reply_message_id')) {
            return redirect()
                ->route('mailbox.threads.show', ['message' => (int) $request->input('reply_message_id')])
                ->with('status', ['success' => true, 'msg' => __('mailbox::lang.send_queued')]);
        }

        return redirect()->route('mailbox.index')->with('status', ['success' => true, 'msg' => __('mailbox::lang.send_queued')]);
    }
}
