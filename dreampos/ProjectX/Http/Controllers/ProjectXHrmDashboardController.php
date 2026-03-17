<?php

namespace Modules\ProjectX\Http\Controllers;

use App\Category;
use App\Http\Controllers\Controller;
use App\User;
use App\Utils\ModuleUtil;
use App\Utils\TransactionUtil;
use Illuminate\Support\Facades\DB;
use Modules\Essentials\Entities\EssentialsAttendance;
use Modules\Essentials\Entities\EssentialsHoliday;
use Modules\Essentials\Entities\EssentialsLeave;
use Modules\Essentials\Entities\EssentialsUserSalesTarget;
use Modules\Essentials\Utils\EssentialsUtil;
use Modules\ProjectX\Utils\ProjectXEssentialsUtil;
use Yajra\DataTables\Facades\DataTables;

class ProjectXHrmDashboardController extends Controller
{
    protected ModuleUtil $moduleUtil;

    protected EssentialsUtil $essentialsUtil;

    protected TransactionUtil $transactionUtil;

    protected ProjectXEssentialsUtil $projectXEssentialsUtil;

    public function __construct(
        ModuleUtil $moduleUtil,
        EssentialsUtil $essentialsUtil,
        TransactionUtil $transactionUtil,
        ProjectXEssentialsUtil $projectXEssentialsUtil
    ) {
        $this->moduleUtil = $moduleUtil;
        $this->essentialsUtil = $essentialsUtil;
        $this->transactionUtil = $transactionUtil;
        $this->projectXEssentialsUtil = $projectXEssentialsUtil;

        $this->middleware(function ($request, $next) {
            $business_id = $this->projectXEssentialsUtil->businessId($request);
            $this->projectXEssentialsUtil->ensureEssentialsAccess($business_id);

            return $next($request);
        });
    }

    public function hrmDashboard()
    {
        $business_id = (int) request()->session()->get('user.business_id');
        $is_admin = $this->moduleUtil->is_admin(auth()->user(), $business_id);
        $user_id = (int) auth()->id();

        $users = User::where('business_id', $business_id)->user()->get();
        $departments = Category::where('business_id', $business_id)
            ->where('category_type', 'hrm_department')
            ->get();
        $users_by_dept = $users->groupBy('essentials_department_id');

        $today = new \Carbon\Carbon('today');
        $one_month_from_today = \Carbon\Carbon::now()->addMonth();

        $leaves = EssentialsLeave::where('business_id', $business_id)
            ->where('status', 'approved')
            ->whereDate('end_date', '>=', $today->format('Y-m-d'))
            ->whereDate('start_date', '<=', $one_month_from_today->format('Y-m-d'))
            ->with(['user', 'leave_type'])
            ->orderBy('start_date', 'asc')
            ->get();

        $todays_leaves = [];
        $upcoming_leaves = [];
        $users_leaves = [];
        foreach ($leaves as $leave) {
            $leave_start = \Carbon\Carbon::parse($leave->start_date);
            $leave_end = \Carbon\Carbon::parse($leave->end_date);

            if ($today->gte($leave_start) && $today->lte($leave_end)) {
                $todays_leaves[] = $leave;
                if ((int) $leave->user_id === $user_id) {
                    $users_leaves[] = $leave;
                }
            } elseif ($today->lt($leave_start) && $leave_start->lte($one_month_from_today)) {
                $upcoming_leaves[] = $leave;
                if ((int) $leave->user_id === $user_id) {
                    $users_leaves[] = $leave;
                }
            }
        }

        $holidays_query = EssentialsHoliday::where('essentials_holidays.business_id', $business_id)
            ->whereDate('end_date', '>=', $today->format('Y-m-d'))
            ->whereDate('start_date', '<=', $one_month_from_today->format('Y-m-d'))
            ->orderBy('start_date', 'asc')
            ->with(['location']);

        $permitted_locations = auth()->user()->permitted_locations();
        if ($permitted_locations !== 'all') {
            $holidays_query->where(function ($query) use ($permitted_locations) {
                $query->whereIn('essentials_holidays.location_id', $permitted_locations)
                    ->orWhereNull('essentials_holidays.location_id');
            });
        }

        $holidays = $holidays_query->get();
        $todays_holidays = [];
        $upcoming_holidays = [];
        foreach ($holidays as $holiday) {
            $holiday_start = \Carbon\Carbon::parse($holiday->start_date);
            $holiday_end = \Carbon\Carbon::parse($holiday->end_date);

            if ($today->gte($holiday_start) && $today->lte($holiday_end)) {
                $todays_holidays[] = $holiday;
            } elseif ($today->lt($holiday_start) && $holiday_start->lte($one_month_from_today)) {
                $upcoming_holidays[] = $holiday;
            }
        }

        $todays_attendances = [];
        if ($is_admin) {
            $todays_attendances = EssentialsAttendance::where('business_id', $business_id)
                ->whereDate('clock_in_time', \Carbon\Carbon::now()->format('Y-m-d'))
                ->with(['employee'])
                ->orderBy('clock_in_time', 'asc')
                ->get();
        }

        $settings = $this->essentialsUtil->getEssentialsSettings();
        $sales_targets = EssentialsUserSalesTarget::where('user_id', $user_id)->get();

        $current_start = \Carbon\Carbon::today()->startOfMonth()->format('Y-m-d');
        $current_end = \Carbon\Carbon::today()->endOfMonth()->format('Y-m-d');
        $current_sales = $this->transactionUtil->getUserTotalSales($business_id, $user_id, $current_start, $current_end);
        $target_achieved_this_month = ! empty($settings['calculate_sales_target_commission_without_tax'])
            ? $current_sales['total_sales_without_tax']
            : $current_sales['total_sales'];

        $last_start = \Carbon\Carbon::parse('first day of last month')->format('Y-m-d');
        $last_end = \Carbon\Carbon::parse('last day of last month')->format('Y-m-d');
        $last_sales = $this->transactionUtil->getUserTotalSales($business_id, $user_id, $last_start, $last_end);
        $target_achieved_last_month = ! empty($settings['calculate_sales_target_commission_without_tax'])
            ? $last_sales['total_sales_without_tax']
            : $last_sales['total_sales'];

        $now = \Carbon\Carbon::now()->addDays(1)->format('Y-m-d');
        $thirty_days_from_now = \Carbon\Carbon::now()->addDays(30)->format('Y-m-d');

        $up_comming_births = User::where('business_id', $business_id)
            ->whereRaw("DATE_FORMAT(dob, '%m-%d') BETWEEN DATE_FORMAT('$now', '%m-%d') AND DATE_FORMAT('$thirty_days_from_now', '%m-%d')")
            ->orderBy('dob', 'asc')
            ->get();

        $today_births = User::where('business_id', $business_id)
            ->whereMonth('dob', \Carbon\Carbon::now()->format('m'))
            ->whereDay('dob', \Carbon\Carbon::now()->format('d'))
            ->get();

        return view('projectx::essentials.hrm.dashboard', compact(
            'users',
            'departments',
            'users_by_dept',
            'todays_holidays',
            'todays_leaves',
            'upcoming_leaves',
            'is_admin',
            'users_leaves',
            'upcoming_holidays',
            'todays_attendances',
            'sales_targets',
            'target_achieved_this_month',
            'target_achieved_last_month',
            'up_comming_births',
            'today_births'
        ));
    }

    public function getUserSalesTargets()
    {
        $business_id = (int) request()->session()->get('user.business_id');
        $is_admin = $this->moduleUtil->is_admin(auth()->user(), $business_id);

        if (! $is_admin) {
            abort(403, __('messages.unauthorized_action'));
        }

        $this_month_start_date = \Carbon\Carbon::today()->startOfMonth()->format('Y-m-d');
        $this_month_end_date = \Carbon\Carbon::today()->endOfMonth()->format('Y-m-d');
        $last_month_start_date = \Carbon\Carbon::parse('first day of last month')->format('Y-m-d');
        $last_month_end_date = \Carbon\Carbon::parse('last day of last month')->format('Y-m-d');

        $settings = $this->essentialsUtil->getEssentialsSettings();

        $query = User::where('users.business_id', $business_id)
            ->join('transactions as t', 't.commission_agent', '=', 'users.id')
            ->where('t.type', 'sell')
            ->whereDate('transaction_date', '>=', $last_month_start_date)
            ->where('t.status', 'final');

        if (! empty($settings['calculate_sales_target_commission_without_tax'])) {
            $query->select(
                DB::raw("CONCAT(COALESCE(surname, ''), ' ', COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) as full_name"),
                DB::raw("SUM(IF(DATE(transaction_date) BETWEEN '{$last_month_start_date}' AND '{$last_month_end_date}', total_before_tax - shipping_charges - (SELECT SUM(item_tax*quantity) FROM transaction_sell_lines as tsl WHERE tsl.transaction_id=t.id), 0) ) as total_sales_last_month"),
                DB::raw("SUM(IF(DATE(transaction_date) BETWEEN '{$this_month_start_date}' AND '{$this_month_end_date}', total_before_tax - shipping_charges - (SELECT SUM(item_tax*quantity) FROM transaction_sell_lines as tsl WHERE tsl.transaction_id=t.id), 0) ) as total_sales_this_month")
            );
        } else {
            $query->select(
                DB::raw("CONCAT(COALESCE(surname, ''), ' ', COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) as full_name"),
                DB::raw("SUM(IF(DATE(transaction_date) BETWEEN '{$last_month_start_date}' AND '{$last_month_end_date}', final_total, 0)) as total_sales_last_month"),
                DB::raw("SUM(IF(DATE(transaction_date) BETWEEN '{$this_month_start_date}' AND '{$this_month_end_date}', final_total, 0)) as total_sales_this_month")
            );
        }

        $query->groupBy('users.id');

        return DataTables::of($query)
            ->editColumn('total_sales_this_month', function ($row) {
                return $this->transactionUtil->num_f($row->total_sales_this_month, true);
            })
            ->editColumn('total_sales_last_month', function ($row) {
                return $this->transactionUtil->num_f($row->total_sales_last_month, true);
            })
            ->make(true);
    }
}
