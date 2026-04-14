@extends('layouts.app')
@section('title', __('home.home'))

@section('content')
@php
    $dashboard_currency = session('currency', []);
    $dashboard_decimal_separator = $dashboard_currency['decimal_separator'] ?? '.';
    $dashboard_thousand_separator = $dashboard_currency['thousand_separator'] ?? ',';

    $dashboard_compact_number = function ($number, $compact_decimals = 2) use ($dashboard_decimal_separator, $dashboard_thousand_separator) {
        $value = (float) $number;
        $abs = abs($value);
        $divisor = 1;
        $suffix = '';

        if ($abs >= 1000000000000) {
            $divisor = 1000000000000;
            $suffix = 'T';
        } elseif ($abs >= 1000000000) {
            $divisor = 1000000000;
            $suffix = 'B';
        } elseif ($abs >= 1000000) {
            $divisor = 1000000;
            $suffix = 'M';
        } elseif ($abs >= 1000) {
            $divisor = 1000;
            $suffix = 'K';
        }

        if ($divisor === 1) {
            return number_format($value, 0, $dashboard_decimal_separator, $dashboard_thousand_separator);
        }

        $formatted = number_format(
            $value / $divisor,
            $compact_decimals,
            $dashboard_decimal_separator,
            $dashboard_thousand_separator
        );

        $formatted = rtrim($formatted, '0');
        $formatted = rtrim($formatted, $dashboard_decimal_separator);

        return $formatted.$suffix;
    };

    $dashboard_primary_amount = function ($number) use ($dashboard_compact_number, $dashboard_decimal_separator, $dashboard_thousand_separator) {
        $value = (float) $number;
        if (abs($value) >= 1000000) {
            return $dashboard_compact_number($value, 2);
        }

        return number_format(
            $value,
            0,
            $dashboard_decimal_separator,
            $dashboard_thousand_separator
        );
    };

    $dashboard_money_amount = function ($number) use ($dashboard_compact_number) {
        $value = (float) $number;
        if (abs($value) >= 1000000) {
            return $dashboard_compact_number($value, 2);
        }

        return num_format_value($value);
    };
@endphp
    <div class="home-dashboard-root" data-home-dashboard-root>
    <div class="row gx-5 gx-xl-10 mb-5">
        <div class="col-12">
            <div class="card card-flush" id="dashboard-date-filter">
                <div class="card-header align-items-center py-5 flex-wrap">
                    <div class="card-title d-flex flex-column">
                        <span class="fs-2 fw-bold text-gray-900">Dashboard Date Filter</span>
                        <span class="text-gray-500 fw-semibold fs-6" data-sales-chart-range-label>{{ data_get($dashboardMeta, 'range.label', 'This month') }}</span>
                    </div>
                    <div class="card-toolbar">
                        <button class="btn btn-light-primary btn-sm" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
                            Select Range
                        </button>
                        <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg-light-primary fw-semibold w-275px" data-kt-menu="true">
                            <div class="menu-item px-3">
                                <div class="menu-content fs-6 text-gray-900 fw-bold px-3 py-4">Filter Range</div>
                            </div>
                            <div class="separator mb-3 opacity-75"></div>
                            <div class="menu-item px-3">
                                <a href="#" class="menu-link px-3" data-sales-chart-range="week">This week</a>
                            </div>
                            <div class="menu-item px-3">
                                <a href="#" class="menu-link px-3" data-sales-chart-range="month">This month</a>
                            </div>
                            <div class="menu-item px-3">
                                <a href="#" class="menu-link px-3" data-sales-chart-range="quarter">This quarter</a>
                            </div>
                            <div class="menu-item px-3">
                                <a href="#" class="menu-link px-3" data-sales-chart-range="year">This year</a>
                            </div>
                            <div class="menu-item px-3">
                                <a href="#" class="menu-link px-3" data-sales-chart-range="custom">Custom date</a>
                            </div>
                            <div class="separator mt-3 opacity-75"></div>
                            <div class="menu-item px-3">
                                <div class="menu-content px-3 py-3">
                                    <div class="mb-3">
                                        <input type="text" class="form-control form-control-sm" data-sales-chart-custom-range placeholder="Select date range" />
                                    </div>
                                    <input type="hidden" data-sales-chart-start-date value="{{ data_get($dashboardMeta, 'range.current_start') }}">
                                    <input type="hidden" data-sales-chart-end-date value="{{ data_get($dashboardMeta, 'range.current_end') }}">
                                    <button type="button" class="btn btn-primary btn-sm px-4 w-100" data-sales-chart-apply-custom>Apply</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--begin::Row-->
    <div class="row gx-5 gx-xl-10 mb-xl-10">
        <!--begin::Col-->
        <div class="col-md-6 col-lg-6 col-xl-6 col-xxl-3 mb-10">
            <!--begin::Card total sell -->
            <div class="card card-flush h-md-50 mb-5 mb-xl-10" data-dashboard-widget="sales-summary">
                <!--begin::Header-->
                <div class="card-header pt-5">
                    <!--begin::Title-->
                    <div class="card-title d-flex flex-column">
                        <!--begin::Info-->
                        <div class="d-flex align-items-center">
                            <!--begin::Currency-->
                            <span class="fs-4 fw-semibold text-gray-500 me-1 align-self-start">{{ data_get($dashboardMeta, 'currency.symbol', '$') }}</span>
                            <!--end::Currency-->
                            <!--begin::Amount-->
                            <span class="fs-2hx fw-bold text-gray-900 me-2 lh-1 ls-n2">{{ $dashboard_primary_amount(data_get($dashboardKpis, 'sales_summary.value', 0)) }}</span>
                            <!--end::Amount-->
                            <!--begin::Badge-->
                            <span class="badge {{ data_get($dashboardKpis, 'sales_summary.is_positive_delta', true) ? 'badge-light-success' : 'badge-light-danger' }} fs-base">
                            <i class="ki-duotone {{ data_get($dashboardKpis, 'sales_summary.is_positive_delta', true) ? 'ki-arrow-up text-success' : 'ki-arrow-down text-danger' }} fs-5 ms-n1">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>{{ num_format_value(data_get($dashboardKpis, 'sales_summary.delta_percent', 0)) }}%</span>
                            <!--end::Badge-->
                        </div>
                        <!--end::Info-->
                        <!--begin::Subtitle-->
                        <span class="text-gray-500 pt-1 fw-semibold fs-6">Total Sales</span>
                        <!--end::Subtitle-->
                    </div>
                    <!--end::Title-->
                </div>
                <!--end::Header-->
                <!--begin::Card body-->
                <div class="card-body pt-2 pb-4 d-flex align-items-center">
                    <!--begin::Chart-->
                    <div class="d-flex flex-center me-5 pt-2">
                        <div id="hm_dashboard_sales_summary_chart" style="min-width: 70px; min-height: 70px" data-kt-size="70" data-kt-line="11"></div>
                    </div>
                    <!--end::Chart-->
                    <!--begin::Labels-->
                    <div class="d-flex flex-column content-justify-center w-100">
                        <!--begin::Label-->
                        <div class="d-flex fs-6 fw-semibold align-items-center">
                            <!--begin::Bullet-->
                            <div class="bullet w-8px h-6px rounded-2 bg-danger me-3"></div>
                            <!--end::Bullet-->
                            <!--begin::Label-->
                            <div class="text-gray-500 flex-grow-1 me-4">{{ data_get($dashboardKpis, 'sales_summary.breakdown.0.label', __('home.total_purchase')) }}</div>
                            <!--end::Label-->
                            <!--begin::Stats-->
                            <div class="fw-bolder text-gray-700 text-xxl-end">{{ $dashboard_money_amount(data_get($dashboardKpis, 'sales_summary.breakdown.0.value', 0)) }}</div>
                            <!--end::Stats-->
                        </div>
                        <!--end::Label-->
                        <!--begin::Label-->
                        <div class="d-flex fs-6 fw-semibold align-items-center my-3">
                            <!--begin::Bullet-->
                            <div class="bullet w-8px h-6px rounded-2 bg-primary me-3"></div>
                            <!--end::Bullet-->
                            <!--begin::Label-->
                            <div class="text-gray-500 flex-grow-1 me-4">{{ data_get($dashboardKpis, 'sales_summary.breakdown.1.label', __('home.invoice_due')) }}</div>
                            <!--end::Label-->
                            <!--begin::Stats-->
                            <div class="fw-bolder text-gray-700 text-xxl-end">{{ $dashboard_money_amount(data_get($dashboardKpis, 'sales_summary.breakdown.1.value', 0)) }}</div>
                            <!--end::Stats-->
                        </div>
                        <!--end::Label-->
                        <!--begin::Label-->
                        <div class="d-flex fs-6 fw-semibold align-items-center">
                            <!--begin::Bullet-->
                            <div class="bullet w-8px h-6px rounded-2 me-3" style="background-color: #E4E6EF"></div>
                            <!--end::Bullet-->
                            <!--begin::Label-->
                            <div class="text-gray-500 flex-grow-1 me-4">{{ data_get($dashboardKpis, 'sales_summary.breakdown.2.label', __('lang_v1.total_sell_return')) }}</div>
                            <!--end::Label-->
                            <!--begin::Stats-->
                            <div class="fw-bolder text-gray-700 text-xxl-end">{{ $dashboard_money_amount(data_get($dashboardKpis, 'sales_summary.breakdown.2.value', 0)) }}</div>
                            <!--end::Stats-->
                        </div>
                        <!--end::Label-->
                    </div>
                    <!--end::Labels-->
                </div>
                <!--end::Card body-->
            </div>
            <!--end::Card total sell-->
            <!--begin::Card widget 5-->
            <div class="card card-flush h-md-50 mb-xl-10" data-dashboard-widget="orders">
                <!--begin::Header-->
                <div class="card-header pt-5">
                    <!--begin::Title-->
                    <div class="card-title d-flex flex-column">
                        <!--begin::Info-->
                        <div class="d-flex align-items-center">
                            <!--begin::Amount-->
                            <span class="fs-2hx fw-bold text-gray-900 me-2 lh-1 ls-n2">{{ num_format_value(data_get($dashboardKpis, 'orders_this_month.count', 0)) }}</span>
                            <!--end::Amount-->
                            <!--begin::Badge-->
                            <span class="badge {{ data_get($dashboardKpis, 'orders_this_month.is_positive_delta', true) ? 'badge-light-success' : 'badge-light-danger' }} fs-base">
                            <i class="ki-duotone {{ data_get($dashboardKpis, 'orders_this_month.is_positive_delta', true) ? 'ki-arrow-up text-success' : 'ki-arrow-down text-danger' }} fs-5 ms-n1">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>{{ num_format_value(data_get($dashboardKpis, 'orders_this_month.delta_percent', 0)) }}%</span>
                            <!--end::Badge-->
                        </div>
                        <!--end::Info-->
                        <!--begin::Subtitle-->
                        <span class="text-gray-500 pt-1 fw-semibold fs-6" data-dashboard-orders-range-label>Orders {{ data_get($dashboardKpis, 'orders_this_month.range_label', 'This month') }}</span>
                        <!--end::Subtitle-->
                    </div>
                    <!--end::Title-->
                </div>
                <!--end::Header-->
                <!--begin::Card body-->
                <div class="card-body d-flex align-items-end pt-0">
                    <!--begin::Progress-->
                    <div class="d-flex align-items-center flex-column mt-3 w-100">
                        <div class="d-flex justify-content-between w-100 mt-auto mb-2">
                            <span class="fw-bolder fs-6 text-gray-900">{{ num_format_value(data_get($dashboardKpis, 'orders_this_month.remaining', 0)) }} to Goal</span>
                            <span class="fw-bold fs-6 text-gray-500">{{ num_format_value(data_get($dashboardKpis, 'orders_this_month.progress_percent', 0)) }}%</span>
                        </div>
                        <div class="h-8px mx-3 w-100 bg-light-success rounded">
                            <div class="bg-success rounded h-8px" role="progressbar" style="width: {{ data_get($dashboardKpis, 'orders_this_month.progress_percent', 0) }}%;" aria-valuenow="{{ data_get($dashboardKpis, 'orders_this_month.progress_percent', 0) }}" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                    <!--end::Progress-->
                </div>
                <!--end::Card body-->
            </div>
            <!--end::Card widget 5-->
        </div>
        <!--end::Col-->
        <!--begin::Col-->
        <div class="col-md-6 col-lg-6 col-xl-6 col-xxl-3 mb-10">
            <!--begin::Card widget 6-->
            <div class="card card-flush h-md-50 mb-5 mb-xl-10" data-dashboard-widget="average-daily-sales">
                <!--begin::Header-->
                <div class="card-header pt-5">
                    <!--begin::Title-->
                    <div class="card-title d-flex flex-column">
                        <!--begin::Info-->
                        <div class="d-flex align-items-center">
                            <!--begin::Currency-->
                            <span class="fs-4 fw-semibold text-gray-500 me-1 align-self-start">{{ data_get($dashboardMeta, 'currency.symbol', '$') }}</span>
                            <!--end::Currency-->
                            <!--begin::Amount-->
                            <span class="fs-2hx fw-bold text-gray-900 me-2 lh-1 ls-n2">{{ $dashboard_primary_amount(data_get($dashboardKpis, 'average_daily_sales.value', 0)) }}</span>
                            <!--end::Amount-->
                            <!--begin::Badge-->
                            <span class="badge {{ data_get($dashboardKpis, 'average_daily_sales.is_positive_delta', true) ? 'badge-light-success' : 'badge-light-danger' }} fs-base">
                            <i class="ki-duotone {{ data_get($dashboardKpis, 'average_daily_sales.is_positive_delta', true) ? 'ki-arrow-up text-success' : 'ki-arrow-down text-danger' }} fs-5 ms-n1">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>{{ num_format_value(data_get($dashboardKpis, 'average_daily_sales.delta_percent', 0)) }}%</span>
                            <!--end::Badge-->
                        </div>
                        <!--end::Info-->
                        <!--begin::Subtitle-->
                        <span class="text-gray-500 pt-1 fw-semibold fs-6" data-dashboard-average-range-label>Average Daily Sales ({{ data_get($dashboardKpis, 'average_daily_sales.range_label', 'This month') }})</span>
                        <!--end::Subtitle-->
                    </div>
                    <!--end::Title-->
                </div>
                <!--end::Header-->
                <!--begin::Card body-->
                <div class="card-body d-flex align-items-end px-0 pb-0">
                    <!--begin::Chart-->
                    <div id="hm_dashboard_average_daily_chart" class="w-100" style="height: 80px"></div>
                    <!--end::Chart-->
                </div>
                <!--end::Card body-->
            </div>
            <!--end::Card widget 6-->
            <!--begin::Card widget 7-->
            <div class="card card-flush h-md-50 mb-xl-10" data-dashboard-widget="new-customers">
                <!--begin::Header-->
                <div class="card-header pt-5">
                    <!--begin::Title-->
                    <div class="card-title d-flex flex-column">
                        <!--begin::Amount-->
                        <span class="fs-2hx fw-bold text-gray-900 me-2 lh-1 ls-n2">{{ num_format_value(data_get($dashboardKpis, 'new_customers_this_month.count', 0)) }}</span>
                        <!--end::Amount-->
                        <!--begin::Subtitle-->
                        <span class="text-gray-500 pt-1 fw-semibold fs-6" data-dashboard-new-customers-range-label>New Customers {{ data_get($dashboardKpis, 'new_customers_this_month.range_label', 'This month') }}</span>
                        <!--end::Subtitle-->
                    </div>
                    <!--end::Title-->
                </div>
                <!--end::Header-->
                <!--begin::Card body-->
                <div class="card-body d-flex flex-column justify-content-end pe-0">
                    <!--begin::Title-->
                    <span class="fs-6 fw-bolder text-gray-800 d-block mb-2">Top Customers</span>
                    <!--end::Title-->
                    <!--begin::Users group-->
                    <div class="symbol-group symbol-hover flex-nowrap">
                        @foreach (data_get($dashboardKpis, 'new_customers_this_month.heroes', []) as $hero)
                            <div class="symbol symbol-35px symbol-circle" data-bs-toggle="tooltip" title="{{ $hero['name'] ?? '' }}">
                                <span class="symbol-label bg-light-primary text-primary fw-bold">{{ $hero['initials'] ?? 'NA' }}</span>
                            </div>
                        @endforeach
                    </div>
                    <!--end::Users group-->
                </div>
                <!--end::Card body-->
            </div>
            <!--end::Card widget 7-->
        </div>
        <!--end::Col-->
        <!--begin::Col-->
        <div class="col-lg-12 col-xl-12 col-xxl-6 mb-5 mb-xl-0">
            <!--begin::Chart widget 3-->
            <div class="card card-flush overflow-hidden h-md-100" data-dashboard-widget="sales-chart">
                <!--begin::Header-->
                <div class="card-header py-5">
                    <!--begin::Title-->
                    <h3 class="card-title align-items-start flex-column">
                        <span class="card-label fw-bold text-gray-900">Sales</span>
                        <span class="text-gray-500 mt-1 fw-semibold fs-6" data-sales-chart-card-range-label>{{ data_get($dashboardKpis, 'sales_this_month.range_label', 'This month') }}</span>
                    </h3>
                    <!--end::Title-->
                    <!--begin::Toolbar-->
                    <div class="card-toolbar">
                        <a href="#dashboard-date-filter" class="btn btn-light-primary btn-sm">Use Global Filter</a>
                    </div>
                    <!--end::Toolbar-->
                </div>
                <!--end::Header-->
                <!--begin::Card body-->
                <div class="card-body d-flex justify-content-between flex-column pb-1 px-0">
                    <!--begin::Statistics-->
                    <div class="px-9 mb-5">
                        <!--begin::Statistics-->
                        <div class="d-flex mb-2">
                            <span class="fs-4 fw-semibold text-gray-500 me-1">{{ data_get($dashboardMeta, 'currency.symbol', '$') }}</span>
                            <span class="fs-2hx fw-bold text-gray-800 me-2 lh-1 ls-n2">{{ $dashboard_primary_amount(data_get($dashboardKpis, 'sales_this_month.value', 0)) }}</span>
                        </div>
                        <!--end::Statistics-->
                        <!--begin::Description-->
                        <span class="fs-6 fw-semibold text-gray-500">Another {{ $dashboard_money_amount(data_get($dashboardKpis, 'sales_this_month.goal_gap', 0)) }} to Goal</span>
                        <!--end::Description-->
                    </div>
                    <!--end::Statistics-->
                    <!--begin::Chart-->
                    <div id="hm_dashboard_sales_chart" class="min-h-auto ps-4 pe-6" style="height: 300px"></div>
                    <!--end::Chart-->
                </div>
                <!--end::Card body-->
            </div>
            <!--end::Chart widget 3-->
        </div>
        <!--end::Col-->
    </div>
    <!--end::Row-->
    <!--begin::Row-->
    <div class="row gy-5 g-xl-10">
        <!--begin::Col-->
        <div class="col-xl-6 mb-xl-10">
            <!--begin::Table widget 2-->
            <div class="card h-md-100" data-dashboard-widget="recent-orders">
                <!--begin::Header-->
                <div class="card-header align-items-center border-0">
                    <!--begin::Title-->
                    <h3 class="fw-bold text-gray-900 m-0">Recent Orders</h3>
                    <!--end::Title-->
                    <!--begin::Menu-->
                    <button class="btn btn-icon btn-color-gray-500 btn-active-color-primary justify-content-end" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end" data-kt-menu-overflow="true">
                        <i class="ki-duotone ki-dots-square fs-1">
                            <span class="path1"></span>
                            <span class="path2"></span>
                            <span class="path3"></span>
                            <span class="path4"></span>
                        </i>
                    </button>
                    <!--begin::Menu 2-->
                    <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg-light-primary fw-semibold w-200px" data-kt-menu="true">
                        <!--begin::Menu item-->
                        <div class="menu-item px-3">
                            <div class="menu-content fs-6 text-gray-900 fw-bold px-3 py-4">Quick Actions</div>
                        </div>
                        <!--end::Menu item-->
                        <!--begin::Menu separator-->
                        <div class="separator mb-3 opacity-75"></div>
                        <!--end::Menu separator-->
                        <!--begin::Menu item-->
                        <div class="menu-item px-3">
                            <a href="#" class="menu-link px-3">New Ticket</a>
                        </div>
                        <!--end::Menu item-->
                        <!--begin::Menu item-->
                        <div class="menu-item px-3">
                            <a href="#" class="menu-link px-3">New Customer</a>
                        </div>
                        <!--end::Menu item-->
                        <!--begin::Menu item-->
                        <div class="menu-item px-3" data-kt-menu-trigger="hover" data-kt-menu-placement="right-start">
                            <!--begin::Menu item-->
                            <a href="#" class="menu-link px-3">
                                <span class="menu-title">New Group</span>
                                <span class="menu-arrow"></span>
                            </a>
                            <!--end::Menu item-->
                            <!--begin::Menu sub-->
                            <div class="menu-sub menu-sub-dropdown w-175px py-4">
                                <!--begin::Menu item-->
                                <div class="menu-item px-3">
                                    <a href="#" class="menu-link px-3">Admin Group</a>
                                </div>
                                <!--end::Menu item-->
                                <!--begin::Menu item-->
                                <div class="menu-item px-3">
                                    <a href="#" class="menu-link px-3">Staff Group</a>
                                </div>
                                <!--end::Menu item-->
                                <!--begin::Menu item-->
                                <div class="menu-item px-3">
                                    <a href="#" class="menu-link px-3">Member Group</a>
                                </div>
                                <!--end::Menu item-->
                            </div>
                            <!--end::Menu sub-->
                        </div>
                        <!--end::Menu item-->
                        <!--begin::Menu item-->
                        <div class="menu-item px-3">
                            <a href="#" class="menu-link px-3">New Contact</a>
                        </div>
                        <!--end::Menu item-->
                        <!--begin::Menu separator-->
                        <div class="separator mt-3 opacity-75"></div>
                        <!--end::Menu separator-->
                        <!--begin::Menu item-->
                        <div class="menu-item px-3">
                            <div class="menu-content px-3 py-3">
                                <a class="btn btn-primary btn-sm px-4" href="#">Generate Reports</a>
                            </div>
                        </div>
                        <!--end::Menu item-->
                    </div>
                    <!--end::Menu 2-->
                    <!--end::Menu-->
                </div>
                <!--end::Header-->
                <!--begin::Body-->
                <div class="card-body pt-2">
                    <!--begin::Nav-->
                    @php
                        $recentTabs = collect($dashboardRecentTabs ?? [])->values()->take(5);
                        $recentTabIcons = [
                            asset('assets/media/svg/products-categories/t-shirt.svg'),
                            asset('assets/media/svg/products-categories/gaming.svg'),
                            asset('assets/media/svg/products-categories/watch.svg'),
                            asset('assets/media/svg/products-categories/gloves.svg'),
                            asset('assets/media/svg/products-categories/shoes.svg'),
                        ];
                        $activeRecentTabIndex = null;
                        for ($recentTabLoopIndex = 0; $recentTabLoopIndex < 5; $recentTabLoopIndex++) {
                            $recentTabLabel = trim((string) data_get($recentTabs, $recentTabLoopIndex . '.label', ''));
                            if ($recentTabLabel !== '') {
                                $activeRecentTabIndex = $recentTabLoopIndex;
                                break;
                            }
                        }
                    @endphp
                    <ul class="nav nav-pills nav-pills-custom mb-3 {{ $activeRecentTabIndex === null ? 'd-none' : '' }}" data-dashboard-recent-tabs-nav>
                        @for ($tabIndex = 0; $tabIndex < 5; $tabIndex++)
                            @php
                                $tabLabel = trim((string) data_get($recentTabs, $tabIndex . '.label', ''));
                                $tabVisible = $tabLabel !== '';
                                $tabIcon = $recentTabIcons[$tabIndex] ?? $recentTabIcons[0];
                            @endphp
                            <li class="nav-item mb-3 {{ $tabIndex < 4 ? 'me-3 me-lg-6' : '' }} {{ $tabVisible ? '' : 'd-none' }}" data-dashboard-recent-tab-nav-item="{{ $tabIndex + 1 }}">
                                <a class="nav-link d-flex justify-content-between flex-column flex-center overflow-hidden w-80px h-85px py-4 {{ $activeRecentTabIndex === $tabIndex ? 'active' : '' }}" data-bs-toggle="pill" href="#kt_stats_widget_2_tab_{{ $tabIndex + 1 }}">
                                    <div class="nav-icon">
                                        <img alt="" src="{{ $tabIcon }}" class="" />
                                    </div>
                                    <span class="nav-text text-gray-700 fw-bold fs-6 lh-1">{{ $tabLabel }}</span>
                                    <span class="bullet-custom position-absolute bottom-0 w-100 h-4px bg-primary"></span>
                                </a>
                            </li>
                        @endfor
                    </ul>
                    <!--end::Nav-->
                    <!--begin::Tab Content-->
                    <div class="tab-content {{ $activeRecentTabIndex === null ? 'd-none' : '' }}" data-dashboard-recent-tabs-content>
                        @for ($tabIndex = 0; $tabIndex < 5; $tabIndex++)
                            @php
                                $tabLabel = trim((string) data_get($recentTabs, $tabIndex . '.label', ''));
                                $tabVisible = $tabLabel !== '';
                            @endphp
                            <div class="tab-pane fade {{ $activeRecentTabIndex === $tabIndex ? 'show active' : '' }} {{ $tabVisible ? '' : 'd-none' }}" id="kt_stats_widget_2_tab_{{ $tabIndex + 1 }}" data-dashboard-recent-tab-pane="{{ $tabIndex + 1 }}">
                                <div class="table-responsive">
                                    <table class="table table-row-dashed align-middle gs-0 gy-4 my-0">
                                        <thead>
                                            <tr class="fs-7 fw-bold text-gray-500 border-bottom-0">
                                                <th class="ps-0 w-50px">ITEM</th>
                                                <th class="min-w-125px"></th>
                                                <th class="text-end min-w-100px">QTY</th>
                                                <th class="pe-0 text-end min-w-100px">PRICE</th>
                                                <th class="pe-0 text-end min-w-100px">TOTAL PRICE</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse (data_get($recentTabs, $tabIndex . '.items', []) as $item)
                                                <tr>
                                                    <td>
                                                        <img src="{{ $item['image_url'] ?? asset('img/default.png') }}" class="w-50px ms-n1" alt="" />
                                                    </td>
                                                    <td class="ps-0">
                                                        <a href="javascript:void(0)" class="text-gray-800 fw-bold text-hover-primary mb-1 fs-6 text-start pe-0">{{ $item['product_name'] ?? '-' }}</a>
                                                        <span class="text-gray-500 fw-semibold fs-7 d-block text-start ps-0">Item: #{{ $item['item_code'] ?? '-' }}</span>
                                                    </td>
                                                    <td>
                                                        <span class="text-gray-800 fw-bold d-block fs-6 ps-0 text-end">x{{ num_format_value($item['qty'] ?? 0) }}</span>
                                                    </td>
                                                    <td class="text-end pe-0">
                                                        <span class="text-gray-800 fw-bold d-block fs-6">@format_currency($item['price'] ?? 0)</span>
                                                    </td>
                                                    <td class="text-end pe-0">
                                                        <span class="text-gray-800 fw-bold d-block fs-6">@format_currency($item['total_price'] ?? 0)</span>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="5" class="text-center text-gray-500">No data</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endfor
                    </div>
                    <div class="text-center text-gray-500 py-10 {{ $activeRecentTabIndex === null ? '' : 'd-none' }}" data-dashboard-recent-tabs-empty>No category data available for current filter</div>
                    <!--end::Tab Content-->
                </div>
                <!--end: Card Body-->
            </div>
            <!--end::Table widget 2-->
        </div>
        <!--end::Col-->
        <!--begin::Col-->
        <div class="col-xl-6 mb-5 mb-xl-10">
            <!--begin::Chart widget 4-->
            <div class="card card-flush overflow-hidden h-md-100" data-dashboard-widget="discounted-sales">
                <!--begin::Header-->
                <div class="card-header py-5">
                    <!--begin::Title-->
                    <h3 class="card-title align-items-start flex-column">
                        <span class="card-label fw-bold text-gray-900">Discounted Product Sales</span>
                        <span class="text-gray-500 mt-1 fw-semibold fs-6" data-dashboard-discounted-header-range-label>Across {{ data_get($dashboardKpis, 'discounted_product_sales.range_label', 'This month') }}</span>
                    </h3>
                    <!--end::Title-->
                    <!--begin::Toolbar-->
                    <div class="card-toolbar">
                        <!--begin::Menu-->
                        <button class="btn btn-icon btn-color-gray-500 btn-active-color-primary justify-content-end" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end" data-kt-menu-overflow="true">
                            <i class="ki-duotone ki-dots-square fs-1">
                                <span class="path1"></span>
                                <span class="path2"></span>
                                <span class="path3"></span>
                                <span class="path4"></span>
                            </i>
                        </button>
                        <!--begin::Menu 2-->
                        <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg-light-primary fw-semibold w-200px" data-kt-menu="true">
                            <!--begin::Menu item-->
                            <div class="menu-item px-3">
                                <div class="menu-content fs-6 text-gray-900 fw-bold px-3 py-4">Quick Actions</div>
                            </div>
                            <!--end::Menu item-->
                            <!--begin::Menu separator-->
                            <div class="separator mb-3 opacity-75"></div>
                            <!--end::Menu separator-->
                            <!--begin::Menu item-->
                            <div class="menu-item px-3">
                                <a href="#" class="menu-link px-3">New Ticket</a>
                            </div>
                            <!--end::Menu item-->
                            <!--begin::Menu item-->
                            <div class="menu-item px-3">
                                <a href="#" class="menu-link px-3">New Customer</a>
                            </div>
                            <!--end::Menu item-->
                            <!--begin::Menu item-->
                            <div class="menu-item px-3" data-kt-menu-trigger="hover" data-kt-menu-placement="right-start">
                                <!--begin::Menu item-->
                                <a href="#" class="menu-link px-3">
                                    <span class="menu-title">New Group</span>
                                    <span class="menu-arrow"></span>
                                </a>
                                <!--end::Menu item-->
                                <!--begin::Menu sub-->
                                <div class="menu-sub menu-sub-dropdown w-175px py-4">
                                    <!--begin::Menu item-->
                                    <div class="menu-item px-3">
                                        <a href="#" class="menu-link px-3">Admin Group</a>
                                    </div>
                                    <!--end::Menu item-->
                                    <!--begin::Menu item-->
                                    <div class="menu-item px-3">
                                        <a href="#" class="menu-link px-3">Staff Group</a>
                                    </div>
                                    <!--end::Menu item-->
                                    <!--begin::Menu item-->
                                    <div class="menu-item px-3">
                                        <a href="#" class="menu-link px-3">Member Group</a>
                                    </div>
                                    <!--end::Menu item-->
                                </div>
                                <!--end::Menu sub-->
                            </div>
                            <!--end::Menu item-->
                            <!--begin::Menu item-->
                            <div class="menu-item px-3">
                                <a href="#" class="menu-link px-3">New Contact</a>
                            </div>
                            <!--end::Menu item-->
                            <!--begin::Menu separator-->
                            <div class="separator mt-3 opacity-75"></div>
                            <!--end::Menu separator-->
                            <!--begin::Menu item-->
                            <div class="menu-item px-3">
                                <div class="menu-content px-3 py-3">
                                    <a class="btn btn-primary btn-sm px-4" href="#">Generate Reports</a>
                                </div>
                            </div>
                            <!--end::Menu item-->
                        </div>
                        <!--end::Menu 2-->
                        <!--end::Menu-->
                    </div>
                    <!--end::Toolbar-->
                </div>
                <!--end::Header-->
                <!--begin::Card body-->
                <div class="card-body d-flex justify-content-between flex-column pb-1 px-0">
                    <!--begin::Info-->
                    <div class="px-9 mb-5">
                        <!--begin::Statistics-->
                        <div class="d-flex align-items-center mb-2">
                            <!--begin::Currency-->
                            <span class="fs-4 fw-semibold text-gray-500 align-self-start me-1">{{ data_get($dashboardMeta, 'currency.symbol', '$') }}</span>
                            <!--end::Currency-->
                            <!--begin::Value-->
                            <span class="fs-2hx fw-bold text-gray-800 me-2 lh-1 ls-n2">{{ $dashboard_primary_amount(data_get($dashboardKpis, 'discounted_product_sales.value', 0)) }}</span>
                            <!--end::Value-->
                            <!--begin::Label-->
                            <span class="badge {{ data_get($dashboardKpis, 'discounted_product_sales.is_positive_delta', true) ? 'badge-light-success' : 'badge-light-danger' }} fs-base">
                            <i class="ki-duotone {{ data_get($dashboardKpis, 'discounted_product_sales.is_positive_delta', true) ? 'ki-arrow-up text-success' : 'ki-arrow-down text-danger' }} fs-5 ms-n1">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>{{ num_format_value(data_get($dashboardKpis, 'discounted_product_sales.delta_percent', 0)) }}%</span>
                            <!--end::Label-->
                        </div>
                        <!--end::Statistics-->
                        <!--begin::Description-->
                        <span class="fs-6 fw-semibold text-gray-500" data-dashboard-discounted-range-label>Total Discounted Sales {{ data_get($dashboardKpis, 'discounted_product_sales.range_label', 'This month') }}</span>
                        <!--end::Description-->
                    </div>
                    <!--end::Info-->
                    <!--begin::Chart-->
                    <div id="hm_dashboard_discounted_chart" class="min-h-auto ps-4 pe-6" style="height: 300px"></div>
                    <!--end::Chart-->
                </div>
                <!--end::Card body-->
            </div>
            <!--end::Chart widget 4-->
        </div>
        <!--end::Col-->
    </div>
    <!--end::Row-->
    <!--begin::Row-->
    <div class="row gy-5 g-xl-10">
        <!--begin::Col-->
        <div class="col-xl-4 mb-xl-10">
            <!--begin::Engage widget 1-->
            <div class="card h-md-100" dir="ltr">
                <!--begin::Body-->
                <div class="card-body d-flex flex-column flex-center">
                    <!--begin::Heading-->
                    <div class="mb-2">
                        <!--begin::Title-->
                        <h1 class="fw-semibold text-gray-800 text-center lh-lg">Have you tried 
                        <br />new 
                        <span class="fw-bolder">eCommerce App ?</span></h1>
                        <!--end::Title-->
                        <!--begin::Illustration-->
                        <div class="py-10 text-center">
                            <img src="assets/media/svg/illustrations/easy/2.svg" class="theme-light-show w-200px" alt="" />
                            <img src="assets/media/svg/illustrations/easy/2-dark.svg" class="theme-dark-show w-200px" alt="" />
                        </div>
                        <!--end::Illustration-->
                    </div>
                    <!--end::Heading-->
                    <!--begin::Links-->
                    <div class="text-center mb-1">
                        <!--begin::Link-->
                        <a class="btn btn-sm btn-primary me-2" href="apps/ecommerce/sales/listing.html">View App</a>
                        <!--end::Link-->
                        <!--begin::Link-->
                        <a class="btn btn-sm btn-light" href="apps/ecommerce/catalog/add-product.html">New Product</a>
                        <!--end::Link-->
                    </div>
                    <!--end::Links-->
                </div>
                <!--end::Body-->
            </div>
            <!--end::Engage widget 1-->
        </div>
        <!--end::Col-->
        <!--begin::Col-->
        <div class="col-xl-8 mb-5 mb-xl-10">
            <!--begin::Table Widget 4-->
            <div class="card card-flush h-xl-100" data-dashboard-widget="product-orders">
                <!--begin::Card header-->
                <div class="card-header pt-7">
                    <!--begin::Title-->
                    <h3 class="card-title align-items-start flex-column">
                        <span class="card-label fw-bold text-gray-800">Product Orders</span>
                        <span class="text-gray-500 mt-1 fw-semibold fs-6">Orders in {{ data_get($dashboardMeta, 'range.label', 'This month') }}</span>
                    </h3>
                    <!--end::Title-->
                    <!--begin::Actions-->
                    <div class="card-toolbar">
                        <!--begin::Filters-->
                        <div class="d-flex flex-stack flex-wrap gap-4">
                            <!--begin::Destination-->
                            <div class="d-flex align-items-center fw-bold">
                                <!--begin::Label-->
                                <div class="text-gray-500 fs-7 me-2">Cateogry</div>
                                <!--end::Label-->
                                <!--begin::Select-->
                                <select class="form-select form-select-transparent text-graY-800 fs-base lh-1 fw-bold py-0 ps-3 w-auto" data-control="select2" data-hide-search="true" data-dropdown-css-class="w-150px" data-placeholder="Select an option">
                                    <option></option>
                                    <option value="Show All" selected="selected">Show All</option>
                                    <option value="a">Category A</option>
                                    <option value="b">Category A</option>
                                </select>
                                <!--end::Select-->
                            </div>
                            <!--end::Destination-->
                            <!--begin::Status-->
                            <div class="d-flex align-items-center fw-bold">
                                <!--begin::Label-->
                                <div class="text-gray-500 fs-7 me-2">Status</div>
                                <!--end::Label-->
                                <!--begin::Select-->
                                <select class="form-select form-select-transparent text-gray-900 fs-7 lh-1 fw-bold py-0 ps-3 w-auto" data-control="select2" data-hide-search="true" data-dropdown-css-class="w-150px" data-placeholder="Select an option" data-dashboard-product-orders="filter_status">
                                    <option></option>
                                    <option value="Show All" selected="selected">Show All</option>
                                    <option value="Shipped">Shipped</option>
                                    <option value="Confirmed">Confirmed</option>
                                    <option value="Rejected">Rejected</option>
                                    <option value="Pending">Pending</option>
                                </select>
                                <!--end::Select-->
                            </div>
                            <!--end::Status-->
                            <!--begin::Search-->
                            <div class="position-relative my-1">
                                <i class="ki-duotone ki-magnifier fs-2 position-absolute top-50 translate-middle-y ms-4">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                                <input type="text" data-dashboard-product-orders="search" class="form-control w-150px fs-7 ps-12" placeholder="Search" />
                            </div>
                            <!--end::Search-->
                        </div>
                        <!--begin::Filters-->
                    </div>
                    <!--end::Actions-->
                </div>
                <!--end::Card header-->
                <!--begin::Card body-->
                <div class="card-body pt-2">
                    <!--begin::Table-->
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-6 gy-3" id="hm_dashboard_product_orders_table">
                            <!--begin::Table head-->
                            <thead>
                                <!--begin::Table row-->
                                <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                                    <th class="min-w-100px">Order ID</th>
                                    <th class="text-end min-w-100px">Created</th>
                                    <th class="text-end min-w-125px">Customer</th>
                                    <th class="text-end min-w-100px">Total</th>
                                    <th class="text-end min-w-100px">Profit</th>
                                    <th class="text-end min-w-50px">Status</th>
                                    <th class="text-end"></th>
                                </tr>
                                <!--end::Table row-->
                            </thead>
                            <!--end::Table head-->
                            <!--begin::Table body-->
                            <tbody class="fw-bold text-gray-600">
                                @forelse ($dashboardProductOrders as $order)
                                    <tr>
                                        <td>
                                            <a href="javascript:void(0)" class="text-gray-800 text-hover-primary">{{ $order['order_id'] ?? '-' }}</a>
                                        </td>
                                        <td class="text-end">{{ $order['created_at'] ?? '-' }}</td>
                                        <td class="text-end">
                                            <a href="javascript:void(0)" class="text-gray-600 text-hover-primary">{{ $order['customer_name'] ?? '-' }}</a>
                                        </td>
                                        <td class="text-end">@format_currency($order['total'] ?? 0)</td>
                                        <td class="text-end">
                                            <span class="text-gray-800 fw-bolder">@format_currency($order['profit'] ?? 0)</span>
                                        </td>
                                        <td class="text-end">
                                            <span class="badge py-3 px-4 fs-7 badge-light-{{ $order['status_variant'] ?? 'warning' }}">{{ $order['status'] ?? '-' }}</span>
                                        </td>
                                        <td class="text-end">
                                            <button type="button" class="btn btn-sm btn-icon btn-light btn-active-light-primary toggle h-25px w-25px" data-dashboard-product-orders="expand_row">
                                                <i class="ki-duotone ki-plus fs-4 m-0 toggle-off"></i>
                                                <i class="ki-duotone ki-minus fs-4 m-0 toggle-on d-none"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-gray-500">No data available</td>
                                    </tr>
                                @endforelse

                            </tbody>
                            <!--end::Table body-->
                        </table>
                    </div>
                    <!--end::Table-->
                </div>
                <!--end::Card body-->
            </div>
            <!--end::Table Widget 4-->
        </div>
        <!--end::Col-->
    </div>
    <!--end::Row-->
    <!--begin::Row-->
    <div class="row gy-5 g-xl-10">
        <!--begin::Col-->
        <div class="col-xl-4">
            <!--begin::List widget 5-->
            <div class="card card-flush h-xl-100" data-dashboard-widget="delivery-feed">
                <!--begin::Header-->
                <div class="card-header pt-7">
                    <!--begin::Title-->
                    <h3 class="card-title align-items-start flex-column">
                        <span class="card-label fw-bold text-gray-900">Product Delivery</span>
                        <span class="text-gray-500 mt-1 fw-semibold fs-6" data-dashboard-delivery-range-label>Deliveries in {{ data_get($dashboardMeta, 'range.label', 'This month') }}</span>
                    </h3>
                    <!--end::Title-->
                    <!--begin::Toolbar-->
                    <div class="card-toolbar">
                        <a href="apps/ecommerce/sales/details.html" class="btn btn-sm btn-light">Order Details</a>
                    </div>
                    <!--end::Toolbar-->
                </div>
                <!--end::Header-->
                <!--begin::Body-->
                <div class="card-body">
                    <!--begin::Scroll-->
                    <div class="hover-scroll-overlay-y pe-3 pe-md-6 me-0" style="height: 415px">
                        @forelse ($dashboardDeliveryFeed as $delivery)
                            <div class="border border-dashed border-gray-300 rounded px-7 py-3 mb-6">
                                <div class="d-flex flex-stack mb-3">
                                    <div class="me-3">
                                        <img src="{{ $delivery['image_url'] ?? asset('img/default.png') }}" class="w-50px ms-n1 me-1" alt="" />
                                        <a href="javascript:void(0)" class="text-gray-800 text-hover-primary fw-bold">{{ $delivery['product_name'] ?? '-' }}</a>
                                    </div>
                                    <div class="m-0"></div>
                                </div>
                                <div class="d-flex flex-stack">
                                    <span class="text-gray-500 fw-bold">To: <span class="text-gray-800 text-hover-primary fw-bold">{{ $delivery['recipient_name'] ?? '-' }}</span></span>
                                    <span class="badge badge-light-{{ $delivery['status_variant'] ?? 'primary' }}">{{ $delivery['status'] ?? '-' }}</span>
                                </div>
                            </div>
                        @empty
                            <div class="text-center text-gray-500 py-10">No data</div>
                        @endforelse
                    
                    </div>
                    <!--end::Scroll-->
                </div>
                <!--end::Body-->
            </div>
            <!--end::List widget 5-->
        </div>
        <!--end::Col-->
        <!--begin::Col-->
        <div class="col-xl-8">
            <!--begin::Table Widget 5-->
            <div class="card card-flush h-xl-100" data-dashboard-widget="stock-table">
                <!--begin::Card header-->
                <div class="card-header pt-7">
                    <!--begin::Title-->
                    <h3 class="card-title align-items-start flex-column">
                        <span class="card-label fw-bold text-gray-900">Stock Report</span>
                        <span class="text-gray-500 mt-1 fw-semibold fs-6">Total 2,356 Items in the Stock</span>
                    </h3>
                    <!--end::Title-->
                    <!--begin::Actions-->
                    <div class="card-toolbar">
                        <!--begin::Filters-->
                        <div class="d-flex flex-stack flex-wrap gap-4">
                            <!--begin::Destination-->
                            <div class="d-flex align-items-center fw-bold">
                                <!--begin::Label-->
                                <div class="text-muted fs-7 me-2">Cateogry</div>
                                <!--end::Label-->
                                <!--begin::Select-->
                                <select class="form-select form-select-transparent text-gray-900 fs-7 lh-1 fw-bold py-0 ps-3 w-auto" data-control="select2" data-hide-search="true" data-dropdown-css-class="w-150px" data-placeholder="Select an option">
                                    <option></option>
                                    <option value="Show All" selected="selected">Show All</option>
                                    <option value="a">Category A</option>
                                    <option value="b">Category B</option>
                                </select>
                                <!--end::Select-->
                            </div>
                            <!--end::Destination-->
                            <!--begin::Status-->
                            <div class="d-flex align-items-center fw-bold">
                                <!--begin::Label-->
                                <div class="text-muted fs-7 me-2">Status</div>
                                <!--end::Label-->
                                <!--begin::Select-->
                                <select class="form-select form-select-transparent text-gray-900 fs-7 lh-1 fw-bold py-0 ps-3 w-auto" data-control="select2" data-hide-search="true" data-dropdown-css-class="w-150px" data-placeholder="Select an option" data-dashboard-stock="filter_status">
                                    <option></option>
                                    <option value="Show All" selected="selected">Show All</option>
                                    <option value="In Stock">In Stock</option>
                                    <option value="Out of Stock">Out of Stock</option>
                                    <option value="Low Stock">Low Stock</option>
                                </select>
                                <!--end::Select-->
                            </div>
                            <!--end::Status-->
                            <!--begin::Search-->
                            <a href="apps/ecommerce/catalog/products.html" class="btn btn-light btn-sm">View Stock</a>
                            <!--end::Search-->
                        </div>
                        <!--begin::Filters-->
                    </div>
                    <!--end::Actions-->
                </div>
                <!--end::Card header-->
                <!--begin::Card body-->
                <div class="card-body">
                    <!--begin::Table-->
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-6 gy-3" id="hm_dashboard_stock_table">
                            <!--begin::Table head-->
                            <thead>
                                <!--begin::Table row-->
                                <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                                    <th class="min-w-150px">Item</th>
                                    <th class="text-end pe-3 min-w-100px">Product ID</th>
                                    <th class="text-end pe-3 min-w-150px">Date Added</th>
                                    <th class="text-end pe-3 min-w-100px">Price</th>
                                    <th class="text-end pe-3 min-w-100px">Status</th>
                                    <th class="text-end pe-0 min-w-75px">Qty</th>
                                </tr>
                                <!--end::Table row-->
                            </thead>
                            <!--end::Table head-->
                            <!--begin::Table body-->
                            <tbody class="fw-bold text-gray-600">
                                @forelse ($dashboardStockRows as $stock)
                                    <tr>
                                        <td>
                                            <a href="javascript:void(0)" class="text-gray-900 text-hover-primary">{{ $stock['item_name'] ?? '-' }}</a>
                                        </td>
                                        <td class="text-end">{{ $stock['product_code'] ?? '-' }}</td>
                                        <td class="text-end">{{ $stock['date_added'] ?? '-' }}</td>
                                        <td class="text-end">@format_currency($stock['price'] ?? 0)</td>
                                        <td class="text-end">
                                            <span class="badge py-3 px-4 fs-7 badge-light-{{ $stock['status_variant'] ?? 'primary' }}">{{ $stock['status_label'] ?? '-' }}</span>
                                        </td>
                                        <td class="text-end" data-order="{{ $stock['qty'] ?? 0 }}">
                                            <span class="text-gray-900 fw-bold">{{ num_format_value($stock['qty'] ?? 0) }} PCS</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-gray-500">No data available</td>
                                    </tr>
                                @endforelse

                            </tbody>
                            <!--end::Table body-->
                        </table>
                    </div>
                    <!--end::Table-->
                </div>
                <!--end::Card body-->
            </div>
            <!--end::Table Widget 5-->
        </div>
        <!--end::Col-->
    </div>
    <!--end::Row-->

@if (!empty($all_locations))
    <div class="row g-5 g-xl-10 mt-1" id="dashboard-data-section">
        <div class="col-12">
            <div class="card card-flush">
                <div class="card-header align-items-center">
                    <div class="card-title">
                        <div class="d-flex flex-column">
                            <span class="fs-2 fw-bold text-gray-900">@lang('home.dashboard')</span>
                            <span class="text-gray-500 fw-semibold fs-6">@lang('lang_v1.sales_order'), @lang('lang_v1.pending_shipments'), @lang('lang_v1.purchase_order')</span>
                        </div>
                    </div>
                    <div class="card-toolbar">
                        <a href="#dashboard-data-section" class="btn btn-light-primary btn-sm">@lang('home.dashboard')</a>
                    </div>
                </div>
            </div>
        </div>

        @if (auth()->user()->can('so.view_all') || auth()->user()->can('so.view_own'))
            <div class="col-12">
                <div class="card card-flush h-100">
                    <div class="card-header align-items-center">
                        <div class="card-title">
                            <h3 class="card-title fw-bold text-gray-900 mb-0">@lang('lang_v1.sales_order')</h3>
                        </div>
                        <div class="card-toolbar">
                            @if (count($all_locations) > 1)
                                {!! Form::select('so_location', $all_locations, null, [
                                    'class' => 'form-select form-select-solid form-select-sm w-200px select2',
                                    'placeholder' => __('lang_v1.select_location'),
                                    'id' => 'so_location',
                                ]) !!}
                            @endif
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <div class="table-responsive">
                            <table class="table align-middle table-row-dashed fs-6 gy-5 ajax_view" id="sales_order_table">
                                <thead>
                                    <tr class="fw-bold fs-7 text-uppercase text-gray-500">
                                        <th>@lang('messages.action')</th>
                                        <th>@lang('messages.date')</th>
                                        <th>@lang('restaurant.order_no')</th>
                                        <th>@lang('sale.customer_name')</th>
                                        <th>@lang('lang_v1.contact_no')</th>
                                        <th>@lang('sale.location')</th>
                                        <th>@lang('sale.status')</th>
                                        <th>@lang('lang_v1.shipping_status')</th>
                                        <th>@lang('lang_v1.quantity_remaining')</th>
                                        <th>@lang('lang_v1.added_by')</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        @if (!empty($common_settings['enable_purchase_requisition']) &&
            (auth()->user()->can('purchase_requisition.view_all') || auth()->user()->can('purchase_requisition.view_own')))
            <div class="col-12 col-xl-6">
                <div class="card card-flush h-100">
                    <div class="card-header align-items-center">
                        <div class="card-title">
                            <h3 class="card-title fw-bold text-gray-900 mb-0">@lang('lang_v1.purchase_requisition')</h3>
                        </div>
                        <div class="card-toolbar">
                            @if (count($all_locations) > 1)
                                {!! Form::select('pr_location', $all_locations, null, [
                                    'class' => 'form-select form-select-solid form-select-sm w-200px select2',
                                    'placeholder' => __('lang_v1.select_location'),
                                    'id' => 'pr_location',
                                ]) !!}
                            @endif
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <div class="table-responsive">
                            <table class="table align-middle table-row-dashed fs-6 gy-5 ajax_view" id="purchase_requisition_table">
                                <thead>
                                    <tr class="fw-bold fs-7 text-uppercase text-gray-500">
                                        <th>@lang('messages.action')</th>
                                        <th>@lang('messages.date')</th>
                                        <th>@lang('purchase.ref_no')</th>
                                        <th>@lang('purchase.location')</th>
                                        <th>@lang('sale.status')</th>
                                        <th>@lang('lang_v1.required_by_date')</th>
                                        <th>@lang('lang_v1.added_by')</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        @if (!empty($common_settings['enable_purchase_order']) &&
            (auth()->user()->can('purchase_order.view_all') || auth()->user()->can('purchase_order.view_own')))
            <div class="col-12 col-xl-6">
                <div class="card card-flush h-100">
                    <div class="card-header align-items-center">
                        <div class="card-title">
                            <h3 class="card-title fw-bold text-gray-900 mb-0">@lang('lang_v1.purchase_order')</h3>
                        </div>
                        <div class="card-toolbar">
                            @if (count($all_locations) > 1)
                                {!! Form::select('po_location', $all_locations, null, [
                                    'class' => 'form-select form-select-solid form-select-sm w-200px select2',
                                    'placeholder' => __('lang_v1.select_location'),
                                    'id' => 'po_location',
                                ]) !!}
                            @endif
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <div class="table-responsive">
                            <table class="table align-middle table-row-dashed fs-6 gy-5 ajax_view" id="purchase_order_table">
                                <thead>
                                    <tr class="fw-bold fs-7 text-uppercase text-gray-500">
                                        <th>@lang('messages.action')</th>
                                        <th>@lang('messages.date')</th>
                                        <th>@lang('purchase.ref_no')</th>
                                        <th>@lang('purchase.location')</th>
                                        <th>@lang('purchase.supplier')</th>
                                        <th>@lang('sale.status')</th>
                                        <th>@lang('lang_v1.quantity_remaining')</th>
                                        <th>@lang('lang_v1.added_by')</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        @if (auth()->user()->can('access_pending_shipments_only') ||
            auth()->user()->can('access_shipping') ||
            auth()->user()->can('access_own_shipping'))
            <div class="col-12">
                <div class="card card-flush h-100">
                    <div class="card-header align-items-center">
                        <div class="card-title">
                            <h3 class="card-title fw-bold text-gray-900 mb-0">@lang('lang_v1.pending_shipments')</h3>
                        </div>
                        <div class="card-toolbar">
                            @if (count($all_locations) > 1)
                                {!! Form::select('pending_shipments_location', $all_locations, null, [
                                    'class' => 'form-select form-select-solid form-select-sm w-200px select2',
                                    'placeholder' => __('lang_v1.select_location'),
                                    'id' => 'pending_shipments_location',
                                ]) !!}
                            @endif
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <div class="table-responsive">
                            <table class="table align-middle table-row-dashed fs-6 gy-5 ajax_view" id="shipments_table">
                                <thead>
                                    <tr class="fw-bold fs-7 text-uppercase text-gray-500">
                                        <th>@lang('messages.action')</th>
                                        <th>@lang('messages.date')</th>
                                        <th>@lang('sale.invoice_no')</th>
                                        <th>@lang('sale.customer_name')</th>
                                        <th>@lang('lang_v1.contact_no')</th>
                                        <th>@lang('sale.location')</th>
                                        <th>@lang('lang_v1.shipping_status')</th>
                                        @if (!empty($custom_labels['shipping']['custom_field_1'] ?? null))
                                            <th>{{ $custom_labels['shipping']['custom_field_1'] }}</th>
                                        @endif
                                        @if (!empty($custom_labels['shipping']['custom_field_2'] ?? null))
                                            <th>{{ $custom_labels['shipping']['custom_field_2'] }}</th>
                                        @endif
                                        @if (!empty($custom_labels['shipping']['custom_field_3'] ?? null))
                                            <th>{{ $custom_labels['shipping']['custom_field_3'] }}</th>
                                        @endif
                                        @if (!empty($custom_labels['shipping']['custom_field_4'] ?? null))
                                            <th>{{ $custom_labels['shipping']['custom_field_4'] }}</th>
                                        @endif
                                        @if (!empty($custom_labels['shipping']['custom_field_5'] ?? null))
                                            <th>{{ $custom_labels['shipping']['custom_field_5'] }}</th>
                                        @endif
                                        <th>@lang('sale.payment_status')</th>
                                        <th>@lang('restaurant.service_staff')</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        @if (auth()->user()->can('account.access') && config('constants.show_payments_recovered_today') == true)
            <div class="col-12">
                <div class="card card-flush h-100">
                    <div class="card-header align-items-center">
                        <div class="card-title">
                            <h3 class="card-title fw-bold text-gray-900 mb-0">@lang('lang_v1.payment_recovered_today')</h3>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <div class="table-responsive">
                            <table class="table align-middle table-row-dashed fs-6 gy-5" id="cash_flow_table">
                                <thead>
                                    <tr class="fw-bold fs-7 text-uppercase text-gray-500">
                                        <th>@lang('messages.date')</th>
                                        <th>@lang('account.account')</th>
                                        <th>@lang('lang_v1.description')</th>
                                        <th>@lang('lang_v1.payment_method')</th>
                                        <th>@lang('lang_v1.payment_details')</th>
                                        <th>@lang('account.credit')</th>
                                        <th>@lang('lang_v1.account_balance') @show_tooltip(__('lang_v1.account_balance_tooltip'))</th>
                                        <th>@lang('lang_v1.total_balance') @show_tooltip(__('lang_v1.total_balance_tooltip'))</th>
                                    </tr>
                                </thead>
                                <tfoot>
                                    <tr class="fw-bold fs-6 text-center">
                                        <td colspan="5">@lang('sale.total'):</td>
                                        <td class="footer_total_credit"></td>
                                        <td colspan="2"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endif

</div>
<div class="modal fade payment_modal" tabindex="-1" aria-hidden="true"></div>
<div class="modal fade edit_pso_status_modal" tabindex="-1" aria-hidden="true"></div>
<div class="modal fade edit_payment_modal" tabindex="-1" aria-hidden="true"></div>

@endsection


@section('css')
    <style>
        .home-dashboard-root {
            max-width: 100%;
            overflow-x: clip;
        }

        @supports not (overflow: clip) {
            .home-dashboard-root {
                overflow-x: hidden;
            }
        }

        .home-dashboard-root #dashboard-date-filter .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem 1rem;
        }

        .home-dashboard-root #dashboard-date-filter .card-title {
            min-width: 0;
            max-width: 100%;
        }

        .home-dashboard-root [data-dashboard-widget] .card-header .d-flex.align-items-center {
            flex-wrap: wrap;
            row-gap: 0.35rem;
            min-width: 0;
        }

        .home-dashboard-root [data-dashboard-widget] .card-header .fs-2hx,
        .home-dashboard-root [data-dashboard-widget] .px-9 .fs-2hx {
            font-size: clamp(1.7rem, 2.7vw, 2.45rem) !important;
            line-height: 1.1;
            max-width: 100%;
            word-break: break-word;
        }

        .home-dashboard-root [data-dashboard-widget] .card-header .badge {
            flex-shrink: 0;
            white-space: nowrap;
        }

        .home-dashboard-root [data-dashboard-widget="sales-summary"] .card-body {
            padding-right: 1rem;
            overflow: hidden;
        }

        .home-dashboard-root [data-dashboard-widget="sales-summary"] .card-body .d-flex.fs-6.fw-semibold.align-items-center {
            display: grid;
            grid-template-columns: 8px minmax(0, 1fr) minmax(0, 8.75rem);
            column-gap: 0.5rem;
            align-items: center;
        }

        .home-dashboard-root [data-dashboard-widget="sales-summary"] .card-body .d-flex.fs-6.fw-semibold.align-items-center .bullet {
            margin-right: 0 !important;
        }

        .home-dashboard-root [data-dashboard-widget="sales-summary"] .card-body .d-flex.fs-6.fw-semibold.align-items-center .text-gray-500 {
            min-width: 0;
            margin-right: 0 !important;
            line-height: 1.2;
        }

        .home-dashboard-root [data-dashboard-widget="sales-summary"] .card-body .d-flex.fs-6.fw-semibold.align-items-center .fw-bolder.text-gray-700 {
            min-width: 0;
            text-align: right;
            line-height: 1.15;
            white-space: normal;
            overflow-wrap: anywhere;
            font-size: 0.95rem;
        }

        .home-dashboard-root [data-dashboard-widget="average-daily-sales"] .card-body,
        .home-dashboard-root [data-dashboard-widget="sales-chart"] .card-body {
            overflow: hidden;
        }

        .home-dashboard-root [data-dashboard-widget="delivery-feed"] .hover-scroll-overlay-y {
            margin-right: 0 !important;
            max-width: 100%;
        }

        .home-dashboard-root [data-dashboard-widget="recent-orders"] [data-dashboard-recent-tabs-nav] {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            max-width: 100%;
        }

        .home-dashboard-root [data-dashboard-widget="recent-orders"] [data-dashboard-recent-tabs-nav] .nav-item {
            margin-right: 0 !important;
        }

        .home-dashboard-root [data-dashboard-widget="product-orders"] .card-toolbar,
        .home-dashboard-root [data-dashboard-widget="stock-table"] .card-toolbar {
            max-width: 100%;
        }

        .home-dashboard-root [data-dashboard-widget="product-orders"] .card-toolbar .d-flex.flex-stack.flex-wrap.gap-4,
        .home-dashboard-root [data-dashboard-widget="stock-table"] .card-toolbar .d-flex.flex-stack.flex-wrap.gap-4 {
            max-width: 100%;
        }

        .home-dashboard-root [data-dashboard-widget="product-orders"] .card-toolbar .d-flex.align-items-center.fw-bold,
        .home-dashboard-root [data-dashboard-widget="stock-table"] .card-toolbar .d-flex.align-items-center.fw-bold,
        .home-dashboard-root [data-dashboard-widget="product-orders"] .card-toolbar .position-relative {
            min-width: 0;
            max-width: min(100%, 16rem);
        }

        .home-dashboard-root [data-dashboard-widget="product-orders"] .select2-container,
        .home-dashboard-root [data-dashboard-widget="stock-table"] .select2-container {
            width: 100% !important;
            max-width: 100%;
        }

        .home-dashboard-root [data-dashboard-widget="product-orders"] .table-responsive,
        .home-dashboard-root [data-dashboard-widget="stock-table"] .table-responsive,
        .home-dashboard-root [data-dashboard-widget="recent-orders"] .table-responsive {
            max-width: 100%;
            overflow-x: auto;
            overflow-y: visible;
        }

        .home-dashboard-root #dashboard-data-section .table-responsive,
        .home-dashboard-root #dashboard-data-section .dataTables_wrapper,
        .home-dashboard-root #dashboard-data-section .dataTables_scroll,
        .home-dashboard-root #dashboard-data-section .dataTables_scrollBody {
            max-width: 100%;
        }

        .home-dashboard-root #dashboard-data-section .table-responsive,
        .home-dashboard-root #dashboard-data-section .dataTables_wrapper,
        .home-dashboard-root #dashboard-data-section .dataTables_scroll,
        .home-dashboard-root #dashboard-data-section .dataTables_scrollBody {
            overflow-x: auto;
        }

        .home-dashboard-root #dashboard-data-section .dataTables_wrapper .dataTables_scrollHead {
            overflow-x: hidden !important;
            max-width: 100%;
        }

        @media (max-width: 991.98px) {
            .home-dashboard-root #dashboard-date-filter .card-header {
                align-items: flex-start;
            }

            .home-dashboard-root #dashboard-date-filter .card-toolbar {
                width: 100%;
                display: flex;
            }

            .home-dashboard-root #dashboard-date-filter .card-toolbar .btn {
                width: 100%;
                max-width: 18rem;
            }

            .home-dashboard-root [data-dashboard-widget="product-orders"] .card-toolbar,
            .home-dashboard-root [data-dashboard-widget="stock-table"] .card-toolbar {
                width: 100%;
                margin-top: 0.75rem;
            }

            .home-dashboard-root [data-dashboard-widget="product-orders"] .card-toolbar .d-flex.flex-stack.flex-wrap.gap-4,
            .home-dashboard-root [data-dashboard-widget="stock-table"] .card-toolbar .d-flex.flex-stack.flex-wrap.gap-4 {
                width: 100%;
                flex-direction: column;
                align-items: stretch !important;
                gap: 0.6rem !important;
            }

            .home-dashboard-root [data-dashboard-widget="product-orders"] .card-toolbar .d-flex.align-items-center.fw-bold,
            .home-dashboard-root [data-dashboard-widget="stock-table"] .card-toolbar .d-flex.align-items-center.fw-bold,
            .home-dashboard-root [data-dashboard-widget="product-orders"] .card-toolbar .position-relative,
            .home-dashboard-root [data-dashboard-widget="stock-table"] .card-toolbar .btn.btn-light.btn-sm {
                width: 100%;
                max-width: 100%;
            }

            .home-dashboard-root [data-dashboard-widget="product-orders"] .card-toolbar .form-select,
            .home-dashboard-root [data-dashboard-widget="stock-table"] .card-toolbar .form-select,
            .home-dashboard-root [data-dashboard-widget="product-orders"] .card-toolbar input[data-dashboard-product-orders="search"],
            .home-dashboard-root [data-dashboard-widget="stock-table"] .card-toolbar .btn.btn-light.btn-sm {
                width: 100% !important;
                max-width: 100%;
            }

            .home-dashboard-root [data-dashboard-widget="product-orders"] .card-toolbar .select2,
            .home-dashboard-root [data-dashboard-widget="stock-table"] .card-toolbar .select2 {
                width: 100% !important;
                max-width: 100%;
            }

            .home-dashboard-root #hm_dashboard_product_orders_table,
            .home-dashboard-root #hm_dashboard_stock_table,
            .home-dashboard-root [data-dashboard-widget="recent-orders"] table {
                width: 100% !important;
                max-width: 100%;
                table-layout: fixed;
            }

            .home-dashboard-root #hm_dashboard_product_orders_table th,
            .home-dashboard-root #hm_dashboard_product_orders_table td,
            .home-dashboard-root #hm_dashboard_stock_table th,
            .home-dashboard-root #hm_dashboard_stock_table td,
            .home-dashboard-root [data-dashboard-widget="recent-orders"] th,
            .home-dashboard-root [data-dashboard-widget="recent-orders"] td,
            .home-dashboard-root [data-dashboard-widget="recent-orders"] .nav-text,
            .home-dashboard-root [data-dashboard-widget="recent-orders"] .text-gray-500,
            .home-dashboard-root [data-dashboard-widget="recent-orders"] a {
                min-width: 0 !important;
                width: auto !important;
                white-space: normal !important;
                overflow-wrap: anywhere;
                word-break: break-word;
            }

            .home-dashboard-root [data-dashboard-widget="recent-orders"] [data-dashboard-recent-tabs-nav] .nav-item,
            .home-dashboard-root [data-dashboard-widget="recent-orders"] [data-dashboard-recent-tabs-nav] .nav-link {
                width: calc(50% - 0.25rem);
                max-width: 100%;
            }

            .home-dashboard-root [data-dashboard-widget="recent-orders"] [data-dashboard-recent-tabs-nav] .nav-link {
                height: auto;
                min-height: 84px;
            }

            .home-dashboard-root #hm_dashboard_product_orders_table .badge,
            .home-dashboard-root #hm_dashboard_stock_table .badge {
                white-space: normal;
                line-height: 1.2;
            }
        }

        @media (max-width: 575.98px) {
            .home-dashboard-root #dashboard-date-filter .card-toolbar .btn {
                max-width: 100%;
            }

            .home-dashboard-root [data-dashboard-widget="recent-orders"] [data-dashboard-recent-tabs-nav] .nav-item,
            .home-dashboard-root [data-dashboard-widget="recent-orders"] [data-dashboard-recent-tabs-nav] .nav-link {
                width: 100%;
            }
        }
    </style>
@endsection

@section('javascript')
    <script src="{{ asset('assets/app/js/home.js?v=' . $asset_v) }}"></script>
    <script src="{{ asset('assets/app/js/payment.js?v=' . $asset_v) }}"></script>
    <script>
        window.homeMetronicDashboardConfig = {
            endpoint: @json(url('/home/metronic-dashboard-data')),
            initialPayload: @json($dashboardData)
        };
    </script>
    <script src="{{ asset('assets/app/js/home-metronic-dashboard.js?v=' . $asset_v) }}"></script>
    @includeIf('sales_order.common_js')
    @includeIf('purchase_order.common_js')
    @if (!empty($all_locations))
        {!! $sells_chart_1->script() !!}
        {!! $sells_chart_2->script() !!}
    @endif
    <script type="text/javascript">
        $(document).ready(function() {
            var legacyDashboardTables = [];
            var legacyDashboardResizeTimer = null;
            var scheduleLegacyDashboardTableAdjust = function(tableApi) {
                if (!tableApi || !tableApi.columns || typeof tableApi.columns.adjust !== 'function') {
                    return;
                }

                var runAdjust = function() {
                    tableApi.columns.adjust();
                    if (tableApi.responsive && typeof tableApi.responsive.recalc === 'function') {
                        tableApi.responsive.recalc();
                    }
                };

                if (window.requestAnimationFrame) {
                    window.requestAnimationFrame(runAdjust);
                } else {
                    window.setTimeout(runAdjust, 0);
                }
            };
            var registerLegacyDashboardTable = function(tableApi) {
                if (!tableApi || legacyDashboardTables.indexOf(tableApi) !== -1) {
                    return;
                }

                legacyDashboardTables.push(tableApi);
                scheduleLegacyDashboardTableAdjust(tableApi);
            };

            $(window).off('resize.homeLegacyDashboardTables').on('resize.homeLegacyDashboardTables', function() {
                if (legacyDashboardResizeTimer) {
                    window.clearTimeout(legacyDashboardResizeTimer);
                }

                legacyDashboardResizeTimer = window.setTimeout(function() {
                    $.each(legacyDashboardTables, function(_, tableApi) {
                        scheduleLegacyDashboardTableAdjust(tableApi);
                    });
                }, 120);
            });

            if ($('#sales_order_table').length) {
                sales_order_table = $('#sales_order_table').DataTable({
                    processing: true,
                    serverSide: true,
                    fixedHeader:false,
                    scrollY: "75vh",
                    scrollX: true,
                    scrollCollapse: true,
                    aaSorting: [
                        [1, 'desc']
                    ],
                    "ajax": {
                        "url": '{{ action([\App\Http\Controllers\SellController::class, 'index']) }}?sale_type=sales_order',
                        "data": function(d) {
                            d.for_dashboard_sales_order = true;

                            if ($('#so_location').length > 0) {
                                d.location_id = $('#so_location').val();
                            }
                        }
                    },
                    columnDefs: [{
                        "targets": 7,
                        "orderable": false,
                        "searchable": false
                    }],
                    columns: [{
                            data: 'action',
                            name: 'action'
                        },
                        {
                            data: 'transaction_date',
                            name: 'transaction_date'
                        },
                        {
                            data: 'invoice_no',
                            name: 'invoice_no'
                        },
                        {
                            data: 'conatct_name',
                            name: 'conatct_name'
                        },
                        {
                            data: 'mobile',
                            name: 'contacts.mobile'
                        },
                        {
                            data: 'business_location',
                            name: 'bl.name'
                        },
                        {
                            data: 'status',
                            name: 'status'
                        },
                        {
                            data: 'shipping_status',
                            name: 'shipping_status'
                        },
                        {
                            data: 'so_qty_remaining',
                            name: 'so_qty_remaining',
                            "searchable": false
                        },
                        {
                            data: 'added_by',
                            name: 'u.first_name'
                        },
                    ],
                    drawCallback: function() {
                        scheduleLegacyDashboardTableAdjust(this.api());
                    }
                });
                registerLegacyDashboardTable(sales_order_table);

                $('#so_location').change(function() {
                    sales_order_table.ajax.reload();
                });
            }

            @if (auth()->user()->can('account.access') && config('constants.show_payments_recovered_today') == true)

                // Cash Flow Table
                if ($('#cash_flow_table').length) {
                    cash_flow_table = $('#cash_flow_table').DataTable({
                        processing: true,
                        serverSide: true,
                        fixedHeader:false,
                        "ajax": {
                            "url": "{{ action([\App\Http\Controllers\AccountController::class, 'cashFlow']) }}",
                            "data": function(d) {
                                d.type = 'credit';
                                d.only_payment_recovered = true;
                            }
                        },
                        "ordering": false,
                        "searching": false,
                        columns: [{
                                data: 'operation_date',
                                name: 'operation_date'
                            },
                            {
                                data: 'account_name',
                                name: 'account_name'
                            },
                            {
                                data: 'sub_type',
                                name: 'sub_type'
                            },
                            {
                                data: 'method',
                                name: 'TP.method'
                            },
                            {
                                data: 'payment_details',
                                name: 'payment_details',
                                searchable: false
                            },
                            {
                                data: 'credit',
                                name: 'amount'
                            },
                            {
                                data: 'balance',
                                name: 'balance'
                            },
                            {
                                data: 'total_balance',
                                name: 'total_balance'
                            },
                        ],
                        "fnDrawCallback": function(oSettings) {
                            __currency_convert_recursively($('#cash_flow_table'));
                        },
                        "footerCallback": function(row, data, start, end, display) {
                            var footer_total_credit = 0;

                            for (var r in data) {
                                footer_total_credit += $(data[r].credit).data('orig-value') ? parseFloat($(
                                    data[r].credit).data('orig-value')) : 0;
                            }
                            $('.footer_total_credit').html(__currency_trans_from_en(footer_total_credit));
                        }
                    });
                }
            @endif

            @if (!empty($common_settings['enable_purchase_order']))
                //Purchase table
                if ($('#purchase_order_table').length) {
                    purchase_order_table = $('#purchase_order_table').DataTable({
                        processing: true,
                        serverSide: true,
                        fixedHeader:false,
                        aaSorting: [
                            [1, 'desc']
                        ],
                        scrollY: "75vh",
                        scrollX: true,
                        scrollCollapse: true,
                        ajax: {
                            url: '{{ action([\App\Http\Controllers\PurchaseOrderController::class, 'index']) }}',
                            data: function(d) {
                                d.from_dashboard = true;

                                if ($('#po_location').length > 0) {
                                    d.location_id = $('#po_location').val();
                                }
                            },
                        },
                        columns: [{
                                data: 'action',
                                name: 'action',
                                orderable: false,
                                searchable: false
                            },
                            {
                                data: 'transaction_date',
                                name: 'transaction_date'
                            },
                            {
                                data: 'ref_no',
                                name: 'ref_no'
                            },
                            {
                                data: 'location_name',
                                name: 'BS.name'
                            },
                            {
                                data: 'name',
                                name: 'contacts.name'
                            },
                            {
                                data: 'status',
                                name: 'transactions.status'
                            },
                            {
                                data: 'po_qty_remaining',
                                name: 'po_qty_remaining',
                                "searchable": false
                            },
                            {
                                data: 'added_by',
                                name: 'u.first_name'
                            }
                        ],
                        drawCallback: function() {
                            scheduleLegacyDashboardTableAdjust(this.api());
                        }
                    });
                    registerLegacyDashboardTable(purchase_order_table);

                    $('#po_location').change(function() {
                        purchase_order_table.ajax.reload();
                    });
                }
            @endif

            @if (!empty($common_settings['enable_purchase_requisition']))
                //Purchase table
                if ($('#purchase_requisition_table').length) {
                    purchase_requisition_table = $('#purchase_requisition_table').DataTable({
                        processing: true,
                        serverSide: true,
                        fixedHeader:false,
                        aaSorting: [
                            [1, 'desc']
                        ],
                        scrollY: "75vh",
                        scrollX: true,
                        scrollCollapse: true,
                        ajax: {
                            url: '{{ action([\App\Http\Controllers\PurchaseRequisitionController::class, 'index']) }}',
                            data: function(d) {
                                d.from_dashboard = true;

                                if ($('#pr_location').length > 0) {
                                    d.location_id = $('#pr_location').val();
                                }
                            },
                        },
                        columns: [{
                                data: 'action',
                                name: 'action',
                                orderable: false,
                                searchable: false
                            },
                            {
                                data: 'transaction_date',
                                name: 'transaction_date'
                            },
                            {
                                data: 'ref_no',
                                name: 'ref_no'
                            },
                            {
                                data: 'location_name',
                                name: 'BS.name'
                            },
                            {
                                data: 'status',
                                name: 'status'
                            },
                            {
                                data: 'delivery_date',
                                name: 'delivery_date'
                            },
                            {
                                data: 'added_by',
                                name: 'u.first_name'
                            },
                        ],
                        drawCallback: function() {
                            scheduleLegacyDashboardTableAdjust(this.api());
                        }
                    });
                    registerLegacyDashboardTable(purchase_requisition_table);

                    $('#pr_location').change(function() {
                        purchase_requisition_table.ajax.reload();
                    });

                    $(document).on('click', 'a.delete-purchase-requisition', function(e) {
                        e.preventDefault();
                        swal({
                            title: LANG.sure,
                            icon: 'warning',
                            buttons: true,
                            dangerMode: true,
                        }).then(willDelete => {
                            if (willDelete) {
                                var href = $(this).attr('href');
                                $.ajax({
                                    method: 'DELETE',
                                    url: href,
                                    dataType: 'json',
                                    success: function(result) {
                                        if (result.success == true) {
                                            toastr.success(result.msg);
                                            purchase_requisition_table.ajax.reload();
                                        } else {
                                            toastr.error(result.msg);
                                        }
                                    },
                                });
                            }
                        });
                    });
                }
            @endif

            if ($('#shipments_table').length) {
                sell_table = $('#shipments_table').DataTable({
                    processing: true,
                    serverSide: true,
                    fixedHeader:false,
                    aaSorting: [
                        [1, 'desc']
                    ],
                    scrollY: "75vh",
                    scrollX: true,
                    scrollCollapse: true,
                    "ajax": {
                        "url": '{{ action([\App\Http\Controllers\SellController::class, 'index']) }}',
                        "data": function(d) {
                            d.only_pending_shipments = true;
                            if ($('#pending_shipments_location').length > 0) {
                                d.location_id = $('#pending_shipments_location').val();
                            }
                        }
                    },
                    columns: [{
                            data: 'action',
                            name: 'action',
                            searchable: false,
                            orderable: false
                        },
                        {
                            data: 'transaction_date',
                            name: 'transaction_date'
                        },
                        {
                            data: 'invoice_no',
                            name: 'invoice_no'
                        },
                        {
                            data: 'conatct_name',
                            name: 'conatct_name'
                        },
                        {
                            data: 'mobile',
                            name: 'contacts.mobile'
                        },
                        {
                            data: 'business_location',
                            name: 'bl.name'
                        },
                        {
                            data: 'shipping_status',
                            name: 'shipping_status'
                        },
                        @if (!empty($custom_labels['shipping']['custom_field_1'] ?? null))
                            {
                                data: 'shipping_custom_field_1',
                                name: 'shipping_custom_field_1'
                            },
                        @endif
                        @if (!empty($custom_labels['shipping']['custom_field_2'] ?? null))
                            {
                                data: 'shipping_custom_field_2',
                                name: 'shipping_custom_field_2'
                            },
                        @endif
                        @if (!empty($custom_labels['shipping']['custom_field_3'] ?? null))
                            {
                                data: 'shipping_custom_field_3',
                                name: 'shipping_custom_field_3'
                            },
                        @endif
                        @if (!empty($custom_labels['shipping']['custom_field_4'] ?? null))
                            {
                                data: 'shipping_custom_field_4',
                                name: 'shipping_custom_field_4'
                            },
                        @endif
                        @if (!empty($custom_labels['shipping']['custom_field_5'] ?? null))
                            {
                                data: 'shipping_custom_field_5',
                                name: 'shipping_custom_field_5'
                            },
                        @endif {
                            data: 'payment_status',
                            name: 'payment_status'
                        },
                        {
                            data: 'waiter',
                            name: 'ss.first_name',
                            @if (empty($is_service_staff_enabled ?? false))
                                visible: false
                            @endif
                        }
                    ],
                    "fnDrawCallback": function(oSettings) {
                        __currency_convert_recursively($('#shipments_table'));
                    },
                    drawCallback: function() {
                        scheduleLegacyDashboardTableAdjust(this.api());
                    },
                    createdRow: function(row, data, dataIndex) {
                        $(row).find('td:eq(4)').attr('class', 'clickable_td');
                    }
                });
                registerLegacyDashboardTable(sell_table);

                $('#pending_shipments_location').change(function() {
                    sell_table.ajax.reload();
                });
            }
        });
    </script>
@endsection
