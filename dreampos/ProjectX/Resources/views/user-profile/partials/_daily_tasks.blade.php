<div class="card card-flush h-100">
    <div class="card-header border-0 pt-6">
        <div class="card-title d-flex align-items-center gap-3">
            <h3 class="fw-bold text-gray-900 mb-0">{{ __('projectx::lang.daily_tasks') }}</h3>
            <span class="badge badge-light-primary fs-7 fw-bold">{{ $daily_tasks['completed_count'] }}/{{ $daily_tasks['total_count'] }}</span>
        </div>
    </div>
    <div class="card-body pt-0">
        <form method="POST" action="{{ route('projectx.user_profile.tasks.store') }}" class="d-flex align-items-center gap-2 mb-6">
            @csrf
            <input type="hidden" name="user_id" value="{{ $targetUser->id }}">
            <input type="hidden" name="task_date" value="{{ $task_date }}">
            <input type="text" name="title" class="form-control form-control-solid" placeholder="{{ __('projectx::lang.task_title') }}" required>
            <button type="submit" class="btn btn-primary btn-sm">{{ __('projectx::lang.add_task') }}</button>
        </form>

        <div class="d-flex flex-column gap-4">
            @forelse($daily_tasks['items'] as $task)
                <div class="d-flex align-items-start justify-content-between gap-3 border border-gray-300 border-dashed rounded p-3">
                    <div class="d-flex align-items-start gap-3 flex-grow-1">
                        <form id="projectx_task_toggle_{{ $task['id'] }}" method="POST" action="{{ route('projectx.user_profile.tasks.update', ['task' => $task['id']]) }}" class="mt-1">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="user_id" value="{{ $targetUser->id }}">
                            <input type="hidden" name="task_date" value="{{ $task_date }}">
                            <input type="hidden" name="is_completed" value="{{ $task['is_completed'] ? 0 : 1 }}">
                            <input class="form-check-input" type="checkbox" {{ $task['is_completed'] ? 'checked' : '' }} onchange="document.getElementById('projectx_task_toggle_{{ $task['id'] }}').submit();">
                        </form>
                        <div class="flex-grow-1">
                            <div class="fw-semibold text-gray-900 {{ $task['is_completed'] ? 'text-decoration-line-through text-muted' : '' }}">
                                {{ $task['title'] }}
                            </div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <button
                            type="button"
                            class="btn btn-icon btn-light-primary btn-sm projectx-task-edit-btn"
                            data-task-id="{{ $task['id'] }}"
                            data-task-title="{{ $task['title'] }}"
                            data-task-date="{{ $task_date }}"
                            data-bs-toggle="modal"
                            data-bs-target="#projectx_user_profile_task_edit_modal"
                        >
                            <i class="ki-duotone ki-pencil fs-4"><span class="path1"></span><span class="path2"></span></i>
                        </button>
                        <form method="POST" action="{{ route('projectx.user_profile.tasks.destroy', ['task' => $task['id']]) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-icon btn-light-danger btn-sm">
                                <i class="ki-duotone ki-trash fs-4"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span></i>
                            </button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="text-muted fw-semibold">{{ __('projectx::lang.no_data_found') }}</div>
            @endforelse
        </div>
    </div>
</div>
