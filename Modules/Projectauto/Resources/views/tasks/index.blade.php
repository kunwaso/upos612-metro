@extends('layouts.app')
@section('title', __('projectauto::lang.pending_tasks'))

@section('content')
    <div class="toolbar d-flex flex-stack py-3 py-lg-5" id="kt_toolbar">
        <div id="kt_toolbar_container" class="container-xxl d-flex flex-stack flex-wrap">
            <div class="page-title d-flex flex-column me-3">
                <h1 class="d-flex text-gray-900 fw-bold my-1 fs-3">{{ __('projectauto::lang.pending_tasks') }}</h1>
            </div>
        </div>
    </div>

    <div class="container-xxl">
        <div class="card">
            <div class="card-body py-4">
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed fs-6 gy-5">
                        <thead>
                            <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                <th>ID</th>
                                <th>{{ __('projectauto::lang.task_type') }}</th>
                                <th>{{ __('projectauto::lang.task_status') }}</th>
                                <th>{{ __('projectauto::lang.idempotency_key') }}</th>
                                <th>{{ __('projectauto::lang.created_at') }}</th>
                                <th class="text-end">{{ __('projectauto::lang.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-600 fw-semibold">
                            @forelse($tasks as $task)
                                <tr>
                                    <td>{{ $task->id }}</td>
                                    <td>{{ $task->task_type }}</td>
                                    <td>
                                        <span class="badge badge-light-{{ $task->status === 'pending' ? 'warning' : ($task->status === 'approved' ? 'success' : ($task->status === 'rejected' ? 'danger' : 'secondary')) }}">
                                            {{ __('projectauto::lang.status_' . $task->status) }}
                                        </span>
                                    </td>
                                    <td>{{ $task->idempotency_key }}</td>
                                    <td>{{ @format_datetime($task->created_at) }}</td>
                                    <td class="text-end">
                                        <a href="{{ route('projectauto.tasks.show', ['id' => $task->id]) }}" class="btn btn-sm btn-light-primary">
                                            {{ __('projectauto::lang.view') }}
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center">{{ __('projectauto::lang.no_records_found') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $tasks->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection
