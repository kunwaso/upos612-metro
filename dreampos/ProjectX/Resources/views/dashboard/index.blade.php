@extends('projectx::layouts.app')

@section('page_title', __('projectx::lang.dashboard'))

@section('content')

<div class="d-flex flex-column flex-root">
    <!--begin::Page-->
    <div class="page launcher sidebar-enabled d-flex flex-row flex-column-fluid me-lg-5"
        id="kt_page" >
        <!--begin::Content-->
        <div class="d-flex flex-row-fluid">
            <!--begin::Container-->
            <div class="d-flex flex-column flex-row-fluid align-items-center">
                
                    <!--begin::Menu-->
                <div class="d-flex flex-column flex-column-fluid mb-5 mb-lg-10">
                    
                <!--begin::Brand-->
                <div class="d-flex flex-center pt-10 pt-lg-0 mb-10 mb-lg-0 h-lg-225px">
                    <!--begin::Sidebar toggle-->
                    <div
                        class="btn btn-icon btn-active-color-primary w-30px h-30px d-lg-none me-4 ms-n15"
                        id="kt_sidebar_toggle"
                    >
                        <i class="ki-duotone ki-abstract-14 fs-1">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                    </div>
                    <!--end::Sidebar toggle-->
                    <!--begin::Logo-->
                    <a href="{{ route('projectx.index') }}">
                        <img
                            alt="Logo"
                            src="{{ asset('modules/projectx/media/logos/default-small.svg') }}"
                            class="h-70px"
                        />
                    </a>
                    <!--end::Logo-->
                </div>
                <!--end::Brand-->
                    
                
                    <!--begin::Row-->
                    <div class="row g-7 w-xxl-850px">
                        <!--begin::Col-->
                        <div class="col-xxl-5">
                            <!--begin::Card-->
                            <div
                                class="card border-0 shadow-none h-lg-100"
                                style="background-color: #a838ff" >
                                <!--begin::Card body-->
                                <div
                                    class="card-body d-flex flex-column flex-center pb-0 pt-15"
                                >
                                    <!--begin::Wrapper-->
                                    <div class="px-10 mb-10">
                                        <!--begin::Heading-->
                                        <h3
                                            class="text-white mb-2 fw-bolder ttext-center text-uppercase mb-6"
                                        >
                                            {{ __('projectx::lang.sell_on_platform') }}
                                        </h3>
                                        <!--end::Heading-->
                                        <!--begin::List-->
                                        <div class="mb-7">
                                            <!--begin::Item-->
                                            <div class="d-flex align-items-center mb-2">
                                                <i
                                                    class="ki-duotone ki-black-right fs-4 text-white opacity-75 me-3"
                                                ></i>
                                                <span class="text-white opacity-75"
                                                    >{{ __('projectx::lang.easy_tool') }}</span
                                                >
                                            </div>
                                            <!--end::Item-->
                                            <!--begin::Item-->
                                            <div class="d-flex align-items-center mb-2">
                                                <i
                                                    class="ki-duotone ki-black-right fs-4 text-white opacity-75 me-3"
                                                ></i>
                                                <span class="text-white opacity-75"
                                                    >{{ __('projectx::lang.fast_reports') }}</span
                                                >
                                            </div>
                                            <!--end::Item-->
                                            <!--begin::Item-->
                                            <div class="d-flex align-items-center mb-2">
                                                <i
                                                    class="ki-duotone ki-black-right fs-4 text-white opacity-75 me-3"
                                                ></i>
                                                <span class="text-white opacity-75"
                                                    >{{ __('projectx::lang.up_to_share') }}</span
                                                >
                                            </div>
                                            <!--end::Item-->
                                        </div>
                                        <!--end::List-->
                                        <!--begin::Link-->
                                        <a
                                            href="{{ route('home') }}"
                                            class="btn btn-hover-rise text-white bg-white bg-opacity-10 text-uppercase fs-7 fw-bold hover-elevate-up"
                                            >{{ __('projectx::lang.go_to_dashboard') }}</a
                                        >
                                        <!--end::Link-->
                                    </div>
                                    <!--end::Wrapper-->
                                    <!--begin::Illustrations-->
                                    <img
                                        class="mw-100 h-225px mx-auto mb-lg-n18"
                                        src="{{ asset('modules/projectx/media/illustrations/sigma-1/12.png') }}"
                                    />
                                    <!--end::Illustrations-->
                                </div>
                                <!--end::Card body-->
                            </div>
                            <!--end::Card-->
                        </div>
                        <!--end::Col-->
                        <!--begin::Col-->
                        <div class="col-xxl-7">
                            <!--begin::Row-->
                            <div class="row g-lg-7">
                                <!--begin::Col-->
                                <div class="col-sm-6">
                                    <!--begin::Card-->

                                    @if(auth()->user()->can('projectx.fabric.view'))
                                    <!--begin::Card-->
                                    <a
                                        href="{{ route('projectx.fabric_manager.list') }}"
                                        class="card border-0 shadow-none min-h-200px mb-7 btn-active-danger hover-elevate-up"
                                        style="background-color: #f9666e"
                                    >
                                    @else
                                    <a
                                        href="{{ route('projectx.index') }}"
                                        class="card border-0 shadow-none min-h-200px mb-7 btn-active-danger hover-elevate-up"
                                        style="background-color: #f9666e"
                                    >
                                    @endif
                                        <!--begin::Card body-->
                                        <div
                                            class="card-body d-flex flex-column flex-center text-center"
                                        >
                                            <!--begin::Illustrations-->
                                            <img
                                                class="mw-100 h-100px mb-7 mx-auto"
                                                src="{{ asset('modules/projectx/media/illustrations/sigma-1/4.png') }}"
                                            />
                                            <!--end::Illustrations-->
                                            <!--begin::Heading-->
                                            <h4 class="text-white fw-bold text-uppercase">
                                                @lang('projectx::lang.fabric_manager')
                                            </h4>
                                            <!--end::Heading-->
                                        </div>
                                        <!--end::Card body-->
                                    </a>
                                    <!--end::Card-->
                                </div>
                                <!--end::Col-->
                                <!--begin::Col-->
                                <div class="col-sm-6">
                                    <!--begin::Card-->

                                    @if(auth()->user()->can('product.create'))
                                    <a
                                        href="{{ action([\App\Http\Controllers\ProductController::class, 'create']) }}"
                                        class="card border-0 shadow-none min-h-200px mb-7"
                                        style="background-color: #35d29a"
                                    >
                                    @else
                                    <a
                                        href="{{ route('home') }}"
                                        class="card border-0 shadow-none min-h-200px mb-7"
                                        style="background-color: #35d29a"
                                    >
                                    @endif
                                        <!--begin::Card body-->
                                        <div
                                            class="card-body d-flex flex-column flex-center text-center"
                                        >
                                            <!--begin::Illustrations-->
                                            <img
                                                class="mw-100 h-100px mb-7 mx-auto"
                                                src="{{ asset('modules/projectx/media/illustrations/sigma-1/5.png') }}"
                                            />
                                            <!--end::Illustrations-->
                                            <!--begin::Heading-->
                                            <h4 class="text-white fw-bold text-uppercase">
                                                @lang('product.add_new_product')
                                            </h4>
                                            <!--end::Heading-->
                                        </div>
                                        <!--end::Card body-->
                                    </a>
                                    <!--end::Card-->
                                </div>
                                <!--end::Col-->
                            </div>
                            <!--end::Row-->
                            <!--begin::Card-->
                            <div class="card border-0 shadow-none min-h-200px hover-elevate-up"
                                style="background-color: #d5d83d" >
                                <!--begin::Card body-->
                                <div class="card-body d-flex flex-center flex-wrap">
                                    <!--begin::Illustrations-->
                                    <img
                                        class="mw-100 h-200px me-4 mb-5 mb-lg-0"
                                        src="{{ asset('modules/projectx/media/illustrations/sigma-1/11.png') }}"
                                    />
                                    <!--end::Illustrations-->
                                    <!--begin::Wrapper-->
                                    <div
                                        class="d-flex flex-column align-items-center align-items-md-start flex-grow-1"
                                        data-bs-theme="light"
                                    >
                                        <!--begin::Heading-->
                                        <h3
                                            class="text-gray-900 fw-bolder text-uppercase mb-5"
                                        >
                                        @lang('projectx::lang.quote_management')
                                        </h3>
                                        <!--end::Heading-->
                                        <!--begin::List-->
                                        <div
                                            class="text-gray-800 mb-5 text-center text-md-start"
                                        >
                                        @lang('projectx::lang.sales_orders') <br /> @lang('projectx::lang.quotes')
                                        </div>
                                        <!--end::List-->
                                        <!--begin::Link-->  
                                        <a
                                            href="{{ route('projectx.sales.orders.index') }}"
                                            class="btn btn-hover-rise text-gray-900 text-uppercase fs-7 fw-bold"
                                            style="background-color: #ebee51"
                                            >@lang('projectx::lang.view_sales_orders')</a
                                        >
                                        <!--end::Link-->
                                    </div>
                                    <!--end::Wrapper-->
                                </div>
                                <!--end::Card body-->
                            </div>
                            <!--end::Card-->
                        </div>
                        <!--end::Col-->
                    </div>
                    <!--end::Row-->
                </div>
                
                    <!--end::Menu-->
                {{-- report --}}
                <div class="row g-5 g-xl-10 mb-5 mb-xl-10">
                    <div class="col-md-6 col-lg-6 col-xl-3">
                        <div class="card card-flush border-0 h-md-100" style="background-color: #7239EA;">
                            <div class="card-header pt-5">
                                <div class="card-title d-flex flex-column">
                                    <span class="fs-2hx fw-bold text-white me-2 lh-1 ls-n2">
                                        {{ number_format($data['total_products']) }}
                                    </span>
                                    <span class="text-white opacity-75 pt-1 fw-semibold fs-6">
                                        {{ __('projectx::lang.total_products') }}
                                    </span>
                                </div>
                            </div>
                            <div class="card-body d-flex align-items-end pt-0">
                                <a href="{{ route('projectx.products') }}" class="text-white opacity-75 text-hover-white fs-7 fw-bold">
                                    {{ __('projectx::lang.view_all_products') }} &rarr;
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-6 col-xl-3">
                        <div class="card card-flush border-0 h-md-100" style="background-color: #F1416C;">
                            <div class="card-header pt-5">
                                <div class="card-title d-flex flex-column">
                                    <span class="fs-2hx fw-bold text-white me-2 lh-1 ls-n2">
                                        {{ $currency['symbol'] ?? '$' }}{{ number_format($data['sales_today'], 2) }}
                                    </span>
                                    <span class="text-white opacity-75 pt-1 fw-semibold fs-6">
                                        {{ __('projectx::lang.sales_today') }}
                                    </span>
                                </div>
                            </div>
                            <div class="card-body d-flex align-items-end pt-0">
                                <a href="{{ route('projectx.sales.orders.index') }}" class="text-white opacity-75 text-hover-white fs-7 fw-bold">
                                    {{ __('projectx::lang.view_all_sales') }} &rarr;
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-6 col-xl-3">
                        <div class="card card-flush border-0 h-md-100" style="background-color: #009EF7;">
                            <div class="card-header pt-5">
                                <div class="card-title d-flex flex-column">
                                    <span class="fs-2hx fw-bold text-white me-2 lh-1 ls-n2">
                                        {{ $currency['symbol'] ?? '$' }}{{ number_format($data['sales_this_month'], 2) }}
                                    </span>
                                    <span class="text-white opacity-75 pt-1 fw-semibold fs-6">
                                        {{ __('projectx::lang.sales_this_month') }}
                                    </span>
                                </div>
                            </div>
                            <div class="card-body d-flex align-items-end pt-0">
                                <span class="text-white opacity-75 fs-7 fw-bold">
                                    {{ date('F Y') }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-6 col-xl-3">
                        <div class="card card-flush border-0 h-md-100" style="background-color: #50CD89;">
                            <div class="card-header pt-5">
                                <div class="card-title d-flex flex-column">
                                    <span class="fs-2hx fw-bold text-white me-2 lh-1 ls-n2">
                                        {{ number_format($data['total_sales_count']) }}
                                    </span>
                                    <span class="text-white opacity-75 pt-1 fw-semibold fs-6">
                                        {{ __('projectx::lang.total_sales') }}
                                    </span>
                                </div>
                            </div>
                            <div class="card-body d-flex align-items-end pt-0">
                                <span class="badge badge-white badge-sm fw-semibold fs-8 px-3 py-2">
                                    {{ __('projectx::lang.final') }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                {{-- end report --}}
            </div>
            <!--begin::Content-->
            
        </div>
        <!--begin::Content-->
        <!--begin::Sidebar-->
        <div
            id="kt_sidebar"
            class="sidebar px-5 py-5 py-lg-8 px-lg-11"
            data-kt-drawer="true"
            data-kt-drawer-name="sidebar"
            data-kt-drawer-activate="{default: true, lg: false}"
            data-kt-drawer-overlay="true"
            data-kt-drawer-width="375px"
            data-kt-drawer-direction="end"
            data-kt-drawer-toggle="#kt_sidebar_toggle" >
            <!--begin::Header-->
            <div class="d-flex flex-stack mb-5 mb-lg-8" id="kt_sidebar_header">
                <!--begin::Title-->
                <h2 class="text-white">{{ __('projectx::lang.recent_activity') }}</h2>
                <!--end::Title-->
                <!--begin::Refresh button-->
                <div class="ms-1">
                    <button
                        id="kt_sidebar_refresh"
                        class="btn btn-icon btn-sm btn-color-white btn-active-color-primary me-n5"
                        title="{{ __('projectx::lang.refresh') }}"
                    >
                        <i class="ki-duotone ki-arrows-circle fs-2">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                    </button>
                </div>
                <!--end::Refresh button-->
            </div>
            <!--end::Header-->
            <!--begin::Body-->
            <div class="mb-5 mb-lg-8" id="kt_sidebar_body_wrap">
                <!--begin::Scroll-->
                <div
                    class="hover-scroll-y me-n6 pe-6"
                    id="kt_sidebar_body"
                    data-kt-scroll="true"
                    data-kt-scroll-height="auto"
                    data-kt-scroll-dependencies="#kt_sidebar_header, #kt_sidebar_footer"
                    data-kt-scroll-wrappers="#kt_page, #kt_sidebar, #kt_sidebar_body_wrap"
                    data-kt-scroll-offset="0"
                >
                    <!--begin::Timeline items-->
                    <div class="timeline" id="kt_sidebar_timeline">
                        <!--begin::Loading state-->
                        <div id="kt_sidebar_loading" class="d-flex justify-content-center py-10">
                            <span class="spinner-border text-white spinner-border-sm" role="status"></span>
                        </div>
                        <!--end::Loading state-->
                    </div>
                    <!--end::Timeline items-->
                </div>
                <!--end::Scroll-->
            </div>
            <!--end::Body-->
            <!--begin::Footer-->
            <div class="text-center" id="kt_sidebar_footer">
                <!--begin::Link-->
                <a
                    href="{{ route('home') }}"
                    class="btn btn-hover-rise text-white bg-white bg-opacity-10 text-uppercase fs-7 fw-bold"
                    >{{ __('projectx::lang.view_dashboard') }}</a
                >
                <!--end::Link-->
            </div>
            <!--end::Footer-->
        </div>
        <!--end::Sidebar-->
    </div>
    <!--end::Page-->
</div>


@endsection

@section('javascript')
<script>
(function () {
    'use strict';

    var ACTIVITY_URL = '{{ route("projectx.sidebar_activity") }}';

    // Icon path counts per icon type (only confirmed keenicons)
    var ICON_PATHS = {
        'ki-sms':           2,
        'ki-credit-cart':   2,
        'ki-briefcase':     2,
        'ki-bank':          2,
        'ki-basket':        4,
        'ki-abstract-26':   2,
        'ki-information-5': 2,
        'ki-arrows-circle': 2,
        'ki-bill':          3,
        'ki-information':   3,
    };

    function buildPaths(count) {
        var html = '';
        for (var i = 1; i <= count; i++) {
            html += '<span class="path' + i + '"></span>';
        }
        return html;
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function renderItem(item, isLast) {
        var contentClass = isLast ? 'timeline-content mt-n1' : 'timeline-content mb-10 mt-n1';
        var paths = ICON_PATHS[item.icon] || 2;
        var isAlert = item.is_alert;

        var amountHtml = '';
        if (item.amount_formatted !== null && item.amount_formatted !== undefined) {
            amountHtml = '<div class="d-flex flex-stack border rounded p-4 mt-3">' +
                '<div class="d-flex align-items-center me-2">' +
                '<i class="ki-duotone ki-bank fs-2x text-white me-4"><span class="path1"></span><span class="path2"></span></i>' +
                '<div class="d-flex flex-column">' +
                '<a href="' + escapeHtml(item.link) + '" class="fs-7 text-white text-hover-success fw-bold">' + escapeHtml(item.ref_no) + '</a>' +
                '<div class="text-white opacity-75">' + escapeHtml(item.amount_formatted) + '</div>' +
                '</div></div>' +
                '<a href="' + escapeHtml(item.link) + '" class="btn btn-sm btn-hover-rise text-white bg-white bg-opacity-10">View</a>' +
                '</div>';
        }

        return '<div class="timeline-item">' +
            '<div class="timeline-line w-40px"></div>' +
            '<div class="timeline-icon symbol symbol-circle symbol-40px me-4">' +
            '<div class="symbol-label' + (isAlert ? ' bg-danger bg-opacity-25' : '') + '">' +
            '<i class="ki-duotone ' + escapeHtml(item.icon) + ' fs-2 text-white">' + buildPaths(paths) + '</i>' +
            '</div></div>' +
            '<div class="' + contentClass + '">' +
            '<div class="pe-3 ' + (amountHtml ? 'mb-3' : '') + '">' +
            '<div class="fs-5 fw-semibold mb-2 text-white">' + escapeHtml(item.title) + '</div>' +
            '<div class="d-flex align-items-center mt-1 fs-6">' +
            '<div class="text-white opacity-50 me-2 fs-7">' + escapeHtml(item.time_ago) + '</div>' +
            (item.sub_label ? '<span class="text-success fs-7 fw-bold">' + escapeHtml(item.sub_label) + '</span>' : '') +
            '</div></div>' +
            amountHtml +
            '</div></div>';
    }

    function loadActivity() {
        var timeline = document.getElementById('kt_sidebar_timeline');
        var loading  = document.getElementById('kt_sidebar_loading');

        if (!timeline) return;

        if (loading) loading.style.display = 'flex';

        fetch(ACTIVITY_URL, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]') ?
                    document.querySelector('meta[name="csrf-token"]').getAttribute('content') : ''
            }
        })
        .then(function (res) { return res.json(); })
        .then(function (data) {
            var html = '';
            if (!data || data.length === 0) {
                html = '<div class="text-center py-10">' +
                    '<div class="text-white opacity-50 fs-6">{{ __("projectx::lang.no_data_found") }}</div>' +
                    '</div>';
            } else {
                for (var i = 0; i < data.length; i++) {
                    html += renderItem(data[i], i === data.length - 1);
                }
            }
            timeline.innerHTML = html;
        })
        .catch(function () {
            if (timeline) {
                timeline.innerHTML = '<div class="text-center py-10">' +
                    '<div class="text-white opacity-50 fs-6">{{ __("projectx::lang.could_not_load") }}</div>' +
                    '</div>';
            }
        });
    }

    // Load on page ready
    document.addEventListener('DOMContentLoaded', function () {
        loadActivity();

        var refreshBtn = document.getElementById('kt_sidebar_refresh');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', function () {
                var icon = this.querySelector('i');
                if (icon) icon.style.transform = 'rotate(360deg)';
                loadActivity();
                setTimeout(function () {
                    if (icon) icon.style.transform = '';
                }, 500);
            });
        }
    });

    // Auto-refresh every 20 seconds
    setInterval(loadActivity, 20000);
}());
</script>
@endsection
