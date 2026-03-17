@extends('projectx::layouts.main')

@section('title', __('essentials::lang.todo'))

@section('content')
<div class="d-flex flex-wrap flex-stack mb-6">
    <div>
        <h1 class="text-gray-900 fw-bold mb-1">{{ $todo->task }}</h1>
        <div class="text-muted fs-7">{{ $todo->task_id }}</div>
    </div>
    <div class="d-flex gap-2">
        @if(auth()->user()->can('essentials.edit_todos'))
            <a href="{{ route('projectx.essentials.todo.edit', ['todo' => $todo->id]) }}" class="btn btn-light-primary btn-sm">@lang('messages.edit')</a>
        @endif
        <a href="{{ route('projectx.essentials.todo.index') }}" class="btn btn-light btn-sm">@lang('business.back')</a>
    </div>
</div>

<div class="row g-7">
    <div class="col-xl-7">
        <div class="card card-flush mb-7">
            <div class="card-header pt-7">
                <h3 class="card-title">@lang('essentials::lang.todo')</h3>
            </div>
            <div class="card-body pt-2">
                <div class="mb-4"><span class="fw-semibold">@lang('essentials::lang.assigned_by'):</span> {{ $todo->assigned_by->user_full_name ?? '-' }}</div>
                <div class="mb-4"><span class="fw-semibold">@lang('essentials::lang.assigned_to'):</span> {{ implode(', ', $users) }}</div>
                <div class="mb-4"><span class="fw-semibold">@lang('essentials::lang.date'):</span> {{ $todo_view['date'] }}</div>
                <div class="mb-4"><span class="fw-semibold">@lang('essentials::lang.end_date'):</span> {{ $todo_view['end_date'] }}</div>
                <div class="mb-4"><span class="fw-semibold">@lang('essentials::lang.priority'):</span> {{ $priorities[$todo->priority] ?? '-' }}</div>
                <div class="mb-4"><span class="fw-semibold">@lang('essentials::lang.change_status'):</span> {{ $task_statuses[$todo->status] ?? $todo->status }}</div>
                <div class="mb-0">
                    <span class="fw-semibold">@lang('essentials::lang.description'):</span>
                    <div class="text-gray-700 mt-2">{!! $todo->description !!}</div>
                </div>
            </div>
        </div>

        <div class="card card-flush mb-7">
            <div class="card-header pt-7"><h3 class="card-title">@lang('essentials::lang.docs')</h3></div>
            <div class="card-body pt-2">
                <form method="POST" action="{{ route('projectx.essentials.todo.documents.store') }}" enctype="multipart/form-data" class="mb-5">
                    @csrf
                    <input type="hidden" name="task_id" value="{{ $todo->id }}">
                    <div class="mb-3">
                        <input type="file" name="documents[]" class="form-control form-control-solid" multiple required>
                    </div>
                    <button type="submit" class="btn btn-light-primary btn-sm">@lang('essentials::lang.upload')</button>
                </form>

                <div class="d-flex flex-column gap-2">
                    @forelse($todo->media as $media)
                        <div class="d-flex justify-content-between align-items-center border border-gray-300 rounded p-3">
                            <a href="{{ $media->display_url }}" target="_blank">{{ $media->display_name }}</a>
                            <button type="button" class="btn btn-sm btn-light-danger projectx-delete-document" data-id="{{ $media->id }}">@lang('messages.delete')</button>
                        </div>
                    @empty
                        <div class="text-muted">@lang('essentials::lang.no_docs_found')</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-5">
        <div class="card card-flush">
            <div class="card-header pt-7"><h3 class="card-title">@lang('essentials::lang.comments')</h3></div>
            <div class="card-body pt-2">
                <form id="projectx_todo_comment_form">
                    @csrf
                    <input type="hidden" name="task_id" value="{{ $todo->id }}">
                    <div class="mb-3">
                        <textarea name="comment" rows="3" class="form-control form-control-solid" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-light-primary btn-sm">@lang('essentials::lang.add_comment')</button>
                </form>
                <hr>
                <div id="projectx_todo_comments_list">
                    @foreach($todo->comments as $comment)
                        @include('projectx::essentials.todo.partials._comment', ['comment' => $comment])
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

@include('projectx::essentials.todo.partials._comment_modal')
@endsection

@section('page_javascript')
<script>
(function () {
    $('#projectx_todo_comment_form').on('submit', function (event) {
        event.preventDefault();
        $.post('{{ route('projectx.essentials.todo.comments.store') }}', $(this).serialize(), function (response) {
            if (response.success) {
                toastr.success(response.msg);
                $('#projectx_todo_comments_list').prepend(response.comment_html);
                $('#projectx_todo_comment_form textarea[name="comment"]').val('');
            } else {
                toastr.error(response.msg);
            }
        });
    });

    $(document).on('click', '.projectx-delete-comment', function () {
        var id = $(this).data('id');
        $.ajax({
            method: 'DELETE',
            url: @json(route('projectx.essentials.todo.comments.destroy', ['id' => '__ID__'])).replace('__ID__', id),
            data: {_token: @json(csrf_token())},
            success: function (response) {
                if (response.success) {
                    toastr.success(response.msg);
                    $('#projectx-todo-comment-' + id).remove();
                } else {
                    toastr.error(response.msg);
                }
            }
        });
    });

    $(document).on('click', '.projectx-delete-document', function () {
        var id = $(this).data('id');
        if (!confirm(@json(__('messages.sure')))) {
            return;
        }
        $.ajax({
            method: 'DELETE',
            url: @json(route('projectx.essentials.todo.documents.destroy', ['id' => '__ID__'])).replace('__ID__', id),
            data: {_token: @json(csrf_token())},
            success: function (response) {
                if (response.success) {
                    toastr.success(response.msg);
                    window.location.reload();
                } else {
                    toastr.error(response.msg);
                }
            }
        });
    });
})();
</script>
@endsection
