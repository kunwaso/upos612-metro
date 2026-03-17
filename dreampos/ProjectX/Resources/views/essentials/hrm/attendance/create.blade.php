<div class="modal-dialog modal-xl" role="document">
    <div class="modal-content">
        <form method="POST" action="{{ route('projectx.essentials.hrm.attendance.store') }}" id="projectx_attendance_form">
            @csrf
            <div class="modal-header">
                <h3 class="modal-title">@lang('essentials::lang.add_latest_attendance')</h3>
                <button type="button" class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="mb-5">
                    <label class="form-label">@lang('essentials::lang.select_employee')</label>
                    <select id="select_employee" class="form-select form-select-solid" data-control="select2" data-hide-search="false">
                        <option value="">@lang('messages.please_select')</option>
                        @foreach($employees as $employee_id => $employee_label)
                            <option value="{{ $employee_id }}">{{ $employee_label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed fs-7" id="employee_attendance_table">
                        <thead>
                            <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                                <th>@lang('essentials::lang.employee')</th>
                                <th>@lang('essentials::lang.clock_in_time')</th>
                                <th>@lang('essentials::lang.clock_out_time')</th>
                                <th>@lang('essentials::lang.shift')</th>
                                <th>@lang('essentials::lang.ip_address')</th>
                                <th>@lang('essentials::lang.clock_in_note')</th>
                                <th>@lang('essentials::lang.clock_out_note')</th>
                                <th>&nbsp;</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">@lang('messages.save')</button>
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">@lang('messages.close')</button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    function initFlatpickrForRow(context) {
        $(context).find('.date_time_picker').flatpickr({
            enableTime: true,
            dateFormat: 'Y-m-d H:i'
        });
    }

    $(document).on('change', '#select_employee', function () {
        var userId = $(this).val();
        if (!userId) {
            return;
        }

        var existingRow = $('#employee_attendance_table tbody').find('tr[data-user_id="' + userId + '"]');
        if (existingRow.length) {
            $('#select_employee').val('').trigger('change');
            return;
        }

        var url = @json(route('projectx.essentials.hrm.attendance.get-attendance-row', ['user_id' => '__ID__'])).replace('__ID__', userId);
        $.get(url, function (response) {
            var $row = $(response);
            $('#employee_attendance_table tbody').append($row);
            initFlatpickrForRow($row);
            $('#select_employee').val('').trigger('change');
        });
    });

    $(document).on('click', '.remove_attendance_row', function () {
        $(this).closest('tr').remove();
    });

    $(document).on('submit', '#projectx_attendance_form', function (event) {
        event.preventDefault();
        var $form = $(this);
        var $submit = $form.find('button[type="submit"]');
        $submit.prop('disabled', true);

        $.ajax({
            method: 'POST',
            url: $form.attr('action'),
            data: $form.serialize(),
            success: function (response) {
                if (response.success) {
                    toastr.success(response.msg);
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('attendance_modal')).hide();
                    if (window.projectxAttendanceTable) {
                        window.projectxAttendanceTable.ajax.reload();
                    }
                } else {
                    toastr.error(response.msg);
                }
            },
            complete: function () {
                $submit.prop('disabled', false);
            }
        });
    });
})();
</script>
