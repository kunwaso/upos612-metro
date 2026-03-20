@extends('layouts.app')
@section('title', __('purchase.purchases'))

@section('content')
    <div class="no-print w-100">
        <div class="mb-5">
            <h1 class="mb-1 text-gray-900 fw-bolder fs-2x">@lang('purchase.purchases')</h1>
            <div class="text-muted fw-semibold fs-6">
                @lang('purchase.list_purchase')
            </div>
            <ul class="breadcrumb breadcrumb-dot fw-semibold text-gray-600 fs-7 my-1">
                <li class="breadcrumb-item text-gray-600">
                    <a href="{{ route('home') }}" class="text-gray-600 text-hover-primary">@lang('home.home')</a>
                </li>
                <li class="breadcrumb-item text-gray-500">@lang('purchase.purchases')</li>
            </ul>
        </div>

        <div class="card card-flush mb-5">
            <div class="card-header border-0 pt-6 align-items-center">
                <div class="card-title"></div>
                <div class="card-toolbar">
                    <button type="button" class="btn btn-light-primary" data-bs-toggle="collapse"
                        data-bs-target="#purchase_list_filters_panel" aria-expanded="false"
                        aria-controls="purchase_list_filters_panel">
                        <i class="ki-duotone ki-filter fs-2">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                        @lang('report.filters')
                    </button>
                </div>
            </div>
            <div class="collapse" id="purchase_list_filters_panel">
                <div class="card-body border-top border-gray-200 pt-6">
                    <div class="row g-5">
                        <div class="col-md-6 col-xl-3">
                            {!! Form::label('purchase_list_filter_location_id', __('purchase.business_location'), ['class' => 'form-label fw-semibold']) !!}
                            {!! Form::select('purchase_list_filter_location_id', $business_locations, null, [
                                'class' => 'form-control select2 w-100',
                                'placeholder' => __('lang_v1.all'),
                            ]) !!}
                        </div>
                        <div class="col-md-6 col-xl-3">
                            {!! Form::label('purchase_list_filter_supplier_id', __('purchase.supplier'), ['class' => 'form-label fw-semibold']) !!}
                            {!! Form::select('purchase_list_filter_supplier_id', $suppliers, null, [
                                'class' => 'form-control select2 w-100',
                                'placeholder' => __('lang_v1.all'),
                            ]) !!}
                        </div>
                        <div class="col-md-6 col-xl-3">
                            {!! Form::label('purchase_list_filter_status', __('purchase.purchase_status'), ['class' => 'form-label fw-semibold']) !!}
                            {!! Form::select('purchase_list_filter_status', $orderStatuses, null, [
                                'class' => 'form-control select2 w-100',
                                'placeholder' => __('lang_v1.all'),
                            ]) !!}
                        </div>
                        <div class="col-md-6 col-xl-3">
                            {!! Form::label('purchase_list_filter_payment_status', __('purchase.payment_status'), ['class' => 'form-label fw-semibold']) !!}
                            {!! Form::select(
                                'purchase_list_filter_payment_status',
                                [
                                    'paid' => __('lang_v1.paid'),
                                    'due' => __('lang_v1.due'),
                                    'partial' => __('lang_v1.partial'),
                                    'overdue' => __('lang_v1.overdue'),
                                ],
                                null,
                                ['class' => 'form-control select2 w-100', 'placeholder' => __('lang_v1.all')],
                            ) !!}
                        </div>
                        <div class="col-md-6 col-xl-3">
                            {!! Form::label('purchase_list_filter_date_range', __('report.date_range'), ['class' => 'form-label fw-semibold']) !!}
                            {!! Form::text('purchase_list_filter_date_range', null, [
                                'placeholder' => __('lang_v1.select_a_date_range'),
                                'class' => 'form-control form-control-solid',
                                'readonly',
                            ]) !!}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card card-flush">
            <div class="card-header border-0 pt-6">
                <div class="card-title">
                    <span class="fs-4 fw-bold text-gray-900">@lang('purchase.all_purchases')</span>
                </div>
                <div class="card-toolbar">
                    @can('purchase.create')
                        <a class="btn btn-primary"
                            href="{{ action([\App\Http\Controllers\PurchaseController::class, 'create']) }}">
                            <i class="ki-duotone ki-plus fs-2">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            @lang('messages.add')
                        </a>
                    @endcan
                </div>
            </div>
            <div class="card-body pt-0">
                @include('purchase.partials.purchase_table')
            </div>
        </div>

        <div class="modal fade product_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
        </div>

        <div class="modal fade payment_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
        </div>

        <div class="modal fade edit_payment_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
        </div>

        @include('purchase.partials.update_purchase_status_modal')
    </div>

    <section id="receipt_section" class="print_section"></section>
@stop

@section('javascript')
    <script>
        var customFieldVisibility = @json($purchase_custom_field_visibility);
    </script>
    <script src="{{ asset('assets/app/js/purchase.js?v=' . $asset_v) }}"></script>
    <script src="{{ asset('assets/app/js/payment.js?v=' . $asset_v) }}"></script>
    <script>
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function(el) {
            if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip && !bootstrap.Tooltip.getInstance(el)) {
                new bootstrap.Tooltip(el);
            }
        });

        $('#purchase_list_filter_date_range').daterangepicker(
            dateRangeSettings,
            function(start, end) {
                $('#purchase_list_filter_date_range').val(start.format(moment_date_format) + ' ~ ' + end.format(
                    moment_date_format));
                purchase_table.ajax.reload();
            }
        );
        $('#purchase_list_filter_date_range').on('cancel.daterangepicker', function(ev, picker) {
            $('#purchase_list_filter_date_range').val('');
            purchase_table.ajax.reload();
        });

        $(document).on('click', '.update_status', function(e) {
            e.preventDefault();
            $('#update_purchase_status_form').find('#status').val($(this).data('status'));
            $('#update_purchase_status_form').find('#purchase_id').val($(this).data('purchase_id'));
            $('#update_purchase_status_modal').modal('show');
        });

        $(document).on('submit', '#update_purchase_status_form', function(e) {
            e.preventDefault();
            var form = $(this);
            var data = form.serialize();

            $.ajax({
                method: 'POST',
                url: $(this).attr('action'),
                dataType: 'json',
                data: data,
                beforeSend: function(xhr) {
                    __disable_submit_button(form.find('button[type="submit"]'));
                },
                success: function(result) {
                    if (result.success == true) {
                        $('#update_purchase_status_modal').modal('hide');
                        toastr.success(result.msg);
                        purchase_table.ajax.reload();
                        $('#update_purchase_status_form')
                            .find('button[type="submit"]')
                            .attr('disabled', false);
                    } else {
                        toastr.error(result.msg);
                    }
                },
            });
        });
    </script>
@endsection
