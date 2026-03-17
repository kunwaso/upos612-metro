@extends('projectx::layouts.main')

@section('title', __('essentials::lang.edit_payroll'))

@section('content')
<form method="POST" action="{{ route('projectx.essentials.hrm.payroll.update', ['payroll' => $payroll->id]) }}" id="projectx_payroll_edit_form">
    @csrf
    @method('PUT')

    <div class="card card-flush mb-7">
        <div class="card-header">
            <h3 class="card-title">
                {!! __('essentials::lang.payroll_of_employee', ['employee' => $payroll->transaction_for->user_full_name, 'date' => $month_name . ' ' . $year]) !!}
            </h3>
        </div>
        <div class="card-body">
            <div class="row g-5">
                <div class="col-md-3">
                    <label class="form-label">@lang('essentials::lang.total_work_duration')</label>
                    <input type="text" name="essentials_duration" id="essentials_duration" class="form-control form-control-solid input_number" value="{{ $payroll->essentials_duration }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">@lang('essentials::lang.duration_unit')</label>
                    <input type="text" name="essentials_duration_unit" id="essentials_duration_unit" class="form-control form-control-solid" value="{{ $payroll->essentials_duration_unit }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">@lang('essentials::lang.amount_per_unit_duartion')</label>
                    <input type="text" name="essentials_amount_per_unit_duration" id="essentials_amount_per_unit_duration" class="form-control form-control-solid input_number" value="{{ @num_format($payroll->essentials_amount_per_unit_duration) }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">@lang('sale.total')</label>
                    <input type="text" id="total" class="form-control form-control-solid input_number" value="{{ @num_format($total_amount) }}">
                </div>
            </div>
        </div>
    </div>

    <div class="card card-flush mb-7">
        <div class="card-header">
            <h3 class="card-title">@lang('essentials::lang.allowances')</h3>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm align-middle table-row-dashed" id="allowance_table">
                    <thead>
                        <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                            <th>@lang('lang_v1.description')</th>
                            <th>@lang('essentials::lang.amount_type')</th>
                            <th>@lang('sale.amount')</th>
                            <th>&nbsp;</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($allowance_rows as $allowance_row)
                            <tr>
                                <td><input type="text" name="allowance_names[]" class="form-control form-control-solid form-control-sm" value="{{ $allowance_row['name'] }}"></td>
                                <td>
                                    <select name="allowance_types[]" class="form-select form-select-solid form-select-sm amount_type">
                                        <option value="fixed" {{ $allowance_row['type'] === 'fixed' ? 'selected' : '' }}>@lang('lang_v1.fixed')</option>
                                        <option value="percent" {{ $allowance_row['type'] === 'percent' ? 'selected' : '' }}>@lang('lang_v1.percentage')</option>
                                    </select>
                                    <div class="input-group mt-2 percent_field {{ $allowance_row['is_percent'] ? '' : 'd-none' }}">
                                        <input type="text" name="allowance_percent[]" class="form-control form-control-solid form-control-sm input_number percent" value="{{ @num_format($allowance_row['percent']) }}">
                                        <span class="input-group-text">%</span>
                                    </div>
                                </td>
                                <td><input type="text" name="allowance_amounts[]" class="form-control form-control-solid form-control-sm input_number allowance value_field" value="{{ @num_format($allowance_row['amount']) }}" {{ $allowance_row['is_percent'] ? 'readonly' : '' }}></td>
                                <td>
                                    @if($loop->first)
                                        <button type="button" class="btn btn-sm btn-light-primary add_allowance"><i class="fa fa-plus"></i></button>
                                    @endif
                                    <button type="button" class="btn btn-sm btn-light-danger remove_tr"><i class="fa fa-minus"></i></button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="2">@lang('sale.total')</th>
                            <th><span id="total_allowances">0</span></th>
                            <th>&nbsp;</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <div class="card card-flush mb-7">
        <div class="card-header">
            <h3 class="card-title">@lang('essentials::lang.deductions')</h3>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm align-middle table-row-dashed" id="deductions_table">
                    <thead>
                        <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                            <th>@lang('lang_v1.description')</th>
                            <th>@lang('essentials::lang.amount_type')</th>
                            <th>@lang('sale.amount')</th>
                            <th>&nbsp;</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($deduction_rows as $deduction_row)
                            <tr>
                                <td><input type="text" name="deduction_names[]" class="form-control form-control-solid form-control-sm" value="{{ $deduction_row['name'] }}"></td>
                                <td>
                                    <select name="deduction_types[]" class="form-select form-select-solid form-select-sm amount_type">
                                        <option value="fixed" {{ $deduction_row['type'] === 'fixed' ? 'selected' : '' }}>@lang('lang_v1.fixed')</option>
                                        <option value="percent" {{ $deduction_row['type'] === 'percent' ? 'selected' : '' }}>@lang('lang_v1.percentage')</option>
                                    </select>
                                    <div class="input-group mt-2 percent_field {{ $deduction_row['is_percent'] ? '' : 'd-none' }}">
                                        <input type="text" name="deduction_percent[]" class="form-control form-control-solid form-control-sm input_number percent" value="{{ @num_format($deduction_row['percent']) }}">
                                        <span class="input-group-text">%</span>
                                    </div>
                                </td>
                                <td><input type="text" name="deduction_amounts[]" class="form-control form-control-solid form-control-sm input_number deduction value_field" value="{{ @num_format($deduction_row['amount']) }}" {{ $deduction_row['is_percent'] ? 'readonly' : '' }}></td>
                                <td>
                                    @if($loop->first)
                                        <button type="button" class="btn btn-sm btn-light-primary add_deduction"><i class="fa fa-plus"></i></button>
                                    @endif
                                    <button type="button" class="btn btn-sm btn-light-danger remove_tr"><i class="fa fa-minus"></i></button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="2">@lang('sale.total')</th>
                            <th><span id="total_deductions">0</span></th>
                            <th>&nbsp;</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <div class="card card-flush">
        <div class="card-body d-flex justify-content-between align-items-center">
            <div>
                <strong>@lang('essentials::lang.gross_amount'):</strong>
                <span id="gross_amount_text">{{ @num_format($payroll->final_total) }}</span>
                <input type="hidden" name="final_total" id="gross_amount" value="{{ $payroll->final_total }}">
            </div>
            <div>
                <button type="submit" class="btn btn-primary">@lang('messages.update')</button>
                <a href="{{ route('projectx.essentials.hrm.payroll.index') }}" class="btn btn-light">@lang('messages.cancel')</a>
            </div>
        </div>
    </div>
</form>
@endsection

@section('page_javascript')
<script>
(function () {
    function rowTemplate(type) {
        var isAllowance = type === 'allowance';
        var prefix = isAllowance ? 'allowance' : 'deduction';
        var addClass = isAllowance ? 'add_allowance' : 'add_deduction';
        var valueClass = isAllowance ? 'allowance' : 'deduction';

        return '' +
            '<tr>' +
                '<td><input type="text" name="' + prefix + '_names[]" class="form-control form-control-solid form-control-sm"></td>' +
                '<td>' +
                    '<select name="' + prefix + '_types[]" class="form-select form-select-solid form-select-sm amount_type">' +
                        '<option value="fixed">@lang('lang_v1.fixed')</option>' +
                        '<option value="percent">@lang('lang_v1.percentage')</option>' +
                    '</select>' +
                    '<div class="input-group mt-2 percent_field d-none">' +
                        '<input type="text" name="' + prefix + '_percent[]" class="form-control form-control-solid form-control-sm input_number percent" value="0">' +
                        '<span class="input-group-text">%</span>' +
                    '</div>' +
                '</td>' +
                '<td><input type="text" name="' + prefix + '_amounts[]" class="form-control form-control-solid form-control-sm input_number value_field ' + valueClass + '" value="0"></td>' +
                '<td>' +
                    '<button type="button" class="btn btn-sm btn-light-primary ' + addClass + '"><i class="fa fa-plus"></i></button> ' +
                    '<button type="button" class="btn btn-sm btn-light-danger remove_tr"><i class="fa fa-minus"></i></button>' +
                '</td>' +
            '</tr>';
    }

    function readNumber(value) {
        var num = parseFloat((value || '0').toString().replace(/,/g, ''));
        return isNaN(num) ? 0 : num;
    }

    function writeNumber($el, value) {
        $el.val((Math.round(value * 100) / 100).toFixed(2));
    }

    function calculateTotal() {
        var duration = readNumber($('#essentials_duration').val());
        var perUnit = readNumber($('#essentials_amount_per_unit_duration').val());
        var baseTotal = duration * perUnit;
        writeNumber($('#total'), baseTotal);

        var totalAllowances = 0;
        $('#allowance_table tbody tr').each(function () {
            var $row = $(this);
            var type = $row.find('.amount_type').val();
            var amount = readNumber($row.find('.allowance').val());
            if (type === 'percent') {
                amount = baseTotal * readNumber($row.find('.percent').val()) / 100;
                writeNumber($row.find('.allowance'), amount);
            }
            totalAllowances += amount;
        });

        var totalDeductions = 0;
        $('#deductions_table tbody tr').each(function () {
            var $row = $(this);
            var type = $row.find('.amount_type').val();
            var amount = readNumber($row.find('.deduction').val());
            if (type === 'percent') {
                amount = baseTotal * readNumber($row.find('.percent').val()) / 100;
                writeNumber($row.find('.deduction'), amount);
            }
            totalDeductions += amount;
        });

        var gross = baseTotal + totalAllowances - totalDeductions;
        $('#total_allowances').text(totalAllowances.toFixed(2));
        $('#total_deductions').text(totalDeductions.toFixed(2));
        $('#gross_amount_text').text(gross.toFixed(2));
        $('#gross_amount').val(gross.toFixed(2));
    }

    $(document).on('click', '.add_allowance', function () {
        $('#allowance_table tbody').append(rowTemplate('allowance'));
    });

    $(document).on('click', '.add_deduction', function () {
        $('#deductions_table tbody').append(rowTemplate('deduction'));
    });

    $(document).on('click', '.remove_tr', function () {
        var $tbody = $(this).closest('tbody');
        if ($tbody.find('tr').length <= 1) {
            return;
        }
        $(this).closest('tr').remove();
        calculateTotal();
    });

    $(document).on('change', '.amount_type', function () {
        var $row = $(this).closest('tr');
        var isPercent = $(this).val() === 'percent';
        $row.find('.percent_field').toggleClass('d-none', !isPercent);
        $row.find('.value_field').prop('readonly', isPercent);
        calculateTotal();
    });

    $(document).on('keyup change', '#essentials_duration, #essentials_amount_per_unit_duration, .allowance, .deduction, .percent, #total', function () {
        calculateTotal();
    });

    calculateTotal();
})();
</script>
@endsection
