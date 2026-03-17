<tr>
    <td>
        <input type="text" name="payrolls[{{ $employee }}][{{ $name_col }}][]" class="form-control form-control-solid form-control-sm">
    </td>
    <td>
        <select name="payrolls[{{ $employee }}][{{ $type_col }}][]" class="form-select form-select-solid form-select-sm amount_type">
            <option value="fixed">@lang('lang_v1.fixed')</option>
            <option value="percent">@lang('lang_v1.percentage')</option>
        </select>
        <div class="input-group mt-2 percent_field d-none">
            <input type="text" name="payrolls[{{ $employee }}][{{ $percent_col }}][]" class="form-control form-control-solid form-control-sm input_number percent" value="0">
            <span class="input-group-text">%</span>
        </div>
    </td>
    <td>
        <input type="text" name="payrolls[{{ $employee }}][{{ $amount_col }}][]" class="form-control form-control-solid form-control-sm input_number value_field {{ $amount_class }}" value="0">
    </td>
    <td>
        <button type="button" class="btn btn-sm btn-light-primary {{ $button_class }}">
            <i class="fa fa-plus"></i>
        </button>
        <button type="button" class="btn btn-sm btn-light-danger remove_tr">
            <i class="fa fa-minus"></i>
        </button>
    </td>
</tr>
