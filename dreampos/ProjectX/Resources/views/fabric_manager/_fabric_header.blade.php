{{-- Shared Fabric detail header card with tabs --}}

<div class="card mb-6 mb-xl-9" data-projectx-fabric-id="{{ (int) $fabric->id }}">
    <div class="card-body pt-9 pb-0">
        <div class="d-flex flex-wrap flex-sm-nowrap mb-6">
            <div class="d-flex flex-center flex-shrink-0 bg-light rounded w-100px h-100px w-lg-150px h-lg-150px me-7 mb-4 overflow-hidden">
                <img class="mw-100 mh-100 p-3" src="{{ $headerFabricImage }}" alt="{{ $fabric->name }}" />
            </div>
            <div class="flex-grow-1">
                <div class="d-flex justify-content-between align-items-start flex-wrap mb-2">
                    <div class="d-flex flex-column">
                        <div class="d-flex align-items-center mb-1">
                            <a href="{{ route('projectx.fabric_manager.fabric', ['fabric_id' => $fabric->id]) }}" class="text-gray-800 text-hover-primary fs-2 fw-bold me-3">{{ $fabric->name }}</a>
                            <span class="badge {{ $fabric->badge_class }} me-auto">{{ $fabric->status_label }}</span>
                        </div>
                        <div class="d-flex flex-wrap fw-semibold mb-4 fs-5 text-gray-500">{{ $headerFabricDescription }}</div>
                    </div>
                    <div class="d-flex mb-4">
                        @can('projectx.fabric.create')
                        <a href="{{ route('projectx.fabric_manager.create') }}" class="btn btn-sm btn-bg-light btn-active-color-primary me-3"><i class="ki-duotone ki-plus fs-4 me-1"><span class="path1"></span><span class="path2"></span></i> {{ __('projectx::lang.add_new_fabric') }}</a>
                        @endcan
                        <a href="{{ route('projectx.fabric_manager.list') }}" class="btn btn-sm btn-primary me-3"> <i class="ki-duotone ki-arrow-left fs-4 me-1"><span class="path1"></span><span class="path2"></span></i> {{ __('projectx::lang.fabrics') }}</a>
                                    <!--begin::Activities-->
                                        
                                        <div class="d-flex align-items-center ms-1 ms-lg-3">
                                            <div class="position-relative btn btn-color-gray-800 btn-icon btn-active-light-primary w-30px h-30px w-md-40px h-md-40px"
                                                id="kt_drawer_chat_toggle"
                                                data-bs-toggle="tooltip"
                                                data-bs-placement="bottom"
                                                title="{{ __('projectx::lang.ai_assistant') }}">
                                                <i class="ki-duotone ki-message-text-2 fs-1">
                                                    <span class="path1"></span>
                                                    <span class="path2"></span>
                                                    <span class="path3"></span>
                                                </i>
                                            </div>
                                        </div>
                                 
                                    <!--end::Activities-->
                        <div class="me-0">
                            <button class="btn btn-sm btn-icon btn-bg-light btn-active-color-primary" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
                                <i class="ki-solid ki-dots-horizontal fs-2x"></i>
                            </button>
                            <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg-light-primary fw-semibold w-200px py-3" data-kt-menu="true">
                                <div class="menu-item px-3">
                                    <div class="menu-content text-muted pb-2 px-3 fs-7 text-uppercase">{{ __('projectx::lang.actions') }}</div>
                                </div>
                                <div class="menu-item px-3">
                                    <a href="#" class="menu-link px-3">{{ __('projectx::lang.create_fabric_datasheet') }}</a>
                                </div>
                                <div class="menu-item px-3">
                                    <a href="#" class="menu-link px-3">{{ __('projectx::lang.generate_pdf_datasheet') }}</a>
                                </div>
                                <div class="menu-item px-3 my-1">
                                    <a href="#" class="menu-link px-3">{{ __('projectx::lang.delete_fabric') }}</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="d-flex flex-wrap justify-content-start">
                    <div class="d-flex flex-wrap">
                        <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3">
                            <div class="d-flex align-items-center">
                                <div class="fs-4 fw-bold">{{ $headerFabricCreatedAt }}</div>
                            </div>
                            <div class="fw-semibold fs-6 text-gray-500">{{ __('projectx::lang.fabric_created_at') }}</div>
                        </div>
                        <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3">
                            <div class="d-flex align-items-center">
                                <div class="fs-4 fw-bold">
                                    @if($fabric->weight_gsm !== null && $fabric->weight_gsm !== '')
                                        @num_format((float) $fabric->weight_gsm)
                                    @else
                                        —
                                    @endif
                                </div>
                            </div>
                            <div class="fw-semibold fs-6 text-gray-500">{{ __('projectx::lang.weight_gsm') }}</div>
                        </div>
                        <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3">
                            <div class="d-flex align-items-center">
                                <div class="fs-4 fw-bold">@format_currency((float) $fabric->purchase_price)</div>
                            </div>
                            <div class="fw-semibold fs-6 text-gray-500">{{ __('projectx::lang.purchase_price') }}</div>
                        </div>
                    </div>
                    <div class="symbol-group symbol-hover mb-3">
                        @if($headerPrimarySupplier)
                            <div class="symbol symbol-35px symbol-circle" data-bs-toggle="tooltip" title="{{ $headerPrimarySupplier->name }}">
                                <span class="symbol-label bg-light-success text-success fw-bold">{{ strtoupper(substr($headerPrimarySupplier->name ?? '', 0, 1)) }}</span>
                            </div>
                            @if($headerSupplierCount > 1 && $fabric->suppliers->count() > 1)
                                @foreach ($fabric->suppliers->skip(1) as $supplier)
                                    <div class="symbol symbol-35px symbol-circle" data-bs-toggle="tooltip" title="{{ $supplier->name }}">
                                        <span class="symbol-label bg-light-success text-success fw-bold">{{ strtoupper(substr($supplier->name ?? '', 0, 1)) }}</span>
                                    </div>
                                @endforeach
                            @endif
                        @else
                            <span class="fs-7 fw-semibold text-gray-500">{{ __('projectx::lang.no_supplier_assigned') }}</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="separator"></div>
        <ul class="nav nav-stretch nav-line-tabs nav-line-tabs-2x border-transparent fs-5 fw-bold">
            <li class="nav-item">
                <a class="nav-link text-active-primary py-5 me-6 {{ ($activeTab ?? '') === 'overview' ? 'active' : '' }}" href="{{ route('projectx.fabric_manager.fabric', ['fabric_id' => $fabric->id]) }}">{{ __('projectx::lang.fm_overview') }}</a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-active-primary py-5 me-6 {{ ($activeTab ?? '') === 'datasheet' ? 'active' : '' }}" href="{{ route('projectx.fabric_manager.datasheet', ['fabric_id' => $fabric->id]) }}">{{ __('projectx::lang.fm_datasheet') }}</a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-active-primary py-5 me-6 {{ ($activeTab ?? '') === 'budget' ? 'active' : '' }}" href="{{ route('projectx.fabric_manager.budget', ['fabric_id' => $fabric->id]) }}">{{ __('projectx::lang.fm_budget') }}</a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-active-primary py-5 me-6 {{ ($activeTab ?? '') === 'users' ? 'active' : '' }}" href="{{ route('projectx.fabric_manager.users', ['fabric_id' => $fabric->id]) }}">{{ __('projectx::lang.fm_users') }}</a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-active-primary py-5 me-6 {{ ($activeTab ?? '') === 'files' ? 'active' : '' }}" href="{{ route('projectx.fabric_manager.files', ['fabric_id' => $fabric->id]) }}">{{ __('projectx::lang.fm_files') }}</a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-active-primary py-5 me-6 {{ ($activeTab ?? '') === 'activity' ? 'active' : '' }}" href="{{ route('projectx.fabric_manager.activity', ['fabric_id' => $fabric->id]) }}">{{ __('projectx::lang.fm_activity') }}</a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-active-primary py-5 me-6 {{ ($activeTab ?? '') === 'settings' ? 'active' : '' }}" href="{{ route('projectx.fabric_manager.settings', ['fabric_id' => $fabric->id]) }}">{{ __('projectx::lang.fm_settings') }}</a>
            </li>
        </ul>
    </div>
</div>
