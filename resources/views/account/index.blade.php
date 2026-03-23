@extends('layouts.app')
@section('title', __('lang_v1.payment_accounts'))

@section('content')
{{-- Toolbar + Breadcrumb --}}
<div id="kt_toolbar" class="toolbar py-3 py-lg-5">
    <div id="kt_toolbar_container" class="container-xxl d-flex flex-stack flex-wrap">
        <div class="page-title d-flex flex-column align-items-start me-3 py-2 gap-2">
            <h1 class="d-flex text-dark fw-bold fs-3 mb-0">
                @lang('lang_v1.payment_accounts')
                <span class="text-gray-500 fw-normal fs-6 ms-2">@lang('account.manage_your_account')</span>
            </h1>
            <ul class="breadcrumb breadcrumb-dot fw-semibold text-gray-600 fs-7">
                <li class="breadcrumb-item text-gray-600"><a href="{{ route('home') }}" class="text-gray-600 text-hover-primary">@lang('home.home')</a></li>
                <li class="breadcrumb-item text-gray-900">@lang('lang_v1.payment_accounts')</li>
            </ul>
        </div>
    </div>
</div>
<div class="d-flex flex-column-fluid align-items-start container-xxl">
    <div class="content flex-row-fluid" id="kt_content">
        @if (!empty($not_linked_payments))
            <div class="row">
                <div class="col-sm-12">
                    <div class="alert alert-danger">
                        <ul>
                            @if (!empty($not_linked_payments))
                                <li>{!! __('account.payments_not_linked_with_account', ['payments' => $not_linked_payments]) !!} <a
                                        href="{{ action([\App\Http\Controllers\AccountReportsController::class, 'paymentAccountReport']) }}">@lang('account.view_details')</a>
                                </li>
                            @endif
                        </ul>
                    </div>
                </div>
            </div>
        @endif
        @can('account.access')
            <div class="row">
                @component('components.widget')
                    <div class="col-sm-12">
                        <div class="nav-tabs-custom">
                            <ul class="nav nav-tabs">
                                <li class="active">
                                    <a href="#other_accounts" data-toggle="tab">
                                        <i class="fa fa-book"></i> <strong>@lang('account.accounts')</strong>
                                    </a>
                                </li>
                                {{--
                    <li>
                        <a href="#capital_accounts" data-toggle="tab">
                            <i class="fa fa-book"></i> <strong>
                            @lang('account.capital_accounts') </strong>
                        </a>
                    </li>
                    --}}
                                <li>
                                    <a href="#account_types" data-toggle="tab">
                                        <i class="fa fa-list"></i> <strong>
                                            @lang('lang_v1.account_types') </strong>
                                    </a>
                                </li>
                            </ul>
                            <div class="tab-content">
                                <div class="tab-pane active" id="other_accounts">
                                    <div class="row">
                                        <div class="col-md-12">
                                            {{-- @component('components.widget') --}}
                                            <div class="col-md-4">
                                                {!! Form::select(
                                                    'account_status',
                                                    ['active' => __('business.is_active'), 'closed' => __('account.closed')],
                                                    null,
                                                    ['class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'account_status'],
                                                ) !!}
                                            </div>
                                            <div class="col-md-8">
                                                    <button type="button" class="tw-dw-btn tw-bg-gradient-to-r tw-from-indigo-600 tw-to-blue-500 tw-font-bold tw-text-white tw-border-none tw-rounded-full btn-modal pull-right"
                                                        data-container=".account_model"
                                                        data-href="{{ action([\App\Http\Controllers\AccountController::class, 'create']) }}">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                            class="icon icon-tabler icons-tabler-outline icon-tabler-plus">
                                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                            <path d="M12 5l0 14" />
                                                            <path d="M5 12l14 0" />
                                                        </svg> @lang('messages.add')
                                                    </button>
                                            </div>
                                            {{-- @endcomponent --}}
                                        </div>
                                        <div class="col-sm-12">
                                            <br>
                                            <div class="table-responsive">
                                                <table class="table table-bordered table-striped" id="other_account_table">
                                                    <thead>
                                                        <tr>
                                                            <th>@lang('lang_v1.name')</th>
                                                            <th>@lang('lang_v1.account_type')</th>
                                                            <th>@lang('lang_v1.account_sub_type')</th>
                                                            <th>@lang('account.account_number')</th>
                                                            <th>@lang('brand.note')</th>
                                                            <th>@lang('lang_v1.balance')</th>
                                                            <th>@lang('lang_v1.account_details')</th>
                                                            <th>@lang('lang_v1.added_by')</th>
                                                            <th>@lang('messages.action')</th>
                                                        </tr>
                                                    </thead>
                                                    <tfoot>
                                                        <tr class="bg-gray font-17 footer-total text-center">
                                                            <td colspan="5"><strong>@lang('sale.total'):</strong></td>
                                                            <td class="footer_total_balance"></td>
                                                            <td colspan="3"></td>
                                                        </tr>
                                                    </tfoot>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                {{--
                    <div class="tab-pane" id="capital_accounts">
                        <table class="table table-bordered table-striped" id="capital_account_table" style="width: 100%;">
                            <thead>
                                <tr>
                                    <th>@lang( 'lang_v1.name' )</th>
                                    <th>@lang('account.account_number')</th>
                                    <th>@lang( 'brand.note' )</th>
                                    <th>@lang('lang_v1.balance')</th>
                                    <th>@lang( 'messages.action' )</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                    --}}
                                <div class="tab-pane" id="account_types">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <button type="button" class="btn btn-primary btn-sm btn-modal pull-right"
                                                data-href="{{ action([\App\Http\Controllers\AccountTypeController::class, 'create']) }}"
                                                data-container="#account_type_modal">
                                                <i class="fa fa-plus"></i> @lang('messages.add')</button>
                                        </div>
                                    </div>
                                    <br>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <table class="table table-striped table-bordered" id="account_types_table"
                                                style="width: 100%;">
                                                <thead>
                                                    <tr>
                                                        <th>@lang('lang_v1.name')</th>
                                                        <th>@lang('messages.action')</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($account_types as $account_type)
                                                        <tr class="account_type_{{ $account_type->id }}">
                                                            <th>{{ $account_type->name }}</th>
                                                            <td>

                                                                {!! Form::open([
                                                                    'url' => action([\App\Http\Controllers\AccountTypeController::class, 'destroy'], $account_type->id),
                                                                    'method' => 'delete',
                                                                ]) !!}
                                                                <button type="button" class="btn btn-sm btn-light-info btn-modal"
                                                                    data-href="{{ action([\App\Http\Controllers\AccountTypeController::class, 'edit'], $account_type->id) }}"
                                                                    data-container="#account_type_modal">
                                                                    <i class="fa fa-edit"></i> @lang('messages.edit')</button>

                                                                <button type="button"
                                                                    class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline  tw-dw-btn-error delete_account_type">
                                                                    <i class="fa fa-trash"></i> @lang('messages.delete')</button>
                                                                {!! Form::close() !!}
                                                            </td>
                                                        </tr>
                                                        @foreach ($account_type->sub_types as $sub_type)
                                                            <tr>
                                                                <td>&nbsp;&nbsp;-- {{ $sub_type->name }}</td>
                                                                <td>


                                                                    {!! Form::open([
                                                                        'url' => action([\App\Http\Controllers\AccountTypeController::class, 'destroy'], $sub_type->id),
                                                                        'method' => 'delete',
                                                                    ]) !!}
                                                                    <button type="button" class="btn btn-sm btn-light-info btn-modal"
                                                                        data-href="{{ action([\App\Http\Controllers\AccountTypeController::class, 'edit'], $sub_type->id) }}"
                                                                        data-container="#account_type_modal">
                                                                        <i class="fa fa-edit"></i> @lang('messages.edit')</button>
                                                                    <button type="button"
                                                                        class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline  tw-dw-btn-error delete_account_type">
                                                                        <i class="fa fa-trash"></i> @lang('messages.delete')</button>
                                                                    {!! Form::close() !!}
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endcomponent
            </div>
        @endcan

        <div class="modal fade account_model" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
        </div>

        <div class="modal fade" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"
            id="account_type_modal">
        </div>
    </div>
</div>
<!-- /.content -->

@endsection

@section('javascript')
    <script>
        $(document).ready(function() {

            $(document).on('click', 'button.close_account', function() {
                swal({
                    title: LANG.sure,
                    icon: "warning",
                    buttons: true,
                    dangerMode: true,
                }).then((willDelete) => {
                    if (willDelete) {
                        var url = $(this).data('url');

                        $.ajax({
                            method: "get",
                            url: url,
                            dataType: "json",
                            success: function(result) {
                                if (result.success == true) {
                                    toastr.success(result.msg);
                                    capital_account_table.ajax.reload();
                                    other_account_table.ajax.reload();
                                } else {
                                    toastr.error(result.msg);
                                }

                            }
                        });
                    }
                });
            });

            $(document).on('submit', 'form#edit_payment_account_form', function(e) {
                e.preventDefault();
                var data = $(this).serialize();
                $.ajax({
                    method: "POST",
                    url: $(this).attr("action"),
                    dataType: "json",
                    data: data,
                    success: function(result) {
                        if (result.success == true) {
                            $('div.account_model').modal('hide');
                            toastr.success(result.msg);
                            capital_account_table.ajax.reload();
                            other_account_table.ajax.reload();
                        } else {
                            toastr.error(result.msg);
                        }
                    }
                });
            });

            $(document).on('submit', 'form#payment_account_form', function(e) {
                e.preventDefault();
                var data = $(this).serialize();
                $.ajax({
                    method: "post",
                    url: $(this).attr("action"),
                    dataType: "json",
                    data: data,
                    success: function(result) {
                        if (result.success == true) {
                            $('div.account_model').modal('hide');
                            toastr.success(result.msg);
                            capital_account_table.ajax.reload();
                            other_account_table.ajax.reload();
                        } else {
                            toastr.error(result.msg);
                        }
                    }
                });
            });

            // capital_account_table
            capital_account_table = $('#capital_account_table').DataTable({
                processing: true,
                serverSide: true,
                fixedHeader:false,
                ajax: '/account/account?account_type=capital',
                columnDefs: [{
                    "targets": 5,
                    "orderable": false,
                    "searchable": false
                }],
                columns: [{
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'account_number',
                        name: 'account_number'
                    },
                    {
                        data: 'note',
                        name: 'note'
                    },
                    {
                        data: 'balance',
                        name: 'balance',
                        searchable: false
                    },
                    {
                        data: 'action',
                        name: 'action'
                    }
                ],
                "fnDrawCallback": function(oSettings) {
                    __currency_convert_recursively($('#capital_account_table'));
                }
            });
            // capital_account_table
            other_account_table = $('#other_account_table').DataTable({
                processing: true,
                serverSide: true,
                fixedHeader:false,
                ajax: {
                    url: '/account/account?account_type=other',
                    data: function(d) {
                        d.account_status = $('#account_status').val();
                    }
                },
                columnDefs: [{
                    "targets": [6, 8],
                    "orderable": false,
                    "searchable": false
                }],
                columns: [{
                        data: 'name',
                        name: 'accounts.name'
                    },
                    {
                        data: 'parent_account_type_name',
                        name: 'pat.name'
                    },
                    {
                        data: 'account_type_name',
                        name: 'ats.name'
                    },
                    {
                        data: 'account_number',
                        name: 'accounts.account_number'
                    },
                    {
                        data: 'note',
                        name: 'accounts.note'
                    },
                    {
                        data: 'balance',
                        name: 'balance',
                        searchable: false
                    },
                    {
                        data: 'account_details',
                        name: 'account_details'
                    },
                    {
                        data: 'added_by',
                        name: 'u.first_name'
                    },
                    {
                        data: 'action',
                        name: 'action'
                    }
                ],
                "fnDrawCallback": function(oSettings) {
                    __currency_convert_recursively($('#other_account_table'));
                },
                "footerCallback": function(row, data, start, end, display) {
                    var footer_total_balance = 0;
                    for (var r in data) {
                        footer_total_balance += $(data[r].balance).data('orig-value') ? parseFloat($(
                            data[r].balance).data('orig-value')) : 0;
                    }

                    $('.footer_total_balance').html(__currency_trans_from_en(footer_total_balance));
                }
            });

        });

        $('#account_status').change(function() {
            other_account_table.ajax.reload();
        });

        $(document).on('submit', 'form#deposit_form', function(e) {
            e.preventDefault();
            var data = $(this).serialize();

            $.ajax({
                method: "POST",
                url: $(this).attr("action"),
                dataType: "json",
                data: data,
                success: function(result) {
                    if (result.success == true) {
                        $('div.view_modal').modal('hide');
                        toastr.success(result.msg);
                        capital_account_table.ajax.reload();
                        other_account_table.ajax.reload();
                    } else {
                        toastr.error(result.msg);
                    }
                }
            });
        });

        $('.account_model').on('shown.bs.modal', function(e) {
            $('.account_model .select2').select2({
                dropdownParent: $(this)
            })
        });

        $(document).on('click', 'button.delete_account_type', function() {
            swal({
                title: LANG.sure,
                icon: "warning",
                buttons: true,
                dangerMode: true,
            }).then((willDelete) => {
                if (willDelete) {
                    $(this).closest('form').submit();
                }
            });
        })

        $(document).on('click', 'button.activate_account', function() {
            swal({
                title: LANG.sure,
                icon: "warning",
                buttons: true,
                dangerMode: true,
            }).then((willActivate) => {
                if (willActivate) {
                    var url = $(this).data('url');
                    $.ajax({
                        method: "get",
                        url: url,
                        dataType: "json",
                        success: function(result) {
                            if (result.success == true) {
                                toastr.success(result.msg);
                                capital_account_table.ajax.reload();
                                other_account_table.ajax.reload();
                            } else {
                                toastr.error(result.msg);
                            }

                        }
                    });
                }
            });
        });
    </script>
@endsection
