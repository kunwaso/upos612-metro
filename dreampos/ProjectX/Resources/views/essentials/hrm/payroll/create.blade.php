@extends('projectx::layouts.main')

@section('title', $title)

@section('content')
<form method="POST" action="{{ $form_action }}" id="projectx_add_payroll_form">
    @csrf
    <input type="hidden" name="transaction_date" value="{{ $transaction_date }}">
    @if(!empty($payroll_group_id))
        <input type="hidden" name="payroll_group_id" value="{{ $payroll_group_id }}">
    @endif
    <input type="hidden" name="location_id" value="{{ $location_id }}">
    <input type="hidden" name="total_gross_amount" id="total_gross_amount" value="0">

    <div class="card card-flush mb-7">
        <div class="card-body">
            <div class="row g-5 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">@lang('essentials::lang.payroll_group_name')</label>
                    <input type="text"
                        name="payroll_group_name"
                        class="form-control form-control-solid"
                        value="{{ $payroll_group_name_value }}"
                        required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">@lang('sale.status')</label>
                    <select name="payroll_group_status" class="form-select form-select-solid" data-control="select2" data-hide-search="true" required>
                        <option value="draft" {{ $payroll_group_status_value === 'draft' ? 'selected' : '' }}>@lang('sale.draft')</option>
                        <option value="final" {{ $payroll_group_status_value === 'final' ? 'selected' : '' }}>@lang('sale.final')</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <div class="text-muted fs-7">
                        <strong>@lang('business.location'):</strong>
                        {{ $location_name }}
                    </div>
                    <div class="text-muted fs-7">
                        {!! $group_name !!}
                    </div>
                </div>
            </div>
        </div>
    </div>

    @foreach($payroll_rows as $payroll_row)
        <div class="card card-flush mb-7 payroll-row" data-employee="{{ $payroll_row['employee_id'] }}">
            <div class="card-header">
                <h3 class="card-title">{{ $payroll_row['name'] }}</h3>
            </div>
            <div class="card-body">
                <input type="hidden" name="payrolls[{{ $payroll_row['employee_id'] }}][expense_for]" value="{{ $payroll_row['employee_id'] }}">
                @if($is_edit)
                    <input type="hidden" name="payrolls[{{ $payroll_row['employee_id'] }}][transaction_id]" value="{{ $payroll_row['transaction_id'] }}">
                @endif

                <div class="row g-5 mb-7">
                    <div class="col-md-3">
                        <label class="form-label">@lang('essentials::lang.total_work_duration')</label>
                        <input type="text" name="payrolls[{{ $payroll_row['employee_id'] }}][essentials_duration]" class="form-control form-control-solid input_number essentials_duration" value="{{ $payroll_row['essentials_duration'] }}" data-id="{{ $payroll_row['employee_id'] }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">@lang('essentials::lang.duration_unit')</label>
                        <input type="text" name="payrolls[{{ $payroll_row['employee_id'] }}][essentials_duration_unit]" class="form-control form-control-solid" value="{{ $payroll_row['essentials_duration_unit'] }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">@lang('essentials::lang.amount_per_unit_duartion')</label>
                        <input type="text" name="payrolls[{{ $payroll_row['employee_id'] }}][essentials_amount_per_unit_duration]" class="form-control form-control-solid input_number essentials_amount_per_unit_duration" value="{{ @num_format($payroll_row['essentials_amount_per_unit_duration']) }}" data-id="{{ $payroll_row['employee_id'] }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">@lang('sale.total')</label>
                        <input type="text" name="payrolls[{{ $payroll_row['employee_id'] }}][total]" class="form-control form-control-solid input_number total" value="0" data-id="{{ $payroll_row['employee_id'] }}">
                    </div>
                </div>

                <div class="row g-5">
                    <div class="col-xl-6">
                        <h5 class="mb-4">@lang('essentials::lang.allowances')</h5>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle table-row-dashed allowance_table" id="allowance_table_{{ $payroll_row['employee_id'] }}" data-id="{{ $payroll_row['employee_id'] }}">
                                <thead>
                                    <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                                        <th>@lang('lang_v1.description')</th>
                                        <th>@lang('essentials::lang.amount_type')</th>
                                        <th>@lang('sale.amount')</th>
                                        <th>&nbsp;</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($payroll_row['allowance_rows'] as $allowance_row)
                                        <tr>
                                            <td><input type="text" name="payrolls[{{ $payroll_row['employee_id'] }}][allowance_names][]" class="form-control form-control-solid form-control-sm" value="{{ $allowance_row['name'] }}"></td>
                                            <td>
                                                <select name="payrolls[{{ $payroll_row['employee_id'] }}][allowance_types][]" class="form-select form-select-solid form-select-sm amount_type">
                                                    <option value="fixed" {{ $allowance_row['type'] === 'fixed' ? 'selected' : '' }}>@lang('lang_v1.fixed')</option>
                                                    <option value="percent" {{ $allowance_row['type'] === 'percent' ? 'selected' : '' }}>@lang('lang_v1.percentage')</option>
                                                </select>
                                                <div class="input-group mt-2 percent_field {{ $allowance_row['is_percent'] ? '' : 'd-none' }}">
                                                    <input type="text" name="payrolls[{{ $payroll_row['employee_id'] }}][allowance_percent][]" class="form-control form-control-solid form-control-sm input_number percent" value="{{ @num_format($allowance_row['percent']) }}">
                                                    <span class="input-group-text">%</span>
                                                </div>
                                            </td>
                                            <td><input type="text" name="payrolls[{{ $payroll_row['employee_id'] }}][allowance_amounts][]" class="form-control form-control-solid form-control-sm input_number value_field allowance" value="{{ @num_format($allowance_row['amount']) }}" {{ $allowance_row['is_percent'] ? 'readonly' : '' }}></td>
                                            <td>
                                                @if($loop->first)
                                                    <button type="button" class="btn btn-sm btn-light-primary add_allowance" data-employee="{{ $payroll_row['employee_id'] }}"><i class="fa fa-plus"></i></button>
                                                @endif
                                                <button type="button" class="btn btn-sm btn-light-danger remove_tr"><i class="fa fa-minus"></i></button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="2">@lang('sale.total')</th>
                                        <th><span id="total_allowances_{{ $payroll_row['employee_id'] }}">0</span></th>
                                        <th>&nbsp;</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    <div class="col-xl-6">
                        <h5 class="mb-4">@lang('essentials::lang.deductions')</h5>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle table-row-dashed deductions_table" id="deductions_table_{{ $payroll_row['employee_id'] }}" data-id="{{ $payroll_row['employee_id'] }}">
                                <thead>
                                    <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                                        <th>@lang('lang_v1.description')</th>
                                        <th>@lang('essentials::lang.amount_type')</th>
                                        <th>@lang('sale.amount')</th>
                                        <th>&nbsp;</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($payroll_row['deduction_rows'] as $deduction_row)
                                        <tr>
                                            <td><input type="text" name="payrolls[{{ $payroll_row['employee_id'] }}][deduction_names][]" class="form-control form-control-solid form-control-sm" value="{{ $deduction_row['name'] }}"></td>
                                            <td>
                                                <select name="payrolls[{{ $payroll_row['employee_id'] }}][deduction_types][]" class="form-select form-select-solid form-select-sm amount_type">
                                                    <option value="fixed" {{ $deduction_row['type'] === 'fixed' ? 'selected' : '' }}>@lang('lang_v1.fixed')</option>
                                                    <option value="percent" {{ $deduction_row['type'] === 'percent' ? 'selected' : '' }}>@lang('lang_v1.percentage')</option>
                                                </select>
                                                <div class="input-group mt-2 percent_field {{ $deduction_row['is_percent'] ? '' : 'd-none' }}">
                                                    <input type="text" name="payrolls[{{ $payroll_row['employee_id'] }}][deduction_percent][]" class="form-control form-control-solid form-control-sm input_number percent" value="{{ @num_format($deduction_row['percent']) }}">
                                                    <span class="input-group-text">%</span>
                                                </div>
                                            </td>
                                            <td><input type="text" name="payrolls[{{ $payroll_row['employee_id'] }}][deduction_amounts][]" class="form-control form-control-solid form-control-sm input_number value_field deduction" value="{{ @num_format($deduction_row['amount']) }}" {{ $deduction_row['is_percent'] ? 'readonly' : '' }}></td>
                                            <td>
                                                @if($loop->first)
                                                    <button type="button" class="btn btn-sm btn-light-primary add_deduction" data-employee="{{ $payroll_row['employee_id'] }}"><i class="fa fa-plus"></i></button>
                                                @endif
                                                <button type="button" class="btn btn-sm btn-light-danger remove_tr"><i class="fa fa-minus"></i></button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="2">@lang('sale.total')</th>
                                        <th><span id="total_deductions_{{ $payroll_row['employee_id'] }}">0</span></th>
                                        <th>&nbsp;</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="mt-5">
                    <strong>@lang('essentials::lang.gross_amount'):</strong>
                    <span id="gross_amount_text_{{ $payroll_row['employee_id'] }}">0</span>
                    <input type="hidden" name="payrolls[{{ $payroll_row['employee_id'] }}][final_total]" class="gross_amount" id="gross_amount_{{ $payroll_row['employee_id'] }}" value="0">
                </div>

                <div class="mt-4">
                    <label class="form-label">@lang('brand.note')</label>
                    <textarea name="payrolls[{{ $payroll_row['employee_id'] }}][staff_note]" class="form-control form-control-solid" rows="2">{{ $payroll_row['staff_note'] }}</textarea>
                </div>
            </div>
        </div>
    @endforeach

    <div class="card card-flush">
        <div class="card-body d-flex justify-content-between align-items-center">
            <label class="form-check form-check-custom form-check-solid mb-0">
                <input class="form-check-input" type="checkbox" name="notify_employee" value="1">
                <span class="form-check-label">@lang('essentials::lang.notify_employee')</span>
            </label>
            <div>
                <button type="submit" class="btn btn-primary">{{ $submit_label }}</button>
                <a href="{{ route('projectx.essentials.hrm.payroll.index') }}" class="btn btn-light">@lang('messages.cancel')</a>
            </div>
        </div>
    </div>
</form>
@endsection

@section('page_javascript')
<script>
(function () {
    function readNumber(value) {
        var num = parseFloat((value || '0').toString().replace(/,/g, ''));
        return isNaN(num) ? 0 : num;
    }

    function writeNumber($el, value) {
        $el.val((Math.round(value * 100) / 100).toFixed(2));
    }

    function calculateRow(employeeId) {
        var $duration = $('input[name="payrolls[' + employeeId + '][essentials_duration]"]');
        var $rate = $('input[name="payrolls[' + employeeId + '][essentials_amount_per_unit_duration]"]');
        var baseTotal = readNumber($duration.val()) * readNumber($rate.val());
        writeNumber($('input[name="payrolls[' + employeeId + '][total]"]'), baseTotal);

        var totalAllowance = 0;
        $('#allowance_table_' + employeeId + ' tbody tr').each(function () {
            var $row = $(this);
            var type = $row.find('.amount_type').val();
            var amount = readNumber($row.find('.allowance').val());
            if (type === 'percent') {
                amount = baseTotal * readNumber($row.find('.percent').val()) / 100;
                writeNumber($row.find('.allowance'), amount);
            }
            totalAllowance += amount;
        });

        var totalDeduction = 0;
        $('#deductions_table_' + employeeId + ' tbody tr').each(function () {
            var $row = $(this);
            var type = $row.find('.amount_type').val();
            var amount = readNumber($row.find('.deduction').val());
            if (type === 'percent') {
                amount = baseTotal * readNumber($row.find('.percent').val()) / 100;
                writeNumber($row.find('.deduction'), amount);
            }
            totalDeduction += amount;
        });

        var grossAmount = baseTotal + totalAllowance - totalDeduction;
        $('#total_allowances_' + employeeId).text(totalAllowance.toFixed(2));
        $('#total_deductions_' + employeeId).text(totalDeduction.toFixed(2));
        $('#gross_amount_text_' + employeeId).text(grossAmount.toFixed(2));
        $('#gross_amount_' + employeeId).val(grossAmount.toFixed(2));
    }

    function calculateTotalGrossAmount() {
        var total = 0;
        $('.gross_amount').each(function () {
            total += readNumber($(this).val());
        });
        $('#total_gross_amount').val(total.toFixed(2));
    }

    function recalculateAll() {
        $('.payroll-row').each(function () {
            var employeeId = $(this).data('employee');
            calculateRow(employeeId);
        });
        calculateTotalGrossAmount();
    }

    $(document).on('change keyup', '.essentials_duration, .essentials_amount_per_unit_duration, .allowance, .deduction, .percent', function () {
        var employeeId = $(this).closest('.payroll-row').data('employee');
        calculateRow(employeeId);
        calculateTotalGrossAmount();
    });

    $(document).on('change', '.amount_type', function () {
        var $row = $(this).closest('tr');
        var isPercent = $(this).val() === 'percent';
        $row.find('.percent_field').toggleClass('d-none', !isPercent);
        $row.find('.value_field').prop('readonly', isPercent);

        var employeeId = $(this).closest('.payroll-row').data('employee');
        calculateRow(employeeId);
        calculateTotalGrossAmount();
    });

    $(document).on('click', '.add_allowance, .add_deduction', function () {
        var employeeId = $(this).data('employee');
        var type = $(this).hasClass('add_allowance') ? 'allowance' : 'deduction';
        var tableId = type === 'allowance' ? '#allowance_table_' + employeeId : '#deductions_table_' + employeeId;

        $.get('{{ route('projectx.essentials.hrm.payroll.get-allowance-deduction-row') }}', {
            employee_id: employeeId,
            type: type
        }, function (response) {
            $(tableId + ' tbody').append(response);
            calculateRow(employeeId);
            calculateTotalGrossAmount();
        });
    });

    $(document).on('click', '.remove_tr', function () {
        var $tableBody = $(this).closest('tbody');
        if ($tableBody.find('tr').length <= 1) {
            return;
        }

        var employeeId = $(this).closest('.payroll-row').data('employee');
        $(this).closest('tr').remove();
        calculateRow(employeeId);
        calculateTotalGrossAmount();
    });

    recalculateAll();
})();
</script>
@endsection
