<?php

namespace Modules\ProjectX\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Essentials\Entities\Reminder;
use Modules\ProjectX\Http\Requests\Essentials\EssentialsReminderStoreRequest;
use Modules\ProjectX\Http\Requests\Essentials\EssentialsReminderUpdateRequest;
use Modules\ProjectX\Utils\ProjectXEssentialsUtil;

class EssentialsReminderController extends Controller
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

        $user_id = $this->projectXEssentialsUtil->authUserId();

        if ($request->ajax()) {
            $events = $this->projectXEssentialsUtil->reminderEventsForProjectX([
                'start_date' => (string) $request->input('start'),
                'end_date' => (string) $request->input('end'),
                'user_id' => $user_id,
                'business_id' => $business_id,
            ], 'projectx.essentials.reminders.show', 'projectx.essentials.reminders.index');

            return response()->json($events);
        }

        return view('projectx::essentials.reminder.index');
    }

    public function store(EssentialsReminderStoreRequest $request)
    {
        $business_id = $this->projectXEssentialsUtil->businessId($request);
        $user_id = $this->projectXEssentialsUtil->authUserId();

        try {
            $input = $this->projectXEssentialsUtil->normalizeReminderInput($request->validated());
            $input['business_id'] = $business_id;
            $input['user_id'] = $user_id;

            Reminder::create($input);

            return $this->respondSuccess(__('lang_v1.success'));
        } catch (\Exception $exception) {
            \Log::emergency('File:' . $exception->getFile() . ' Line:' . $exception->getLine() . ' Message:' . $exception->getMessage());

            return $this->respondWentWrong($exception);
        }
    }

    public function show(Request $request, int $id)
    {
        $business_id = $this->projectXEssentialsUtil->businessId($request);
        $this->projectXEssentialsUtil->ensureEssentialsAccess($business_id);

        $user_id = $this->projectXEssentialsUtil->authUserId();

        $reminder = Reminder::where('business_id', $business_id)
            ->where('user_id', $user_id)
            ->findOrFail($id);

        $date = ! empty($reminder->date)
            ? $this->projectXEssentialsUtil->formatDate((string) $reminder->date)
            : '';
        $time = $this->projectXEssentialsUtil->formatReminderTime((string) $reminder->time);

        $repeat = [
            'one_time' => __('essentials::lang.one_time'),
            'every_day' => __('essentials::lang.every_day'),
            'every_week' => __('essentials::lang.every_week'),
            'every_month' => __('essentials::lang.every_month'),
        ];

        return view('projectx::essentials.reminder.show', compact('reminder', 'date', 'time', 'repeat'));
    }

    public function update(EssentialsReminderUpdateRequest $request, int $id)
    {
        $business_id = $this->projectXEssentialsUtil->businessId($request);
        $this->projectXEssentialsUtil->ensureEssentialsAccess($business_id);

        $user_id = $this->projectXEssentialsUtil->authUserId();

        try {
            Reminder::where('business_id', $business_id)
                ->where('user_id', $user_id)
                ->where('id', $id)
                ->update(['repeat' => (string) $request->input('repeat')]);

            return $this->respondSuccess(__('lang_v1.updated_success'));
        } catch (\Exception $exception) {
            \Log::emergency('File:' . $exception->getFile() . ' Line:' . $exception->getLine() . ' Message:' . $exception->getMessage());

            return $this->respondWentWrong($exception);
        }
    }

    public function destroy(Request $request, int $id)
    {
        $business_id = $this->projectXEssentialsUtil->businessId($request);
        $this->projectXEssentialsUtil->ensureEssentialsAccess($business_id);

        $user_id = $this->projectXEssentialsUtil->authUserId();

        try {
            Reminder::where('business_id', $business_id)
                ->where('user_id', $user_id)
                ->where('id', $id)
                ->delete();

            return $this->respondSuccess(__('lang_v1.deleted_success'));
        } catch (\Exception $exception) {
            \Log::emergency('File:' . $exception->getFile() . ' Line:' . $exception->getLine() . ' Message:' . $exception->getMessage());

            return $this->respondWentWrong($exception);
        }
    }
}
