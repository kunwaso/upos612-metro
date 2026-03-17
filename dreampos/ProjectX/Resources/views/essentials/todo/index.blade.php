@extends('projectx::layouts.main')

@section('title', __('essentials::lang.todo_list'))

@section('content')
<div class="d-flex flex-wrap flex-stack mb-6">
    <div>
        <h1 class="text-gray-900 fw-bold mb-1">@lang('essentials::lang.todo_list')</h1>
        <div class="text-muted fw-semibold fs-6">@lang('essentials::lang.todo')</div>
    </div>
    @if(auth()->user()->can('essentials.add_todos'))
        <a href="{{ route('projectx.essentials.todo.create') }}" class="btn btn-primary btn-sm">@lang('essentials::lang.add_to_do')</a>
    @endif
</div>

<div class="card card-flush mb-7">
    <div class="card-body pt-6">
        <div class="row g-5">
            <div class="col-md-3">
                <label class="form-label">@lang('essentials::lang.priority')</label>
                <select id="projectx_todo_filter_priority" class="form-select form-select-solid" data-control="select2" data-hide-search="true">
                    <option value="">@lang('messages.all')</option>
                    @foreach($priorities as $priorityKey => $priorityLabel)
                        <option value="{{ $priorityKey }}">{{ $priorityLabel }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">@lang('essentials::lang.change_status')</label>
                <select id="projectx_todo_filter_status" class="form-select form-select-solid" data-control="select2" data-hide-search="true">
                    <option value="">@lang('messages.all')</option>
                    @foreach($task_statuses as $statusKey => $statusLabel)
                        <option value="{{ $statusKey }}">{{ $statusLabel }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">@lang('essentials::lang.user')</label>
                <select id="projectx_todo_filter_user_id" class="form-select form-select-solid" data-control="select2" data-hide-search="false">
                    <option value="">@lang('messages.all')</option>
                    @foreach($users as $id => $label)
                        <option value="{{ $id }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">@lang('report.start_date')</label>
                <input type="text" id="projectx_todo_filter_start_date" class="form-control form-control-solid projectx-flatpickr-date">
            </div>
            <div class="col-md-2">
                <label class="form-label">@lang('report.end_date')</label>
                <input type="text" id="projectx_todo_filter_end_date" class="form-control form-control-solid projectx-flatpickr-date">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="button" class="btn btn-light-primary" id="projectx_todo_filter_apply">@lang('report.apply_filters')</button>
            </div>
        </div>
    </div>
</div>

<div class="card card-flush">
    <div class="card-body pt-6">
        <table class="table align-middle table-row-dashed fs-6 gy-5" id="projectx_todo_table">
            <thead>
                <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                    <th>@lang('essentials::lang.task')</th>
                    <th>@lang('essentials::lang.assigned_by')</th>
                    <th>@lang('essentials::lang.assigned_to')</th>
                    <th>@lang('essentials::lang.date')</th>
                    <th>@lang('essentials::lang.end_date')</th>
                    <th>@lang('essentials::lang.change_status')</th>
                    <th>@lang('messages.action')</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

@include('projectx::essentials.todo.partials._change_status_modal')
@include('projectx::essentials.todo.partials._shared_docs_modal')
@endsection

@section('page_javascript')
<script>
(function () {
    $('.projectx-flatpickr-date').flatpickr({
        dateFormat: 'Y-m-d'
    });

    var table = $('#projectx_todo_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route('projectx.essentials.todo.index') }}',
            data: function (d) {
                d.priority = $('#projectx_todo_filter_priority').val();
                d.status = $('#projectx_todo_filter_status').val();
                d.user_id = $('#projectx_todo_filter_user_id').val();
                d.start_date = $('#projectx_todo_filter_start_date').val();
                d.end_date = $('#projectx_todo_filter_end_date').val();
            }
        },
        columns: [
            { data: 'task', name: 'task' },
            { data: 'assigned_by', name: 'assigned_by' },
            { data: 'users', name: 'users', orderable: false, searchable: false },
            { data: 'date', name: 'date' },
            { data: 'end_date', name: 'end_date' },
            { data: 'status', name: 'status' },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ],
        order: [[3, 'desc']]
    });

    $('#projectx_todo_filter_apply').on('click', function () {
        table.ajax.reload();
    });

    $(document).on('click', '.projectx-delete-todo', function (event) {
        event.preventDefault();
        var id = $(this).data('id');
        if (!confirm(@json(__('messages.sure')))) {
            return;
        }

        var url = @json(route('projectx.essentials.todo.destroy', ['todo' => '__ID__'])).replace('__ID__', id);
        $.ajax({
            method: 'DELETE',
            url: url,
            data: {_token: @json(csrf_token())},
            success: function (response) {
                if (response.success) {
                    toastr.success(response.msg);
                    table.ajax.reload();
                } else {
                    toastr.error(response.msg);
                }
            }
        });
    });

    $(document).on('click', '.projectx-change-status', function (event) {
        event.preventDefault();
        $('#projectx_todo_change_status_todo_id').val($(this).data('id'));
        $('#projectx_todo_change_status_status').val($(this).data('status')).trigger('change');
        bootstrap.Modal.getOrCreateInstance(document.getElementById('projectx_todo_change_status_modal')).show();
    });

    $('#projectx_todo_change_status_submit').on('click', function () {
        var id = $('#projectx_todo_change_status_todo_id').val();
        var url = @json(route('projectx.essentials.todo.update', ['todo' => '__ID__'])).replace('__ID__', id);
        $.ajax({
            method: 'PUT',
            url: url,
            data: $('#projectx_todo_change_status_form').serialize(),
            success: function (response) {
                if (response.success) {
                    toastr.success(response.msg);
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('projectx_todo_change_status_modal')).hide();
                    table.ajax.reload();
                } else {
                    toastr.error(response.msg);
                }
            }
        });
    });

    $(document).on('click', '.projectx-view-shared-docs', function () {
        var url = $(this).data('url');
        $.get(url, function (html) {
            $('#projectx_todo_shared_docs_modal_body').html(html);
            bootstrap.Modal.getOrCreateInstance(document.getElementById('projectx_todo_shared_docs_modal')).show();
        });
    });
})();
</script>
@endsection
