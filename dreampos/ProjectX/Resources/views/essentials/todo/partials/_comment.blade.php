<div class="d-flex flex-column gap-1 border border-gray-300 rounded p-4 mb-4" id="projectx-todo-comment-{{ $comment->id }}">
    <div class="d-flex justify-content-between align-items-start">
        <div class="fw-semibold text-gray-900">{{ $comment->added_by->user_full_name ?? __('user.user') }}</div>
        <small class="text-muted">@if(!empty($comment->created_at)) @format_datetime($comment->created_at) @endif</small>
    </div>
    <div class="text-gray-700">{!! nl2br(e($comment->comment)) !!}</div>
    @if((int) $comment->comment_by === (int) auth()->id())
        <div>
            <button type="button" class="btn btn-sm btn-light-danger projectx-delete-comment" data-id="{{ $comment->id }}">@lang('messages.delete')</button>
        </div>
    @endif
</div>
