<?php

namespace Modules\ProjectX\Http\Controllers;

use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Http\Request;
use Modules\Essentials\Entities\EssentialsMessage;
use Modules\Essentials\Notifications\NewMessageNotification;
use Modules\ProjectX\Http\Requests\Essentials\EssentialsMessageStoreRequest;
use Modules\ProjectX\Utils\ProjectXEssentialsUtil;

class EssentialsMessageController extends Controller
{
    protected ProjectXEssentialsUtil $projectXEssentialsUtil;

    public function __construct(ProjectXEssentialsUtil $projectXEssentialsUtil)
    {
        $this->projectXEssentialsUtil = $projectXEssentialsUtil;
    }

    public function index(Request $request)
    {
        $business_id = $this->projectXEssentialsUtil->businessId($request);
        $this->projectXEssentialsUtil->ensureEssentialsAccess($business_id);

        if (! auth()->user()->can('essentials.view_message') && ! auth()->user()->can('essentials.create_message')) {
            abort(403, __('messages.unauthorized_action'));
        }

        $messages = $this->projectXEssentialsUtil->getMessagesQuery($business_id)->get();
        $business_locations = $this->projectXEssentialsUtil->getBusinessLocations($business_id);
        $last_chat_time = optional($messages->last())->created_at?->toDateTimeString();

        return view('projectx::essentials.messages.index', compact('messages', 'business_locations', 'last_chat_time'));
    }

    public function store(EssentialsMessageStoreRequest $request)
    {
        $business_id = $this->projectXEssentialsUtil->businessId($request);
        $user_id = $this->projectXEssentialsUtil->authUserId();

        try {
            $input = $request->validated();
            $input['business_id'] = $business_id;
            $input['user_id'] = $user_id;
            $input['message'] = $this->projectXEssentialsUtil->normalizeMessage((string) $input['message']);

            $location_id = ! empty($input['location_id']) ? (int) $input['location_id'] : null;
            $last_message = $this->projectXEssentialsUtil->getLastMessageForLocation($business_id, $location_id);

            $message = EssentialsMessage::create($input);
            $database_notification = $this->projectXEssentialsUtil->shouldSendDatabaseNotification($last_message);
            $this->notifyUsers($message, $database_notification);

            $html = view('projectx::essentials.messages.partials._message_div', compact('message'))->render();

            return $this->respondSuccess(__('lang_v1.success'), ['html' => $html]);
        } catch (\Exception $exception) {
            \Log::emergency('File:' . $exception->getFile() . ' Line:' . $exception->getLine() . ' Message:' . $exception->getMessage());

            return $this->respondWentWrong($exception);
        }
    }

    public function destroy(Request $request, int $id)
    {
        $business_id = $this->projectXEssentialsUtil->businessId($request);
        $this->projectXEssentialsUtil->ensureEssentialsAccess($business_id);

        if (! auth()->user()->can('essentials.create_message')) {
            return $this->respondUnauthorized(__('messages.unauthorized_action'));
        }

        try {
            EssentialsMessage::where('business_id', $business_id)
                ->where('user_id', $this->projectXEssentialsUtil->authUserId())
                ->where('id', $id)
                ->delete();

            return $this->respondSuccess(__('lang_v1.deleted_success'));
        } catch (\Exception $exception) {
            \Log::emergency('File:' . $exception->getFile() . ' Line:' . $exception->getLine() . ' Message:' . $exception->getMessage());

            return $this->respondWentWrong($exception);
        }
    }

    public function getNewMessages(Request $request)
    {
        $business_id = $this->projectXEssentialsUtil->businessId($request);
        $this->projectXEssentialsUtil->ensureEssentialsAccess($business_id);

        if (! auth()->user()->can('essentials.view_message') && ! auth()->user()->can('essentials.create_message')) {
            abort(403, __('messages.unauthorized_action'));
        }

        $last_chat_time = (string) $request->input('last_chat_time', '');

        $query = $this->projectXEssentialsUtil->getMessagesQuery($business_id)
            ->where('user_id', '!=', $this->projectXEssentialsUtil->authUserId());

        if (! empty($last_chat_time)) {
            $query->where('created_at', '>', $last_chat_time);
        }

        $messages = $query->get();

        return view('projectx::essentials.messages.partials.recent_messages', compact('messages'));
    }

    protected function notifyUsers(EssentialsMessage $message, bool $database_notification = true): void
    {
        $business_id = (int) $message->business_id;

        $query = User::where('id', '!=', (int) $message->user_id)
            ->where('business_id', $business_id);

        if (empty($message->location_id)) {
            $users = $query->get();
        } else {
            $users = $query->permission('location.' . $message->location_id)->get();
        }

        if ($users->isNotEmpty()) {
            $message->database_notification = $database_notification;
            \Notification::send(
                $users,
                new NewMessageNotification($message, route('projectx.essentials.messages.index'))
            );
        }
    }
}
