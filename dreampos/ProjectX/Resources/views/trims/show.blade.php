@extends('projectx::layouts.main')

@section('title', __('projectx::lang.trim_overview'))

@section('content')
    @if(session('status'))
        <div class="alert alert-{{ session('status.success') ? 'success' : 'danger' }} alert-dismissible fade show mb-5" role="alert">
            {{ session('status.msg') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('projectx::lang.close') }}"></button>
        </div>
    @endif

    @include('projectx::trims._trim_header', ['trim' => $trim ?? null, 'currency' => $currency ?? null, 'activeTab' => 'overview'])

    <div class="row g-5 g-xl-8">
        <div class="col-xl-6">
            <div class="card card-flush h-100">
                <div class="card-header">
                    <div class="card-title">
                        <h3 class="fw-bold m-0">{{ __('projectx::lang.trim_specifications') }}</h3>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <div class="d-flex flex-column gap-4">
                        <div class="d-flex flex-stack">
                            <span class="text-gray-600 fw-semibold">{{ __('projectx::lang.material') }}</span>
                            <span class="text-gray-900 fw-bold">{{ $trim->material ?? '-' }}</span>
                        </div>
                        <div class="separator separator-dashed"></div>
                        <div class="d-flex flex-stack">
                            <span class="text-gray-600 fw-semibold">{{ __('projectx::lang.color_value') }}</span>
                            <span class="text-gray-900 fw-bold">{{ $trim->color_value ?? '-' }}</span>
                        </div>
                        <div class="separator separator-dashed"></div>
                        <div class="d-flex flex-stack">
                            <span class="text-gray-600 fw-semibold">{{ __('projectx::lang.size_dimension') }}</span>
                            <span class="text-gray-900 fw-bold">{{ $trim->size_dimension ?? '-' }}</span>
                        </div>
                        <div class="separator separator-dashed"></div>
                        <div class="d-flex flex-stack">
                            <span class="text-gray-600 fw-semibold">{{ __('projectx::lang.unit_of_measure') }}</span>
                            <span class="text-gray-900 fw-bold">{{ $trim->unit_of_measure ?? '-' }}</span>
                        </div>
                        <div class="separator separator-dashed"></div>
                        <div class="d-flex flex-stack">
                            <span class="text-gray-600 fw-semibold">{{ __('projectx::lang.placement') }}</span>
                            <span class="text-gray-900 fw-bold">{{ $trim->placement ?? '-' }}</span>
                        </div>
                        <div class="separator separator-dashed"></div>
                        <div class="d-flex flex-stack">
                            <span class="text-gray-600 fw-semibold">{{ __('projectx::lang.quantity_per_garment') }}</span>
                            <span class="text-gray-900 fw-bold">{{ $trim->quantity_per_garment ?? '-' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="card card-flush h-100">
                <div class="card-header">
                    <div class="card-title">
                        <h3 class="fw-bold m-0">{{ __('projectx::lang.trim_commercial') }}</h3>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <div class="d-flex flex-column gap-4">
                        <div class="d-flex flex-stack">
                            <span class="text-gray-600 fw-semibold">{{ __('projectx::lang.supplier') }}</span>
                            <span class="text-gray-900 fw-bold">{{ optional($trim->supplier ?? null)->name ?? optional($trim->supplier ?? null)->supplier_business_name ?? '-' }}</span>
                        </div>
                        <div class="separator separator-dashed"></div>
                        <div class="d-flex flex-stack">
                            <span class="text-gray-600 fw-semibold">{{ __('projectx::lang.unit_cost') }}</span>
                            <span class="text-gray-900 fw-bold">@format_currency((float) ($trim->unit_cost ?? 0))</span>
                        </div>
                        <div class="separator separator-dashed"></div>
                        <div class="d-flex flex-stack">
                            <span class="text-gray-600 fw-semibold">{{ __('projectx::lang.currency_label') }}</span>
                            <span class="text-gray-900 fw-bold">{{ $trim->currency ?? data_get($currency ?? [], 'code', '-') }}</span>
                        </div>
                        <div class="separator separator-dashed"></div>
                        <div class="d-flex flex-stack">
                            <span class="text-gray-600 fw-semibold">{{ __('projectx::lang.lead_time_days') }}</span>
                            <span class="text-gray-900 fw-bold">{{ $trim->lead_time_days ?? '-' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="card card-flush h-100">
                <div class="card-header">
                    <div class="card-title">
                        <h3 class="fw-bold m-0">{{ __('projectx::lang.care_testing') }}</h3>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <div class="text-gray-700 fw-semibold" style="white-space: pre-line;">
                        {{ $trim->care_testing ?? '-' }}
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="card card-flush h-100">
                <div class="card-header">
                    <div class="card-title">
                        <h3 class="fw-bold m-0">{{ __('projectx::lang.lifecycle') }}</h3>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <div class="d-flex flex-column gap-4">
                        <div class="d-flex flex-stack">
                            <span class="text-gray-600 fw-semibold">{{ __('projectx::lang.status') }}</span>
                            <span class="badge {{ $trim->badge_class ?? 'badge-light-secondary' }}">{{ $trim->status_label ?? ucfirst(str_replace('_', ' ', (string) ($trim->status ?? 'draft'))) }}</span>
                        </div>
                        <div class="separator separator-dashed"></div>
                        <div class="d-flex flex-stack">
                            <span class="text-gray-600 fw-semibold">{{ __('projectx::lang.approved_at') }}</span>
                            <span class="text-gray-900 fw-bold">
                                @if(!empty($trim->approved_at))
                                    @format_date($trim->approved_at)
                                @else
                                    -
                                @endif
                            </span>
                        </div>
                        <div class="separator separator-dashed"></div>
                        <div class="d-flex flex-stack">
                            <span class="text-gray-600 fw-semibold">{{ __('projectx::lang.qc_at') }}</span>
                            <span class="text-gray-900 fw-bold">
                                @if(!empty($trim->qc_at))
                                    @format_date($trim->qc_at)
                                @else
                                    -
                                @endif
                            </span>
                        </div>
                        <div class="separator separator-dashed"></div>
                        <div>
                            <div class="text-gray-600 fw-semibold mb-2">{{ __('projectx::lang.qc_notes') }}</div>
                            <div class="text-gray-900 fw-bold" style="white-space: pre-line;">{{ $trim->qc_notes ?? '-' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
