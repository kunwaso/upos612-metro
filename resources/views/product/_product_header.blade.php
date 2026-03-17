{{-- Shared Product detail header card with tabs --}}

<div class="card mb-6 mb-xl-9" data-product-id="{{ (int) $product->id }}">
    <div class="card-body pt-9 pb-0">
        <div class="d-flex flex-wrap flex-sm-nowrap mb-6">
            <div class="d-flex flex-center flex-shrink-0 bg-light rounded w-100px h-100px w-lg-150px h-lg-150px me-7 mb-4 overflow-hidden">
                <img class="mw-100 mh-100 p-3" src="{{ $productImageUrl }}" alt="{{ $product->name }}" />
            </div>
            <div class="flex-grow-1">
                <div class="d-flex justify-content-between align-items-start flex-wrap mb-2">
                    <div class="d-flex flex-column">
                        <div class="d-flex align-items-center mb-1">
                            <a href="{{ route('product.detail', ['id' => $product->id, 'tab' => 'overview']) }}" class="text-gray-800 text-hover-primary fs-2 fw-bold me-3">{{ $product->name }}</a>
                            <span class="badge badge-light-primary me-auto">@lang('lang_v1.' . $product->type)</span>
                        </div>
                        <div class="d-flex flex-wrap fw-semibold mb-4 fs-5 text-gray-500">{{ $productDescription }}</div>
                    </div>
                    <div class="d-flex mb-4">
                        @can('product.create')
                        <a href="{{ route('products.create') }}" class="btn btn-sm btn-bg-light btn-active-color-primary me-3"><i class="ki-duotone ki-plus fs-4 me-1"><span class="path1"></span><span class="path2"></span></i> @lang('product.add_product')</a>
                        @endcan
                        <a href="{{ route('products.index') }}" class="btn btn-sm btn-primary me-3"> <i class="ki-duotone ki-arrow-left fs-4 me-1"><span class="path1"></span><span class="path2"></span></i> @lang('product.products')</a>
                        <div class="me-0">
                            @can('product.update')
                            <a href="{{ route('products.edit', $product->id) }}" class="btn btn-sm btn-icon btn-bg-light btn-active-color-primary" data-bs-toggle="tooltip" title="@lang('product.edit_product')">
                                <i class="ki-duotone ki-pencil fs-2"><span class="path1"></span><span class="path2"></span></i>
                            </a>
                            @endcan
                            <button class="btn btn-sm btn-icon btn-bg-light btn-active-color-primary" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
                                <i class="ki-solid ki-dots-horizontal fs-2x"></i>
                            </button>
                            <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg-light-primary fw-semibold w-200px py-3" data-kt-menu="true">
                                <div class="menu-item px-3">
                                    <div class="menu-content text-muted pb-2 px-3 fs-7 text-uppercase">@lang('messages.actions')</div>
                                </div>
                                @can('product.update')
                                <div class="menu-item px-3">
                                    <a href="{{ route('products.edit', $product->id) }}" class="menu-link px-3">@lang('product.edit_product')</a>
                                </div>
                                @endcan
                                <div class="menu-item px-3">
                                    <a href="{{ route('product.detail', ['id' => $product->id]) }}" class="menu-link px-3">@lang('product.view_product')</a>
                                </div>
                                <div class="menu-item px-3">
                                    <a href="#" class="menu-link px-3 no-print" onclick="window.print(); return false;">@lang('messages.print')</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="d-flex flex-wrap justify-content-start">
                    <div class="d-flex flex-wrap">
                        <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3">
                            <div class="d-flex align-items-center">
                                <div class="fs-4 fw-bold">{{ $productCreatedAt }}</div>
                            </div>
                            <div class="fw-semibold fs-6 text-gray-500">@lang('lang_v1.created_at')</div>
                        </div>
                        <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3">
                            <div class="d-flex align-items-center">
                                <div class="fs-4 fw-bold">{{ $product->unit->short_name ?? '—' }}</div>
                            </div>
                            <div class="fw-semibold fs-6 text-gray-500">@lang('product.unit')</div>
                        </div>
                        <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3">
                            <div class="d-flex align-items-center">
                                <div class="fs-4 fw-bold">{{ $product->category->name ?? '—' }}</div>
                            </div>
                            <div class="fw-semibold fs-6 text-gray-500">@lang('product.category')</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="separator"></div>
        <ul class="nav nav-stretch nav-line-tabs nav-line-tabs-2x border-transparent fs-5 fw-bold">
            <li class="nav-item">
                <a class="nav-link text-active-primary py-5 me-6 {{ ($activeTab ?? '') === 'overview' ? 'active' : '' }}" href="{{ route('product.detail', ['id' => $product->id, 'tab' => 'overview']) }}">@lang('product.overview')</a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-active-primary py-5 me-6 {{ ($activeTab ?? '') === 'stock' ? 'active' : '' }}" href="{{ route('product.detail', ['id' => $product->id, 'tab' => 'stock']) }}">@lang('product.stock')</a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-active-primary py-5 me-6 {{ ($activeTab ?? '') === 'prices' ? 'active' : '' }}" href="{{ route('product.detail', ['id' => $product->id, 'tab' => 'prices']) }}">@lang('product.prices')</a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-active-primary py-5 me-6 {{ ($activeTab ?? '') === 'quotes' ? 'active' : '' }}" href="{{ route('product.detail', ['id' => $product->id, 'tab' => 'quotes']) }}">@lang('product.product_budget')</a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-active-primary py-5 me-6 {{ ($activeTab ?? '') === 'files' ? 'active' : '' }}" href="{{ route('product.detail', ['id' => $product->id, 'tab' => 'files']) }}">@lang('product.product_files')</a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-active-primary py-5 me-6 {{ ($activeTab ?? '') === 'contacts' ? 'active' : '' }}" href="{{ route('product.detail', ['id' => $product->id, 'tab' => 'contacts']) }}">@lang('product.product_users')</a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-active-primary py-5 me-6 {{ ($activeTab ?? '') === 'activity' ? 'active' : '' }}" href="{{ route('product.detail', ['id' => $product->id, 'tab' => 'activity']) }}">@lang('product.product_activity')</a>
            </li>
        </ul>
    </div>
</div>
