<?php

namespace Modules\ProjectX\Utils;

use App\Media;
use App\User;
use App\Utils\LockScreenUtil;
use App\Utils\ModuleUtil;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Modules\Essentials\Entities\EssentialsAttendance;
use Modules\Essentials\Entities\EssentialsLeave;
use Modules\Essentials\Entities\EssentialsLeaveType;
use Modules\ProjectX\Entities\UserAttendanceOverride;
use Modules\ProjectX\Entities\UserDailyTask;

class UserProfileUtil
{
    protected const HEATMAP_START_HOUR = 9;
    protected const HEATMAP_END_HOUR = 17;

    protected ModuleUtil $moduleUtil;
    protected LockScreenUtil $lockScreenUtil;

    public function __construct(ModuleUtil $moduleUtil, LockScreenUtil $lockScreenUtil)
    {
        $this->moduleUtil = $moduleUtil;
        $this->lockScreenUtil = $lockScreenUtil;
    }

    public function resolveTargetUser(int $business_id, int $auth_user_id, ?int $requested_user_id = null): User
    {
        $target_user_id = ! empty($requested_user_id) ? (int) $requested_user_id : $auth_user_id;

        return User::where('business_id', $business_id)->findOrFail($target_user_id);
    }

    public function updateRootProfile(User $user, array $input, Request $request): User
    {
        if (! empty($input['dob'])) {
            $input['dob'] = $this->moduleUtil->uf_date($input['dob']);
        }

        if (isset($input['bank_details']) && is_array($input['bank_details'])) {
            $input['bank_details'] = json_encode($input['bank_details']);
        }

        $user->update($input);

        Media::uploadMedia($user->business_id, $user, $request, 'profile_photo', true);

        if ((int) $request->session()->get('user.id') === (int) $user->id) {
            $this->syncUserSession($request, $user);
        }

        return $user->fresh(['media']);
    }

    public function updatePassword(User $user, string $current_password, string $new_password): bool
    {
        if (! Hash::check($current_password, (string) $user->password)) {
            return false;
        }

        $user->password = Hash::make($new_password);
        $user->save();

        return true;
    }

    public function updateLockScreenPin(User $user, string $pin): void
    {
        $this->lockScreenUtil->storePin($user, $pin);
    }

    public function getProfilePayload(User $user): array
    {
        $role_name_array = $user->getRoleNames();
        $role_label = ! empty($role_name_array[0]) ? explode('#', $role_name_array[0])[0] : __('projectx::lang.not_set');

        return [
            'id' => (int) $user->id,
            'full_name' => trim((string) $user->user_full_name),
            'role_label' => $role_label,
            'staff_code' => (string) ($user->username ?: '-'),
            'phone' => (string) ($user->contact_number ?: '-'),
            'email' => (string) ($user->email ?: '-'),
            'address' => (string) ($user->current_address ?: ($user->permanent_address ?: '-')),
            'avatar_url' => (string) $user->image_url,
        ];
    }

    public function getLeaveSummary(int $business_id, int $user_id, Carbon $selected_date): array
    {
        if (! $this->isEssentialsAvailable()) {
            return [
                'annual_total' => 0,
                'taken' => 0,
                'remaining' => 0,
                'is_available' => false,
            ];
        }

        $year_start = $selected_date->copy()->startOfYear()->toDateString();
        $year_end = $selected_date->copy()->endOfYear()->toDateString();

        $annual_total = (int) EssentialsLeaveType::where('business_id', $business_id)
            ->whereNotNull('max_leave_count')
            ->sum('max_leave_count');

        $approved_leaves = EssentialsLeave::where('business_id', $business_id)
            ->where('user_id', $user_id)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $year_end)
            ->whereDate('end_date', '>=', $year_start)
            ->get(['start_date', 'end_date']);

        $taken = 0;
        foreach ($approved_leaves as $leave) {
            $start = Carbon::parse($leave->start_date)->max(Carbon::parse($year_start));
            $end = Carbon::parse($leave->end_date)->min(Carbon::parse($year_end));

            if ($end->greaterThanOrEqualTo($start)) {
                $taken += $start->diffInDays($end) + 1;
            }
        }

        $remaining = max($annual_total - $taken, 0);

        return [
            'annual_total' => $annual_total,
            'taken' => $taken,
            'remaining' => $remaining,
            'is_available' => true,
        ];
    }

    public function getTodayAttendanceSummary(int $business_id, int $user_id, Carbon $today): array
    {
        if (! $this->isEssentialsAvailable()) {
            return [
                'start_work' => '--',
                'end_work' => '--',
                'duration_human' => '0m',
                'is_clocked_in' => false,
                'is_available' => false,
            ];
        }

        $entries = EssentialsAttendance::where('business_id', $business_id)
            ->where('user_id', $user_id)
            ->whereDate('clock_in_time', $today->toDateString())
            ->orderBy('clock_in_time')
            ->get(['clock_in_time', 'clock_out_time']);

        if ($entries->isEmpty()) {
            return [
                'start_work' => '--',
                'end_work' => '--',
                'duration_human' => '0m',
                'is_clocked_in' => false,
                'is_available' => true,
            ];
        }

        $first_clock_in = Carbon::parse($entries->first()->clock_in_time);
        $last_clock_out = null;
        $duration_seconds = 0;
        $is_clocked_in = false;

        foreach ($entries as $entry) {
            $start = Carbon::parse($entry->clock_in_time);
            $end = ! empty($entry->clock_out_time) ? Carbon::parse($entry->clock_out_time) : Carbon::now();
            if (empty($entry->clock_out_time)) {
                $is_clocked_in = true;
            }

            if (! empty($entry->clock_out_time)) {
                $parsed_end = Carbon::parse($entry->clock_out_time);
                if (is_null($last_clock_out) || $parsed_end->greaterThan($last_clock_out)) {
                    $last_clock_out = $parsed_end;
                }
            }

            if ($end->greaterThan($start)) {
                $duration_seconds += $start->diffInSeconds($end);
            }
        }

        return [
            'start_work' => $first_clock_in->format('h.i A'),
            'end_work' => ! is_null($last_clock_out) ? $last_clock_out->format('h.i A') : '--',
            'duration_human' => $this->formatDuration($duration_seconds),
            'is_clocked_in' => $is_clocked_in,
            'is_available' => true,
        ];
    }

    public function getDailyTasks(int $business_id, int $user_id, Carbon $task_date): array
    {
        $tasks = UserDailyTask::forBusiness($business_id)
            ->where('user_id', $user_id)
            ->whereDate('task_date', $task_date->toDateString())
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return [
            'items' => $tasks->map(function (UserDailyTask $task) {
                return [
                    'id' => (int) $task->id,
                    'title' => (string) $task->title,
                    'is_completed' => (bool) $task->is_completed,
                    'completed_at' => ! empty($task->completed_at) ? $task->completed_at->toDateTimeString() : null,
                    'sort_order' => (int) $task->sort_order,
                ];
            })->values()->all(),
            'total_count' => $tasks->count(),
            'completed_count' => $tasks->where('is_completed', true)->count(),
        ];
    }

    public function createDailyTask(int $business_id, int $user_id, Carbon $task_date, string $title, int $actor_id): UserDailyTask
    {
        $max_sort = (int) UserDailyTask::forBusiness($business_id)
            ->where('user_id', $user_id)
            ->whereDate('task_date', $task_date->toDateString())
            ->max('sort_order');

        return UserDailyTask::create([
            'business_id' => $business_id,
            'user_id' => $user_id,
            'task_date' => $task_date->toDateString(),
            'title' => $title,
            'is_completed' => false,
            'sort_order' => $max_sort + 1,
            'created_by' => $actor_id,
            'updated_by' => $actor_id,
        ]);
    }

    public function updateDailyTask(UserDailyTask $task, array $data, int $actor_id): UserDailyTask
    {
        if (array_key_exists('title', $data) && ! is_null($data['title'])) {
            $task->title = $data['title'];
        }

        if (array_key_exists('task_date', $data) && ! empty($data['task_date'])) {
            $task->task_date = Carbon::parse($data['task_date'])->toDateString();
        }

        if (array_key_exists('sort_order', $data) && ! is_null($data['sort_order'])) {
            $task->sort_order = (int) $data['sort_order'];
        }

        if (array_key_exists('is_completed', $data) && ! is_null($data['is_completed'])) {
            $task->is_completed = (bool) $data['is_completed'];
            $task->completed_at = $task->is_completed ? Carbon::now() : null;
        }

        $task->updated_by = $actor_id;
        $task->save();

        return $task;
    }

    public function canMutateTask(UserDailyTask $task, int $auth_user_id, bool $can_update_other): bool
    {
        return $task->user_id === $auth_user_id || $can_update_other;
    }

    public function findTaskForBusiness(int $business_id, int $task_id): UserDailyTask
    {
        return UserDailyTask::forBusiness($business_id)->findOrFail($task_id);
    }

    public function upsertAttendanceOverride(int $business_id, int $user_id, array $data, int $actor_id): UserAttendanceOverride
    {
        return UserAttendanceOverride::updateOrCreate(
            [
                'business_id' => $business_id,
                'user_id' => $user_id,
                'work_date' => Carbon::parse($data['work_date'])->toDateString(),
                'hour_slot' => (int) $data['hour_slot'],
            ],
            [
                'status' => (string) $data['status'],
                'note' => $data['note'] ?? null,
                'created_by' => $actor_id,
                'updated_by' => $actor_id,
            ]
        );
    }

    public function deleteAttendanceOverride(int $business_id, int $user_id, string $work_date, int $hour_slot): int
    {
        return UserAttendanceOverride::forBusiness($business_id)
            ->where('user_id', $user_id)
            ->whereDate('work_date', Carbon::parse($work_date)->toDateString())
            ->where('hour_slot', $hour_slot)
            ->delete();
    }

    public function getWeeklyHeatmap(int $business_id, int $user_id, Carbon $selected_date): array
    {
        $week_start = $selected_date->copy()->startOfWeek(Carbon::MONDAY)->startOfDay();
        $week_end = $selected_date->copy()->endOfWeek(Carbon::SUNDAY)->endOfDay();

        $hours = range(self::HEATMAP_START_HOUR, self::HEATMAP_END_HOUR);
        $days = [];
        $cells = [];

        for ($day = $week_start->copy(); $day->lessThanOrEqualTo($week_end); $day->addDay()) {
            $day_key = $day->toDateString();
            $days[] = [
                'date' => $day_key,
                'day_label' => $day->format('D'),
                'date_label' => $day->format('d M'),
            ];

            $cells[$day_key] = [];
            foreach ($hours as $hour) {
                $cells[$day_key][$hour] = [
                    'date' => $day_key,
                    'hour_slot' => $hour,
                    'status' => UserAttendanceOverride::STATUS_NOT_PRESENT,
                    'is_overridden' => false,
                    'note' => null,
                ];
            }
        }

        if ($this->isEssentialsAvailable()) {
            $this->applyLeaveStatuses($cells, $business_id, $user_id, $week_start, $week_end, $hours);
            $this->applyAttendanceStatuses($cells, $business_id, $user_id, $week_start, $week_end, $hours);
        }

        $overrides = UserAttendanceOverride::forBusiness($business_id)
            ->where('user_id', $user_id)
            ->whereDate('work_date', '>=', $week_start->toDateString())
            ->whereDate('work_date', '<=', $week_end->toDateString())
            ->get();

        foreach ($overrides as $override) {
            $day_key = Carbon::parse($override->work_date)->toDateString();
            $hour = (int) $override->hour_slot;

            if (! isset($cells[$day_key][$hour])) {
                continue;
            }

            $cells[$day_key][$hour]['status'] = $override->status;
            $cells[$day_key][$hour]['is_overridden'] = true;
            $cells[$day_key][$hour]['note'] = $override->note;
        }

        return [
            'days' => $days,
            'hours' => array_map(function (int $hour) {
                return [
                    'hour_slot' => $hour,
                    'label' => sprintf('%02d.00', $hour),
                ];
            }, $hours),
            'cells' => $cells,
            'start_date' => $week_start->toDateString(),
            'end_date' => $week_end->toDateString(),
            'is_available' => $this->isEssentialsAvailable(),
        ];
    }

    public function getHeatmapStatusMeta(): array
    {
        return [
            UserAttendanceOverride::STATUS_PRESENT => [
                'label' => __('projectx::lang.attendance_present'),
                'cell_class' => 'bg-success',
                'legend_class' => 'bg-success',
            ],
            UserAttendanceOverride::STATUS_BREAK => [
                'label' => __('projectx::lang.attendance_break'),
                'cell_class' => 'bg-primary',
                'legend_class' => 'bg-primary',
            ],
            UserAttendanceOverride::STATUS_LATE => [
                'label' => __('projectx::lang.attendance_late'),
                'cell_class' => 'bg-danger',
                'legend_class' => 'bg-danger',
            ],
            UserAttendanceOverride::STATUS_PERMISSION => [
                'label' => __('projectx::lang.attendance_permission'),
                'cell_class' => 'bg-warning',
                'legend_class' => 'bg-warning',
            ],
            UserAttendanceOverride::STATUS_NOT_PRESENT => [
                'label' => __('projectx::lang.attendance_not_present'),
                'cell_class' => 'bg-light-secondary',
                'legend_class' => 'bg-light-secondary',
            ],
        ];
    }

    public function getUsersForAdminFilter(int $business_id): array
    {
        $users = User::forDropdown($business_id, false);

        return collect($users)->map(function ($label, $id) {
            return [
                'id' => (int) $id,
                'name' => (string) $label,
            ];
        })->values()->all();
    }

    public function getLeaveTypes(int $business_id): array
    {
        if (! $this->isEssentialsAvailable()) {
            return [];
        }

        $leave_types = EssentialsLeaveType::forDropdown($business_id);

        return collect($leave_types)->map(function ($label, $id) {
            return [
                'id' => (int) $id,
                'name' => (string) $label,
            ];
        })->values()->all();
    }

    public function isEssentialsAvailableForBusiness(int $business_id): bool
    {
        if (! $this->isEssentialsAvailable()) {
            return false;
        }

        if (auth()->check() && auth()->user()->can('superadmin')) {
            return true;
        }

        return (bool) $this->moduleUtil->hasThePermissionInSubscription($business_id, 'essentials_module');
    }

    protected function applyLeaveStatuses(array &$cells, int $business_id, int $user_id, Carbon $week_start, Carbon $week_end, array $hours): void
    {
        $leaves = EssentialsLeave::where('business_id', $business_id)
            ->where('user_id', $user_id)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $week_end->toDateString())
            ->whereDate('end_date', '>=', $week_start->toDateString())
            ->get(['start_date', 'end_date']);

        foreach ($leaves as $leave) {
            $start = Carbon::parse($leave->start_date)->max($week_start);
            $end = Carbon::parse($leave->end_date)->min($week_end);

            for ($day = $start->copy()->startOfDay(); $day->lessThanOrEqualTo($end->copy()->startOfDay()); $day->addDay()) {
                $day_key = $day->toDateString();

                if (! isset($cells[$day_key])) {
                    continue;
                }

                foreach ($hours as $hour) {
                    if (! isset($cells[$day_key][$hour])) {
                        continue;
                    }

                    $cells[$day_key][$hour]['status'] = UserAttendanceOverride::STATUS_PERMISSION;
                }
            }
        }
    }

    protected function applyAttendanceStatuses(array &$cells, int $business_id, int $user_id, Carbon $week_start, Carbon $week_end, array $hours): void
    {
        $attendances = EssentialsAttendance::where('business_id', $business_id)
            ->where('user_id', $user_id)
            ->where('clock_in_time', '<=', $week_end->toDateTimeString())
            ->where(function ($query) use ($week_start) {
                $query->whereNull('clock_out_time')
                    ->orWhere('clock_out_time', '>=', $week_start->toDateTimeString());
            })
            ->orderBy('clock_in_time')
            ->get(['clock_in_time', 'clock_out_time']);

        $first_clock_in_by_day = [];
        $intervals_by_day = [];

        foreach ($attendances as $attendance) {
            $interval_start = Carbon::parse($attendance->clock_in_time)->max($week_start);
            $interval_end = ! empty($attendance->clock_out_time)
                ? Carbon::parse($attendance->clock_out_time)->min($week_end)
                : Carbon::now()->min($week_end);

            if ($interval_end->lessThanOrEqualTo($interval_start)) {
                continue;
            }

            for ($day = $interval_start->copy()->startOfDay(); $day->lessThanOrEqualTo($interval_end->copy()->startOfDay()); $day->addDay()) {
                $day_key = $day->toDateString();
                if (! isset($cells[$day_key])) {
                    continue;
                }

                $day_start = $day->copy()->startOfDay();
                $day_end = $day->copy()->endOfDay();
                $effective_start = $interval_start->copy()->max($day_start);
                $effective_end = $interval_end->copy()->min($day_end);

                if ($effective_end->lessThanOrEqualTo($effective_start)) {
                    continue;
                }

                if (! isset($first_clock_in_by_day[$day_key]) || $effective_start->lessThan($first_clock_in_by_day[$day_key])) {
                    $first_clock_in_by_day[$day_key] = $effective_start->copy();
                }

                $intervals_by_day[$day_key][] = [
                    'start' => $effective_start->copy(),
                    'end' => $effective_end->copy(),
                ];

                foreach ($hours as $hour) {
                    $slot_start = $day->copy()->setTime($hour, 0, 0);
                    $slot_end = $slot_start->copy()->addHour();

                    if ($effective_end->greaterThan($slot_start) && $effective_start->lessThan($slot_end)) {
                        $cells[$day_key][$hour]['status'] = UserAttendanceOverride::STATUS_PRESENT;
                    }
                }
            }
        }

        foreach ($first_clock_in_by_day as $day_key => $first_clock_in) {
            if ($first_clock_in->hour > self::HEATMAP_START_HOUR || ($first_clock_in->hour === self::HEATMAP_START_HOUR && $first_clock_in->minute > 0)) {
                if (isset($cells[$day_key][self::HEATMAP_START_HOUR])
                    && $cells[$day_key][self::HEATMAP_START_HOUR]['status'] !== UserAttendanceOverride::STATUS_PRESENT) {
                    $cells[$day_key][self::HEATMAP_START_HOUR]['status'] = UserAttendanceOverride::STATUS_LATE;
                }
            }
        }

        foreach ($intervals_by_day as $day_key => $intervals) {
            usort($intervals, function (array $left, array $right) {
                if ($left['start']->equalTo($right['start'])) {
                    return 0;
                }

                return $left['start']->lessThan($right['start']) ? -1 : 1;
            });

            for ($index = 0; $index < count($intervals) - 1; $index++) {
                $gap_start = $intervals[$index]['end']->copy();
                $gap_end = $intervals[$index + 1]['start']->copy();

                if ($gap_end->lessThanOrEqualTo($gap_start)) {
                    continue;
                }

                foreach ($hours as $hour) {
                    if (! isset($cells[$day_key][$hour])) {
                        continue;
                    }

                    $slot_start = Carbon::parse($day_key)->setTime($hour, 0, 0);
                    $slot_end = $slot_start->copy()->addHour();
                    if ($gap_end->greaterThan($slot_start)
                        && $gap_start->lessThan($slot_end)
                        && $cells[$day_key][$hour]['status'] === UserAttendanceOverride::STATUS_NOT_PRESENT) {
                        $cells[$day_key][$hour]['status'] = UserAttendanceOverride::STATUS_BREAK;
                    }
                }
            }
        }
    }

    protected function isEssentialsAvailable(): bool
    {
        if (! $this->moduleUtil->isModuleInstalled('Essentials')) {
            return false;
        }

        return class_exists(EssentialsLeave::class)
            && class_exists(EssentialsAttendance::class)
            && class_exists(EssentialsLeaveType::class);
    }

    protected function formatDuration(int $duration_seconds): string
    {
        $hours = intdiv($duration_seconds, 3600);
        $minutes = intdiv($duration_seconds % 3600, 60);

        if ($hours <= 0) {
            return $minutes . 'm';
        }

        return $hours . 'h ' . str_pad((string) $minutes, 2, '0', STR_PAD_LEFT) . 'm';
    }

    protected function syncUserSession(Request $request, User $user): void
    {
        $session_user = (array) $request->session()->get('user', []);

        $session_user['id'] = (int) $user->id;
        $session_user['business_id'] = (int) $user->business_id;
        $session_user['surname'] = (string) $user->surname;
        $session_user['first_name'] = (string) $user->first_name;
        $session_user['last_name'] = (string) $user->last_name;
        $session_user['email'] = (string) $user->email;
        $session_user['language'] = (string) $user->language;
        $session_user['contact_number'] = (string) $user->contact_number;

        $request->session()->put('user', $session_user);
    }
}
