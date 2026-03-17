@extends('layouts.app')
@section('title', __('projectauto::lang.task_details'))

@section('content')
    <div class="toolbar d-flex flex-stack py-3 py-lg-5" id="kt_toolbar">
        <div id="kt_toolbar_container" class="container-xxl d-flex flex-stack flex-wrap">
            <div class="page-title d-flex flex-column me-3">
                <h1 class="d-flex text-gray-900 fw-bold my-1 fs-3">{{ __('projectauto::lang.task_details') }} #{{ $task->id }}</h1>
            </div>
            <div>
                <a href="{{ route('projectauto.tasks.index') }}" class="btn btn-light">{{ __('projectauto::lang.back_to_list') }}</a>
            </div>
        </div>
    </div>

    <div class="container-xxl">
        <div class="row g-5">
            <div class="col-xl-7">
                <div class="card mb-5">
                    <div class="card-body">
                        <div class="mb-3"><strong>{{ __('projectauto::lang.task_type') }}:</strong> {{ $task->task_type }}</div>
                        <div class="mb-3"><strong>{{ __('projectauto::lang.task_status') }}:</strong> {{ __('projectauto::lang.status_' . $task->status) }}</div>
                        <div class="mb-3"><strong>{{ __('projectauto::lang.idempotency_key') }}:</strong> {{ $task->idempotency_key ?: '-' }}</div>
                        <div class="mb-3"><strong>{{ __('projectauto::lang.notes') }}:</strong> {{ $task->notes ?: '-' }}</div>
                        <div class="mb-3"><strong>{{ __('projectauto::lang.created_at') }}:</strong> {{ @format_datetime($task->created_at) }}</div>
                        <div class="mb-3"><strong>{{ __('projectauto::lang.updated_at') }}:</strong> {{ @format_datetime($task->updated_at) }}</div>
                        @if(!empty($task->result_model_type) && !empty($task->result_model_id))
                            <div class="mb-3">
                                <strong>{{ __('projectauto::lang.task_result') }}:</strong>
                                {{ class_basename($task->result_model_type) }} #{{ $task->result_model_id }}
                            </div>
                        @endif

                        <div>
                            <strong>{{ __('projectauto::lang.task_payload') }}:</strong>
                            <pre class="bg-light rounded p-4 mt-2">{{ json_encode($task->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-5">
                @if($task->status === 'pending' && auth()->user()->can('projectauto.tasks.approve'))
                    <div class="card mb-5">
                        <div class="card-header"><h3 class="card-title">{{ __('projectauto::lang.approve') }}</h3></div>
                        <div class="card-body">
                            <form method="POST" action="{{ route('projectauto.tasks.accept', ['id' => $task->id]) }}">
                                @csrf
                                <button type="submit" class="btn btn-success">{{ __('projectauto::lang.approve') }}</button>
                            </form>
                        </div>
                    </div>

                    <div class="card mb-5">
                        <div class="card-header"><h3 class="card-title">{{ __('projectauto::lang.reject') }}</h3></div>
                        <div class="card-body">
                            <form method="POST" action="{{ route('projectauto.tasks.reject', ['id' => $task->id]) }}">
                                @csrf
                                <div class="mb-4">
                                    <label class="form-label">{{ __('projectauto::lang.rejection_notes') }}</label>
                                    <textarea class="form-control" name="rejection_notes" rows="3" required></textarea>
                                </div>
                                <button type="submit" class="btn btn-danger">{{ __('projectauto::lang.reject') }}</button>
                            </form>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header"><h3 class="card-title">{{ __('projectauto::lang.modify_and_approve') }}</h3></div>
                        <div class="card-body">
                            <form method="POST" action="{{ route('projectauto.tasks.modify_accept', ['id' => $task->id]) }}">
                                @csrf
                                <div class="mb-4">
                                    <label class="form-label">{{ __('projectauto::lang.task_payload') }}</label>
                                    <textarea class="form-control font-monospace" name="payload" rows="10" required>{{ json_encode($task->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</textarea>
                                </div>
                                <div class="mb-4">
                                    <label class="form-label">{{ __('projectauto::lang.notes') }}</label>
                                    <input type="text" class="form-control" name="notes" value="{{ old('notes') }}">
                                </div>
                                <button type="submit" class="btn btn-primary">{{ __('projectauto::lang.modify_and_approve') }}</button>
                            </form>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
