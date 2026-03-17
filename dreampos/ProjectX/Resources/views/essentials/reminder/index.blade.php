@extends('projectx::layouts.main')

@section('title', __('essentials::lang.reminders'))

@section('content')
<div class="d-flex flex-wrap flex-stack mb-6">
    <div>
        <h1 class="text-gray-900 fw-bold mb-1">@lang('essentials::lang.reminders')</h1>
    </div>
    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#projectx_reminder_create_modal">@lang('essentials::lang.add_reminder')</button>
</div>

<div class="card card-flush">
    <div class="card-body pt-7">
        <div id="projectx_reminders_calendar"></div>
    </div>
</div>

@include('projectx::essentials.reminder.create')

<div class="modal fade" id="projectx_reminder_show_modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">@lang('essentials::lang.reminder_details')</h3>
                <button type="button" class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-2"></i>
                </button>
            </div>
            <div class="modal-body" id="projectx_reminder_show_modal_body"></div>
        </div>
    </div>
</div>
@endsection

@section('page_javascript')
<script>
(function () {
    $('.projectx-reminder-date').flatpickr({dateFormat: 'Y-m-d'});
    $('.projectx-reminder-time').flatpickr({enableTime: true, noCalendar: true, dateFormat: 'H:i'});

    var calendarElement = $('#projectx_reminders_calendar');
    calendarElement.fullCalendar({
        events: {
            url: '{{ route('projectx.essentials.reminders.index') }}',
            type: 'GET'
        },
        eventClick: function (event) {
            if (!event.url) {
                return false;
            }
            $.get(event.url, function (html) {
                $('#projectx_reminder_show_modal_body').html(html);
                bootstrap.Modal.getOrCreateInstance(document.getElementById('projectx_reminder_show_modal')).show();
            });
            return false;
        }
    });

    $('#projectx_reminder_create_submit').on('click', function () {
        $.post('{{ route('projectx.essentials.reminders.store') }}', $('#projectx_reminder_create_form').serialize(), function (response) {
            if (response.success) {
                toastr.success(response.msg);
                bootstrap.Modal.getOrCreateInstance(document.getElementById('projectx_reminder_create_modal')).hide();
                $('#projectx_reminder_create_form')[0].reset();
                calendarElement.fullCalendar('refetchEvents');
            } else {
                toastr.error(response.msg);
            }
        });
    });

    $(document).on('click', '#projectx_reminder_update_btn', function () {
        var id = $(this).data('id');
        var url = @json(route('projectx.essentials.reminders.update', ['reminder' => '__ID__'])).replace('__ID__', id);
        $.ajax({
            method: 'PUT',
            url: url,
            data: $('#projectx_reminder_update_form').serialize(),
            success: function (response) {
                if (response.success) {
                    toastr.success(response.msg);
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('projectx_reminder_show_modal')).hide();
                    calendarElement.fullCalendar('refetchEvents');
                } else {
                    toastr.error(response.msg);
                }
            }
        });
    });

    $(document).on('click', '#projectx_reminder_delete_btn', function () {
        var id = $(this).data('id');
        if (!confirm(@json(__('messages.sure')))) {
            return;
        }

        var url = @json(route('projectx.essentials.reminders.destroy', ['reminder' => '__ID__'])).replace('__ID__', id);
        $.ajax({
            method: 'DELETE',
            url: url,
            data: {_token: @json(csrf_token())},
            success: function (response) {
                if (response.success) {
                    toastr.success(response.msg);
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('projectx_reminder_show_modal')).hide();
                    calendarElement.fullCalendar('refetchEvents');
                } else {
                    toastr.error(response.msg);
                }
            }
        });
    });
})();
</script>
@endsection
