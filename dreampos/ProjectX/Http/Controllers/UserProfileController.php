<?php

namespace Modules\ProjectX\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Modules\ProjectX\Http\Requests\DestroyProjectxAttendanceOverrideRequest;
use Modules\ProjectX\Http\Requests\StoreProjectxUserDailyTaskRequest;
use Modules\ProjectX\Http\Requests\UpdateProjectxUserDailyTaskRequest;
use Modules\ProjectX\Http\Requests\UpdateProjectxUserLockScreenPinRequest;
use Modules\ProjectX\Http\Requests\UpdateProjectxUserPasswordRequest;
use Modules\ProjectX\Http\Requests\UpdateProjectxUserProfileRequest;
use Modules\ProjectX\Http\Requests\UpsertProjectxAttendanceOverrideRequest;
use Modules\ProjectX\Utils\UserProfileUtil;

class UserProfileController extends Controller
{
    protected UserProfileUtil $userProfileUtil;

    public function __construct(UserProfileUtil $userProfileUtil)
    {
        $this->userProfileUtil = $userProfileUtil;
    }

    public function index(Request $request)
    {
        $business_id = (int) $request->session()->get('user.business_id');
        $auth_user_id = (int) auth()->id();
        $requested_user_id = (int) $request->query('user_id', 0);

        $target_user = $this->userProfileUtil->resolveTargetUser($business_id, $auth_user_id, $requested_user_id ?: null);
        $this->authorizeUserView($target_user->id, $auth_user_id);

        $selected_date = $request->filled('date')
            ? Carbon::parse((string) $request->query('date'))
            : Carbon::today();

        $task_date = $request->filled('task_date')
            ? Carbon::parse((string) $request->query('task_date'))
            : Carbon::today();

        $profile = $this->userProfileUtil->getProfilePayload($target_user);
        $leave = $this->userProfileUtil->getLeaveSummary($business_id, (int) $target_user->id, $selected_date);
        $attendance_today = $this->userProfileUtil->getTodayAttendanceSummary($business_id, (int) $target_user->id, Carbon::today());
        $daily_tasks = $this->userProfileUtil->getDailyTasks($business_id, (int) $target_user->id, $task_date);
        $heatmap = $this->userProfileUtil->getWeeklyHeatmap($business_id, (int) $target_user->id, $selected_date);
        $heatmap_status_meta = $this->userProfileUtil->getHeatmapStatusMeta();
        $leave_types = $this->userProfileUtil->getLeaveTypes($business_id);
        $config_languages = config('constants.langs', []);
        $languages = [];
        foreach ($config_languages as $key => $value) {
            $languages[$key] = is_array($value) ? ($value['full_name'] ?? $key) : (string) $value;
        }

        $users_for_admin_filter = auth()->user()->can('user.view')
            ? $this->userProfileUtil->getUsersForAdminFilter($business_id)
            : [];
        $essentials_available = $this->userProfileUtil->isEssentialsAvailableForBusiness($business_id);

        return view('projectx::user-profile.index', [
            'targetUser' => $target_user,
            'profile' => $profile,
            'leave' => $leave,
            'attendance_today' => $attendance_today,
            'daily_tasks' => $daily_tasks,
            'heatmap' => $heatmap,
            'heatmap_status_meta' => $heatmap_status_meta,
            'users_for_admin_filter' => $users_for_admin_filter,
            'leave_types' => $leave_types,
            'selected_date' => $selected_date->toDateString(),
            'task_date' => $task_date->toDateString(),
            'today_label' => Carbon::today()->format('D, d M Y'),
            'is_self_profile' => (int) $target_user->id === $auth_user_id,
            'can_edit_others' => auth()->user()->can('user.update'),
            'languages' => $languages,
            'essentials_available' => $essentials_available,
        ]);
    }

    public function update(UpdateProjectxUserProfileRequest $request)
    {
        $business_id = (int) $request->session()->get('user.business_id');
        $auth_user_id = (int) auth()->id();

        $validated = $request->validated();
        $target_user = $this->userProfileUtil->resolveTargetUser(
            $business_id,
            $auth_user_id,
            ! empty($validated['user_id']) ? (int) $validated['user_id'] : null
        );

        $this->authorizeUserUpdate($target_user->id, $auth_user_id);

        $input = $validated;
        unset($input['user_id'], $input['profile_photo']);

        try {
            $this->userProfileUtil->updateRootProfile($target_user, $input, $request);

            return redirect()->back()->with('status', [
                'success' => true,
                'msg' => __('lang_v1.profile_updated_successfully'),
            ]);
        } catch (\Exception $exception) {
            \Log::emergency('File:'.$exception->getFile().' Line:'.$exception->getLine().' Message:'.$exception->getMessage());

            return redirect()->back()->withInput()->with('status', [
                'success' => false,
                'msg' => __('messages.something_went_wrong'),
            ]);
        }
    }

    public function updatePassword(UpdateProjectxUserPasswordRequest $request)
    {
        $business_id = (int) $request->session()->get('user.business_id');
        $auth_user_id = (int) auth()->id();

        $validated = $request->validated();
        $target_user = $this->userProfileUtil->resolveTargetUser(
            $business_id,
            $auth_user_id,
            ! empty($validated['user_id']) ? (int) $validated['user_id'] : null
        );

        $this->authorizeUserUpdate($target_user->id, $auth_user_id);

        try {
            $updated = $this->userProfileUtil->updatePassword(
                $target_user,
                (string) $validated['current_password'],
                (string) $validated['new_password']
            );

            return redirect()->back()->with('status', [
                'success' => $updated,
                'msg' => $updated
                    ? __('lang_v1.password_updated_successfully')
                    : __('lang_v1.u_have_entered_wrong_password'),
            ]);
        } catch (\Exception $exception) {
            \Log::emergency('File:'.$exception->getFile().' Line:'.$exception->getLine().' Message:'.$exception->getMessage());

            return redirect()->back()->withInput()->with('status', [
                'success' => false,
                'msg' => __('messages.something_went_wrong'),
            ]);
        }
    }

    public function updateLockScreenPin(UpdateProjectxUserLockScreenPinRequest $request)
    {
        $business_id = (int) $request->session()->get('user.business_id');
        $auth_user_id = (int) auth()->id();

        $validated = $request->validated();
        $target_user = $this->userProfileUtil->resolveTargetUser(
            $business_id,
            $auth_user_id,
            ! empty($validated['user_id']) ? (int) $validated['user_id'] : null
        );

        $this->authorizeUserUpdate($target_user->id, $auth_user_id);

        try {
            $this->userProfileUtil->updateLockScreenPin($target_user, (string) $validated['lock_screen_pin']);

            return redirect()->back()->with('status', [
                'success' => true,
                'msg' => __('lang_v1.lock_screen_pin_updated_successfully'),
            ]);
        } catch (\Exception $exception) {
            \Log::emergency('File:'.$exception->getFile().' Line:'.$exception->getLine().' Message:'.$exception->getMessage());

            return redirect()->back()->withInput()->with('status', [
                'success' => false,
                'msg' => __('messages.something_went_wrong'),
            ]);
        }
    }

    public function storeTask(StoreProjectxUserDailyTaskRequest $request)
    {
        $business_id = (int) $request->session()->get('user.business_id');
        $auth_user_id = (int) auth()->id();
        $validated = $request->validated();

        $target_user = $this->userProfileUtil->resolveTargetUser(
            $business_id,
            $auth_user_id,
            ! empty($validated['user_id']) ? (int) $validated['user_id'] : null
        );

        $this->authorizeUserUpdate($target_user->id, $auth_user_id);

        try {
            $task_date = ! empty($validated['task_date'])
                ? Carbon::parse((string) $validated['task_date'])
                : Carbon::today();

            $task = $this->userProfileUtil->createDailyTask(
                $business_id,
                (int) $target_user->id,
                $task_date,
                (string) $validated['title'],
                $auth_user_id
            );

            if ($request->expectsJson()) {
                return $this->respondSuccess(__('projectx::lang.task_created_successfully'), [
                    'data' => ['id' => (int) $task->id],
                ]);
            }

            return redirect()->back()->with('status', [
                'success' => true,
                'msg' => __('projectx::lang.task_created_successfully'),
            ]);
        } catch (\Exception $exception) {
            \Log::emergency('File:'.$exception->getFile().' Line:'.$exception->getLine().' Message:'.$exception->getMessage());

            if ($request->expectsJson()) {
                return $this->respondWentWrong($exception);
            }

            return redirect()->back()->withInput()->with('status', [
                'success' => false,
                'msg' => __('messages.something_went_wrong'),
            ]);
        }
    }

    public function updateTask(UpdateProjectxUserDailyTaskRequest $request, int $task_id)
    {
        $business_id = (int) $request->session()->get('user.business_id');
        $auth_user_id = (int) auth()->id();
        $can_update_others = auth()->user()->can('user.update');

        $task = $this->userProfileUtil->findTaskForBusiness($business_id, $task_id);
        if (! $this->userProfileUtil->canMutateTask($task, $auth_user_id, $can_update_others)) {
            return $this->unauthorizedResponse($request);
        }

        try {
            $validated = $request->validated();
            unset($validated['user_id']);

            $this->userProfileUtil->updateDailyTask($task, $validated, $auth_user_id);

            if ($request->expectsJson()) {
                return $this->respondSuccess(__('lang_v1.updated_success'));
            }

            return redirect()->back()->with('status', [
                'success' => true,
                'msg' => __('lang_v1.updated_success'),
            ]);
        } catch (\Exception $exception) {
            \Log::emergency('File:'.$exception->getFile().' Line:'.$exception->getLine().' Message:'.$exception->getMessage());

            if ($request->expectsJson()) {
                return $this->respondWentWrong($exception);
            }

            return redirect()->back()->withInput()->with('status', [
                'success' => false,
                'msg' => __('messages.something_went_wrong'),
            ]);
        }
    }

    public function destroyTask(Request $request, int $task_id)
    {
        $business_id = (int) $request->session()->get('user.business_id');
        $auth_user_id = (int) auth()->id();
        $can_update_others = auth()->user()->can('user.update');

        $task = $this->userProfileUtil->findTaskForBusiness($business_id, $task_id);
        if (! $this->userProfileUtil->canMutateTask($task, $auth_user_id, $can_update_others)) {
            return $this->unauthorizedResponse($request);
        }

        try {
            $task->delete();

            if ($request->expectsJson()) {
                return $this->respondSuccess(__('lang_v1.deleted_success'));
            }

            return redirect()->back()->with('status', [
                'success' => true,
                'msg' => __('lang_v1.deleted_success'),
            ]);
        } catch (\Exception $exception) {
            \Log::emergency('File:'.$exception->getFile().' Line:'.$exception->getLine().' Message:'.$exception->getMessage());

            if ($request->expectsJson()) {
                return $this->respondWentWrong($exception);
            }

            return redirect()->back()->with('status', [
                'success' => false,
                'msg' => __('messages.something_went_wrong'),
            ]);
        }
    }

    public function upsertHeatmapOverride(UpsertProjectxAttendanceOverrideRequest $request)
    {
        $business_id = (int) $request->session()->get('user.business_id');
        $auth_user_id = (int) auth()->id();
        $validated = $request->validated();

        $target_user = $this->userProfileUtil->resolveTargetUser(
            $business_id,
            $auth_user_id,
            ! empty($validated['user_id']) ? (int) $validated['user_id'] : null
        );

        $this->authorizeUserUpdate($target_user->id, $auth_user_id);

        try {
            $override = $this->userProfileUtil->upsertAttendanceOverride(
                $business_id,
                (int) $target_user->id,
                $validated,
                $auth_user_id
            );

            return $this->respondSuccess(__('lang_v1.updated_success'), [
                'data' => ['id' => (int) $override->id],
            ]);
        } catch (\Exception $exception) {
            \Log::emergency('File:'.$exception->getFile().' Line:'.$exception->getLine().' Message:'.$exception->getMessage());

            return $this->respondWentWrong($exception);
        }
    }

    public function destroyHeatmapOverride(DestroyProjectxAttendanceOverrideRequest $request)
    {
        $business_id = (int) $request->session()->get('user.business_id');
        $auth_user_id = (int) auth()->id();
        $validated = $request->validated();

        $target_user = $this->userProfileUtil->resolveTargetUser(
            $business_id,
            $auth_user_id,
            ! empty($validated['user_id']) ? (int) $validated['user_id'] : null
        );

        $this->authorizeUserUpdate($target_user->id, $auth_user_id);

        try {
            $this->userProfileUtil->deleteAttendanceOverride(
                $business_id,
                (int) $target_user->id,
                (string) $validated['work_date'],
                (int) $validated['hour_slot']
            );

            return $this->respondSuccess(__('lang_v1.deleted_success'));
        } catch (\Exception $exception) {
            \Log::emergency('File:'.$exception->getFile().' Line:'.$exception->getLine().' Message:'.$exception->getMessage());

            return $this->respondWentWrong($exception);
        }
    }

    protected function authorizeUserView(int $target_user_id, int $auth_user_id): void
    {
        if ($target_user_id !== $auth_user_id && ! auth()->user()->can('user.view')) {
            abort(403, __('messages.unauthorized_action'));
        }
    }

    protected function authorizeUserUpdate(int $target_user_id, int $auth_user_id): void
    {
        if ($target_user_id !== $auth_user_id && ! auth()->user()->can('user.update')) {
            abort(403, __('messages.unauthorized_action'));
        }
    }

    protected function unauthorizedResponse(Request $request)
    {
        if ($request->expectsJson()) {
            return $this->respondUnauthorized(__('messages.unauthorized_action'));
        }

        abort(403, __('messages.unauthorized_action'));
    }
}
