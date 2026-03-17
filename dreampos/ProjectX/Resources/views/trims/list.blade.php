@extends('projectx::layouts.main')

@section('title', __('projectx::lang.trims_and_accessories'))

@section('content')
    @if(session('status'))
        <div class="alert alert-{{ session('status.success') ? 'success' : 'danger' }} alert-dismissible fade show mb-5" role="alert">
            {{ session('status.msg') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('projectx::lang.close') }}"></button>
        </div>
    @endif

    <div class="toolbar d-flex flex-stack py-3 py-lg-5 mb-5">
        <div class="page-title d-flex flex-column me-3">
            <h1 class="d-flex text-gray-900 fw-bold my-1 fs-3">{{ __('projectx::lang.trims_and_accessories') }}</h1>
            <ul class="breadcrumb breadcrumb-dot fw-semibold text-gray-600 fs-7 my-1">
                <li class="breadcrumb-item text-gray-600">
                    <a href="{{ Route::has('projectx.index') ? route('projectx.index') : '#' }}" class="text-gray-600 text-hover-primary">{{ __('projectx::lang.home') }}</a>
                </li>
                <li class="breadcrumb-item text-gray-500">{{ __('projectx::lang.trims_and_accessories') }}</li>
            </ul>
        </div>
        <div class="d-flex align-items-center py-2 gap-3">
            <form method="GET" action="{{ Route::has('projectx.trim_manager.list') ? route('projectx.trim_manager.list') : '#' }}" class="m-0">
                <select name="status" class="form-select form-select-solid form-select-sm w-175px" onchange="this.form.submit()">
                    <option value="all" {{ ($status_filter ?? 'all') === 'all' ? 'selected' : '' }}>{{ __('projectx::lang.all_statuses') }}</option>
                    <option value="draft" {{ ($status_filter ?? '') === 'draft' ? 'selected' : '' }}>{{ __('projectx::lang.trim_status_draft') }}</option>
                    <option value="sample_requested" {{ ($status_filter ?? '') === 'sample_requested' ? 'selected' : '' }}>{{ __('projectx::lang.trim_status_sample_requested') }}</option>
                    <option value="sample_received" {{ ($status_filter ?? '') === 'sample_received' ? 'selected' : '' }}>{{ __('projectx::lang.trim_status_sample_received') }}</option>
                    <option value="approved" {{ ($status_filter ?? '') === 'approved' ? 'selected' : '' }}>{{ __('projectx::lang.trim_status_approved') }}</option>
                    <option value="bulk_ordered" {{ ($status_filter ?? '') === 'bulk_ordered' ? 'selected' : '' }}>{{ __('projectx::lang.trim_status_bulk_ordered') }}</option>
                    <option value="bulk_received" {{ ($status_filter ?? '') === 'bulk_received' ? 'selected' : '' }}>{{ __('projectx::lang.trim_status_bulk_received') }}</option>
                    <option value="qc_passed" {{ ($status_filter ?? '') === 'qc_passed' ? 'selected' : '' }}>{{ __('projectx::lang.trim_status_qc_passed') }}</option>
                    <option value="qc_failed" {{ ($status_filter ?? '') === 'qc_failed' ? 'selected' : '' }}>{{ __('projectx::lang.trim_status_qc_failed') }}</option>
                </select>
            </form>

            @can('projectx.trim.create')
                <a href="{{ Route::has('projectx.trim_manager.create') ? route('projectx.trim_manager.create') : '#' }}" class="btn btn-sm btn-primary">
                    {{ __('projectx::lang.add_trim') }}
                </a>
            @endcan
        </div>
    </div>

    <div class="row gx-6 gx-xl-9">
        <div class="col-lg-6 col-xxl-4">
            <div class="card h-100">
                <div class="card-body p-9">
                    <div class="fs-2hx fw-bold">{{ data_get($statusCounts ?? [], 'total', 0) }}</div>
                    <div class="fs-4 fw-semibold text-gray-500 mb-7">{{ __('projectx::lang.current_trims') }}</div>
                    <div class="d-flex flex-column justify-content-center flex-row-fluid pe-11 mb-5">
                        <div class="d-flex fs-6 fw-semibold align-items-center mb-3">
                            <div class="bullet bg-warning me-3"></div>
                            <div class="text-gray-500">{{ __('projectx::lang.trim_status_draft') }}</div>
                            <div class="ms-auto fw-bold text-gray-700">{{ data_get($statusCounts ?? [], 'draft', 0) }}</div>
                        </div>
                        <div class="d-flex fs-6 fw-semibold align-items-center mb-3">
                            <div class="bullet bg-info me-3"></div>
                            <div class="text-gray-500">{{ __('projectx::lang.trim_status_sample_requested') }}</div>
                            <div class="ms-auto fw-bold text-gray-700">{{ data_get($statusCounts ?? [], 'sample_requested', 0) }}</div>
                        </div>
                        <div class="d-flex fs-6 fw-semibold align-items-center">
                            <div class="bullet bg-success me-3"></div>
                            <div class="text-gray-500">{{ __('projectx::lang.trim_status_approved') }}</div>
                            <div class="ms-auto fw-bold text-gray-700">{{ data_get($statusCounts ?? [], 'approved', 0) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6 col-xxl-4">
            <div class="card h-100">
                <div class="card-body p-9">
                    <div class="fs-2hx fw-bold">{{ is_countable($categories ?? []) ? count($categories ?? []) : 0 }}</div>
                    <div class="fs-4 fw-semibold text-gray-500 mb-7">{{ __('projectx::lang.by_category') }}</div>
                    <div class="separator separator-dashed mb-5"></div>
                    <div class="fs-6 fw-semibold text-gray-700 mb-2">{{ __('projectx::lang.selected_status') }}: <span class="text-gray-500">{{ $status_filter ?? 'all' }}</span></div>
                    <div class="fs-6 fw-semibold text-gray-700">{{ __('projectx::lang.selected_category') }}: <span class="text-gray-500">{{ $category_filter ?? __('projectx::lang.all_categories') }}</span></div>
                </div>
            </div>
        </div>

        <div class="col-lg-6 col-xxl-4">
            <div class="card h-100">
                <div class="card-body p-9 d-flex flex-column justify-content-between">
                    <div>
                        <div class="fs-2hx fw-bold">{{ data_get($statusCounts ?? [], 'qc_passed', 0) }}</div>
                        <div class="fs-4 fw-semibold text-gray-500 mb-7">{{ __('projectx::lang.trim_status_qc_passed') }}</div>
                    </div>
                    @can('projectx.trim.create')
                        <a href="{{ Route::has('projectx.trim_manager.create') ? route('projectx.trim_manager.create') : '#' }}" class="btn btn-primary btn-sm">
                            {{ __('projectx::lang.add_trim') }}
                        </a>
                    @endcan
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex flex-wrap flex-stack my-5">
        <h2 class="fs-2 fw-semibold my-2">{{ __('projectx::lang.trims_and_accessories') }}
            <span class="fs-6 text-gray-500 ms-1">{{ __('projectx::lang.trims_by_status') }}</span>
        </h2>

        <form method="GET" action="{{ Route::has('projectx.trim_manager.list') ? route('projectx.trim_manager.list') : '#' }}" class="d-flex flex-wrap gap-3 my-1">
            <select name="status" class="form-select form-select-sm form-select-solid fw-bold w-175px">
                <option value="all" {{ ($status_filter ?? 'all') === 'all' ? 'selected' : '' }}>{{ __('projectx::lang.all_statuses') }}</option>
                <option value="draft" {{ ($status_filter ?? '') === 'draft' ? 'selected' : '' }}>{{ __('projectx::lang.trim_status_draft') }}</option>
                <option value="sample_requested" {{ ($status_filter ?? '') === 'sample_requested' ? 'selected' : '' }}>{{ __('projectx::lang.trim_status_sample_requested') }}</option>
                <option value="sample_received" {{ ($status_filter ?? '') === 'sample_received' ? 'selected' : '' }}>{{ __('projectx::lang.trim_status_sample_received') }}</option>
                <option value="approved" {{ ($status_filter ?? '') === 'approved' ? 'selected' : '' }}>{{ __('projectx::lang.trim_status_approved') }}</option>
                <option value="bulk_ordered" {{ ($status_filter ?? '') === 'bulk_ordered' ? 'selected' : '' }}>{{ __('projectx::lang.trim_status_bulk_ordered') }}</option>
                <option value="bulk_received" {{ ($status_filter ?? '') === 'bulk_received' ? 'selected' : '' }}>{{ __('projectx::lang.trim_status_bulk_received') }}</option>
                <option value="qc_passed" {{ ($status_filter ?? '') === 'qc_passed' ? 'selected' : '' }}>{{ __('projectx::lang.trim_status_qc_passed') }}</option>
                <option value="qc_failed" {{ ($status_filter ?? '') === 'qc_failed' ? 'selected' : '' }}>{{ __('projectx::lang.trim_status_qc_failed') }}</option>
            </select>

            <select name="category" class="form-select form-select-sm form-select-solid fw-bold w-225px">
                <option value="">{{ __('projectx::lang.all_categories') }}</option>
                @foreach(($categories ?? []) as $categoryKey => $categoryItem)
                    <option
                        value="{{ data_get($categoryItem, 'id', $categoryKey) }}"
                        {{ (string) ($category_filter ?? '') === (string) data_get($categoryItem, 'id', $categoryKey) ? 'selected' : '' }}
                    >
                        {{ data_get($categoryItem, 'name', is_scalar($categoryItem) ? $categoryItem : $categoryKey) }}
                    </option>
                @endforeach
            </select>

            <button type="submit" class="btn btn-sm btn-light-primary">{{ __('projectx::lang.filter') }}</button>
        </form>
    </div>

    <div class="row g-6 g-xl-9">
        @forelse(($trims ?? []) as $trim)
            <div class="col-md-6 col-xl-4">
                <a href="{{ Route::has('projectx.trim_manager.show') && !empty($trim->id) ? route('projectx.trim_manager.show', ['id' => $trim->id]) : '#' }}" class="card border-hover-primary">
                    <div class="card-header border-0 pt-9">
                        <div class="card-title m-0">
                            <div class="symbol symbol-80px symbol-2by3 symbol-lg-150px mb-4 bg-light">
                                @if(!empty($trim->image_path))
                                    <img src="{{ asset('storage/' . $trim->image_path) }}" alt="{{ $trim->name ?? __('projectx::lang.trim_name') }}" class="p-3" />
                                @else
                                    <span class="symbol-label bg-light-primary text-primary fs-lg fw-bold">
                                        {{ strtoupper(substr((string) ($trim->name ?? 'TR'), 0, 2)) }}
                                    </span>
                                @endif
                            </div>
                        </div>
                        <div class="card-toolbar">
                            <span class="badge {{ $trim->badge_class ?? 'badge-light-secondary' }} fw-bold me-auto px-4 py-3">
                                {{ $trim->status_label ?? ucfirst(str_replace('_', ' ', (string) ($trim->status ?? 'draft'))) }}
                            </span>
                        </div>
                    </div>
                    <div class="card-body p-9">
                        <div class="fs-3 fw-bold text-gray-900">{{ $trim->name ?? '-' }}</div>
                        <div class="fs-7 fw-semibold text-gray-500 mb-2">{{ __('projectx::lang.part_number') }}: {{ $trim->part_number ?? '-' }}</div>
                        <div class="fs-7 fw-semibold text-gray-500 mb-4">{{ __('projectx::lang.trim_category') }}: {{ optional($trim->trimCategory ?? null)->name ?? '-' }}</div>

                        <div class="d-flex flex-wrap mb-5">
                            <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-7 mb-3">
                                <div class="fs-3 text-gray-800 fw-bold">
                                    @format_currency((float) ($trim->unit_cost ?? 0))
                                </div>
                                <div class="fw-semibold text-gray-500">{{ __('projectx::lang.unit_cost') }}</div>
                            </div>
                            <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 mb-3">
                                <div class="fs-3 text-gray-800 fw-bold">{{ $trim->lead_time_days ?? '-' }}</div>
                                <div class="fw-semibold text-gray-500">{{ __('projectx::lang.lead_time_days') }}</div>
                            </div>
                        </div>

                        @if(!empty(optional($trim->supplier ?? null)->name) || !empty(optional($trim->supplier ?? null)->supplier_business_name))
                            <div class="d-flex align-items-center">
                                <div class="symbol symbol-35px symbol-circle me-2">
                                    <span class="symbol-label bg-light-success text-success fw-bold">
                                        {{ strtoupper(substr((string) (optional($trim->supplier ?? null)->name ?? optional($trim->supplier ?? null)->supplier_business_name ?? 'S'), 0, 1)) }}
                                    </span>
                                </div>
                                <span class="fs-7 fw-semibold text-gray-600">
                                    {{ optional($trim->supplier ?? null)->name ?? optional($trim->supplier ?? null)->supplier_business_name }}
                                </span>
                            </div>
                        @else
                            <div class="fs-7 fw-semibold text-gray-500">{{ __('projectx::lang.no_supplier_assigned') }}</div>
                        @endif
                    </div>
                </a>
            </div>
        @empty
            <div class="col-12">
                <div class="card">
                    <div class="card-body p-9 text-center">
                        <div class="text-gray-500 fs-4 fw-semibold mb-5">{{ __('projectx::lang.no_trims_found') }}</div>
                        @can('projectx.trim.create')
                            <a href="{{ Route::has('projectx.trim_manager.create') ? route('projectx.trim_manager.create') : '#' }}" class="btn btn-primary btn-sm">
                                {{ __('projectx::lang.add_trim') }}
                            </a>
                        @endcan
                    </div>
                </div>
            </div>
        @endforelse
    </div>
@endsection
