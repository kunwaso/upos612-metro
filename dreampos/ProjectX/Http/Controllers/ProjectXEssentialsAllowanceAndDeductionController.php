<?php

namespace Modules\ProjectX\Http\Controllers;

use App\Http\Controllers\Controller;
use App\User;
use App\Utils\ModuleUtil;
use Illuminate\Http\Request;
use Modules\Essentials\Entities\EssentialsAllowanceAndDeduction;
use Modules\Essentials\Utils\EssentialsUtil;
use Modules\ProjectX\Http\Requests\Essentials\EssentialsAllowanceDeductionStoreRequest;
use Modules\ProjectX\Http\Requests\Essentials\EssentialsAllowanceDeductionUpdateRequest;
use Modules\ProjectX\Utils\ProjectXEssentialsUtil;
use Yajra\DataTables\Facades\DataTables;

class ProjectXEssentialsAllowanceAndDeductionController extends Controller
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
        $this->authorizeAllowanceAccess();

        if ($request->ajax()) {
            $allowances = EssentialsAllowanceAndDeduction::where('business_id', $business_id)
                ->with('employees');

            return DataTables::of($allowances)
                ->addColumn('action', function (EssentialsAllowanceAndDeduction $row) {
                    $actions = [];
                    if (auth()->user()->can('essentials.add_allowance_and_deduction')) {
                        $actions[] = '<a href="' . route('projectx.essentials.allowance-deduction.edit', ['allowance_deduction' => $row->id]) . '" class="dropdown-item">' . e(__('messages.edit')) . '</a>';
                        $actions[] = '<a href="#" data-id="' . e((string) $row->id) . '" class="dropdown-item projectx-delete-allowance">' . e(__('messages.delete')) . '</a>';
                    }

                    if (empty($actions)) {
                        return '';
                    }

                    return '<div class="dropdown">'
                        . '<button class="btn btn-sm btn-light btn-active-light-primary" data-bs-toggle="dropdown" type="button">' . e(__('messages.actions')) . '</button>'
                        . '<div class="dropdown-menu dropdown-menu-end">' . implode('', $actions) . '</div>'
                        . '</div>';
                })
                ->editColumn('applicable_date', function (EssentialsAllowanceAndDeduction $row) {
                    return ! empty($row->applicable_date) ? $this->essentialsUtil->format_date($row->applicable_date) : '-';
                })
                ->editColumn('type', function (EssentialsAllowanceAndDeduction $row) {
                    return __('essentials::lang.' . $row->type);
                })
                ->editColumn('amount', function (EssentialsAllowanceAndDeduction $row) {
                    $suffix = $row->amount_type === 'percent' ? ' %' : '';

                    return '<span class="display_currency" data-currency_symbol="false">' . e((string) $row->amount) . '</span>' . $suffix;
                })
                ->editColumn('employees', function (EssentialsAllowanceAndDeduction $row) {
                    return $row->employees->pluck('user_full_name')->implode(', ');
                })
                ->rawColumns(['action', 'amount'])
                ->make(true);
        }

        return view('projectx::essentials.allowance_deduction.index');
    }

    public function create(Request $request)
    {
        $business_id = $this->projectXEssentialsUtil->businessId($request);
        $this->authorizeMutateAllowance();

        $users = User::forDropdown($business_id, false);

        return view('projectx::essentials.allowance_deduction.create', compact('users'));
    }

    public function store(EssentialsAllowanceDeductionStoreRequest $request)
    {
        $business_id = $this->projectXEssentialsUtil->businessId($request);
        $this->authorizeMutateAllowance();

        try {
            $input = $request->validated();
            $input['business_id'] = $business_id;
            $input['amount'] = $this->moduleUtil->num_uf((string) $input['amount']);
            $input['applicable_date'] = ! empty($input['applicable_date'])
                ? $this->essentialsUtil->uf_date((string) $input['applicable_date'])
                : null;

            $allowance = EssentialsAllowanceAndDeduction::create($input);
            $allowance->employees()->sync((array) $request->input('employees', []));

            if ($request->expectsJson() || $request->ajax()) {
                return $this->respondSuccess(__('lang_v1.added_success'));
            }

            return redirect()->route('projectx.essentials.allowance-deduction.index')
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
        return redirect()->route('projectx.essentials.allowance-deduction.edit', ['allowance_deduction' => $id]);
    }

    public function edit(Request $request, int $id)
    {
        $business_id = $this->projectXEssentialsUtil->businessId($request);
        $this->authorizeMutateAllowance();

        $allowance = EssentialsAllowanceAndDeduction::where('business_id', $business_id)
            ->with('employees')
            ->findOrFail($id);

        $users = User::forDropdown($business_id, false);
        $selected_users = $allowance->employees->pluck('id')->all();
        $applicable_date = ! empty($allowance->applicable_date) ? $this->essentialsUtil->format_date($allowance->applicable_date) : null;

        return view('projectx::essentials.allowance_deduction.edit', compact('allowance', 'users', 'selected_users', 'applicable_date'));
    }

    public function update(EssentialsAllowanceDeductionUpdateRequest $request, int $id)
    {
        $business_id = $this->projectXEssentialsUtil->businessId($request);
        $this->authorizeMutateAllowance();

        try {
            $input = $request->validated();
            $input['amount'] = $this->moduleUtil->num_uf((string) $input['amount']);
            $input['applicable_date'] = ! empty($input['applicable_date'])
                ? $this->essentialsUtil->uf_date((string) $input['applicable_date'])
                : null;

            $allowance = EssentialsAllowanceAndDeduction::where('business_id', $business_id)->findOrFail($id);
            $allowance->update($input);
            $allowance->employees()->sync((array) $request->input('employees', []));

            if ($request->expectsJson() || $request->ajax()) {
                return $this->respondSuccess(__('lang_v1.updated_success'));
            }

            return redirect()->route('projectx.essentials.allowance-deduction.index')
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
        $this->authorizeMutateAllowance();

        try {
            EssentialsAllowanceAndDeduction::where('business_id', $business_id)
                ->where('id', $id)
                ->delete();

            if ($request->expectsJson() || $request->ajax()) {
                return $this->respondSuccess(__('lang_v1.deleted_success'));
            }

            return redirect()->route('projectx.essentials.allowance-deduction.index')
                ->with('status', ['success' => true, 'msg' => __('lang_v1.deleted_success')]);
        } catch (\Exception $exception) {
            \Log::emergency('File:' . $exception->getFile() . ' Line:' . $exception->getLine() . ' Message:' . $exception->getMessage());

            if ($request->expectsJson() || $request->ajax()) {
                return $this->respondWentWrong($exception);
            }

            return redirect()->back()->with('status', ['success' => false, 'msg' => __('messages.something_went_wrong')]);
        }
    }

    protected function authorizeAllowanceAccess(): void
    {
        if (! auth()->user()->can('essentials.add_allowance_and_deduction')
            && ! auth()->user()->can('essentials.view_allowance_and_deduction')) {
            abort(403, __('messages.unauthorized_action'));
        }
    }

    protected function authorizeMutateAllowance(): void
    {
        if (! auth()->user()->can('essentials.add_allowance_and_deduction')) {
            abort(403, __('messages.unauthorized_action'));
        }
    }
}
