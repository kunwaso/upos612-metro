<?php

namespace App\Http\Controllers;

use App\CalendarSchedule;
use App\Http\Requests\StoreCalendarScheduleRequest;
use App\Http\Requests\UpdateCalendarScheduleRequest;
use App\Utils\CalendarEventUtil;
use Illuminate\Http\Request;

class CalendarController extends Controller
{
    protected CalendarEventUtil $calendarEventUtil;

    public function __construct(CalendarEventUtil $calendarEventUtil)
    {
        $this->calendarEventUtil = $calendarEventUtil;
    }

    public function index(Request $request)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $user = $request->user();

        if ($request->ajax()) {
            $filters = $this->calendarEventUtil->buildFilters($request, $businessId, $user);

            return response()->json($this->calendarEventUtil->getEvents($filters, $user));
        }

        return view('home.calendar', $this->calendarEventUtil->getCalendarPageData($businessId, $user));
    }

    public function createFlow(Request $request)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $type = (string) $request->query('type');
        $payload = $this->calendarEventUtil->buildCreateFlowResponse($type, $businessId, $request->user());

        if (empty($payload)) {
            abort(403, __('messages.unauthorized_action'));
        }

        return response()->json($payload);
    }

    public function storeSchedule(StoreCalendarScheduleRequest $request)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $user = $request->user();

        CalendarSchedule::create(
            $this->calendarEventUtil->prepareSchedulePayload(
                $request->validated(),
                $businessId,
                $user,
                $this->calendarEventUtil->canManageAllSchedules($businessId, $user)
            )
        );

        return response()->json([
            'success' => true,
            'msg' => __('lang_v1.added_success'),
        ]);
    }

    public function updateSchedule(UpdateCalendarScheduleRequest $request, int $id)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $user = $request->user();
        $schedule = $this->findScheduleOrFail($id, $businessId);

        if (! $this->calendarEventUtil->canEditSchedule($schedule, $businessId, $user)) {
            abort(403, __('messages.unauthorized_action'));
        }

        $schedule->update(
            $this->calendarEventUtil->prepareSchedulePayload(
                $request->validated(),
                $businessId,
                $user,
                $this->calendarEventUtil->canManageAllSchedules($businessId, $user)
            )
        );

        return response()->json([
            'success' => true,
            'msg' => __('lang_v1.updated_success'),
        ]);
    }

    public function destroySchedule(Request $request, int $id)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $user = $request->user();
        $schedule = $this->findScheduleOrFail($id, $businessId);

        if (! $this->calendarEventUtil->canEditSchedule($schedule, $businessId, $user)) {
            abort(403, __('messages.unauthorized_action'));
        }

        $schedule->delete();

        return response()->json([
            'success' => true,
            'msg' => __('lang_v1.deleted_success'),
        ]);
    }

    protected function findScheduleOrFail(int $id, int $businessId): CalendarSchedule
    {
        return CalendarSchedule::where('business_id', $businessId)->findOrFail($id);
    }
}
