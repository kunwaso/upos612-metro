<?php

namespace Modules\ProjectX\Http\Controllers;

use App\Http\Controllers\Controller;
use App\User;
use App\Utils\ModuleUtil;
use Illuminate\Http\Request;
use Modules\Essentials\Entities\EssentialsUserShift;
use Modules\Essentials\Entities\Shift;
use Modules\ProjectX\Http\Requests\Essentials\EssentialsHrmShiftAssignUsersRequest;
use Modules\ProjectX\Http\Requests\Essentials\EssentialsHrmShiftStoreRequest;
use Modules\ProjectX\Http\Requests\Essentials\EssentialsHrmShiftUpdateRequest;
use Modules\ProjectX\Utils\ProjectXEssentialsUtil;
use Yajra\DataTables\Facades\DataTables;

class ProjectXEssentialsShiftController extends Controller
{
    protected ModuleUtil $moduleUtil;

    protected ProjectXEssentialsUtil $projectXEssentialsUtil;

    public function __construct(ModuleUtil $moduleUtil, ProjectXEssentialsUtil $projectXEssentialsUtil)
    {
        $this->moduleUtil = $moduleUtil;
        $this->projectXEssentialsUtil = $projectXEssentialsUtil;

        $this->middleware(function ($request, $next) {
            $business_id = $this->projectXEssentialsUtil->businessId($request);
            $this->projectXEssentialsUtil->ensureEssentialsAccess($business_id);

            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $business_id = $this->projectXEssentialsUtil->businessId($request);
        $this->authorizeShiftAccess($business_id, false);

        if ($request->ajax()) {
            $shifts = Shift::where('business_id', $business_id)
                ->select(['id', 'name', 'type', 'start_time', 'end_time', 'holidays']);

            return DataTables::of($shifts)
                ->editColumn('start_time', function (Shift $row) {
                    return ! empty($row->start_time) ? $this->moduleUtil->format_time($row->start_time) : '-';
                })
                ->editColumn('end_time', function (Shift $row) {
                    return ! empty($row->end_time) ? $this->moduleUtil->format_time($row->end_time) : '-';
                })
                ->editColumn('type', function (Shift $row) {
                    return __('essentials::lang.' . $row->type);
                })
                ->editColumn('holidays', function (Shift $row) {
                    if (empty($row->holidays) || ! is_array($row->holidays)) {
                        return '';
                    }

                    $holidays = array_map(function ($item) {
                        return __('lang_v1.' . $item);
                    }, $row->holidays);

                    return implode(', ', $holidays);
                })
                ->addColumn('action', function (Shift $row) {
                    $actions = [];
                    $actions[] = '<a href="' . route('projectx.essentials.hrm.shift.edit', ['shift' => $row->id]) . '" class="dropdown-item">' . e(__('messages.edit')) . '</a>';
                    $actions[] = '<a href="' . route('projectx.essentials.hrm.shift.assign-users', ['shift_id' => $row->id]) . '" class="dropdown-item">' . e(__('essentials::lang.assign_users')) . '</a>';
                    $actions[] = '<a href="#" data-id="' . e((string) $row->id) . '" class="dropdown-item projectx-delete-shift">' . e(__('messages.delete')) . '</a>';

                    return '<div class="dropdown">'
                        . '<button class="btn btn-sm btn-light btn-active-light-primary" data-bs-toggle="dropdown" type="button">' . e(__('messages.actions')) . '</button>'
                        . '<div class="dropdown-menu dropdown-menu-end">' . implode('', $actions) . '</div>'
                        . '</div>';
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('projectx::essentials.hrm.shift.index');
    }

    public function create(Request $request)
    {
        $business_id = $this->projectXEssentialsUtil->businessId($request);
        $this->authorizeShiftAccess($business_id, true);

        $days = $this->moduleUtil->getDays();
        $view_data = $this->getShiftFormViewData(null, $days);

        return view('projectx::essentials.hrm.shift.form', $view_data);
    }

    public function store(EssentialsHrmShiftStoreRequest $request)
    {
        $business_id = $this->projectXEssentialsUtil->businessId($request);
        $this->authorizeShiftAccess($business_id, true);

        try {
            $input = $request->validated();
            $input['start_time'] = $input['type'] !== 'flexible_shift' && ! empty($input['start_time'])
                ? $this->moduleUtil->uf_time((string) $input['start_time'])
                : null;
            $input['end_time'] = $input['type'] !== 'flexible_shift' && ! empty($input['end_time'])
                ? $this->moduleUtil->uf_time((string) $input['end_time'])
                : null;
            $input['is_allowed_auto_clockout'] = $request->boolean('is_allowed_auto_clockout') ? 1 : 0;
            $input['auto_clockout_time'] = ! empty($input['auto_clockout_time'])
                ? $this->moduleUtil->uf_time((string) $input['auto_clockout_time'])
                : null;
            $input['business_id'] = $business_id;

            Shift::create($input);

            if ($request->expectsJson() || $request->ajax()) {
                return $this->respondSuccess(__('lang_v1.added_success'));
            }

            return redirect()->route('projectx.essentials.hrm.shift.index')
                ->with('status', ['success' => true, 'msg' => __('lang_v1.added_success')]);
        } catch (\Exception $exception) {
            \Log::emergency('File:' . $exception->getFile() . ' Line:' . $exception->getLine() . ' Message:' . $exception->getMessage());

            if ($request->expectsJson() || $request->ajax()) {
                return $this->respondWentWrong($exception);
            }

            return redirect()->back()->withInput()->with('status', ['success' => false, 'msg' => __('messages.something_went_wrong')]);
        }
    }

    public function show(Request $request, int $id)
    {
        $business_id = $this->projectXEssentialsUtil->businessId($request);
        $this->authorizeShiftAccess($business_id, true);

        return redirect()->route('projectx.essentials.hrm.shift.edit', ['shift' => $id]);
    }

    public function edit(Request $request, int $id)
    {
        $business_id = $this->projectXEssentialsUtil->businessId($request);
        $this->authorizeShiftAccess($business_id, true);

        $shift = Shift::where('business_id', $business_id)->findOrFail($id);
        $days = $this->moduleUtil->getDays();

        $view_data = $this->getShiftFormViewData($shift, $days);

        return view('projectx::essentials.hrm.shift.form', $view_data);
    }

    public function update(EssentialsHrmShiftUpdateRequest $request, int $id)
    {
        $business_id = $this->projectXEssentialsUtil->businessId($request);
        $this->authorizeShiftAccess($business_id, true);

        try {
            $input = $request->validated();
            $input['start_time'] = $input['type'] !== 'flexible_shift' && ! empty($input['start_time'])
                ? $this->moduleUtil->uf_time((string) $input['start_time'])
                : null;
            $input['end_time'] = $input['type'] !== 'flexible_shift' && ! empty($input['end_time'])
                ? $this->moduleUtil->uf_time((string) $input['end_time'])
                : null;
            $input['is_allowed_auto_clockout'] = $request->boolean('is_allowed_auto_clockout') ? 1 : 0;
            $input['auto_clockout_time'] = ! empty($input['auto_clockout_time'])
                ? $this->moduleUtil->uf_time((string) $input['auto_clockout_time'])
                : null;
            $input['holidays'] = ! empty($input['holidays']) ? $input['holidays'] : null;

            Shift::where('business_id', $business_id)
                ->where('id', $id)
                ->update($input);

            if ($request->expectsJson() || $request->ajax()) {
                return $this->respondSuccess(__('lang_v1.updated_success'));
            }

            return redirect()->route('projectx.essentials.hrm.shift.index')
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
        $this->authorizeShiftAccess($business_id, true);

        try {
            Shift::where('business_id', $business_id)
                ->where('id', $id)
                ->delete();

            EssentialsUserShift::where('essentials_shift_id', $id)->delete();

            if ($request->expectsJson() || $request->ajax()) {
                return $this->respondSuccess(__('lang_v1.deleted_success'));
            }

            return redirect()->route('projectx.essentials.hrm.shift.index')
                ->with('status', ['success' => true, 'msg' => __('lang_v1.deleted_success')]);
        } catch (\Exception $exception) {
            \Log::emergency('File:' . $exception->getFile() . ' Line:' . $exception->getLine() . ' Message:' . $exception->getMessage());

            if ($request->expectsJson() || $request->ajax()) {
                return $this->respondWentWrong($exception);
            }

            return redirect()->back()->with('status', ['success' => false, 'msg' => __('messages.something_went_wrong')]);
        }
    }

    public function getAssignUsers(Request $request, int $shift_id)
    {
        $business_id = $this->projectXEssentialsUtil->businessId($request);
        $this->authorizeShiftAccess($business_id, true);

        $shift = Shift::where('business_id', $business_id)
            ->with(['user_shifts'])
            ->findOrFail($shift_id);

        $users = User::forDropdown($business_id, false);

        $user_shifts = [];
        foreach ($shift->user_shifts as $user_shift) {
            $user_shifts[$user_shift->user_id] = [
                'start_date' => ! empty($user_shift->start_date) ? $this->moduleUtil->format_date($user_shift->start_date) : null,
                'end_date' => ! empty($user_shift->end_date) ? $this->moduleUtil->format_date($user_shift->end_date) : null,
            ];
        }

        return view('projectx::essentials.hrm.shift.assign_users', compact('shift', 'users', 'user_shifts'));
    }

    public function postAssignUsers(EssentialsHrmShiftAssignUsersRequest $request)
    {
        $business_id = $this->projectXEssentialsUtil->businessId($request);
        $this->authorizeShiftAccess($business_id, true);

        try {
            $shift_id = (int) $request->input('shift_id');

            Shift::where('business_id', $business_id)->findOrFail($shift_id);

            $user_shifts = (array) $request->input('user_shift', []);
            $user_ids = [];

            foreach ($user_shifts as $user_id => $value) {
                if (empty($value['is_added'])) {
                    continue;
                }

                $user_ids[] = (int) $user_id;

                EssentialsUserShift::updateOrCreate(
                    [
                        'essentials_shift_id' => $shift_id,
                        'user_id' => (int) $user_id,
                    ],
                    [
                        'start_date' => ! empty($value['start_date']) ? $this->moduleUtil->uf_date((string) $value['start_date']) : null,
                        'end_date' => ! empty($value['end_date']) ? $this->moduleUtil->uf_date((string) $value['end_date']) : null,
                    ]
                );
            }

            EssentialsUserShift::where('essentials_shift_id', $shift_id)
                ->when(! empty($user_ids), function ($query) use ($user_ids) {
                    $query->whereNotIn('user_id', $user_ids);
                })
                ->when(empty($user_ids), function ($query) {
                    return $query;
                })
                ->delete();

            if ($request->expectsJson() || $request->ajax()) {
                return $this->respondSuccess(__('lang_v1.added_success'));
            }

            return redirect()->route('projectx.essentials.hrm.shift.index')
                ->with('status', ['success' => true, 'msg' => __('lang_v1.added_success')]);
        } catch (\Exception $exception) {
            \Log::emergency('File:' . $exception->getFile() . ' Line:' . $exception->getLine() . ' Message:' . $exception->getMessage());

            if ($request->expectsJson() || $request->ajax()) {
                return $this->respondWentWrong($exception);
            }

            return redirect()->back()->withInput()->with('status', ['success' => false, 'msg' => __('messages.something_went_wrong')]);
        }
    }

    /**
     * @param  array<string, string>  $days
     * @return array<string, mixed>
     */
    protected function getShiftFormViewData(?Shift $shift, array $days): array
    {
        $is_edit = ! empty($shift);
        $title = $is_edit
            ? __('messages.edit') . ' ' . __('essentials::lang.shift')
            : __('essentials::lang.add_shift');

        return [
            'days' => $days,
            'is_edit' => $is_edit,
            'title' => $title,
            'form_action' => $is_edit
                ? route('projectx.essentials.hrm.shift.update', ['shift' => $shift->id])
                : route('projectx.essentials.hrm.shift.store'),
            'submit_label' => $is_edit ? __('messages.update') : __('messages.save'),
            'shift_name' => $shift->name ?? '',
            'shift_type' => $shift->type ?? 'fixed_shift',
            'shift_start_time' => ! empty($shift?->start_time)
                ? \Carbon\Carbon::parse($shift->start_time)->format('H:i')
                : '',
            'shift_end_time' => ! empty($shift?->end_time)
                ? \Carbon\Carbon::parse($shift->end_time)->format('H:i')
                : '',
            'shift_holidays' => is_array($shift?->holidays) ? $shift->holidays : [],
            'shift_is_allowed_auto_clockout' => ! empty($shift?->is_allowed_auto_clockout),
            'shift_auto_clockout_time' => ! empty($shift?->auto_clockout_time)
                ? \Carbon\Carbon::parse($shift->auto_clockout_time)->format('H:i')
                : '',
        ];
    }

    protected function authorizeShiftAccess(int $business_id, bool $require_admin): void
    {
        $has_essentials_access = auth()->user()->can('superadmin')
            || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'essentials_module');

        if (! $require_admin) {
            if (! $has_essentials_access) {
                abort(403, __('messages.unauthorized_action'));
            }

            return;
        }

        $is_admin = $this->moduleUtil->is_admin(auth()->user(), $business_id);
        if (! $has_essentials_access && ! $is_admin) {
            abort(403, __('messages.unauthorized_action'));
        }
    }
}
