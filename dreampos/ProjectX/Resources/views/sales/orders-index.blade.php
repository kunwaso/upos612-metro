@extends('projectx::layouts.main')

@section('title', __('projectx::lang.sales_orders'))

@section('content')



<div class="d-flex flex-wrap flex-stack mb-6">
    <!--begin::Col sales progress-->
    <div class="col-xl-4 px-5">    
        <div class="card h-xl-100"> 
         <!--begin::Header-->
         <div class="card-header border-0 bg-success py-5">
            <h3 class="card-title fw-bold text-white">Sales Progress</h3>
        </div>
        <!--end::Header-->
        <!--begin::Body-->
        <div class="card-body p-0">
            <!--begin::Chart-->
            <div class="mixed-widget-12-chart card-rounded-bottom bg-success" data-kt-color="success" style="height: 250px"></div>
            <!--end::Chart-->
            <!--begin::Stats-->
            <div class="card-rounded bg-body mt-n10 position-relative card-px py-10">
                <!--begin::Row-->
                <div class="row g-0 mb-7">
                    <!--begin::Col-->
                    <div class="col mx-5">
                        <div class="fs-6 text-gray-500">Fabrics sold</div>
                        <div class="fs-2 fw-bold text-gray-800">$650</div>
                    </div>
                    <!--end::Col-->
                    <!--begin::Col-->
                    <div class="col mx-5">
                        <div class="fs-6 text-gray-500">Trim sold</div>
                        <div class="fs-2 fw-bold text-gray-800">$29,500</div>
                    </div>
                    <!--end::Col-->
                </div>
                <!--end::Row-->
                <!--begin::Row-->
                <div class="row g-0">
                    <!--begin::Col-->
                    <div class="col mx-5">
                        <div class="fs-6 text-gray-500">total sales</div>
                        <div class="fs-2 fw-bold text-gray-800">$55,000</div>
                    </div>
                    <!--end::Col-->
                    <!--begin::Col-->
                    <div class="col mx-5">
                        <div class="fs-6 text-gray-500">total orders</div>
                        <div class="fs-2 fw-bold text-gray-800">20</div>
                    </div>
                    <!--end::Col-->
                </div>
                <!--end::Row-->
            </div>
            <!--end::Stats-->
        </div>
        <!--end::Body-->
        </div>  
    </div>
        
    <!--end::Col-->     

    <!--begin::Col-->
    <div class="col-xl-8">
        <!--begin::sales order delivery schedule-->
    <div class="card card-flush h-xl-100">
        <!--begin::Card header-->
        <div class="card-header pt-5">
            <!--begin::Card title-->
            <h3 class="card-title align-items-start flex-column">
                <span class="card-label fw-bold text-gray-900">sales order delivery Schedule</span>

                <span class="text-gray-500 pt-2 fw-semibold fs-6">20 sales orders</span>
            </h3>
            <!--end::Card title-->

            <!--begin::Card toolbar-->
            <div class="card-toolbar">
                <!--begin::Tabs-->
                <ul class="nav" role="tablist">
                    <li class="nav-item" role="presentation">
                        <a class="nav-link btn btn-sm btn-color-muted btn-active btn-active-light fw-bold px-4 me-1 active" data-kt-timeline-widget-1="tab" data-bs-toggle="tab" href="#kt_timeline_widget_1_tab_day" aria-selected="true" role="tab">week</a>
                    </li>

                    <li class="nav-item" role="presentation">
                        <a class="nav-link btn btn-sm btn-color-muted btn-active btn-active-light fw-bold px-4 me-1" data-kt-timeline-widget-1="tab" data-bs-toggle="tab" href="#kt_timeline_widget_1_tab_week" aria-selected="false" tabindex="-1" role="tab">month</a>
                    </li>

                    <li class="nav-item" role="presentation">
                        <a class="nav-link btn btn-sm btn-color-muted btn-active btn-active-light fw-bold px-4 me-1" data-kt-timeline-widget-1="tab" data-bs-toggle="tab" href="#kt_timeline_widget_1_tab_month" aria-selected="false" tabindex="-1" role="tab">Year</a>
                    </li>
                </ul>
                <!--end::Tabs-->
            </div>
            <!--end::Card toolbar-->
        </div>
        <!--end::Card header-->

        <!--begin::Card body-->
        <div class="card-body pb-0">
            <!--begin::Tab content-->
            <div class="tab-content">
                <!--begin::Tab pane-->
                <div class="tab-pane active blockui" id="kt_timeline_widget_1_tab_day" role="tabpanel" aria-labelledby="day-tab" data-kt-timeline-widget-1-blockui="true" style="">
                    <div class="table-responsive pb-10">
                        <!--begin::Timeline-->
                        <div id="kt_timeline_widget_1_1" class="vis-timeline-custom h-350px min-w-700px" data-kt-timeline-widget-1-image-root="/metronic8/demo10/assets/media/" style="position: relative;"><div class="vis-timeline vis-bottom vis-ltr" style="touch-action: pan-y; user-select: none; -webkit-user-drag: none; -webkit-tap-highlight-color: rgba(0, 0, 0, 0); visibility: visible; height: 354px;"><div class="vis-panel vis-background" style="height: 354px; width: 700px; left: 0px; top: 0px;"></div><div class="vis-panel vis-background vis-vertical" style="height: 354px; width: 573px; left: 129px; top: 0px;"><div class="vis-axis" style="top: 304px; left: 0px;"><div class="vis-group"></div><div class="vis-group"></div><div class="vis-group"></div><div class="vis-group"></div></div><div class="vis-time-axis vis-background"><div class="vis-grid vis-vertical vis-minor vis-h10  vis-today  vis-even" style="width: 184.333px; height: 330px; transform: translate(-152.302px, -1px);"></div><div class="vis-grid vis-vertical vis-minor vis-h11  vis-today  vis-odd" style="width: 184.333px; height: 330px; transform: translate(32.0316px, -1px);"></div><div class="vis-grid vis-vertical vis-minor vis-h12  vis-today  vis-even" style="width: 184.333px; height: 330px; transform: translate(216.365px, -1px);"></div><div class="vis-grid vis-vertical vis-minor vis-h13  vis-today  vis-odd" style="width: 184.333px; height: 330px; transform: translate(400.698px, -1px);"></div></div></div><div class="vis-panel vis-background vis-horizontal" style="height: 305px; width: 700px; left: 0px; top: -1px;"></div><div class="vis-panel vis-center" style="touch-action: pan-y; user-select: none; -webkit-user-drag: none; -webkit-tap-highlight-color: rgba(0, 0, 0, 0); height: 305px; width: 573px; left: 128px; top: -1px;"><div class="vis-content" style="left: 0px; transform: translateY(0px);"><div class="vis-itemset" style="height: 303px;"><div class="vis-background"><div class="vis-group" style="height: 0px;"><div style="visibility: hidden; position: absolute;"></div></div><div class="vis-group" style="height: 75px;"><div style="visibility: hidden; position: absolute;"></div></div><div class="vis-group" style="height: 75px;"><div style="visibility: hidden; position: absolute;"></div></div><div class="vis-group" style="height: 75px;"><div style="visibility: hidden; position: absolute;"></div></div><div class="vis-group" style="height: 78px;"><div style="visibility: hidden; position: absolute;"></div></div></div><div class="vis-foreground"><div class="vis-group" style="height: 75px;"><div class="vis-item vis-range vis-readonly" style="transform: translateX(10px); width: 276.5px; top: 17.5px;"><div class="vis-item-overflow"><div class="vis-item-content" style="transform: translateX(0px);"><div class="rounded-pill bg-light-primary d-flex align-items-center position-relative h-40px w-100 p-2 overflow-hidden">
                        <div class="position-absolute rounded-pill d-block bg-primary start-0 top-0 h-100 z-index-1" style="width:60%;"></div>
            
                        <div class="d-flex align-items-center position-relative z-index-2">
                            <div class="symbol-group symbol-hover flex-nowrap me-3">
                                <div class="symbol symbol-circle symbol-25px"><img alt="" src="/metronic8/demo10/assets/media/avatars/300-6.jpg"></div><div class="symbol symbol-circle symbol-25px"><img alt="" src="/metronic8/demo10/assets/media/avatars/300-1.jpg"></div>
                            </div>
            
                            <a href="#" class="fw-bold text-white text-hover-dark">sales order 1</a>
                        </div>
            
                        <div class="d-flex flex-center bg-body rounded-pill fs-7 fw-bolder ms-auto h-100 px-3 position-relative z-index-2">
                            60%
                        </div>
                    </div>        
                    </div></div><div class="vis-item-visible-frame"></div></div></div><div class="vis-group" style="height: 75px;"><div class="vis-item vis-range vis-readonly" style="transform: translateX(194.333px); width: 184.333px; top: 17.5px;"><div class="vis-item-overflow"><div class="vis-item-content" style="transform: translateX(0px);"><div class="rounded-pill bg-light-success d-flex align-items-center position-relative h-40px w-100 p-2 overflow-hidden">
                        <div class="position-absolute rounded-pill d-block bg-success start-0 top-0 h-100 z-index-1" style="width:47%;"></div>
            
                        <div class="d-flex align-items-center position-relative z-index-2">
                            <div class="symbol-group symbol-hover flex-nowrap me-3">
                                <div class="symbol symbol-circle symbol-25px"><img alt="" src="/metronic8/demo10/assets/media/avatars/300-2.jpg"></div>
                            </div>
            
                            <a href="#" class="fw-bold text-white text-hover-dark">sales order 2</a>
                        </div>
            
                        <div class="d-flex flex-center bg-body rounded-pill fs-7 fw-bolder ms-auto h-100 px-3 position-relative z-index-2">
                            47%
                        </div>
                    </div>        
                    </div></div><div class="vis-item-visible-frame"></div></div></div><div class="vis-group" style="height: 75px;"><div class="vis-item vis-range vis-readonly" style="transform: translateX(102.167px); width: 368.667px; top: 17.5px;"><div class="vis-item-overflow"><div class="vis-item-content" style="transform: translateX(0px);"><div class="rounded-pill bg-light-danger d-flex align-items-center position-relative h-40px w-100 p-2 overflow-hidden">
                        <div class="position-absolute rounded-pill d-block bg-danger start-0 top-0 h-100 z-index-1" style="width:55%;"></div>
            
                        <div class="d-flex align-items-center position-relative z-index-2">
                            <div class="symbol-group symbol-hover flex-nowrap me-3">
                                <div class="symbol symbol-circle symbol-25px"><img alt="" src="/metronic8/demo10/assets/media/avatars/300-5.jpg"></div><div class="symbol symbol-circle symbol-25px"><img alt="" src="/metronic8/demo10/assets/media/avatars/300-20.jpg"></div>
                            </div>
            
                            <a href="#" class="fw-bold text-white text-hover-dark">sales order 3</a>
                        </div>
            
                        <div class="d-flex flex-center bg-body rounded-pill fs-7 fw-bolder ms-auto h-100 px-3 position-relative z-index-2">
                            55%
                        </div>
                    </div>        
                    </div></div><div class="vis-item-visible-frame"></div></div></div><div class="vis-group" style="height: 78px;"><div class="vis-item vis-range vis-readonly" style="transform: translateX(286.5px); width: 276.5px; top: 18px;"><div class="vis-item-overflow"><div class="vis-item-content" style="transform: translateX(0px);"><div class="rounded-pill bg-light-info d-flex align-items-center position-relative h-40px w-100 p-2 overflow-hidden">
                        <div class="position-absolute rounded-pill d-block bg-info start-0 top-0 h-100 z-index-1" style="width:75%;"></div>
            
                        <div class="d-flex align-items-center position-relative z-index-2">
                            <div class="symbol-group symbol-hover flex-nowrap me-3">
                                <div class="symbol symbol-circle symbol-25px"><img alt="" src="/metronic8/demo10/assets/media/avatars/300-23.jpg"></div><div class="symbol symbol-circle symbol-25px"><img alt="" src="/metronic8/demo10/assets/media/avatars/300-12.jpg"></div><div class="symbol symbol-circle symbol-25px"><img alt="" src="/metronic8/demo10/assets/media/avatars/300-9.jpg"></div>
                            </div>
            
                            <a href="#" class="fw-bold text-white text-hover-dark">sales order 4</a>
                        </div>
            
                        <div class="d-flex flex-center bg-body rounded-pill fs-7 fw-bolder ms-auto h-100 px-3 position-relative z-index-2">
                            75%
                        </div>
                    </div>        
                    </div></div><div class="vis-item-visible-frame"></div></div></div></div></div></div><div class="vis-shadow vis-top" style="visibility: hidden;"></div><div class="vis-shadow vis-bottom" style="visibility: hidden;"></div></div><div class="vis-panel vis-left" style="touch-action: none; user-select: none; -webkit-user-drag: none; -webkit-tap-highlight-color: rgba(0, 0, 0, 0); height: 305px; left: 0px; top: -1px;"><div class="vis-content" style="left: 0px; top: 0px;"><div class="vis-labelset"><div class="vis-label vis-group-level-0" title="" style="height: 75px;"><div class="vis-inner">Research</div></div><div class="vis-label vis-group-level-0" title="" style="height: 75px;"><div class="vis-inner">Phase 2.6 QA</div></div><div class="vis-label vis-group-level-0" title="" style="height: 75px;"><div class="vis-inner">UI Design</div></div><div class="vis-label vis-group-level-0" title="" style="height: 78px;"><div class="vis-inner">Development</div></div></div></div><div class="vis-shadow vis-top" style="visibility: hidden;"></div><div class="vis-shadow vis-bottom" style="visibility: hidden;"></div></div><div class="vis-panel vis-right" style="height: 305px; left: 701px; top: -1px;"><div class="vis-content" style="left: 0px; top: 0px;"></div><div class="vis-shadow vis-top" style="visibility: hidden;"></div><div class="vis-shadow vis-bottom" style="visibility: hidden;"></div></div><div class="vis-panel vis-top" style="width: 573px; left: 128px; top: 0px;"></div><div class="vis-panel vis-bottom" style="width: 573px; left: 128px; top: 304px;"><div class="vis-time-axis vis-foreground" style="height: 50px;"><div class="vis-text vis-minor vis-measure" style="position: absolute;">0</div><div class="vis-text vis-major vis-measure" style="position: absolute;">0</div><div class="vis-text vis-minor vis-h10  vis-today  vis-even" style="transform: translate(-151.802px, 0px); width: 184.333px;">10:00</div><div class="vis-text vis-minor vis-h11  vis-today  vis-odd" style="transform: translate(32.5316px, 0px); width: 184.333px;">11:00</div><div class="vis-text vis-minor vis-h12  vis-today  vis-even" style="transform: translate(216.865px, 0px); width: 184.333px;">12:00</div><div class="vis-text vis-minor vis-h13  vis-today  vis-odd" style="transform: translate(401.198px, 0px); width: 184.333px;">13:00</div><div class="vis-text vis-major vis-h13  vis-today  vis-odd" style="transform: translate(0px, 25px);"><div>Thu 5 March</div></div></div></div><div class="vis-rolling-mode-btn" style="visibility: hidden;"></div></div></div>
                        <!--end::Timeline-->
                    </div>
                </div>
                <!--end::Tab pane-->

                <!--begin::Tab pane-->
                <div class="tab-pane blockui" id="kt_timeline_widget_1_tab_week" role="tabpanel" aria-labelledby="week-tab" data-kt-timeline-widget-1-blockui="true" style="overflow: hidden;">
                    <div class="table-responsive pb-10">
                        <!--begin::Timeline-->
                        <div id="kt_timeline_widget_1_2" class="vis-timeline-custom h-350px min-w-700px" data-kt-timeline-widget-1-image-root="/metronic8/demo10/assets/media/"></div>
                        <!--end::Timeline-->
                    </div>
                <div class="blockui-overlay bg-body" style="z-index: 1;"><span class="spinner-border text-primary"></span></div></div>
                <!--end::Tab pane-->

                <!--begin::Tab pane-->
                <div class="tab-pane blockui" id="kt_timeline_widget_1_tab_month" role="tabpanel" aria-labelledby="month-tab" data-kt-timeline-widget-1-blockui="true" style="overflow: hidden;">
                    <div class="table-responsive pb-10">
                        <!--begin::Timeline-->
                        <div id="kt_timeline_widget_1_3" class="vis-timeline-custom h-350px min-w-700px" data-kt-timeline-widget-1-image-root="/metronic8/demo10/assets/media/"></div>
                        <!--end::Timeline-->
                    </div>
                <div class="blockui-overlay bg-body" style="z-index: 1;"><span class="spinner-border text-primary"></span></div></div>
                <!--end::Tab pane-->
            </div>
            <!--end::Tab content-->
        </div>
        <!--end::Card body-->
    </div>
<!--end::sales order delivery schedule-->    </div>
<!--end::Col-->
</div>



<div class="d-flex flex-wrap flex-stack mb-6">
    <div>
        <h1 class="text-gray-900 fw-bold mb-1">{{ __('projectx::lang.sales_orders') }}</h1>
        <div class="text-muted fw-semibold fs-6">{{ __('projectx::lang.sales_orders_description') }}</div>
    </div>
    <a href="{{ route('projectx.sales') }}" class="btn btn-light-primary btn-sm">
        <i class="ki-duotone ki-document fs-5 me-1"><span class="path1"></span><span class="path2"></span></i>
        {{ __('projectx::lang.fabric_quotes') }}
    </a>
</div>

<div class="card card-flush">
    <div class="card-header pt-7">
        <h3 class="card-title fw-bold text-gray-900">{{ __('projectx::lang.sales_orders') }}</h3>
    </div>
    <div class="card-body pt-5">
        <div class="table-responsive">
            <table class="table table-row-dashed table-row-gray-300 align-middle gs-0 gy-4" id="projectx_orders_table">
                <thead>
                    <tr class="fw-bold text-muted text-uppercase fs-7">
                        <th>{{ __('projectx::lang.invoice_no') }}</th>
                        <th>{{ __('projectx::lang.quote_no') }}</th>
                        <th>{{ __('projectx::lang.quote_type') }}</th>
                        <th>{{ __('projectx::lang.customer') }}</th>
                        <th>{{ __('projectx::lang.location') }}</th>
                        <th>{{ __('projectx::lang.date') }}</th>
                        <th>{{ __('projectx::lang.status') }}</th>
                        <th>{{ __('projectx::lang.grand_total') }}</th>
                        <th>{{ __('projectx::lang.payment_status') }}</th>
                        <th class="text-end">{{ __('projectx::lang.action') }}</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('page_javascript')
<script>
    (function () {
        const table = $('#projectx_orders_table');
        if (!table.length) {
            return;
        }

        table.DataTable({
            processing: true,
            serverSide: true,
            ajax: '{{ route('projectx.sales.orders.index') }}',
            order: [[5, 'desc']],
            columns: [
                {data: 'invoice_no', name: 'transactions.invoice_no'},
                {data: 'quote_number', name: 'pq.quote_number'},
                {data: 'quote_type_badge', name: 'quote_type_badge', orderable: false, searchable: false},
                {data: 'customer_name', name: 'customer_name', defaultContent: '-'},
                {data: 'location_name', name: 'location_name', defaultContent: '-'},
                {data: 'transaction_date', name: 'transactions.transaction_date'},
                {data: 'status_badge', name: 'status_badge', orderable: false, searchable: false},
                {data: 'final_total', name: 'transactions.final_total'},
                {data: 'payment_status', name: 'transactions.payment_status'},
                {data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-end'}
            ]
        });

        $(document).on('click', '.js-toggle-order-hold', function () {
            const btn = $(this);
            const orderId = btn.data('order-id');
            const isOnHold = String(btn.data('is-on-hold')) === '1';

            $.ajax({
                method: 'PATCH',
                url: '{{ url('/projectx/sales/orders') }}/' + orderId + '/hold',
                data: {
                    _token: '{{ csrf_token() }}',
                    is_on_hold: isOnHold ? 0 : 1
                },
                success: function (response) {
                    if (response && response.success) {
                        table.DataTable().ajax.reload(null, false);
                    } else if (response && response.msg) {
                        alert(response.msg);
                    }
                },
                error: function (xhr) {
                    if (xhr.responseJSON && xhr.responseJSON.msg) {
                        alert(xhr.responseJSON.msg);
                    }
                }
            });
        });
    })();
</script>
@endsection
