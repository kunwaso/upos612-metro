<div class="modal-dialog modal-xl" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">
                {!! __('essentials::lang.payroll_of_employee', ['employee' => $payroll->transaction_for->user_full_name, 'date' => $month_name . ' ' . $year]) !!}
            </h3>
            <button type="button" class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body">
            <div class="row g-5 mb-6">
                <div class="col-md-6">
                    <strong>@lang('essentials::lang.employee'):</strong> {{ $payroll->transaction_for->user_full_name }}<br>
                    <strong>@lang('essentials::lang.department'):</strong> {{ $department_name }}<br>
                    <strong>@lang('essentials::lang.designation'):</strong> {{ $designation_name }}<br>
                    <strong>@lang('lang_v1.primary_work_location'):</strong> {{ $location_display_name }}
                </div>
                <div class="col-md-6">
                    <strong>@lang('essentials::lang.total_work_duration'):</strong> {{ (int) $total_work_duration }}<br>
                    <strong>@lang('essentials::lang.days_present'):</strong> {{ $total_days_present }}<br>
                    <strong>@lang('essentials::lang.days_absent'):</strong> {{ $total_leaves }}<br>
                    <strong>@lang('purchase.ref_no'):</strong> {{ $payroll->ref_no }}
                </div>
            </div>

            <div class="table-responsive mb-6">
                <table class="table table-sm align-middle table-row-dashed">
                    <thead>
                        <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                            <th>@lang('essentials::lang.allowances')</th>
                            <th>@lang('sale.amount')</th>
                            <th>@lang('essentials::lang.deductions')</th>
                            <th>@lang('sale.amount')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pay_component_rows as $component_row)
                            <tr>
                                <td>{{ $component_row['allowance_name'] }}</td>
                                <td>{{ $component_row['allowance_amount'] !== null ? @num_format($component_row['allowance_amount']) : '' }}</td>
                                <td>{{ $component_row['deduction_name'] }}</td>
                                <td>{{ $component_row['deduction_amount'] !== null ? @num_format($component_row['deduction_amount']) : '' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mb-6">
                <strong>@lang('essentials::lang.net_pay'):</strong>
                <span class="display_currency" data-currency_symbol="true">{{ $payroll->final_total }}</span>
            </div>

            <div class="table-responsive">
                <table class="table table-sm align-middle table-row-dashed">
                    <thead>
                        <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                            <th>#</th>
                            <th>@lang('messages.date')</th>
                            <th>@lang('purchase.ref_no')</th>
                            <th>@lang('sale.amount')</th>
                            <th>@lang('sale.payment_mode')</th>
                            <th>@lang('sale.payment_note')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payroll->payment_lines as $payment_line)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ @format_date($payment_line->paid_on) }}</td>
                                <td>{{ $payment_line->payment_ref_no }}</td>
                                <td><span class="display_currency" data-currency_symbol="true">{{ $payment_line->amount }}</span></td>
                                <td>{{ $payment_method_labels[$payment_line->id] }}</td>
                                <td>{{ $payment_line->note }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center">@lang('purchase.no_records_found')</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-light" data-bs-dismiss="modal">@lang('messages.close')</button>
        </div>
    </div>
</div>
