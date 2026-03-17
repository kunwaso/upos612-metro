<?php

namespace Modules\ProjectX\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Utils\ModuleUtil;
use Illuminate\Http\Request;
use Modules\Essentials\Entities\EssentialsLeaveType;
use Modules\ProjectX\Http\Requests\Essentials\EssentialsHrmLeaveTypeStoreRequest;
use Modules\ProjectX\Http\Requests\Essentials\EssentialsHrmLeaveTypeUpdateRequest;
use Modules\ProjectX\Utils\ProjectXEssentialsUtil;
use Yajra\DataTables\Facades\DataTables;

class ProjectXEssentialsLeaveTypeController extends Controller
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
        $this->authorizeCrudLeaveType();

        if ($request->ajax()) {
            $leave_types = EssentialsLeaveType::where('business_id', $business_id)
                ->select(['id', 'leave_type', 'max_leave_count', 'leave_count_interval']);

            return DataTables::of($leave_types)
                ->addColumn('action', function (EssentialsLeaveType $row) {
                    $actions = [];
                    $actions[] = '<a href="' . route('projectx.essentials.hrm.leave-type.edit', ['leave_type' => $row->id]) . '" class="dropdown-item">' . e(__('messages.edit')) . '</a>';
                    $actions[] = '<a href="#" data-id="' . e((string) $row->id) . '" class="dropdown-item projectx-delete-leave-type">' . e(__('messages.delete')) . '</a>';

                    return '<div class="dropdown">'
                        . '<button class="btn btn-sm btn-light btn-active-light-primary" data-bs-toggle="dropdown" type="button">' . e(__('messages.actions')) . '</button>'
                        . '<div class="dropdown-menu dropdown-menu-end">' . implode('', $actions) . '</div>'
                        . '</div>';
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('projectx::essentials.hrm.leave_type.index');
    }

    public function create(Request $request)
    {
        $this->authorizeCrudLeaveType();

        return view('projectx::essentials.hrm.leave_type.create');
    }

    public function store(EssentialsHrmLeaveTypeStoreRequest $request)
    {
        $business_id = $this->projectXEssentialsUtil->businessId($request);
        $this->authorizeCrudLeaveType();

        try {
            $input = $request->validated();
            $input['business_id'] = $business_id;

            EssentialsLeaveType::create($input);

            if ($request->expectsJson() || $request->ajax()) {
                return $this->respondSuccess(__('lang_v1.added_success'));
            }

            return redirect()->route('projectx.essentials.hrm.leave-type.index')
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
        $this->authorizeCrudLeaveType();

        $leave_type = EssentialsLeaveType::where('business_id', $business_id)->findOrFail($id);

        return view('projectx::essentials.hrm.leave_type.show', compact('leave_type'));
    }

    public function edit(Request $request, int $id)
    {
        $business_id = $this->projectXEssentialsUtil->businessId($request);
        $this->authorizeCrudLeaveType();

        $leave_type = EssentialsLeaveType::where('business_id', $business_id)->findOrFail($id);

        return view('projectx::essentials.hrm.leave_type.edit', compact('leave_type'));
    }

    public function update(EssentialsHrmLeaveTypeUpdateRequest $request, int $id)
    {
        $business_id = $this->projectXEssentialsUtil->businessId($request);
        $this->authorizeCrudLeaveType();

        try {
            $input = $request->validated();

            EssentialsLeaveType::where('business_id', $business_id)
                ->where('id', $id)
                ->update($input);

            if ($request->expectsJson() || $request->ajax()) {
                return $this->respondSuccess(__('lang_v1.updated_success'));
            }

            return redirect()->route('projectx.essentials.hrm.leave-type.index')
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
        $this->authorizeCrudLeaveType();

        try {
            EssentialsLeaveType::where('business_id', $business_id)
                ->where('id', $id)
                ->delete();

            if ($request->expectsJson() || $request->ajax()) {
                return $this->respondSuccess(__('lang_v1.deleted_success'));
            }

            return redirect()->route('projectx.essentials.hrm.leave-type.index')
                ->with('status', ['success' => true, 'msg' => __('lang_v1.deleted_success')]);
        } catch (\Exception $exception) {
            \Log::emergency('File:' . $exception->getFile() . ' Line:' . $exception->getLine() . ' Message:' . $exception->getMessage());

            if ($request->expectsJson() || $request->ajax()) {
                return $this->respondWentWrong($exception);
            }

            return redirect()->back()->with('status', ['success' => false, 'msg' => __('messages.something_went_wrong')]);
        }
    }

    protected function authorizeCrudLeaveType(): void
    {
        if (! auth()->user()->can('essentials.crud_leave_type')) {
            abort(403, __('messages.unauthorized_action'));
        }
    }
}
