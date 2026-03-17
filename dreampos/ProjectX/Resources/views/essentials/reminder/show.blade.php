<div class="mb-5">
    <h3 class="mb-2">{{ $reminder->name }}</h3>
    <div class="text-muted">{{ $date }} {{ $time }}</div>
</div>

<div class="mb-5">
    <div class="fw-semibold">@lang('essentials::lang.repeat')</div>
    <div class="text-gray-700">{{ $repeat[$reminder->repeat] ?? $reminder->repeat }}</div>
</div>

<form id="projectx_reminder_update_form">
    @csrf
    @method('PUT')
    <div class="mb-4">
        <label class="form-label required">@lang('essentials::lang.change_reminder_repeat')</label>
        <select name="repeat" class="form-select form-select-solid" data-control="select2" data-hide-search="true">
            @foreach($repeat as $repeatKey => $repeatLabel)
                <option value="{{ $repeatKey }}" {{ $reminder->repeat === $repeatKey ? 'selected' : '' }}>{{ $repeatLabel }}</option>
            @endforeach
        </select>
    </div>
</form>

<div class="d-flex justify-content-between">
    <button type="button" class="btn btn-light-danger" id="projectx_reminder_delete_btn" data-id="{{ $reminder->id }}">@lang('essentials::lang.delete_reminder')</button>
    <button type="button" class="btn btn-primary" id="projectx_reminder_update_btn" data-id="{{ $reminder->id }}">@lang('messages.update')</button>
</div>
