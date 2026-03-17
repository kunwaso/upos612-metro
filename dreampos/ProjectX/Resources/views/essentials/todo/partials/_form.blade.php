<div class="row g-5">
    <div class="col-md-6">
        <label class="form-label required">@lang('essentials::lang.task')</label>
        <input type="text" name="task" class="form-control form-control-solid" value="{{ $todo_form['task'] }}" required>
    </div>
    <div class="col-md-3">
        <label class="form-label required">@lang('essentials::lang.date')</label>
        <input type="text" name="date" class="form-control form-control-solid projectx-flatpickr-datetime" value="{{ $todo_form['date'] }}" required>
    </div>
    <div class="col-md-3">
        <label class="form-label">@lang('essentials::lang.end_date')</label>
        <input type="text" name="end_date" class="form-control form-control-solid projectx-flatpickr-datetime" value="{{ $todo_form['end_date'] }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">@lang('essentials::lang.priority')</label>
        <select name="priority" class="form-select form-select-solid" data-control="select2" data-hide-search="true">
            <option value="">@lang('messages.please_select')</option>
            @foreach($priorities as $priorityKey => $priorityLabel)
                <option value="{{ $priorityKey }}" {{ $todo_form['priority'] === $priorityKey ? 'selected' : '' }}>{{ $priorityLabel }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">@lang('essentials::lang.change_status')</label>
        <select name="status" class="form-select form-select-solid" data-control="select2" data-hide-search="true">
            @foreach($task_statuses as $statusKey => $statusLabel)
                <option value="{{ $statusKey }}" {{ $todo_form['status'] === $statusKey ? 'selected' : '' }}>{{ $statusLabel }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">@lang('essentials::lang.estimated_hours')</label>
        <input type="number" min="0" step="0.25" name="estimated_hours" class="form-control form-control-solid" value="{{ $todo_form['estimated_hours'] }}">
    </div>
    @if(!empty($users))
        <div class="col-12">
            <label class="form-label">@lang('essentials::lang.assigned_to')</label>
            <select name="users[]" class="form-select form-select-solid" data-control="select2" data-placeholder="@lang('essentials::lang.assigned_to')" multiple>
                @foreach($users as $id => $label)
                    <option value="{{ $id }}" {{ in_array((int) $id, $todo_form['users'], true) ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
    @endif
    <div class="col-12">
        <label class="form-label">@lang('essentials::lang.description')</label>
        <textarea name="description" rows="5" class="form-control form-control-solid">{{ $todo_form['description'] }}</textarea>
    </div>
</div>
