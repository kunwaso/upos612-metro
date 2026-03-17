<div class="toolbar d-flex flex-stack py-3 py-lg-5" id="kt_toolbar">
    <div id="kt_toolbar_container" class="container-xxl d-flex flex-stack flex-wrap">
        <div class="page-title d-flex flex-column me-3">
            <h1 class="d-flex text-gray-900 fw-bold my-1 fs-3">{{ __('projectx::lang.user_profile') }}</h1>
            <ul class="breadcrumb breadcrumb-dot fw-semibold text-gray-600 fs-7 my-1">
                <li class="breadcrumb-item text-gray-600">
                    <a href="{{ route('projectx.index') }}" class="text-gray-600 text-hover-primary">{{ __('projectx::lang.X-Projects') }}</a>
                </li>
                <li class="breadcrumb-item text-gray-500">{{ __('projectx::lang.user_profile_dashboard') }}</li>
            </ul>
        </div>
        <div class="d-flex align-items-center gap-3 py-2">
            @if(!empty($users_for_admin_filter))
                <form method="GET" action="{{ route('projectx.user_profile.index') }}" class="d-flex align-items-center gap-2">
                    <input type="hidden" name="date" value="{{ $selected_date }}">
                    <input type="hidden" name="task_date" value="{{ $task_date }}">
                    <label for="projectx_user_profile_user_id" class="form-label mb-0 text-muted">{{ __('projectx::lang.select_user') }}</label>
                    <select class="form-select form-select-sm form-select-solid min-w-200px" id="projectx_user_profile_user_id" name="user_id" onchange="this.form.submit()">
                        @foreach($users_for_admin_filter as $user_option)
                            <option value="{{ $user_option['id'] }}" {{ (int) $user_option['id'] === (int) $targetUser->id ? 'selected' : '' }}>
                                {{ $user_option['name'] }}
                            </option>
                        @endforeach
                    </select>
                </form>
            @endif
            <a href="{{ route('projectx.user_profile.index', ['user_id' => $targetUser->id, 'date' => $selected_date, 'task_date' => $task_date]) }}" class="btn btn-sm btn-light btn-active-light-primary">
                {{ __('projectx::lang.refresh') }}
            </a>
        </div>
    </div>
</div>
