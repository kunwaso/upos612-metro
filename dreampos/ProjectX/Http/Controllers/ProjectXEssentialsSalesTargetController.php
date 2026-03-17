<?php

namespace Modules\ProjectX\Http\Controllers;

use App\Http\Controllers\Controller;
use App\User;
use App\Utils\ModuleUtil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Essentials\Entities\EssentialsUserSalesTarget;
use Modules\ProjectX\Http\Requests\Essentials\EssentialsHrmSalesTargetSaveRequest;
use Modules\ProjectX\Utils\ProjectXEssentialsUtil;
use Yajra\DataTables\Facades\DataTables;

class ProjectXEssentialsSalesTargetController extends Controller
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
        $this->authorizeSalesTargetAccess();

        if ($request->ajax()) {
            $users = User::where('business_id', $business_id)
                ->user()
                ->where('allow_login', 1)
                ->select([
                    'id',
                    DB::raw("CONCAT(COALESCE(surname, ''), ' ', COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) as full_name"),
                ]);

            return DataTables::of($users)
                ->addColumn('action', function (User $row) {
                    return '<a href="' . route('projectx.essentials.hrm.sales-target.set', ['id' => $row->id]) . '" class="btn btn-sm btn-light-primary">'
                        . e(__('essentials::lang.set_sales_target'))
                        . '</a>';
                })
                ->filterColumn('full_name', function ($query, $keyword) {
                    $query->where(function ($q) use ($keyword) {
                        $q->whereRaw("CONCAT(COALESCE(surname, ''), ' ', COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) like ?", ["%{$keyword}%"])
                            ->orWhere('username', 'like', "%{$keyword}%")
                            ->orWhere('email', 'like', "%{$keyword}%");
                    });
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('projectx::essentials.hrm.sales_target.index');
    }

    public function setSalesTarget(Request $request, int $id)
    {
        $business_id = $this->projectXEssentialsUtil->businessId($request);
        $this->authorizeSalesTargetAccess();

        $user = User::where('business_id', $business_id)->findOrFail($id);
        $sales_targets = EssentialsUserSalesTarget::where('user_id', $id)->get();

        return view('projectx::essentials.hrm.sales_target.set_target', compact('user', 'sales_targets'));
    }

    public function saveSalesTarget(EssentialsHrmSalesTargetSaveRequest $request)
    {
        $business_id = $this->projectXEssentialsUtil->businessId($request);
        $this->authorizeSalesTargetAccess();

        try {
            $target_ids = [];

            foreach ((array) $request->input('edit_target', []) as $key => $value) {
                EssentialsUserSalesTarget::where('user_id', (int) $request->input('user_id'))
                    ->where('id', (int) $key)
                    ->update([
                        'target_start' => $this->moduleUtil->num_uf((string) ($value['target_start'] ?? 0)),
                        'target_end' => $this->moduleUtil->num_uf((string) ($value['target_end'] ?? 0)),
                        'commission_percent' => $this->moduleUtil->num_uf((string) ($value['commission_percent'] ?? 0)),
                    ]);

                $target_ids[] = (int) $key;
            }

            EssentialsUserSalesTarget::where('user_id', (int) $request->input('user_id'))
                ->when(! empty($target_ids), function ($query) use ($target_ids) {
                    $query->whereNotIn('id', $target_ids);
                })
                ->when(empty($target_ids), function ($query) {
                    return $query;
                })
                ->delete();

            $starts = (array) $request->input('sales_amount_start', []);
            $ends = (array) $request->input('sales_amount_end', []);
            $commissions = (array) $request->input('commission', []);

            foreach ($starts as $key => $value) {
                $target_start = $this->moduleUtil->num_uf((string) $value);
                $target_end = $this->moduleUtil->num_uf((string) ($ends[$key] ?? '0'));

                if (empty($target_start) && empty($target_end)) {
                    continue;
                }

                EssentialsUserSalesTarget::create([
                    'user_id' => (int) $request->input('user_id'),
                    'target_start' => $target_start,
                    'target_end' => $target_end,
                    'commission_percent' => $this->moduleUtil->num_uf((string) ($commissions[$key] ?? '0')),
                ]);
            }

            return redirect()->route('projectx.essentials.hrm.sales-target.index')
                ->with('status', ['success' => true, 'msg' => __('lang_v1.success')]);
        } catch (\Exception $exception) {
            \Log::emergency('File:' . $exception->getFile() . ' Line:' . $exception->getLine() . ' Message:' . $exception->getMessage());

            return redirect()->back()->withInput()->with('status', ['success' => false, 'msg' => __('messages.something_went_wrong')]);
        }
    }

    protected function authorizeSalesTargetAccess(): void
    {
        if (! auth()->user()->can('essentials.access_sales_target')) {
            abort(403, __('messages.unauthorized_action'));
        }
    }
}
