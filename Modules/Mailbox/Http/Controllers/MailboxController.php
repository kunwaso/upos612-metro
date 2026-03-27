<?php

namespace Modules\Mailbox\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Modules\Mailbox\Utils\MailboxMessageUtil;

class MailboxController extends Controller
{
    public function index(Request $request, MailboxMessageUtil $messageUtil)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $userId = (int) auth()->id();
        $filters = [
            'account_id' => $request->filled('account_id') ? (int) $request->input('account_id') : null,
            'folder' => (string) $request->input('folder', 'inbox'),
            'search' => (string) $request->input('search', ''),
            'status' => (string) $request->input('status', ''),
            'sort' => (string) $request->input('sort', 'newest'),
        ];

        $accounts = $messageUtil->accountsForOwner($businessId, $userId);
        $messages = $messageUtil->threadSummariesForOwner($businessId, $userId, $filters);
        $counts = $messageUtil->folderCounts($businessId, $userId, $filters['account_id']);

        return view('mailbox::inbox.index', compact('accounts', 'messages', 'counts', 'filters'));
    }

    public function showThread(Request $request, int $message, MailboxMessageUtil $messageUtil)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $userId = (int) auth()->id();
        $selectedMessage = $messageUtil->getMessageForOwner($businessId, $userId, $message);
        $messageUtil->markThreadAsRead($selectedMessage);
        $selectedMessage->is_read = true;
        $threadMessages = $messageUtil->threadForMessage($selectedMessage);
        $accounts = $messageUtil->accountsForOwner($businessId, $userId);
        $counts = $messageUtil->folderCounts($businessId, $userId, (int) $selectedMessage->mailbox_account_id);
        $filters = [
            'folder' => (string) $request->input('folder', $selectedMessage->folder),
            'account_id' => (int) $selectedMessage->mailbox_account_id,
        ];

        return view('mailbox::inbox.thread', compact('selectedMessage', 'threadMessages', 'accounts', 'counts', 'filters'));
    }

    public function toggleRead(Request $request, int $message, MailboxMessageUtil $messageUtil)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $userId = (int) auth()->id();
        $mailboxMessage = $messageUtil->getMessageForOwner($businessId, $userId, $message);
        $value = $request->has('value') ? $request->boolean('value') : ! $mailboxMessage->is_read;
        $messageUtil->updateReadState($mailboxMessage, $value);

        return $this->statusResponse($request);
    }

    public function toggleStar(Request $request, int $message, MailboxMessageUtil $messageUtil)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $userId = (int) auth()->id();
        $mailboxMessage = $messageUtil->getMessageForOwner($businessId, $userId, $message);
        $value = $request->has('value') ? $request->boolean('value') : ! $mailboxMessage->is_starred;
        $messageUtil->updateStarState($mailboxMessage, $value);

        return $this->statusResponse($request);
    }

    public function moveToTrash(Request $request, int $message, MailboxMessageUtil $messageUtil)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $userId = (int) auth()->id();
        $mailboxMessage = $messageUtil->getMessageForOwner($businessId, $userId, $message);
        $messageUtil->moveToTrash($mailboxMessage);

        return $this->statusResponse($request);
    }

    public function downloadAttachment(Request $request, int $attachment, MailboxMessageUtil $messageUtil)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $userId = (int) auth()->id();
        $mailboxAttachment = $messageUtil->getAttachmentForOwner($businessId, $userId, $attachment);
        $mailboxAttachment = $messageUtil->ensureAttachmentDownloaded($mailboxAttachment);

        return Storage::disk((string) $mailboxAttachment->disk)->download(
            (string) $mailboxAttachment->disk_path,
            (string) $mailboxAttachment->filename
        );
    }

    protected function statusResponse(Request $request)
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => true]);
        }

        return redirect()->back()->with('status', ['success' => true, 'msg' => __('lang_v1.success')]);
    }
}
