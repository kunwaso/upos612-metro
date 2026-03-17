@extends('projectx::layouts.main')

@section('title', __('projectx::lang.user_profile'))

@section('content')
@include('projectx::user-profile.partials._toolbar')

@if(session('status'))
    <div class="alert {{ session('status.success') ? 'alert-success' : 'alert-danger' }} d-flex align-items-center mb-7">
        <i class="ki-duotone ki-information fs-2 me-3"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
        <span>{{ session('status.msg') }}</span>
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger mb-7">
        <ul class="mb-0 ps-4">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@if(!empty($essentials_available))
    <div class="card card-flush mb-7">
        <div class="card-header pt-7">
            <h3 class="card-title fw-bold text-gray-900">{{ __('essentials::lang.essentials') }}</h3>
        </div>
        <div class="card-body pt-3">
            <div class="d-flex flex-wrap gap-3">
                <a href="{{ route('projectx.essentials.todo.index') }}" class="btn btn-light-primary btn-sm">{{ __('essentials::lang.todo') }}</a>
                <a href="{{ route('projectx.essentials.documents.index', ['type' => 'document']) }}" class="btn btn-light-primary btn-sm">{{ __('essentials::lang.document') }}</a>
                <a href="{{ route('projectx.essentials.documents.index', ['type' => 'memos']) }}" class="btn btn-light-primary btn-sm">{{ __('essentials::lang.memos') }}</a>
                <a href="{{ route('projectx.essentials.reminders.index') }}" class="btn btn-light-primary btn-sm">{{ __('essentials::lang.reminders') }}</a>
                @if(auth()->user()->can('essentials.view_message') || auth()->user()->can('essentials.create_message'))
                    <a href="{{ route('projectx.essentials.messages.index') }}" class="btn btn-light-primary btn-sm">{{ __('essentials::lang.messages') }}</a>
                @endif
                <a href="{{ route('projectx.essentials.knowledge-base.index') }}" class="btn btn-light-primary btn-sm">{{ __('essentials::lang.knowledge_base') }}</a>
                @if(auth()->user()->can('edit_essentials_settings'))
                    <a href="{{ route('projectx.essentials.settings.edit') }}" class="btn btn-light-primary btn-sm">{{ __('business.settings') }}</a>
                @endif
            </div>
        </div>
    </div>
@endif

<div class="row g-7">
    <div class="col-xxl-8">
        @include('projectx::user-profile.partials._profile_summary')
        @include('projectx::user-profile.partials._leave_cards')
        @include('projectx::user-profile.partials._attendance_today')
    </div>
    <div class="col-xxl-4">
        @include('projectx::user-profile.partials._daily_tasks')
    </div>
    <div class="col-12">
        @include('projectx::user-profile.partials._heatmap')
    </div>
</div>

@include('projectx::user-profile.partials._modals')
@endsection

@section('page_javascript')
<script>
(function () {
    'use strict';

    var genericErrorMessage = @json(__('messages.something_went_wrong'));
    var heatmapStatusMeta = @json($heatmap_status_meta);
    var heatmapDefaultStatus = 'not_present';
    var heatmapStatusClasses = Object.keys(heatmapStatusMeta).map(function (statusKey) {
        return heatmapStatusMeta[statusKey] && heatmapStatusMeta[statusKey].cell_class
            ? heatmapStatusMeta[statusKey].cell_class
            : '';
    }).filter(Boolean);
    var heatmapOverrideIcon = '<i class="ki-duotone ki-check-circle fs-5 text-white"><span class="path1"></span><span class="path2"></span></i>';

    function updateHeatmapCellFromOverrideForm(form) {
        if (!form || form.id !== 'projectx_user_profile_heatmap_override_form') {
            return;
        }

        var userIdInput = form.querySelector('input[name="user_id"]');
        var workDateInput = form.querySelector('input[name="work_date"]');
        var hourSlotInput = form.querySelector('input[name="hour_slot"]');
        var statusInput = form.querySelector('select[name="status"]');
        var noteInput = form.querySelector('textarea[name="note"]');

        var userId = userIdInput ? userIdInput.value : '';
        var workDate = workDateInput ? workDateInput.value : '';
        var hourSlot = hourSlotInput ? hourSlotInput.value : '';
        var status = statusInput ? statusInput.value : heatmapDefaultStatus;
        var note = noteInput ? noteInput.value : '';

        if (!workDate || !hourSlot) {
            return;
        }

        var selector = '.projectx-heatmap-cell[data-work-date="' + workDate + '"][data-hour-slot="' + hourSlot + '"]';
        if (userId) {
            selector += '[data-user-id="' + userId + '"]';
        }

        var cell = document.querySelector(selector);
        if (!cell) {
            return;
        }

        heatmapStatusClasses.forEach(function (statusClass) {
            cell.classList.remove(statusClass);
        });

        var targetStatus = heatmapStatusMeta[status] ? status : heatmapDefaultStatus;
        var targetClass = heatmapStatusMeta[targetStatus] && heatmapStatusMeta[targetStatus].cell_class
            ? heatmapStatusMeta[targetStatus].cell_class
            : '';
        if (targetClass) {
            cell.classList.add(targetClass);
        }

        cell.setAttribute('data-status', targetStatus);
        cell.setAttribute('data-note', note);
        cell.innerHTML = heatmapOverrideIcon;
    }

    var essentialsForms = document.querySelectorAll('.projectx-essentials-json-form, .projectx-heatmap-json-form');
    essentialsForms.forEach(function (form) {
        form.addEventListener('submit', function (event) {
            event.preventDefault();

            var submitButton = form.querySelector('button[type="submit"]');
            if (submitButton) {
                submitButton.setAttribute('disabled', 'disabled');
            }

            fetch(form.action, {
                method: (form.getAttribute('method') || 'POST').toUpperCase(),
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: new FormData(form),
                credentials: 'same-origin'
            })
                .then(function (response) {
                    if (response.status === 204) {
                        return { success: true };
                    }

                    return response.json().catch(function () {
                        return { success: false, msg: genericErrorMessage };
                    });
                })
                .then(function (result) {
                    var success = !!(result && result.success);
                    var message = result && result.msg ? result.msg : genericErrorMessage;
                    var successAction = form.getAttribute('data-projectx-success-action') || 'reload';
                    var modalId = form.getAttribute('data-projectx-modal-id');

                    if (window.toastr) {
                        if (success) {
                            window.toastr.success(message);
                        } else {
                            window.toastr.error(message);
                        }
                    } else {
                        window.alert(message);
                    }

                    if (success) {
                        if (form.classList.contains('projectx-heatmap-json-form')) {
                            updateHeatmapCellFromOverrideForm(form);
                        }

                        if (successAction === 'close_modal') {
                            if (modalId && window.bootstrap && window.bootstrap.Modal) {
                                var modalElement = document.getElementById(modalId);
                                if (modalElement) {
                                    window.bootstrap.Modal.getOrCreateInstance(modalElement).hide();
                                }
                            }

                            return;
                        }

                        window.location.reload();
                    }
                })
                .catch(function () {
                    if (window.toastr) {
                        window.toastr.error(genericErrorMessage);
                    } else {
                        window.alert(genericErrorMessage);
                    }
                })
                .finally(function () {
                    if (submitButton) {
                        submitButton.removeAttribute('disabled');
                    }
                });
        });
    });

    var taskEditButtons = document.querySelectorAll('.projectx-task-edit-btn');
    var taskEditForm = document.getElementById('projectx_user_profile_task_edit_form');
    var taskEditTitle = document.getElementById('projectx_user_profile_task_edit_title');
    var taskEditDate = document.getElementById('projectx_user_profile_task_edit_date');
    var taskUpdateBaseUrl = '{{ url('/projectx/user-profile/tasks') }}';

    taskEditButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            var taskId = this.getAttribute('data-task-id');
            var taskTitle = this.getAttribute('data-task-title') || '';
            var taskDate = this.getAttribute('data-task-date') || '';

            if (taskEditForm) {
                taskEditForm.setAttribute('action', taskUpdateBaseUrl + '/' + taskId);
            }
            if (taskEditTitle) {
                taskEditTitle.value = taskTitle;
            }
            if (taskEditDate) {
                taskEditDate.value = taskDate;
            }
        });
    });

    var heatmapCells = document.querySelectorAll('.projectx-heatmap-cell');
    var heatmapUserId = document.getElementById('projectx_user_profile_heatmap_user_id');
    var heatmapWorkDate = document.getElementById('projectx_user_profile_heatmap_work_date');
    var heatmapHourSlot = document.getElementById('projectx_user_profile_heatmap_hour_slot');
    var heatmapStatus = document.getElementById('projectx_user_profile_heatmap_status');
    var heatmapNote = document.getElementById('projectx_user_profile_heatmap_note');
    var heatmapClearUserId = document.getElementById('projectx_user_profile_heatmap_clear_user_id');
    var heatmapClearWorkDate = document.getElementById('projectx_user_profile_heatmap_clear_work_date');
    var heatmapClearHourSlot = document.getElementById('projectx_user_profile_heatmap_clear_hour_slot');

    heatmapCells.forEach(function (cell) {
        cell.addEventListener('click', function () {
            var userId = this.getAttribute('data-user-id') || '';
            var workDate = this.getAttribute('data-work-date') || '';
            var hourSlot = this.getAttribute('data-hour-slot') || '';
            var status = this.getAttribute('data-status') || 'not_present';
            var note = this.getAttribute('data-note') || '';

            if (heatmapUserId) {
                heatmapUserId.value = userId;
            }
            if (heatmapWorkDate) {
                heatmapWorkDate.value = workDate;
            }
            if (heatmapHourSlot) {
                heatmapHourSlot.value = hourSlot;
            }
            if (heatmapStatus) {
                heatmapStatus.value = status;
            }
            if (heatmapNote) {
                heatmapNote.value = note;
            }

            if (heatmapClearUserId) {
                heatmapClearUserId.value = userId;
            }
            if (heatmapClearWorkDate) {
                heatmapClearWorkDate.value = workDate;
            }
            if (heatmapClearHourSlot) {
                heatmapClearHourSlot.value = hourSlot;
            }
        });
    });
})();
</script>
@endsection
