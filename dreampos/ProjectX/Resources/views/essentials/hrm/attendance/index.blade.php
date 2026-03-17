@extends('projectx::layouts.main')

@section('title', __('essentials::lang.attendance'))

@section('content')
<div class="d-flex flex-wrap flex-stack mb-6">
    <div>
        <h1 class="text-gray-900 fw-bold mb-1">@lang('essentials::lang.attendance')</h1>
        <div class="text-muted fw-semibold fs-6">@lang('essentials::lang.all_attendance')</div>
    </div>
    @if(auth()->user()->can('essentials.crud_all_attendance'))
        <button type="button"
            class="btn btn-primary btn-sm btn-modal"
            data-href="{{ route('projectx.essentials.hrm.attendance.create') }}"
            data-container="#attendance_modal">
            @lang('essentials::lang.add_latest_attendance')
        </button>
    @endif
</div>

@if(session('notification'))
    <div class="alert alert-danger mb-6">
        {{ session('notification.msg') }}
    </div>
@endif

@if($is_employee_allowed)
    <div class="card card-flush mb-7">
        <div class="card-body">
            <div class="row g-5">
                <div class="col-md-6">
                    <label class="form-label">@lang('essentials::lang.location')</label>
                    <input type="text" class="form-control form-control-solid" id="projectx_clock_in_out_location" />
                </div>
                <div class="col-md-6">
                    <label class="form-label">@lang('essentials::lang.note')</label>
                    <input type="text" class="form-control form-control-solid" id="projectx_clock_note" />
                </div>
                <div class="col-md-12 d-flex gap-3">
                    <button type="button"
                        class="btn btn-success clock_in_btn {{ empty($clock_in) ? '' : 'd-none' }}"
                        data-type="clock_in">
                        @lang('essentials::lang.clock_in')
                    </button>
                    <button type="button"
                        class="btn btn-warning clock_out_btn {{ empty($clock_in) ? 'd-none' : '' }}"
                        data-type="clock_out">
                        @lang('essentials::lang.clock_out')
                    </button>
                    @if(!empty($clock_in))
                        <div class="text-muted fs-7 align-self-center">
                            @lang('essentials::lang.clocked_in_at'): {{ @format_datetime($clock_in->clock_in_time) }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endif

<div class="card card-flush mb-7">
    <div class="card-body">
        <div class="row g-5">
            @if(!empty($employees))
                <div class="col-md-4">
                    <label class="form-label">@lang('essentials::lang.employee')</label>
                    <select id="employee_id" class="form-select form-select-solid" data-control="select2" data-hide-search="false">
                        <option value="">@lang('lang_v1.all')</option>
                        @foreach($employees as $employee_id => $employee_label)
                            <option value="{{ $employee_id }}">{{ $employee_label }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="col-md-3">
                <label class="form-label">@lang('report.start_date')</label>
                <input type="text" id="attendance_start_date" class="form-control form-control-solid projectx-flatpickr-date" />
            </div>
            <div class="col-md-3">
                <label class="form-label">@lang('report.end_date')</label>
                <input type="text" id="attendance_end_date" class="form-control form-control-solid projectx-flatpickr-date" />
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="button" class="btn btn-light-primary w-100" id="projectx_attendance_filter_apply">
                    @lang('report.apply_filters')
                </button>
            </div>
        </div>
    </div>
</div>

<div id="user_attendance_summary" class="card card-flush mb-7 d-none">
    <div class="card-body">
        <strong>@lang('essentials::lang.total_work_hours'):</strong>
        <span id="total_work_hours"></span>
    </div>
</div>

<div class="card card-flush mb-7">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table align-middle table-row-dashed fs-6 gy-5" id="projectx_attendance_table">
                <thead>
                    <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                        <th>@lang('lang_v1.date')</th>
                        <th>@lang('essentials::lang.employee')</th>
                        <th>@lang('essentials::lang.clock_in')</th>
                        <th>@lang('essentials::lang.clock_out')</th>
                        <th>@lang('essentials::lang.work_duration')</th>
                        <th>@lang('essentials::lang.ip_address')</th>
                        <th>@lang('essentials::lang.shift')</th>
                        @if(auth()->user()->can('essentials.crud_all_attendance'))
                            <th>@lang('messages.action')</th>
                        @endif
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

@if(auth()->user()->can('essentials.crud_all_attendance'))
    <div class="row g-5">
        <div class="col-xl-6">
            <div class="card card-flush h-100">
                <div class="card-header">
                    <h3 class="card-title">@lang('essentials::lang.attendance_by_shift')</h3>
                </div>
                <div class="card-body">
                    <div class="mb-5">
                        <label class="form-label">@lang('lang_v1.date')</label>
                        <input type="text" id="attendance_by_shift_date_filter" class="form-control form-control-solid projectx-flatpickr-date" />
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-7" id="attendance_by_shift_table">
                            <thead>
                                <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                                    <th>@lang('essentials::lang.shift')</th>
                                    <th>@lang('essentials::lang.present')</th>
                                    <th>@lang('essentials::lang.absent')</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="card card-flush h-100">
                <div class="card-header">
                    <h3 class="card-title">@lang('essentials::lang.attendance_by_date')</h3>
                </div>
                <div class="card-body">
                    <div class="row g-4 mb-5">
                        <div class="col-md-6">
                            <label class="form-label">@lang('report.start_date')</label>
                            <input type="text" id="attendance_by_date_start_filter" class="form-control form-control-solid projectx-flatpickr-date" />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">@lang('report.end_date')</label>
                            <input type="text" id="attendance_by_date_end_filter" class="form-control form-control-solid projectx-flatpickr-date" />
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-7" id="attendance_by_date_table">
                            <thead>
                                <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                                    <th>@lang('lang_v1.date')</th>
                                    <th>@lang('essentials::lang.present')</th>
                                    <th>@lang('essentials::lang.absent')</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-flush mt-7">
        <div class="card-header">
            <h3 class="card-title">@lang('essentials::lang.import_attendance')</h3>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('projectx.essentials.hrm.attendance.import') }}" enctype="multipart/form-data">
                @csrf
                <div class="row g-5 align-items-end">
                    <div class="col-md-6">
                        <label class="form-label">@lang('product.file_to_import')</label>
                        <input type="file" name="attendance" accept=".xls,.xlsx" class="form-control form-control-solid" required>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary">@lang('messages.submit')</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endif

<div class="modal fade" id="attendance_modal" tabindex="-1" aria-hidden="true"></div>
<div class="modal fade" id="edit_attendance_modal" tabindex="-1" aria-hidden="true"></div>
<div class="modal fade" id="projectx_attendance_view_modal" tabindex="-1" aria-hidden="true"></div>
@endsection

@section('page_javascript')
<script>
(function () {
    var csrfToken = @json(csrf_token());
    var canCrudAllAttendance = {{ auth()->user()->can('essentials.crud_all_attendance') ? 'true' : 'false' }};

    $('.projectx-flatpickr-date').flatpickr({dateFormat: 'Y-m-d'});

    var tableColumns = [
        {data: 'date', name: 'clock_in_time'},
        {data: 'user', name: 'user'},
        {data: 'clock_in', name: 'clock_in', orderable: false, searchable: false},
        {data: 'clock_out', name: 'clock_out', orderable: false, searchable: false},
        {data: 'work_duration', name: 'work_duration', orderable: false, searchable: false},
        {data: 'ip_address', name: 'ip_address'},
        {data: 'shift_name', name: 'es.name'}
    ];

    if (canCrudAllAttendance) {
        tableColumns.push({data: 'action', name: 'action', orderable: false, searchable: false});
    }

    window.projectxAttendanceTable = $('#projectx_attendance_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route('projectx.essentials.hrm.attendance.index') }}',
            data: function (d) {
                if ($('#employee_id').length) {
                    d.employee_id = $('#employee_id').val();
                }
                d.start_date = $('#attendance_start_date').val();
                d.end_date = $('#attendance_end_date').val();
            }
        },
        columns: tableColumns
    });

    function getAttendanceSummary() {
        if (!$('#employee_id').length) {
            return;
        }

        var userId = $('#employee_id').val();
        if (!userId) {
            $('#user_attendance_summary').addClass('d-none');
            return;
        }

        $.ajax({
            url: '{{ route('projectx.essentials.hrm.attendance.user-attendance-summary') }}',
            data: {
                user_id: userId,
                start_date: $('#attendance_start_date').val(),
                end_date: $('#attendance_end_date').val()
            },
            dataType: 'html',
            success: function (response) {
                $('#total_work_hours').html(response);
                $('#user_attendance_summary').removeClass('d-none');
            }
        });
    }

    function getAttendanceByShift() {
        $.ajax({
            url: '{{ route('projectx.essentials.hrm.attendance.get-attendance-by-shift') }}',
            data: {date: $('#attendance_by_shift_date_filter').val()},
            dataType: 'html',
            success: function (response) {
                $('#attendance_by_shift_table tbody').html(response);
            }
        });
    }

    function getAttendanceByDate() {
        $.ajax({
            url: '{{ route('projectx.essentials.hrm.attendance.get-attendance-by-date') }}',
            data: {
                start_date: $('#attendance_by_date_start_filter').val(),
                end_date: $('#attendance_by_date_end_filter').val()
            },
            dataType: 'html',
            success: function (response) {
                $('#attendance_by_date_table tbody').html(response);
            }
        });
    }

    $('#projectx_attendance_filter_apply, #employee_id, #attendance_start_date, #attendance_end_date').on('change click', function () {
        window.projectxAttendanceTable.ajax.reload();
        getAttendanceSummary();
    });

    $('#attendance_by_shift_date_filter').on('change', getAttendanceByShift);
    $('#attendance_by_date_start_filter, #attendance_by_date_end_filter').on('change', getAttendanceByDate);

    if ($('#attendance_by_shift_date_filter').length) {
        var today = (new Date()).toISOString().split('T')[0];
        $('#attendance_by_shift_date_filter').val(today);
        $('#attendance_by_date_start_filter').val(today);
        $('#attendance_by_date_end_filter').val(today);
        getAttendanceByShift();
        getAttendanceByDate();
    }

    $(document).on('click', '.btn-modal', function (event) {
        event.preventDefault();
        var href = $(this).data('href') || $(this).attr('href');
        var container = $(this).data('container');

        $.get(href, function (response) {
            $(container).html(response);
            bootstrap.Modal.getOrCreateInstance(document.querySelector(container)).show();
        });
    });

    $(document).on('click', '.delete-attendance', function () {
        if (!confirm(@json(__('messages.sure')))) {
            return;
        }

        $.ajax({
            method: 'DELETE',
            url: $(this).data('href'),
            data: {_token: csrfToken},
            success: function (response) {
                if (response.success) {
                    toastr.success(response.msg);
                    window.projectxAttendanceTable.ajax.reload();
                } else {
                    toastr.error(response.msg);
                }
            }
        });
    });

    $(document).on('click', '.clock_in_btn, .clock_out_btn', function () {
        var type = $(this).data('type');
        var payload = {
            _token: csrfToken,
            type: type,
            clock_in_out_location: $('#projectx_clock_in_out_location').val()
        };

        if (type === 'clock_in') {
            payload.clock_in_note = $('#projectx_clock_note').val();
        } else {
            payload.clock_out_note = $('#projectx_clock_note').val();
        }

        $.post('{{ route('projectx.essentials.hrm.attendance.clock-in-clock-out') }}', payload, function (response) {
            if (response.success) {
                toastr.success(response.msg);
                $('.clock_in_btn').toggleClass('d-none', type === 'clock_in');
                $('.clock_out_btn').toggleClass('d-none', type === 'clock_out');
                window.projectxAttendanceTable.ajax.reload();
            } else {
                toastr.error(response.msg);
            }
        });
    });
})();
</script>
@endsection
