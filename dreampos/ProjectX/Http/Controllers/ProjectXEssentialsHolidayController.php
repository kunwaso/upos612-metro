<?php

namespace Modules\ProjectX\Http\Controllers;

use App\BusinessLocation;
use App\Http\Controllers\Controller;
use App\Utils\ModuleUtil;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\Essentials\Entities\EssentialsHoliday;
use Modules\Essentials\Utils\EssentialsUtil;
use Modules\ProjectX\Http\Requests\Essentials\EssentialsHrmHolidayStoreRequest;
use Modules\ProjectX\Http\Requests\Essentials\EssentialsHrmHolidayUpdateRequest;
use Modules\ProjectX\Utils\ProjectXEssentialsUtil;
use Yajra\DataTables\Facades\DataTables;

class ProjectXEssentialsHolidayController extends Controller
{
    protected ModuleUtil $moduleUtil;

    protected EssentialsUtil $essentialsUtil;

    protected ProjectXEssentialsUtil $projectXEssentialsUtil;

    public function __construct(
        ModuleUtil $moduleUtil,
        EssentialsUtil $essentialsUtil,
        ProjectXEssentialsUtil $projectXEssentialsUtil
    ) {
        $this->moduleUtil = $moduleUtil;
        $this->essentialsUtil = $essentialsUtil;
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
        $this->authorizeHolidayAccess($business_id, false);
        $is_admin = $this->moduleUtil->is_admin(auth()->user(), $business_id);

        if ($request->ajax()) {
            $permitted_locations = auth()->user()->permitted_locations();
            $holidays = $this->essentialsUtil->Gettotalholiday(
                $business_id,
                $request->input('location_id'),
                $request->input('start_date'),
                $request->input('end_date'),
                $permitted_locations
            );

            return DataTables::of($holidays)
                ->addColumn('action', function ($row) use ($is_admin) {
                    if (! $is_admin) {
                        return '';
                    }

                    $edit = '<a href="' . route('projectx.essentials.hrm.holiday.edit', ['holiday' => $row->id]) . '" class="dropdown-item">' . e(__('messages.edit')) . '</a>';
                    $delete = '<a href="#" data-id="' . e((string) $row->id) . '" class="dropdown-item projectx-delete-holiday">' . e(__('messages.delete')) . '</a>';

                    return '<div class="dropdown">'
                        . '<button class="btn btn-sm btn-light btn-active-light-primary" data-bs-toggle="dropdown" type="button">' . e(__('messages.actions')) . '</button>'
                        . '<div class="dropdown-menu dropdown-menu-end">' . $edit . $delete . '</div>'
                        . '</div>';
                })
                ->editColumn('location', function ($row) {
                    return $row->location ?? __('lang_v1.all');
                })
                ->editColumn('start_date', function ($row) {
                    $start = \Carbon\Carbon::parse($row->start_date);
                    $end = \Carbon\Carbon::parse($row->end_date);
                    $diff = $start->diffInDays($end) + 1;

                    return $this->moduleUtil->format_date($start) . ' - '
                        . $this->moduleUtil->format_date($end)
                        . ' (' . $diff . ' ' . Str::plural(__('lang_v1.day'), $diff) . ')';
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        $locations = BusinessLocation::forDropdown($business_id);

        return view('projectx::essentials.hrm.holiday.index', compact('locations', 'is_admin'));
    }

    public function create(Request $request)
    {
        $business_id = $this->projectXEssentialsUtil->businessId($request);
        $this->authorizeHolidayAccess($business_id, true);

        $locations = BusinessLocation::forDropdown($business_id);

        return view('projectx::essentials.hrm.holiday.create', compact('locations'));
    }

    public function store(EssentialsHrmHolidayStoreRequest $request)
    {
        $business_id = $this->projectXEssentialsUtil->businessId($request);
        $this->authorizeHolidayAccess($business_id, true);

        try {
            $input = $request->validated();
            $input['start_date'] = $this->moduleUtil->uf_date((string) $input['start_date']);
            $input['end_date'] = $this->moduleUtil->uf_date((string) $input['end_date']);
            $input['business_id'] = $business_id;

            EssentialsHoliday::create($input);

            if ($request->expectsJson() || $request->ajax()) {
                return $this->respondSuccess(__('lang_v1.added_success'));
            }

            return redirect()->route('projectx.essentials.hrm.holiday.index')
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
        $this->authorizeHolidayAccess($business_id, true);

        $holiday = EssentialsHoliday::where('business_id', $business_id)->findOrFail($id);

        return view('projectx::essentials.hrm.holiday.show', compact('holiday'));
    }

    public function edit(Request $request, int $id)
    {
        $business_id = $this->projectXEssentialsUtil->businessId($request);
        $this->authorizeHolidayAccess($business_id, true);

        $holiday = EssentialsHoliday::where('business_id', $business_id)->findOrFail($id);
        $locations = BusinessLocation::forDropdown($business_id);

        return view('projectx::essentials.hrm.holiday.edit', compact('locations', 'holiday'));
    }

    public function update(EssentialsHrmHolidayUpdateRequest $request, int $id)
    {
        $business_id = $this->projectXEssentialsUtil->businessId($request);
        $this->authorizeHolidayAccess($business_id, true);

        try {
            $input = $request->validated();
            $input['start_date'] = $this->moduleUtil->uf_date((string) $input['start_date']);
            $input['end_date'] = $this->moduleUtil->uf_date((string) $input['end_date']);

            EssentialsHoliday::where('business_id', $business_id)
                ->where('id', $id)
                ->update($input);

            if ($request->expectsJson() || $request->ajax()) {
                return $this->respondSuccess(__('lang_v1.updated_success'));
            }

            return redirect()->route('projectx.essentials.hrm.holiday.index')
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
        $this->authorizeHolidayAccess($business_id, true);

        try {
            EssentialsHoliday::where('business_id', $business_id)
                ->where('id', $id)
                ->delete();

            if ($request->expectsJson() || $request->ajax()) {
                return $this->respondSuccess(__('lang_v1.deleted_success'));
            }

            return redirect()->route('projectx.essentials.hrm.holiday.index')
                ->with('status', ['success' => true, 'msg' => __('lang_v1.deleted_success')]);
        } catch (\Exception $exception) {
            \Log::emergency('File:' . $exception->getFile() . ' Line:' . $exception->getLine() . ' Message:' . $exception->getMessage());

            if ($request->expectsJson() || $request->ajax()) {
                return $this->respondWentWrong($exception);
            }

            return redirect()->back()->with('status', ['success' => false, 'msg' => __('messages.something_went_wrong')]);
        }
    }

    protected function authorizeHolidayAccess(int $business_id, bool $require_admin): void
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
