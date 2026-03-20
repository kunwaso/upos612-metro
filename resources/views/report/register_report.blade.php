@extends('layouts.app')
@section('title', __('report.register_report'))

@section('content')

{{-- Toolbar + Breadcrumb --}}
<div id="kt_toolbar" class="toolbar py-3 py-lg-5">
    <div id="kt_toolbar_container" class="container-xxl d-flex flex-stack flex-wrap">
        <div class="page-title d-flex flex-column align-items-start me-3 py-2 gap-2">
            <h1 class="d-flex text-dark fw-bold fs-3 mb-0">{{ __('report.register_report') }}</h1>
            <ul class="breadcrumb breadcrumb-dot fw-semibold text-gray-600 fs-7">
                <li class="breadcrumb-item text-gray-600"><a href="{{ route('home') }}" class="text-gray-600 text-hover-primary">@lang('home.home')</a></li>
                <li class="breadcrumb-item text-gray-900">{{ __('report.register_report') }}</li>
            </ul>
        </div>
    </div>
</div>
<div class="d-flex flex-column-fluid align-items-start container-xxl">
    <div class="content flex-row-fluid" id="kt_content">
    <div class="row">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('report.filters')])
              {!! Form::open(['url' => action([\App\Http\Controllers\ReportController::class, 'getStockReport']), 'method' => 'get', 'id' => 'register_report_filter_form' ]) !!}
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('register_user_id',  __('report.user') . ':') !!}
                        {!! Form::select('register_user_id', $users, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('report.all_users')]); !!}
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('register_status',  __('sale.status') . ':') !!}
                        {!! Form::select('register_status', ['open' => __('cash_register.open'), 'close' => __('cash_register.close')], null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('report.all')]); !!}
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('register_report_date_range', __('report.date_range') . ':') !!}
                        {!! Form::text('register_report_date_range', null , ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'id' => 'register_report_date_range', 'readonly']); !!}
                    </div>
                </div>
                {!! Form::close() !!}
            @endcomponent
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-primary'])
                <table class="table table-bordered table-striped" id="register_report_table">
                    <thead>
                        <tr>
                            <th>@lang('report.open_time')</th>
                            <th>@lang('report.close_time')</th>
                            <th>@lang('sale.location')</th>
                            <th>@lang('report.user')</th>
                            <th>@lang('cash_register.total_card_slips')</th>
                            <th>@lang('cash_register.total_cheques')</th>
                            <th>@lang('cash_register.total_cash')</th>
                            <th>@lang('lang_v1.total_bank_transfer')</th>
                            <th>@lang('lang_v1.total_advance_payment')</th>
                            <th>{{$payment_types['custom_pay_1']}}</th>
                            <th>{{$payment_types['custom_pay_2']}}</th>
                            <th>{{$payment_types['custom_pay_3']}}</th>
                            <th>{{$payment_types['custom_pay_4']}}</th>
                            <th>{{$payment_types['custom_pay_5']}}</th>
                            <th>{{$payment_types['custom_pay_6']}}</th>
                            <th>{{$payment_types['custom_pay_7']}}</th>
                            <th>@lang('cash_register.other_payments')</th>
                            <th>@lang('sale.total')</th>
                            <th>@lang('messages.action')</th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr class="bg-gray font-17 text-center footer-total">
                            <td colspan="4"><strong>@lang('sale.total'):</strong></td>
                            <td class="footer_total_card_payment"></td>
                            <td class="footer_total_cheque_payment"></td>
                            <td class="footer_total_cash_payment"></td>
                            <td class="footer_total_bank_transfer_payment"></td>
                            <td class="footer_total_advance_payment"></td>'
                            <td class="footer_total_custom_pay_1"></td>
                            <td class="footer_total_custom_pay_2"></td>
                            <td class="footer_total_custom_pay_3"></td>
                            <td class="footer_total_custom_pay_4"></td>
                            <td class="footer_total_custom_pay_5"></td>
                            <td class="footer_total_custom_pay_6"></td>
                            <td class="footer_total_custom_pay_7"></td>
                            <td class="footer_total_other_payments"></td>
                            <td class="footer_total"></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            @endcomponent
        </div>
    </div>
    </div>
</div>
<!-- /.content -->
<div class="modal fade view_register" tabindex="-1" role="dialog" 
    aria-labelledby="gridSystemModalLabel">
</div>

@endsection

@section('javascript')
    <script src="{{ asset('assets/app/js/report.js?v=' . $asset_v) }}"></script>
@endsection
