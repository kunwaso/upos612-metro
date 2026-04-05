@extends('layouts.app')

@section('title', __('vasaccounting::lang.dashboard'))

@section('content')
    @php
        $dashboardActions = '<div class="d-flex flex-wrap gap-3">'
            . '<a href="' . route('vasaccounting.vouchers.create') . '" class="btn btn-primary btn-sm">' . $vasAccountingUtil->actionLabel('new_voucher') . '</a>'
            . '<a href="' . route('vasaccounting.closing.index') . '" class="btn btn-light-warning btn-sm">' . $vasAccountingUtil->actionLabel('period_close') . '</a>'
            . '</div>';

        $initialKpiCards = [
            [
                'key' => 'open_periods',
                'label' => $vasAccountingUtil->metricLabel('open_periods'),
                'value' => number_format((int) ($metrics['openPeriods'] ?? 0)),
                'delta' => 0,
                'direction' => 'flat',
                'hint' => __('vasaccounting::lang.views.dashboard.metrics.open_periods'),
                'icon' => 'ki-outline ki-calendar-8',
                'badgeVariant' => 'light-primary',
            ],
            [
                'key' => 'posting_failures',
                'label' => $vasAccountingUtil->metricLabel('posting_failures'),
                'value' => number_format((int) ($metrics['postingFailures'] ?? 0)),
                'delta' => 0,
                'direction' => 'flat',
                'hint' => __('vasaccounting::lang.views.dashboard.metrics.posting_failures'),
                'icon' => 'ki-outline ki-shield-cross',
                'badgeVariant' => 'light-danger',
            ],
            [
                'key' => 'inventory_value',
                'label' => $vasAccountingUtil->metricLabel('inventory_value'),
                'value' => number_format((float) ($inventoryTotals['inventory_value'] ?? 0), 2),
                'delta' => 0,
                'direction' => 'flat',
                'hint' => __('vasaccounting::lang.views.dashboard.metrics.inventory_value'),
                'icon' => 'ki-outline ki-package',
                'badgeVariant' => 'light-success',
            ],
            [
                'key' => 'posted_this_month',
                'label' => $vasAccountingUtil->metricLabel('posted_this_month'),
                'value' => number_format((int) ($metrics['postedThisMonth'] ?? 0)),
                'delta' => 0,
                'direction' => 'flat',
                'hint' => __('vasaccounting::lang.views.dashboard.metrics.posted_this_month'),
                'icon' => 'ki-outline ki-chart-line-up-2',
                'badgeVariant' => 'light-info',
            ],
        ];

        $failureWidgetItems = $failureWidgetItems ?? [];

        $trendToolbar = '
            <select id="vas-dashboard-range" class="form-select form-select-solid form-select-sm w-140px">
                <option value="month">' . __('vasaccounting::lang.views.dashboard.chart.ranges.month') . '</option>
                <option value="quarter">' . __('vasaccounting::lang.views.dashboard.chart.ranges.quarter') . '</option>
                <option value="year" selected>' . __('vasaccounting::lang.views.dashboard.chart.ranges.year') . '</option>
            </select>
        ';
    @endphp

    @include('vasaccounting::partials.header', [
        'title' => __('vasaccounting::lang.dashboard'),
        'subtitle' => data_get($vasAccountingPageMeta ?? [], 'subtitle'),
        'actions' => $dashboardActions,
    ])

    @if (!empty($autoBootstrapped))
        <div class="alert alert-success d-flex align-items-start gap-3 mb-8">
            <i class="fas fa-check-circle mt-1"></i>
            <div>
                <div class="fw-bold">{{ __('vasaccounting::lang.auto_bootstrap_title') }}</div>
                <div class="text-muted">{{ __('vasaccounting::lang.auto_bootstrap_body') }}</div>
            </div>
        </div>
    @endif

    <div class="card card-flush mb-8">
        <div class="card-body py-6">
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-5">
                <div>
                    <div class="text-gray-900 fw-bold fs-4 mb-1">{{ __('vasaccounting::lang.views.dashboard.snapshot.title') }}</div>
                    <div class="text-muted fs-7">{{ __('vasaccounting::lang.views.dashboard.snapshot.subtitle') }}</div>
                </div>
                <div class="d-flex flex-wrap gap-3">
                    <a href="{{ route('vasaccounting.reports.index') }}" class="btn btn-light-primary btn-sm">{{ $vasAccountingUtil->actionLabel('open_reports') }}</a>
                    <a href="{{ route('vasaccounting.vouchers.index') }}" class="btn btn-light btn-sm">{{ __('vasaccounting::lang.views.dashboard.snapshot.voucher_queue') }}</a>
                </div>
            </div>
        </div>
    </div>

    <div id="vas-dashboard-kpis" class="mb-8">
        @include('vasaccounting::partials.workspace.kpi_strip', ['cards' => $initialKpiCards])
    </div>

    <div class="row g-5 g-xl-10 mb-8">
        <div class="col-xl-8">
            @include('vasaccounting::partials.workspace.chart_card', [
                'title' => __('vasaccounting::lang.views.dashboard.chart.title'),
                'subtitle' => __('vasaccounting::lang.views.dashboard.chart.subtitle'),
                'chartId' => 'vas-dashboard-trend-chart',
                'toolbar' => $trendToolbar,
                'chartHeight' => '340px',
            ])
        </div>
        <div class="col-xl-4">
            <div id="vas-dashboard-failures-widget">
                @include('vasaccounting::partials.workspace.side_widget', [
                    'title' => __('vasaccounting::lang.views.dashboard.operations_board.title'),
                    'subtitle' => __('vasaccounting::lang.views.dashboard.operations_board.blockers_body'),
                    'listId' => 'vas-dashboard-failures-list',
                    'items' => $failureWidgetItems,
                ])
            </div>
        </div>
    </div>

    <div class="card card-flush mb-8">
        <div class="card-header align-items-center py-5 gap-2 gap-md-5">
            <div class="card-title d-flex flex-column">
                <span>{{ __('vasaccounting::lang.views.dashboard.recent_vouchers.title') }}</span>
                @if (!empty($selectedLocationId))
                    <span class="text-muted fw-semibold fs-8 mt-1">{{ __('vasaccounting::lang.views.dashboard.recent_vouchers.filtered_branch') }}</span>
                @endif
            </div>
            <div class="card-toolbar">
                <a href="{{ route('vasaccounting.vouchers.index') }}" class="btn btn-light-primary btn-sm">{{ $vasAccountingUtil->actionLabel('open_register') }}</a>
            </div>
        </div>
        <div class="card-body pt-0">
            @include('vasaccounting::partials.workspace.table_toolbar', [
                'searchId' => 'vas-dashboard-voucher-search',
                'actions' => [
                    ['label' => $vasAccountingUtil->actionLabel('open_reports'), 'url' => route('vasaccounting.reports.index'), 'style' => 'light-primary', 'method' => 'GET'],
                ],
            ])
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-6 gy-5" id="vas-dashboard-vouchers-table">
                    <thead>
                        <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                            <th>{{ __('vasaccounting::lang.views.dashboard.recent_vouchers.table.voucher') }}</th>
                            <th>{{ __('vasaccounting::lang.views.dashboard.recent_vouchers.table.type') }}</th>
                            <th>{{ __('vasaccounting::lang.views.dashboard.recent_vouchers.table.module_area') }}</th>
                            <th>{{ __('vasaccounting::lang.views.dashboard.recent_vouchers.table.posting_date') }}</th>
                            <th class="text-end">{{ __('vasaccounting::lang.views.dashboard.recent_vouchers.table.total_amount') }}</th>
                            <th>{{ __('vasaccounting::lang.views.dashboard.recent_vouchers.table.status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentVouchers as $voucher)
                            <tr>
                                <td><a href="{{ route('vasaccounting.vouchers.show', $voucher->id) }}" class="text-gray-900 fw-semibold">{{ $voucher->voucher_no }}</a></td>
                                <td>{{ $vasAccountingUtil->voucherTypeLabel((string) $voucher->voucher_type) }}</td>
                                <td>{{ $vasAccountingUtil->moduleAreaLabel((string) ($voucher->module_area ?: 'accounting')) }}</td>
                                <td>{{ optional($voucher->posting_date)->format('Y-m-d') ?: $voucher->posting_date }}</td>
                                <td class="text-end">{{ number_format((float) $voucher->total_debit, 2) }}</td>
                                <td><span class="badge badge-light-primary">{{ $vasAccountingUtil->documentStatusLabel((string) $voucher->status) }}</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-muted">{{ __('vasaccounting::lang.views.dashboard.recent_vouchers.empty') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card card-flush mt-8">
        <div class="card-header">
            <div class="card-title">{{ __('vasaccounting::lang.views.dashboard.period_watchlist.title') }}</div>
            <div class="card-toolbar">
                <a href="{{ route('vasaccounting.periods.index') }}" class="btn btn-light btn-sm">{{ $vasAccountingUtil->actionLabel('manage_periods') }}</a>
            </div>
        </div>
        <div class="card-body pt-0">
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-6 gy-5" id="vas-dashboard-periods-table">
                    <thead>
                        <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                            <th>{{ __('vasaccounting::lang.views.dashboard.period_watchlist.table.period') }}</th>
                            <th>{{ __('vasaccounting::lang.views.dashboard.period_watchlist.table.range') }}</th>
                            <th>{{ __('vasaccounting::lang.views.dashboard.period_watchlist.table.status') }}</th>
                            <th class="text-end">{{ __('vasaccounting::lang.views.dashboard.period_watchlist.table.close_center') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($periods as $period)
                            <tr>
                                <td class="text-gray-900 fw-semibold">{{ $vasAccountingUtil->localizedPeriodName($period->name) }}</td>
                                <td>{{ optional($period->start_date)->format('Y-m-d') ?: $period->start_date }} - {{ optional($period->end_date)->format('Y-m-d') ?: $period->end_date }}</td>
                                <td>
                                    <span class="badge {{ $period->status === 'closed' ? 'badge-light-danger' : ($period->status === 'soft_locked' ? 'badge-light-warning' : 'badge-light-success') }}">
                                        {{ $vasAccountingUtil->periodStatusLabel((string) $period->status) }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('vasaccounting.closing.index') }}" class="btn btn-light-primary btn-sm">{{ $vasAccountingUtil->actionLabel('open') }}</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-muted">{{ __('vasaccounting::lang.views.dashboard.period_watchlist.empty') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@section('javascript')
    @include('vasaccounting::partials.workspace_scripts')
    <script>
        $(document).ready(function () {
            const locationId = @json($selectedLocationId);
            const kpiUrl = @json(route('vasaccounting.ui.dashboard.kpis'));
            const trendUrl = @json(route('vasaccounting.ui.dashboard.trends'));
            const failureUrl = @json(route('vasaccounting.ui.dashboard.failures'));

            const buildUrl = function (baseUrl, params) {
                const query = new URLSearchParams();
                Object.keys(params || {}).forEach(function (key) {
                    const value = params[key];
                    if (value !== null && value !== undefined && value !== '') {
                        query.set(key, value);
                    }
                });

                const queryString = query.toString();
                return queryString ? (baseUrl + '?' + queryString) : baseUrl;
            };

            const voucherTable = window.VasWorkspace?.initLocalDataTable('#vas-dashboard-vouchers-table', {
                pageLength: 8,
                order: [[3, 'desc']]
            });

            if (voucherTable) {
                $('#vas-dashboard-voucher-search').on('keyup', function () {
                    voucherTable.search(this.value).draw();
                });
            }

            window.VasWorkspace?.initLocalDataTable('#vas-dashboard-periods-table', {
                pageLength: 6,
                order: []
            });

            const loadKpis = function () {
                $.getJSON(buildUrl(kpiUrl, { location_id: locationId }), function (payload) {
                    window.VasWorkspace?.renderKpiStrip('#vas-dashboard-kpis', payload);
                });
            };

            const loadTrends = function (range) {
                $.getJSON(buildUrl(trendUrl, { location_id: locationId, range: range }), function (payload) {
                    window.VasWorkspace?.renderTrendChart('vas-dashboard-trend-chart', payload);
                });
            };

            const loadFailures = function () {
                $.getJSON(buildUrl(failureUrl, { location_id: locationId }), function (payload) {
                    window.VasWorkspace?.renderFailureList('#vas-dashboard-failures-list', payload);
                });
            };

            $('#vas-dashboard-range').on('change', function () {
                loadTrends(this.value);
            });

            loadKpis();
            loadTrends($('#vas-dashboard-range').val());
            loadFailures();
        });
    </script>
@endsection
