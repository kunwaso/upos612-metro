<?php

namespace Modules\VasAccounting\Http\Controllers;

use App\BusinessLocation;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\VasAccounting\Http\Requests\DashboardUiDataRequest;
use Modules\VasAccounting\Entities\VasAccountingPeriod;
use Modules\VasAccounting\Entities\VasPostingFailure;
use Modules\VasAccounting\Entities\VasVoucher;
use Modules\VasAccounting\Services\VasInventoryValuationService;
use Modules\VasAccounting\Utils\VasAccountingUtil;

class DashboardController extends VasBaseController
{
    public function __construct(
        protected VasAccountingUtil $vasUtil,
        protected VasInventoryValuationService $inventoryValuationService
    ) {
    }

    public function index(Request $request)
    {
        $this->authorizePermission('vas_accounting.access');

        $businessId = $this->businessId($request);
        $selectedLocationId = $this->selectedLocationId($request);
        $bootstrap = $this->vasUtil->ensureBusinessBootstrapped($businessId, (int) auth()->id());
        $metrics = $this->vasUtil->dashboardMetrics($businessId);
        $inventoryTotals = $this->inventoryValuationService->totals($businessId);
        $recentVouchers = VasVoucher::query()
            ->where('business_id', $businessId)
            ->when($selectedLocationId, fn ($query) => $query->where('business_location_id', $selectedLocationId))
            ->latest()
            ->take(8)
            ->get();
        $periods = VasAccountingPeriod::query()->where('business_id', $businessId)->latest('start_date')->take(6)->get();
        $failures = VasPostingFailure::query()->where('business_id', $businessId)->whereNull('resolved_at')->latest()->take(5)->get();
        $failureWidgetItems = $failures->map(function (VasPostingFailure $failure) {
            return [
                'title' => $this->displayFailureMessage((string) $failure->error_message),
                'description' => $this->failureSourceLabel($failure),
                'icon' => 'ki-outline ki-information-4',
                'badgeVariant' => 'light-warning',
            ];
        })->all();

        return view('vasaccounting::dashboard.index', compact('metrics', 'inventoryTotals', 'recentVouchers', 'periods', 'failures', 'failureWidgetItems') + [
            'autoBootstrapped' => $bootstrap['bootstrapped'],
            'locationOptions' => BusinessLocation::forDropdown($businessId),
            'selectedLocationId' => $selectedLocationId,
        ]);
    }

    public function kpis(DashboardUiDataRequest $request): JsonResponse
    {
        $this->authorizePermission('vas_accounting.access');

        $businessId = $this->businessId($request);
        $selectedLocationId = $this->selectedLocationId($request);
        $metrics = $this->vasUtil->dashboardMetrics($businessId);
        $inventoryTotals = $this->inventoryValuationService->totals($businessId);

        $basePostedQuery = VasVoucher::query()
            ->where('business_id', $businessId)
            ->where('status', 'posted')
            ->when($selectedLocationId, fn ($query) => $query->where('business_location_id', $selectedLocationId));

        $thisMonth = now();
        $lastMonth = now()->copy()->subMonthNoOverflow();

        $postedThisMonth = (clone $basePostedQuery)
            ->whereMonth('posted_at', $thisMonth->month)
            ->whereYear('posted_at', $thisMonth->year)
            ->count();
        $postedLastMonth = (clone $basePostedQuery)
            ->whereMonth('posted_at', $lastMonth->month)
            ->whereYear('posted_at', $lastMonth->year)
            ->count();

        $baseFailureQuery = VasPostingFailure::query()
            ->where('business_id', $businessId)
            ->when($selectedLocationId, fn ($query) => $query->where('business_location_id', $selectedLocationId));

        $openFailureCount = (clone $baseFailureQuery)
            ->whereNull('resolved_at')
            ->count();

        $failuresThisMonth = (clone $baseFailureQuery)
            ->whereMonth('created_at', $thisMonth->month)
            ->whereYear('created_at', $thisMonth->year)
            ->count();
        $failuresLastMonth = (clone $baseFailureQuery)
            ->whereMonth('created_at', $lastMonth->month)
            ->whereYear('created_at', $lastMonth->year)
            ->count();

        $cards = [
            [
                'key' => 'open_periods',
                'label' => $this->vasUtil->metricLabel('open_periods'),
                'value' => number_format((int) ($metrics['openPeriods'] ?? 0)),
                'delta' => 0,
                'direction' => 'flat',
                'hint' => __('vasaccounting::lang.views.dashboard.metrics.open_periods'),
                'icon' => 'ki-outline ki-calendar-8',
                'badgeVariant' => 'light-primary',
            ],
            [
                'key' => 'posting_failures',
                'label' => $this->vasUtil->metricLabel('posting_failures'),
                'value' => number_format((int) $openFailureCount),
                'delta' => $this->percentageDelta($failuresThisMonth, $failuresLastMonth),
                'direction' => $failuresThisMonth > $failuresLastMonth ? 'down' : ($failuresThisMonth < $failuresLastMonth ? 'up' : 'flat'),
                'hint' => __('vasaccounting::lang.views.dashboard.metrics.posting_failures'),
                'icon' => 'ki-outline ki-shield-cross',
                'badgeVariant' => 'light-danger',
            ],
            [
                'key' => 'inventory_value',
                'label' => $this->vasUtil->metricLabel('inventory_value'),
                'value' => number_format((float) data_get($inventoryTotals, 'inventory_value', 0), 2),
                'delta' => 0,
                'direction' => 'flat',
                'hint' => __('vasaccounting::lang.views.dashboard.metrics.inventory_value'),
                'icon' => 'ki-outline ki-package',
                'badgeVariant' => 'light-success',
            ],
            [
                'key' => 'posted_this_month',
                'label' => $this->vasUtil->metricLabel('posted_this_month'),
                'value' => number_format((int) $postedThisMonth),
                'delta' => $this->percentageDelta($postedThisMonth, $postedLastMonth),
                'direction' => $postedThisMonth > $postedLastMonth ? 'up' : ($postedThisMonth < $postedLastMonth ? 'down' : 'flat'),
                'hint' => __('vasaccounting::lang.views.dashboard.metrics.posted_this_month'),
                'icon' => 'ki-outline ki-chart-line-up-2',
                'badgeVariant' => 'light-info',
            ],
        ];

        return response()->json([
            'cards' => $cards,
            'updated_at' => now()->toIso8601String(),
        ]);
    }

    public function trends(DashboardUiDataRequest $request): JsonResponse
    {
        $this->authorizePermission('vas_accounting.access');

        $businessId = $this->businessId($request);
        $selectedLocationId = $this->selectedLocationId($request);
        $range = (string) ($request->input('range') ?: 'year');

        if ($range === 'month') {
            $startDate = now()->startOfMonth();
            $endDate = now()->endOfMonth();
            $rows = VasVoucher::query()
                ->selectRaw('DATE(posting_date) as bucket, COALESCE(SUM(total_debit), 0) as debit_total, COALESCE(SUM(total_credit), 0) as credit_total')
                ->where('business_id', $businessId)
                ->whereBetween('posting_date', [$startDate->toDateString(), $endDate->toDateString()])
                ->where('status', 'posted')
                ->when($selectedLocationId, fn ($query) => $query->where('business_location_id', $selectedLocationId))
                ->groupBy('bucket')
                ->orderBy('bucket')
                ->get()
                ->keyBy('bucket');

            $labels = [];
            $debitSeries = [];
            $creditSeries = [];
            $cursor = $startDate->copy();
            while ($cursor->lte($endDate)) {
                $bucket = $cursor->toDateString();
                $labels[] = $cursor->format('d M');
                $debitSeries[] = round((float) data_get($rows, $bucket . '.debit_total', 0), 2);
                $creditSeries[] = round((float) data_get($rows, $bucket . '.credit_total', 0), 2);
                $cursor->addDay();
            }
        } else {
            $dateRange = $range === 'quarter'
                ? [now()->startOfQuarter(), now()->endOfQuarter()]
                : [now()->startOfYear(), now()->endOfYear()];

            $startDate = $dateRange[0];
            $endDate = $dateRange[1];

            $rows = VasVoucher::query()
                ->selectRaw('MONTH(posting_date) as month_num, COALESCE(SUM(total_debit), 0) as debit_total, COALESCE(SUM(total_credit), 0) as credit_total')
                ->where('business_id', $businessId)
                ->whereBetween('posting_date', [$startDate->toDateString(), $endDate->toDateString()])
                ->where('status', 'posted')
                ->when($selectedLocationId, fn ($query) => $query->where('business_location_id', $selectedLocationId))
                ->groupBy('month_num')
                ->orderBy('month_num')
                ->get()
                ->keyBy('month_num');

            $labels = [];
            $debitSeries = [];
            $creditSeries = [];
            $cursor = $startDate->copy()->startOfMonth();
            $lastMonth = $endDate->copy()->startOfMonth();
            while ($cursor->lte($lastMonth)) {
                $monthNum = (int) $cursor->month;
                $labels[] = $cursor->format('M');
                $debitSeries[] = round((float) data_get($rows, $monthNum . '.debit_total', 0), 2);
                $creditSeries[] = round((float) data_get($rows, $monthNum . '.credit_total', 0), 2);
                $cursor->addMonthNoOverflow();
            }
        }

        return response()->json([
            'labels' => $labels,
            'series' => [
                ['name' => __('vasaccounting::lang.views.dashboard.chart.posted_debit'), 'data' => $debitSeries],
                ['name' => __('vasaccounting::lang.views.dashboard.chart.posted_credit'), 'data' => $creditSeries],
            ],
            'meta' => [
                'range' => $range,
                'currency' => (string) data_get($request->session()->all(), 'currency.symbol', 'VND'),
                'location_id' => $selectedLocationId,
            ],
        ]);
    }

    public function failures(DashboardUiDataRequest $request): JsonResponse
    {
        $this->authorizePermission('vas_accounting.access');

        $businessId = $this->businessId($request);
        $selectedLocationId = $this->selectedLocationId($request);

        $failures = VasPostingFailure::query()
            ->where('business_id', $businessId)
            ->whereNull('resolved_at')
            ->when($selectedLocationId, fn ($query) => $query->where('business_location_id', $selectedLocationId))
            ->latest()
            ->limit(12)
            ->get()
            ->map(function (VasPostingFailure $failure) {
                return [
                    'id' => $failure->id,
                    'message' => $this->displayFailureMessage((string) $failure->error_message),
                    'source' => $this->failureSourceLabel($failure),
                    'occurred_at' => optional(Carbon::parse((string) $failure->created_at))->toDateTimeString(),
                ];
            })
            ->values()
            ->all();

        return response()->json([
            'failures' => $failures,
            'updated_at' => now()->toIso8601String(),
        ]);
    }

    protected function percentageDelta(int|float $current, int|float $previous): float
    {
        $previous = (float) $previous;
        $current = (float) $current;
        if ($previous === 0.0) {
            return $current === 0.0 ? 0.0 : 100.0;
        }

        return round((($current - $previous) / abs($previous)) * 100, 1);
    }

    protected function failureSourceLabel(VasPostingFailure $failure): string
    {
        return (string) $failure->source_type . ':' . (string) $failure->source_id;
    }

    protected function displayFailureMessage(string $message): string
    {
        $normalized = trim(preg_replace('/\s+/', ' ', $message) ?: '');
        if ($normalized === '') {
            return 'Posting failed. Open the source document and retry.';
        }

        $lower = strtolower($normalized);
        if (
            str_contains($lower, 'sqlstate[23000]')
            || str_contains($lower, 'integrity constraint violation')
            || str_contains($lower, 'cannot add or update a child row')
        ) {
            return 'Posting blocked by missing linked data. Check account, product, warehouse, and mapping references.';
        }

        return str($normalized)->limit(120)->toString();
    }
}
