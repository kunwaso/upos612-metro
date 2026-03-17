@extends('projectx::layouts.main')

@section('title', __('essentials::lang.set_sales_target'))

@section('content')
<div class="card card-flush">
    <div class="card-header">
        <h3 class="card-title">
            @lang('essentials::lang.set_sales_target_for', ['user' => $user->user_full_name])
        </h3>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('projectx.essentials.hrm.sales-target.save') }}">
            @csrf
            <input type="hidden" name="user_id" value="{{ $user->id }}">

            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-7" id="projectx_sales_target_form_table">
                    <thead>
                        <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                            <th>@lang('essentials::lang.total_sales_amount_from')</th>
                            <th>@lang('essentials::lang.total_sales_amount_to')</th>
                            <th>@lang('essentials::lang.commission_percent')</th>
                            <th>
                                <button type="button" class="btn btn-sm btn-light-primary" id="add_target_row">
                                    <i class="fa fa-plus"></i>
                                </button>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($sales_targets as $sales_target)
                            <tr>
                                <td>
                                    <input type="text"
                                        name="edit_target[{{ $sales_target->id }}][target_start]"
                                        class="form-control form-control-solid input_number"
                                        value="{{ @num_format($sales_target->target_start) }}"
                                        required>
                                </td>
                                <td>
                                    <input type="text"
                                        name="edit_target[{{ $sales_target->id }}][target_end]"
                                        class="form-control form-control-solid input_number"
                                        value="{{ @num_format($sales_target->target_end) }}"
                                        required>
                                </td>
                                <td>
                                    <input type="text"
                                        name="edit_target[{{ $sales_target->id }}][commission_percent]"
                                        class="form-control form-control-solid input_number"
                                        value="{{ @num_format($sales_target->commission_percent) }}"
                                        required>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-light-danger remove_target_row">
                                        <i class="fa fa-times"></i>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                        <tr>
                            <td><input type="text" name="sales_amount_start[]" class="form-control form-control-solid input_number" value="0" required></td>
                            <td><input type="text" name="sales_amount_end[]" class="form-control form-control-solid input_number" value="0" required></td>
                            <td><input type="text" name="commission[]" class="form-control form-control-solid input_number" value="0" required></td>
                            <td>
                                <button type="button" class="btn btn-sm btn-light-danger remove_target_row">
                                    <i class="fa fa-times"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="mt-7">
                <button type="submit" class="btn btn-primary">@lang('messages.save')</button>
                <a href="{{ route('projectx.essentials.hrm.sales-target.index') }}" class="btn btn-light">@lang('messages.cancel')</a>
            </div>
        </form>
    </div>
</div>

<table class="d-none" id="projectx_sales_target_row_template">
    <tbody>
        <tr>
            <td><input type="text" name="sales_amount_start[]" class="form-control form-control-solid input_number" value="0" required></td>
            <td><input type="text" name="sales_amount_end[]" class="form-control form-control-solid input_number" value="0" required></td>
            <td><input type="text" name="commission[]" class="form-control form-control-solid input_number" value="0" required></td>
            <td>
                <button type="button" class="btn btn-sm btn-light-danger remove_target_row">
                    <i class="fa fa-times"></i>
                </button>
            </td>
        </tr>
    </tbody>
</table>
@endsection

@section('page_javascript')
<script>
(function () {
    $('#add_target_row').on('click', function () {
        $('#projectx_sales_target_form_table tbody').append($('#projectx_sales_target_row_template tbody').html());
    });

    $(document).on('click', '.remove_target_row', function () {
        var rowCount = $('#projectx_sales_target_form_table tbody tr').length;
        if (rowCount <= 1) {
            return;
        }
        $(this).closest('tr').remove();
    });
})();
</script>
@endsection
