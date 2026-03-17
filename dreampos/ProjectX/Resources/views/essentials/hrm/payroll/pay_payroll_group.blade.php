@extends('projectx::layouts.main')

@section('title', __('essentials::lang.add_payment_for_payroll_group'))

@section('content')
<form method="POST" action="{{ route('projectx.essentials.hrm.payroll.post-payment-payroll-group') }}" id="projectx_payroll_group_payment_form">
    @csrf
    <input type="hidden" name="payroll_group_id" value="{{ $payroll_group->id }}">

    <div class="card card-flush">
        <div class="card-header">
            <h3 class="card-title">
                @lang('essentials::lang.add_payment_for_payroll_group')
                <span class="text-muted fs-7 ms-2">({{ $payroll_group->name }})</span>
            </h3>
        </div>
        <div class="card-body">
            <div class="mb-5">
                <strong>@lang('essentials::lang.payroll_for_month', ['date' => $month_name . ' ' . $year])</strong>
            </div>

            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-7">
                    <thead>
                        <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                            <th>@lang('essentials::lang.employee')</th>
                            <th>@lang('sale.total_amount')</th>
                            <th>@lang('sale.payments')</th>
                            <th>@lang('purchase.add_payment')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($payrolls as $employee_id => $payroll)
                            <tr>
                                <td>
                                    {{ $payroll['employee'] }}
                                    <input type="hidden" name="payments[{{ $employee_id }}][transaction_id]" value="{{ $payroll['transaction_id'] }}">
                                    <input type="hidden" name="payments[{{ $employee_id }}][employee_id]" value="{{ $payroll['employee_id'] }}">
                                </td>
                                <td>@format_currency($payroll['final_total'])</td>
                                <td>
                                    @forelse($payroll['payments'] as $payment)
                                        <div class="mb-3">
                                            <strong>@lang('messages.date'):</strong> {{ @format_datetime($payment->paid_on) }}<br>
                                            <strong>@lang('purchase.amount'):</strong> <span class="display_currency" data-currency_symbol="true">{{ $payment->amount }}</span><br>
                                            <strong>@lang('purchase.payment_method'):</strong> {{ $payment->method }}
                                        </div>
                                    @empty
                                        <span class="text-muted">@lang('purchase.no_records_found')</span>
                                    @endforelse
                                </td>
                                <td style="min-width: 320px;">
                                    @if($payroll['payment_status'] === 'paid')
                                        <span class="badge badge-light-success">@lang('lang_v1.paid')</span>
                                        <input type="hidden" name="payments[{{ $employee_id }}][final_total]" value="">
                                        <input type="hidden" name="payments[{{ $employee_id }}][method]" value="">
                                    @else
                                        <div class="mb-3">
                                            <label class="form-label">@lang('purchase.amount')</label>
                                            <input type="text" name="payments[{{ $employee_id }}][final_total]" class="form-control form-control-solid input_number payment_amount" value="{{ $payroll['amount'] }}">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">@lang('lang_v1.paid_on')</label>
                                            <input type="text" name="payments[{{ $employee_id }}][paid_on]" class="form-control form-control-solid projectx-flatpickr-datetime" value="{{ @format_datetime($payroll['paid_on']) }}">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">@lang('purchase.payment_method')</label>
                                            <select name="payments[{{ $employee_id }}][method]" class="form-select form-select-solid payment_types" data-id="{{ $employee_id }}" data-control="select2">
                                                <option value="">@lang('messages.please_select')</option>
                                                @foreach($payment_types as $method_key => $method_label)
                                                    <option value="{{ $method_key }}">{{ $method_label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">@lang('lang_v1.payment_account')</label>
                                            <select name="payments[{{ $employee_id }}][account_id]" class="form-select form-select-solid" data-control="select2">
                                                <option value="">@lang('messages.please_select')</option>
                                                @foreach($accounts as $account_id => $account_name)
                                                    <option value="{{ $account_id }}">{{ $account_name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">@lang('lang_v1.payment_note')</label>
                                            <textarea name="payments[{{ $employee_id }}][payment_note]" class="form-control form-control-solid" rows="2"></textarea>
                                        </div>

                                        <div class="payment-extra-fields d-none" id="card_{{ $employee_id }}">
                                            <input type="text" name="payments[{{ $employee_id }}][card_number]" class="form-control form-control-solid mb-2" placeholder="@lang('lang_v1.card_no')">
                                            <input type="text" name="payments[{{ $employee_id }}][card_holder_name]" class="form-control form-control-solid mb-2" placeholder="@lang('lang_v1.card_holder_name')">
                                            <input type="text" name="payments[{{ $employee_id }}][card_transaction_number]" class="form-control form-control-solid mb-2" placeholder="@lang('lang_v1.card_transaction_no')">
                                            <input type="text" name="payments[{{ $employee_id }}][card_type]" class="form-control form-control-solid mb-2" placeholder="@lang('lang_v1.card_type')">
                                            <input type="text" name="payments[{{ $employee_id }}][card_month]" class="form-control form-control-solid mb-2" placeholder="@lang('lang_v1.month')">
                                            <input type="text" name="payments[{{ $employee_id }}][card_year]" class="form-control form-control-solid mb-2" placeholder="@lang('lang_v1.year')">
                                            <input type="text" name="payments[{{ $employee_id }}][card_security]" class="form-control form-control-solid mb-2" placeholder="@lang('lang_v1.security_code')">
                                        </div>
                                        <div class="payment-extra-fields d-none" id="cheque_{{ $employee_id }}">
                                            <input type="text" name="payments[{{ $employee_id }}][cheque_number]" class="form-control form-control-solid" placeholder="@lang('lang_v1.cheque_no')">
                                        </div>
                                        <div class="payment-extra-fields d-none" id="bank_transfer_{{ $employee_id }}">
                                            <input type="text" name="payments[{{ $employee_id }}][bank_account_number]" class="form-control form-control-solid" placeholder="@lang('lang_v1.bank_account_number')">
                                        </div>
                                        <div class="payment-extra-fields d-none" id="custom_pay_1_{{ $employee_id }}">
                                            <input type="text" name="payments[{{ $employee_id }}][transaction_no_1]" class="form-control form-control-solid" placeholder="@lang('lang_v1.transaction_no')">
                                        </div>
                                        <div class="payment-extra-fields d-none" id="custom_pay_2_{{ $employee_id }}">
                                            <input type="text" name="payments[{{ $employee_id }}][transaction_no_2]" class="form-control form-control-solid" placeholder="@lang('lang_v1.transaction_no')">
                                        </div>
                                        <div class="payment-extra-fields d-none" id="custom_pay_3_{{ $employee_id }}">
                                            <input type="text" name="payments[{{ $employee_id }}][transaction_no_3]" class="form-control form-control-solid" placeholder="@lang('lang_v1.transaction_no')">
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-7 text-end">
                <button type="submit" class="btn btn-primary">@lang('lang_v1.pay')</button>
                <a href="{{ route('projectx.essentials.hrm.payroll.index') }}" class="btn btn-light">@lang('messages.cancel')</a>
            </div>
        </div>
    </div>
</form>
@endsection

@section('page_javascript')
<script>
(function () {
    $('.projectx-flatpickr-datetime').flatpickr({
        enableTime: true,
        dateFormat: 'Y-m-d H:i'
    });

    $('.payment_types').on('change', function () {
        var id = $(this).data('id');
        var method = $(this).val();
        var selectors = ['#card_' + id, '#cheque_' + id, '#bank_transfer_' + id, '#custom_pay_1_' + id, '#custom_pay_2_' + id, '#custom_pay_3_' + id];

        selectors.forEach(function (selector) {
            $(selector).addClass('d-none');
        });

        if (method === 'card') {
            $('#card_' + id).removeClass('d-none');
        } else if (method === 'cheque') {
            $('#cheque_' + id).removeClass('d-none');
        } else if (method === 'bank_transfer') {
            $('#bank_transfer_' + id).removeClass('d-none');
        } else if (method === 'custom_pay_1') {
            $('#custom_pay_1_' + id).removeClass('d-none');
        } else if (method === 'custom_pay_2') {
            $('#custom_pay_2_' + id).removeClass('d-none');
        } else if (method === 'custom_pay_3') {
            $('#custom_pay_3_' + id).removeClass('d-none');
        }
    });
})();
</script>
@endsection
