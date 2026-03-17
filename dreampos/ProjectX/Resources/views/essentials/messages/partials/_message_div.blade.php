<div class="d-flex flex-column gap-1 border border-gray-300 rounded p-4 mb-4" id="projectx_message_{{ $message->id }}">
    <div class="d-flex justify-content-between">
        <div class="fw-semibold text-gray-900">{{ $message->sender->user_full_name ?? '' }}</div>
        <small class="text-muted">@if(!empty($message->created_at)) @format_datetime($message->created_at) @endif</small>
    </div>
    <div class="text-gray-700">{!! $message->message !!}</div>
    @if((int) $message->user_id === (int) auth()->id() && auth()->user()->can('essentials.create_message'))
        <div>
            <button type="button" class="btn btn-sm btn-light-danger projectx-delete-message" data-id="{{ $message->id }}">@lang('messages.delete')</button>
        </div>
    @endif
</div>
