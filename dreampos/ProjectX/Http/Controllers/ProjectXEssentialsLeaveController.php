<?php

namespace Modules\ProjectX\Http\Controllers;

use App\Business;
use App\Http\Controllers\Controller;
use App\User;
use App\Utils\ModuleUtil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Essentials\Entities\EssentialsLeave;
use Modules\Essentials\Entities\EssentialsLeaveType;
use Modules\Essentials\Notifications\LeaveStatusNotification;
use Modules\Essentials\Notifications\NewLeaveNotification;
use Modules\ProjectX\Http\Requests\Essentials\EssentialsHrmLeaveStatusRequest;
use Modules\ProjectX\Http\Requests\Essentials\EssentialsHrmLeaveStoreRequest;
use Modules\ProjectX\Http\Requests\Essentials\EssentialsHrmLeaveUpdateRequest;
use Modules\ProjectX\Utils\ProjectXEssentialsUtil;
use Spatie\Activitylog\Models\Activity;
use Yajra\DataTables\Facades\DataTables;

class ProjectXEssentialsLeaveController extends Controller
{
    protected ModuleUtil $moduleUtil;

    protected ProjectXEssentialsUtil $projectXEssentialsUtil;

    protected array $leave_statuses;

    public function __construct(ModuleUtil $moduleUtil, ProjectXEssentialsUtil $projectXEssentialsUtil)
    {
        $this->moduleUtil = $moduleUtil;
        $this->projectXEssentialsUtil = $projectXEssentialsUtil;

        $this->leave_statuses = [
            'pending' => ['name' => __('lang_v1.pending'), 'class' => 'badge-light-warning'],
            'approved' => ['name' => __('essentials::lang.approved'), 'class' => 'badge-light-success'],
            'cancelled' => ['name' => __('essentials::lang.cancelled'), 'class' => 'badge-light-danger'],
        ];

        $this->middleware(function ($request, $next) {
            $business_id = $this->projectXEssentialsUtil->businessId($request);
            $this->projectXEssentialsUtil->ensureEssentialsAccess($business_id);

            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $business_id = $this->projectXEssentialsUtil->businessId($request);
        $can_crud_all_leave = auth()->user()->can('essentials.crud_all_leave');
        $can_crud_own_leave = auth()->user()->can('essentials.crud_own_leave');

        if (! $can_crud_all_leave && ! $can_crud_own_leave) {
            abort(403, __('messages.unauthorized_action'));
        }

        if ($request->ajax()) {
            $leaves = EssentialsLeave::where('essentials_leaves.business_id', $business_id)
                ->join('users as u', 'u.id', '=', 'essentials_leaves.user_id')
                ->join('essentials_leave_types as lt', 'lt.id', '=', 'essentials_leaves.essentials_leave_type_id')
                ->select([
                    'essentials_leaves.id',
                    DB::raw("CONCAT(COALESCE(u.surname, ''), ' ', COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) as user"),
                    'lt.leave_type',
                    'start_date',
                    'end_date',
                    'ref_no',
                    'essentials_leaves.status',
                    'reason',
                    'status_note',
                ]);

            if ($request->filled('user_id')) {
                $leaves->where('essentials_leaves.user_id', (int) $request->input('user_id'));
            }

            if (! $can_crud_all_leave && $can_crud_own_leave) {
                $leaves->where('essentials_leaves.user_id', (int) auth()->id());
            }

            if ($request->filled('status')) {
                $leaves->where('essentials_leaves.status', (string) $request->input('status'));
            }

            if ($request->filled('leave_type')) {
                $leaves->where('essentials_leaves.essentials_leave_type_id', (int) $request->input('leave_type'));
            }

            if ($request->filled('start_date') && $request->filled('end_date')) {
                $leaves->whereDate('essentials_leaves.start_date', '>=', (string) $request->input('start_date'))
                    ->whereDate('essentials_leaves.start_date', '<=', (string) $request->input('end_date'));
            }

            return DataTables::of($leaves)
                ->addColumn('action', function ($row) {
                    $actions = [];
                    if (auth()->user()->can('essentials.crud_all_leave')) {
                        $actions[] = '<a href="#" data-id="' . e((string) $row->id) . '" class="dropdown-item projectx-delete-leave">' . e(__('messages.delete')) . '</a>';
                    }
                    $actions[] = '<a href="' . route('projectx.essentials.hrm.leave.activity', ['id' => $row->id]) . '" class="dropdown-item">' . e(__('essentials::lang.activity')) . '</a>';

                    return '<div class="dropdown">'
                        . '<button class="btn btn-sm btn-light btn-active-light-primary" data-bs-toggle="dropdown" type="button">' . e(__('messages.actions')) . '</button>'
                        . '<div class="dropdown-menu dropdown-menu-end">' . implode('', $actions) . '</div>'
                        . '</div>';
                })
                ->editColumn('start_date', function ($row) {
                    $start_date = \Carbon\Carbon::parse($row->start_date);
                    $end_date = \Carbon\Carbon::parse($row->end_date);
                    $diff = $start_date->diffInDays($end_date) + 1;

                    return $this->moduleUtil->format_date($start_date)
                        . ' - '
                        . $this->moduleUtil->format_date($end_date)
                        . ' (' . $diff . ' ' . \Str::plural(__('lang_v1.day'), $diff) . ')';
                })
                ->editColumn('status', function ($row) {
                    $status_meta = $this->leave_statuses[$row->status] ?? ['name' => $row->status, 'class' => 'badge-light'];
                    $badge = '<span class="badge ' . e($status_meta['class']) . '">' . e($status_meta['name']) . '</span>';

                    if (auth()->user()->can('essentials.crud_all_leave') || auth()->user()->can('essentials.approve_leave')) {
                        return '<a href="#" class="projectx-change-leave-status" data-id="' . e((string) $row->id) . '" data-status="' . e((string) $row->status) . '">' . $badge . '</a>';
                    }

                    return $badge;
                })
                ->filterColumn('user', function ($query, $keyword) {
                    $query->whereRaw("CONCAT(COALESCE(u.surname, ''), ' ', COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) like ?", ["%{$keyword}%"]);
                })
                ->rawColumns(['action', 'status'])
                ->make(true);
        }

        $users = [];
        if ($can_crud_all_leave || auth()->user()->can('essentials.approve_leave')) {
            $users = User::forDropdown($business_id, false);
        }

        $leave_statuses = $this->leave_statuses;
        $leave_types = EssentialsLeaveType::forDropdown($business_id);

        return view('projectx::essentials.hrm.leave.index', compact('leave_statuses', 'users', 'leave_types'));
    }

    public function create(Request $request)
    {
        $business_id = $this->projectXEssentialsUtil->businessId($request);
        $this->authorizeLeaveMutation();

        $leave_types = EssentialsLeaveType::forDropdown($business_id);
        $settings = request()->session()->get('business.essentials_settings');
        $settings = ! empty($settings) ? json_decode($settings, true) : [];
        $instructions = ! empty($settings['leave_instructions']) ? $settings['leave_instructions'] : '';

        $employees = [];
        if (auth()->user()->can('essentials.crud_all_leave')) {
            $employees = User::forDropdown($business_id, false, false, false, true);
        }

        return view('projectx::essentials.hrm.leave.create', compact('leave_types', 'instructions', 'employees'));
    }

    public function store(EssentialsHrmLeaveStoreRequest $request)
    {
        $business_id = $this->projectXEssentialsUtil->businessId($request);
        $this->authorizeLeaveMutation();

        try {
            $input = $request->validated();
            $input['business_id'] = $business_id;
            $input['status'] = 'pending';
            $input['start_date'] = $this->moduleUtil->uf_date((string) $input['start_date']);
            $input['end_date'] = $this->moduleUtil->uf_date((string) $input['end_date']);

            DB::beginTransaction();
            if (auth()->user()->can('essentials.crud_all_leave') && ! empty($input['employees'])) {
                foreach ((array) $input['employees'] as $user_id) {
                    $this->addLeave($input, (int) $user_id);
                }
            } else {
                $this->addLeave($input);
            }
            DB::commit();

            if ($request->expectsJson() || $request->ajax()) {
                return $this->respondSuccess(__('lang_v1.added_success'));
            }

            return redirect()->route('projectx.essentials.hrm.leave.index')
                ->with('status', ['success' => true, 'msg' => __('lang_v1.added_success')]);
        } catch (\Exception $exception) {
            DB::rollBack();
            \Log::emergency('File:' . $exception->getFile() . ' Line:' . $exception->getLine() . ' Message:' . $exception->getMessage());

            if ($request->expectsJson() || $request->ajax()) {
                return $this->respondWentWrong($exception);
            }

            return redirect()->back()->withInput()->with('status', ['success' => false, 'msg' => __('messages.something_went_wrong')]);
        }
    }

    protected function addLeave(array $input, ?int $user_id = null): void
    {
        $input['user_id'] = $user_id ?: (int) request()->session()->get('user.id');

        $ref_count = $this->moduleUtil->setAndGetReferenceCount('leave');
        if (empty($input['ref_no'])) {
            $settings = request()->session()->get('business.essentials_settings');
            $settings = ! empty($settings) ? json_decode($settings, true) : [];
            $prefix = ! empty($settings['leave_ref_no_prefix']) ? $settings['leave_ref_no_prefix'] : '';
            $input['ref_no'] = $this->moduleUtil->generateReferenceNumber('leave', $ref_count, null, $prefix);
        }

        $leave = EssentialsLeave::create($input);
        $admins = $this->moduleUtil->get_admins((int) $input['business_id']);
        \Notification::send($admins, new NewLeaveNotification($leave));
    }

    public function show(Request $request, int $id)
    {
        $business_id = $this->projectXEssentialsUtil->businessId($request);
        $leave = EssentialsLeave::where('business_id', $business_id)
            ->with(['user', 'leave_type'])
            ->findOrFail($id);

        return view('projectx::essentials.hrm.leave.show', compact('leave'));
    }

    public function edit(Request $request, int $id)
    {
        $business_id = $this->projectXEssentialsUtil->businessId($request);
        $this->authorizeLeaveMutation();

        $leave = EssentialsLeave::where('business_id', $business_id)->findOrFail($id);
        $leave_types = EssentialsLeaveType::forDropdown($business_id);
        $employees = auth()->user()->can('essentials.crud_all_leave')
            ? User::forDropdown($business_id, false, false, false, true)
            : [];

        return view('projectx::essentials.hrm.leave.edit', compact('leave', 'leave_types', 'employees'));
    }

    public function update(EssentialsHrmLeaveUpdateRequest $request, int $id)
    {
        $business_id = $this->projectXEssentialsUtil->businessId($request);
        $this->authorizeLeaveMutation();

        try {
            $input = $request->validated();
            if (! empty($input['start_date'])) {
                $input['start_date'] = $this->moduleUtil->uf_date((string) $input['start_date']);
            }
            if (! empty($input['end_date'])) {
                $input['end_date'] = $this->moduleUtil->uf_date((string) $input['end_date']);
            }

            EssentialsLeave::where('business_id', $business_id)
                ->where('id', $id)
                ->update($input);

            if ($request->expectsJson() || $request->ajax()) {
                return $this->respondSuccess(__('lang_v1.updated_success'));
            }

            return redirect()->route('projectx.essentials.hrm.leave.index')
                ->with('status', ['success' => true, 'msg' => __('lang_v1.updated_success')]);
        } catch (\Exception $exception) {
            \Log::emergency('File:' . $exception->getFile() . ' Line:' . $exception->getLine() . ' Message:' . $exception->getMessage());

            if ($request->expectsJson() || $request->ajax()) {
                return $this->respondWentWrong($exception);
            }

            return redirect()->back()->withInput()->with('status', ['success' => false, 'msg' => __('messages.something_went_wrong')]);
        }
    }

    public function destroy(Request $request, int $id)
    {
        $business_id = $this->projectXEssentialsUtil->businessId($request);

        if (! auth()->user()->can('essentials.crud_all_leave')) {
            abort(403, __('messages.unauthorized_action'));
        }

        try {
            EssentialsLeave::where('business_id', $business_id)
                ->where('id', $id)
                ->delete();

            if ($request->expectsJson() || $request->ajax()) {
                return $this->respondSuccess(__('lang_v1.deleted_success'));
            }

            return redirect()->route('projectx.essentials.hrm.leave.index')
                ->with('status', ['success' => true, 'msg' => __('lang_v1.deleted_success')]);
        } catch (\Exception $exception) {
            \Log::emergency('File:' . $exception->getFile() . ' Line:' . $exception->getLine() . ' Message:' . $exception->getMessage());

            if ($request->expectsJson() || $request->ajax()) {
                return $this->respondWentWrong($exception);
            }

            return redirect()->back()->with('status', ['success' => false, 'msg' => __('messages.something_went_wrong')]);
        }
    }

    public function changeStatus(EssentialsHrmLeaveStatusRequest $request)
    {
        $business_id = $this->projectXEssentialsUtil->businessId($request);

        try {
            $input = $request->validated();
            $leave = EssentialsLeave::where('business_id', $business_id)
                ->findOrFail((int) $input['leave_id']);

            $leave->is_additional = $request->boolean('is_additional');
            $leave->status = $input['status'];
            $leave->status_note = $input['status_note'] ?? null;
            $leave->save();

            $notification_leave = clone $leave;
            $notification_leave->status = $this->leave_statuses[$leave->status]['name'] ?? $leave->status;
            $notification_leave->changed_by = (int) auth()->id();
            $leave->user->notify(new LeaveStatusNotification($notification_leave));

            return $this->respondSuccess(__('lang_v1.updated_success'));
        } catch (\Exception $exception) {
            \Log::emergency('File:' . $exception->getFile() . ' Line:' . $exception->getLine() . ' Message:' . $exception->getMessage());

            return $this->respondWentWrong($exception);
        }
    }

    public function activity(Request $request, int $id)
    {
        $business_id = $this->projectXEssentialsUtil->businessId($request);

        $leave = EssentialsLeave::where('business_id', $business_id)->findOrFail($id);
        $activities = Activity::forSubject($leave)
            ->with(['causer', 'subject'])
            ->latest()
            ->get();

        return view('projectx::essentials.hrm.leave.activity', compact('leave', 'activities'));
    }

    public function getUserLeaveSummary(Request $request)
    {
        $business_id = $this->projectXEssentialsUtil->businessId($request);
        $is_admin = $this->moduleUtil->is_admin(auth()->user(), $business_id);
        $user_id = $is_admin ? (int) $request->input('user_id') : (int) auth()->id();

        if (empty($user_id)) {
            return '';
        }

        $query = EssentialsLeave::where('business_id', $business_id)
            ->where('user_id', $user_id)
            ->with(['leave_type'])
            ->select('status', 'essentials_leave_type_id', 'start_date', 'end_date');

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereDate('start_date', '>=', (string) $request->input('start_date'))
                ->whereDate('start_date', '<=', (string) $request->input('end_date'));
        }

        $leaves = $query->get();
        $statuses = $this->leave_statuses;
        $leaves_summary = [];
        $status_summary = array_fill_keys(array_keys($statuses), 0);

        foreach ($leaves as $leave) {
            $start = \Carbon\Carbon::parse($leave->start_date);
            $end = \Carbon\Carbon::parse($leave->end_date);
            $diff = $start->diffInDays($end) + 1;

            $leaves_summary[$leave->essentials_leave_type_id][$leave->status] =
                ($leaves_summary[$leave->essentials_leave_type_id][$leave->status] ?? 0) + $diff;
            $status_summary[$leave->status] = ($status_summary[$leave->status] ?? 0) + $diff;
        }

        $leave_types = EssentialsLeaveType::where('business_id', $business_id)->get();
        $user = User::where('business_id', $business_id)->findOrFail($user_id);
        $leave_type_summary_rows = [];

        foreach ($leave_types as $leave_type) {
            $status_counts = [];
            foreach ($statuses as $status_key => $status_meta) {
                $status_counts[] = [
                    'name' => $status_meta['name'],
                    'count' => $leaves_summary[$leave_type->id][$status_key] ?? 0,
                ];
            }

            $leave_type_summary_rows[] = [
                'leave_type' => $leave_type,
                'status_counts' => $status_counts,
            ];
        }

        return view('projectx::essentials.hrm.leave.user_leave_summary', compact('leaves_summary', 'leave_types', 'statuses', 'user', 'status_summary', 'leave_type_summary_rows'));
    }

    public function changeLeaveStatus(Request $request)
    {
        $business_id = $this->projectXEssentialsUtil->businessId($request);
        $current_leave = EssentialsLeave::where('business_id', $business_id)->findOrFail((int) $request->input('id'));
        $leave_statuses = $this->leave_statuses;
        $leaveCount = $this->checkLeaveAvailability($current_leave);
        $leaveType = EssentialsLeaveType::where('business_id', $business_id)
            ->findOrFail((int) $current_leave->essentials_leave_type_id);

        return view('projectx::essentials.hrm.leave.change_status_modal', compact('leave_statuses', 'current_leave', 'leaveType', 'leaveCount'));
    }

    public function checkLeaveAvailability(EssentialsLeave $currentLeave): int
    {
        $leaveType = EssentialsLeaveType::where('business_id', (int) $currentLeave->business_id)
            ->find((int) $currentLeave->essentials_leave_type_id);

        if (empty($leaveType)) {
            return 0;
        }

        $leaveQuery = EssentialsLeave::where('business_id', (int) $currentLeave->business_id)
            ->where('user_id', (int) $currentLeave->user_id)
            ->where('essentials_leave_type_id', (int) $currentLeave->essentials_leave_type_id)
            ->where('status', 'approved')
            ->where('id', '!=', (int) $currentLeave->id);

        if ($leaveType->leave_count_interval === 'month') {
            $leaveStartDate = \Carbon\Carbon::parse($currentLeave->start_date);
            $leaveQuery->whereMonth('start_date', $leaveStartDate->month)
                ->whereYear('start_date', $leaveStartDate->year);
        } elseif ($leaveType->leave_count_interval === 'year') {
            $leaveStartDate = \Carbon\Carbon::parse($currentLeave->start_date);
            $currentYear = $leaveStartDate->year;

            $business = Business::where('id', (int) $currentLeave->business_id)->first();
            $start_month = ! empty($business) ? (int) $business->fy_start_month : 1;

            $financialYearStart = $leaveStartDate->month >= $start_month
                ? \Carbon\Carbon::createFromDate($currentYear, $start_month, 1)
                : \Carbon\Carbon::createFromDate($currentYear - 1, $start_month, 1);

            $financialYearEnd = $financialYearStart->copy()->addYear()->subDay();

            $leaveQuery->whereDate('start_date', '>=', $financialYearStart)
                ->whereDate('start_date', '<=', $financialYearEnd);
        }

        return (int) $leaveQuery->get()->sum(function ($leave) {
            $start = \Carbon\Carbon::parse($leave->start_date);
            $end = \Carbon\Carbon::parse($leave->end_date);

            return $start->diffInDays($end) + 1;
        });
    }

    protected function authorizeLeaveMutation(): void
    {
        if (! auth()->user()->can('essentials.crud_all_leave') && ! auth()->user()->can('essentials.crud_own_leave')) {
            abort(403, __('messages.unauthorized_action'));
        }
    }
}
