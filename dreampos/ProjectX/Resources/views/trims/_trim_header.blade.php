{{-- Shared Trim detail header card with tabs --}}
<div class="card mb-6 mb-xl-9" data-projectx-trim-id="{{ (int) ($trim->id ?? 0) }}">
    <div class="card-body pt-9 pb-0">
        <div class="d-flex flex-wrap flex-sm-nowrap mb-6">
            <div class="d-flex flex-center flex-shrink-0 bg-light rounded w-100px h-100px w-lg-150px h-lg-150px me-7 mb-4 overflow-hidden">
                @if(!empty($trim->image_path))
                    <img class="mw-100 mh-100 p-3" src="{{ asset('storage/' . $trim->image_path) }}" alt="{{ $trim->name ?? __('projectx::lang.trim_name') }}" />
                @else
                    <span class="symbol-label bg-light-primary text-primary fs-2x fw-bold">
                        {{ strtoupper(substr((string) ($trim->name ?? 'TR'), 0, 2)) }}
                    </span>
                @endif
            </div>

            <div class="flex-grow-1">
                <div class="d-flex justify-content-between align-items-start flex-wrap mb-2">
                    <div class="d-flex flex-column">
                        <div class="d-flex align-items-center mb-1">
                            <a
                                href="{{ Route::has('projectx.trim_manager.show') && !empty($trim->id) ? route('projectx.trim_manager.show', ['id' => $trim->id]) : '#' }}"
                                class="text-gray-800 text-hover-primary fs-2 fw-bold me-3"
                            >
                                {{ $trim->name ?? __('projectx::lang.trim_name') }}
                            </a>
                            <span class="badge {{ $trim->badge_class ?? 'badge-light-secondary' }} me-auto">
                                {{ $trim->status_label ?? ucfirst(str_replace('_', ' ', (string) ($trim->status ?? 'draft'))) }}
                            </span>
                        </div>
                        <div class="d-flex flex-wrap fw-semibold mb-4 fs-6 text-gray-500">
                            <span>{{ optional($trim->trimCategory ?? null)->name ?? __('projectx::lang.not_set') }}</span>
                            @if(!empty($trim->material))
                                <span class="mx-2">•</span>
                                <span>{{ $trim->material }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="d-flex mb-4">
                        @can('projectx.trim.create')
                            <a
                                href="{{ Route::has('projectx.trim_manager.create') ? route('projectx.trim_manager.create') : '#' }}"
                                class="btn btn-sm btn-bg-light btn-active-color-primary me-3"
                            >
                                <i class="ki-duotone ki-plus fs-4 me-1"><span class="path1"></span><span class="path2"></span></i>
                                {{ __('projectx::lang.add_trim') }}
                            </a>
                        @endcan

                        <a
                            href="{{ Route::has('projectx.trim_manager.list') ? route('projectx.trim_manager.list') : '#' }}"
                            class="btn btn-sm btn-primary me-3"
                        >
                            <i class="ki-duotone ki-arrow-left fs-4 me-1"><span class="path1"></span><span class="path2"></span></i>
                            {{ __('projectx::lang.trims_and_accessories') }}
                        </a>

                        @if(request()->routeIs('projectx.trim_manager.show', 'projectx.trim_manager.datasheet', 'projectx.trim_manager.budget'))
                            <div class="d-flex align-items-center ms-1 ms-lg-3">
                                <div
                                    class="position-relative btn btn-color-gray-800 btn-icon btn-active-light-primary w-30px h-30px w-md-40px h-md-40px"
                                    id="kt_drawer_chat_toggle"
                                    data-bs-toggle="tooltip"
                                    data-bs-placement="bottom"
                                    title="{{ __('projectx::lang.ai_assistant') }}"
                                >
                                    <i class="ki-duotone ki-message-text-2 fs-1">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                        <span class="path3"></span>
                                    </i>
                                </div>
                            </div>
                        @endif

                        @can('projectx.trim.edit')
                            @if(!empty($trim->id))
                                <a
                                    href="{{ Route::has('projectx.trim_manager.edit') ? route('projectx.trim_manager.edit', ['id' => $trim->id]) : '#' }}"
                                    class="btn btn-sm btn-light btn-active-light-primary"
                                >
                                    {{ __('projectx::lang.edit') }}
                                </a>
                            @endif
                        @endcan
                    </div>
                </div>

                <div class="d-flex flex-wrap justify-content-start">
                    <div class="d-flex flex-wrap">
                        <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3">
                            <div class="fs-4 fw-bold">{{ optional($trim->trimCategory ?? null)->name ?? __('projectx::lang.not_set') }}</div>
                            <div class="fw-semibold fs-6 text-gray-500">{{ __('projectx::lang.trim_category') }}</div>
                        </div>

                        <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3">
                            <div class="fs-4 fw-bold">
                                @format_currency((float) ($trim->unit_cost ?? 0))
                            </div>
                            <div class="fw-semibold fs-6 text-gray-500">{{ __('projectx::lang.unit_cost') }}</div>
                        </div>

                        <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3">
                            <div class="fs-4 fw-bold">{{ $trim->lead_time_days ?? '-' }}</div>
                            <div class="fw-semibold fs-6 text-gray-500">{{ __('projectx::lang.lead_time_days') }}</div>
                        </div>

                        <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3">
                            <div class="fs-4 fw-bold">
                                @if(!empty($trim->approved_at))
                                    @format_date($trim->approved_at)
                                @else
                                    -
                                @endif
                            </div>
                            <div class="fw-semibold fs-6 text-gray-500">{{ __('projectx::lang.approved_at') }}</div>
                        </div>

                        <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3">
                            <div class="fs-4 fw-bold">
                                @if(!empty($trim->qc_at))
                                    @format_date($trim->qc_at)
                                @else
                                    -
                                @endif
                            </div>
                            <div class="fw-semibold fs-6 text-gray-500">{{ __('projectx::lang.qc_at') }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="separator"></div>

        <ul class="nav nav-stretch nav-line-tabs nav-line-tabs-2x border-transparent fs-5 fw-bold">
            <li class="nav-item">
                <a
                    class="nav-link text-active-primary py-5 me-6 {{ ($activeTab ?? 'overview') === 'overview' ? 'active' : '' }}"
                    href="{{ Route::has('projectx.trim_manager.show') && !empty($trim->id) ? route('projectx.trim_manager.show', ['id' => $trim->id]) : '#' }}"
                >
                    {{ __('projectx::lang.fm_overview') }}
                </a>
            </li>
            <li class="nav-item">
                <a
                    class="nav-link text-active-primary py-5 me-6 {{ ($activeTab ?? '') === 'datasheet' ? 'active' : '' }}"
                    href="{{ Route::has('projectx.trim_manager.datasheet') && !empty($trim->id) ? route('projectx.trim_manager.datasheet', ['id' => $trim->id]) : '#' }}"
                >
                    {{ __('projectx::lang.trim_datasheet') }}
                </a>
            </li>
            <li class="nav-item">
                <a
                    class="nav-link text-active-primary py-5 me-6 {{ ($activeTab ?? '') === 'budget' ? 'active' : '' }}"
                    href="{{ Route::has('projectx.trim_manager.budget') && !empty($trim->id) ? route('projectx.trim_manager.budget', ['id' => $trim->id]) : '#' }}"
                >
                    {{ __('projectx::lang.trim_budget') }}
                </a>
            </li>
        </ul>
    </div>
</div>
