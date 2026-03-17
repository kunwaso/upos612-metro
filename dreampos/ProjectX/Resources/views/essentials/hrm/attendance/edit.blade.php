<div class="modal-dialog" role="document">
    <div class="modal-content">
        <form method="POST" action="{{ route('projectx.essentials.hrm.attendance.update', ['attendance' => $attendance->id]) }}" id="projectx_attendance_edit_form">
            @csrf
            @method('PUT')
            <input type="hidden" name="employees" id="employees" value="{{ $attendance->employee->id }}">
            <input type="hidden" name="attendance_id" id="attendance_id" value="{{ $attendance->id }}">

            <div class="modal-header">
                <h3 class="modal-title">@lang('essentials::lang.edit_attendance')</h3>
                <button type="button" class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal">&times;</button>
            </div>

            <div class="modal-body">
                <div class="mb-5">
                    <strong>@lang('essentials::lang.employees'):</strong> {{ $attendance->employee->user_full_name }}
                </div>
                <div class="row g-5">
                    <div class="col-md-6">
                        <label class="form-label">@lang('essentials::lang.clock_in_time')</label>
                        <input type="text"
                            name="clock_in_time"
                            id="clock_in_time"
                            class="form-control form-control-solid projectx-flatpickr-datetime"
                            value="{{ @format_datetime($attendance->clock_in_time) }}"
                            required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">@lang('essentials::lang.clock_out_time')</label>
                        <input type="text"
                            name="clock_out_time"
                            id="clock_out_time"
                            class="form-control form-control-solid projectx-flatpickr-datetime"
                            value="{{ !empty($attendance->clock_out_time) ? @format_datetime($attendance->clock_out_time) : '' }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">@lang('essentials::lang.ip_address')</label>
                        <input type="text" name="ip_address" class="form-control form-control-solid" value="{{ $attendance->ip_address }}">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">@lang('essentials::lang.clock_in_note')</label>
                        <textarea name="clock_in_note" class="form-control form-control-solid" rows="3">{{ $attendance->clock_in_note }}</textarea>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">@lang('essentials::lang.clock_out_note')</label>
                        <textarea name="clock_out_note" class="form-control form-control-solid" rows="3">{{ $attendance->clock_out_note }}</textarea>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">@lang('messages.update')</button>
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">@lang('messages.close')</button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    $('.projectx-flatpickr-datetime').flatpickr({
        enableTime: true,
        dateFormat: 'Y-m-d H:i'
    });

    $(document).on('submit', '#projectx_attendance_edit_form', function (event) {
        event.preventDefault();
        var $form = $(this);
        var $submit = $form.find('button[type="submit"]');
        $submit.prop('disabled', true);

        $.ajax({
            method: 'PUT',
            url: $form.attr('action'),
            data: $form.serialize(),
            success: function (response) {
                if (response.success) {
                    toastr.success(response.msg);
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('edit_attendance_modal')).hide();
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
