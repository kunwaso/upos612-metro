@extends('layouts.app')
@section('title', __('projectauto::lang.settings'))

@section('content')
    <div class="toolbar d-flex flex-stack py-3 py-lg-5" id="kt_toolbar">
        <div id="kt_toolbar_container" class="container-xxl d-flex flex-stack flex-wrap">
            <div class="page-title d-flex flex-column me-3">
                <h1 class="d-flex text-gray-900 fw-bold my-1 fs-3">{{ __('projectauto::lang.settings') }}</h1>
            </div>
            <div>
                <a href="{{ route('projectauto.settings.create') }}" class="btn btn-primary">{{ __('projectauto::lang.create_rule') }}</a>
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
                                <th>{{ __('projectauto::lang.rules') }}</th>
                                <th>{{ __('projectauto::lang.trigger_type') }}</th>
                                <th>{{ __('projectauto::lang.task_type') }}</th>
                                <th>{{ __('projectauto::lang.priority') }}</th>
                                <th>{{ __('projectauto::lang.is_active') }}</th>
                                <th class="text-end">{{ __('projectauto::lang.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-600 fw-semibold">
                            @forelse($rules as $rule)
                                <tr>
                                    <td>{{ $rule->id }}</td>
                                    <td>{{ $rule->name }}</td>
                                    <td>{{ __('projectauto::lang.' . $rule->trigger_type) }}</td>
                                    <td>{{ $rule->task_type }}</td>
                                    <td>{{ $rule->priority }}</td>
                                    <td>
                                        <span class="badge badge-light-{{ $rule->is_active ? 'success' : 'secondary' }}">
                                            {{ $rule->is_active ? __('lang_v1.yes') : __('lang_v1.no') }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('projectauto.settings.edit', ['id' => $rule->id]) }}" class="btn btn-sm btn-light-primary me-2">{{ __('messages.edit') }}</a>
                                        <form method="POST" action="{{ route('projectauto.settings.destroy', ['id' => $rule->id]) }}" style="display: inline-block;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-light-danger" onclick="return confirm('Delete this rule?')">{{ __('projectauto::lang.delete') }}</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center">{{ __('projectauto::lang.no_records_found') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $rules->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection
