@extends('projectx::layouts.main')

@section('title', __('essentials::lang.view_payroll_group'))

@section('content')
<div class="card card-flush">
    <div class="card-header">
        <h3 class="card-title">
            @lang('essentials::lang.view_payroll_group')
            <span class="text-muted fs-7 ms-2">({{ $payroll_group->name }})</span>
        </h3>
    </div>
    <div class="card-body">
        <div class="mb-5">
            <strong>@lang('essentials::lang.payroll_for_month', ['date' => $month_name . ' ' . $year])</strong><br>
            <strong>@lang('sale.status'):</strong> @lang('sale.' . $payroll_group->status)
        </div>

        <div class="table-responsive">
            <table class="table align-middle table-row-dashed fs-6 gy-5">
                <thead>
                    <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                        <th>@lang('essentials::lang.employee')</th>
                        <th>@lang('essentials::lang.gross_amount')</th>
                        <th>@lang('lang_v1.bank_details')</th>
                        <th>@lang('sale.payment_status')</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($payrolls as $payroll)
                        <tr>
                            <td>{{ $payroll['employee'] }}</td>
                            <td>@format_currency($payroll['final_total'])</td>
                            <td>
                                <strong>@lang('lang_v1.bank_name'):</strong> {{ $payroll['bank_name'] }}<br>
                                <strong>@lang('lang_v1.account_holder_name'):</strong> {{ $payroll['bank_account_holder_name'] }}<br>
                                <strong>@lang('lang_v1.bank_account_no'):</strong> {{ $payroll['bank_account_number'] }}
                            </td>
                            <td>{{ __('lang_v1.' . $payroll['payment_status']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
