@extends('projectx::layouts.main')

@section('title', __('essentials::lang.payroll'))

@section('content')
<div class="d-flex flex-wrap flex-stack mb-6">
    <div>
        <h1 class="text-gray-900 fw-bold mb-1">@lang('essentials::lang.payroll')</h1>
    </div>
    <div class="d-flex gap-2">
        @if(auth()->user()->can('essentials.create_payroll'))
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#projectx_payroll_modal">
                @lang('messages.add')
            </button>
        @endif
        @if(auth()->user()->can('essentials.view_allowance_and_deduction') || auth()->user()->can('essentials.add_allowance_and_deduction'))
            <a href="{{ route('projectx.essentials.allowance-deduction.index') }}" class="btn btn-light-primary btn-sm">
                @lang('essentials::lang.pay_components')
            </a>
        @endif
    </div>
</div>

<div class="card card-flush mb-7">
    <div class="card-body">
        <div class="row g-5">
            @if(auth()->user()->can('essentials.view_all_payroll'))
                <div class="col-md-3">
                    <label class="form-label">@lang('essentials::lang.employee')</label>
                    <select id="user_id_filter" class="form-select form-select-solid" data-control="select2">
                        <option value="">@lang('lang_v1.all')</option>
                        @foreach($employees as $employee_id => $employee_name)
                            <option value="{{ $employee_id }}">{{ $employee_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">@lang('purchase.business_location')</label>
                    <select id="location_id_filter" class="form-select form-select-solid" data-control="select2">
                        <option value="">@lang('lang_v1.all')</option>
                        @foreach($locations as $location_id => $location_name)
                            <option value="{{ $location_id }}">{{ $location_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">@lang('essentials::lang.department')</label>
                    <select id="department_id" class="form-select form-select-solid" data-control="select2">
                        <option value="">@lang('lang_v1.all')</option>
                        @foreach($departments as $department_id => $department_name)
                            <option value="{{ $department_id }}">{{ $department_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">@lang('essentials::lang.designation')</label>
                    <select id="designation_id" class="form-select form-select-solid" data-control="select2">
                        <option value="">@lang('lang_v1.all')</option>
                        @foreach($designations as $designation_id => $designation_name)
                            <option value="{{ $designation_id }}">{{ $designation_name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="col-md-3">
                <label class="form-label">@lang('essentials::lang.month_year')</label>
                <input type="text" id="month_year_filter" class="form-control form-control-solid projectx-flatpickr-month" placeholder="MM/YYYY">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="button" class="btn btn-light-primary w-100" id="projectx_payroll_apply_filter">@lang('report.apply_filters')</button>
            </div>
        </div>
    </div>
</div>

<div class="card card-flush mb-7">
    <div class="card-header">
        <h3 class="card-title">@lang('essentials::lang.all_payrolls')</h3>
    </div>
    <div class="card-body pt-6">
        <div class="table-responsive">
            <table class="table align-middle table-row-dashed fs-6 gy-5" id="projectx_payrolls_table">
                <thead>
                    <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                        <th>@lang('essentials::lang.employee')</th>
                        <th>@lang('essentials::lang.department')</th>
                        <th>@lang('essentials::lang.designation')</th>
                        <th>@lang('essentials::lang.month_year')</th>
                        <th>@lang('purchase.ref_no')</th>
                        <th>@lang('sale.total_amount')</th>
                        <th>@lang('sale.payment_status')</th>
                        <th>@lang('messages.action')</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

@if(auth()->user()->can('essentials.view_all_payroll'))
    <div class="card card-flush">
        <div class="card-header">
            <h3 class="card-title">@lang('essentials::lang.all_payroll_groups')</h3>
        </div>
        <div class="card-body pt-6">
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-6 gy-5" id="projectx_payroll_group_table">
                    <thead>
                        <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                            <th>@lang('essentials::lang.name')</th>
                            <th>@lang('sale.status')</th>
                            <th>@lang('sale.payment_status')</th>
                            <th>@lang('essentials::lang.total_gross_amount')</th>
                            <th>@lang('lang_v1.added_by')</th>
                            <th>@lang('business.location')</th>
                            <th>@lang('lang_v1.created_at')</th>
                            <th>@lang('messages.action')</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
@endif

@if(auth()->user()->can('essentials.create_payroll'))
    <div class="modal fade" id="projectx_payroll_modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="GET" action="{{ route('projectx.essentials.hrm.payroll.create') }}" id="add_payroll_step1">
                    <div class="modal-header">
                        <h3 class="modal-title">@lang('essentials::lang.add_payroll')</h3>
                        <button type="button" class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-5">
                            <label class="form-label">@lang('business.location')</label>
                            <select name="primary_work_location" id="primary_work_location" class="form-select form-select-solid" data-control="select2">
                                @foreach($locations as $location_id => $location_name)
                                    <option value="{{ $location_id }}">{{ $location_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-5">
                            <label class="form-label">@lang('essentials::lang.employee')</label>
                            <select name="employee_ids[]" id="employee_ids" class="form-select form-select-solid" data-control="select2" multiple required>
                                @foreach($employees as $employee_id => $employee_name)
                                    <option value="{{ $employee_id }}">{{ $employee_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-0">
                            <label class="form-label">@lang('essentials::lang.month_year')</label>
                            <input type="text" name="month_year" id="month_year" class="form-control form-control-solid projectx-flatpickr-month" placeholder="MM/YYYY" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">@lang('essentials::lang.proceed')</button>
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">@lang('messages.close')</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

<div class="modal fade view_modal" tabindex="-1" aria-hidden="true"></div>
<div class="modal fade payment_modal" tabindex="-1" aria-hidden="true"></div>
@endsection

@section('page_javascript')
<script>
(function () {
    $('.projectx-flatpickr-month').flatpickr({
        dateFormat: 'm/Y'
    });

    var payrollTable = $('#projectx_payrolls_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route('projectx.essentials.hrm.payroll.index') }}',
            data: function (d) {
                d.user_id = $('#user_id_filter').val();
                d.location_id = $('#location_id_filter').val();
                d.department_id = $('#department_id').val();
                d.designation_id = $('#designation_id').val();
                d.month_year = $('#month_year_filter').val();
            }
        },
        columns: [
            {data: 'user', name: 'user'},
            {data: 'department', name: 'dept.name'},
            {data: 'designation', name: 'dsgn.name'},
            {data: 'transaction_date', name: 'transaction_date'},
            {data: 'ref_no', name: 'ref_no'},
            {data: 'final_total', name: 'final_total'},
            {data: 'payment_status', name: 'payment_status'},
            {data: 'action', name: 'action', orderable: false, searchable: false}
        ]
    });

    $('#projectx_payroll_apply_filter, #user_id_filter, #location_id_filter, #department_id, #designation_id, #month_year_filter').on('change click', function () {
        payrollTable.ajax.reload();
    });

    @if(auth()->user()->can('essentials.view_all_payroll'))
    var payrollGroupTable = $('#projectx_payroll_group_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route('projectx.essentials.hrm.payroll.payroll-group-datatable') }}',
        columns: [
            {data: 'name', name: 'essentials_payroll_groups.name'},
            {data: 'status', name: 'essentials_payroll_groups.status'},
            {data: 'payment_status', name: 'essentials_payroll_groups.payment_status'},
            {data: 'gross_total', name: 'essentials_payroll_groups.gross_total'},
            {data: 'added_by', name: 'added_by'},
            {data: 'location_name', name: 'BL.name'},
            {data: 'created_at', name: 'essentials_payroll_groups.created_at'},
            {data: 'action', name: 'action', orderable: false, searchable: false}
        ]
    });
    @endif

    $(document).on('click', '.btn-modal', function (event) {
        event.preventDefault();
        var href = $(this).data('href') || $(this).attr('href');
        var container = $(this).data('container') || '.view_modal';
        $.get(href, function (response) {
            $(container).html(response);
            bootstrap.Modal.getOrCreateInstance(document.querySelector(container)).show();
        });
    });

    $(document).on('click', '.add_payment_modal', function (event) {
        event.preventDefault();
        var href = $(this).attr('href');
        $.get(href, function (response) {
            $('.payment_modal').html(response);
            bootstrap.Modal.getOrCreateInstance(document.querySelector('.payment_modal')).show();
        });
    });

    $(document).on('click', '.delete-payroll', function (event) {
        event.preventDefault();
        if (!confirm(@json(__('messages.sure')))) {
            return;
        }

        $.ajax({
            method: 'DELETE',
            url: $(this).attr('href'),
            data: {_token: @json(csrf_token())},
            success: function (response) {
                if (response.success) {
                    toastr.success(response.msg);
                    @if(auth()->user()->can('essentials.view_all_payroll'))
                    payrollGroupTable.ajax.reload();
                    @endif
                    payrollTable.ajax.reload();
                } else {
                    toastr.error(response.msg);
                }
            }
        });
    });

    $('#primary_work_location').on('change', function () {
        $.ajax({
            method: 'GET',
            url: '{{ route('projectx.essentials.hrm.payroll.location-employees') }}',
            dataType: 'json',
            data: {location_id: $(this).val()},
            success: function (response) {
                if (response.success) {
                    $('#employee_ids').html(response.employees_html).trigger('change');
                }
            }
        });
    });
})();
</script>
@endsection
