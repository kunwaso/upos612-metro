{{-- Toolbar: matches Modules/ProjectX/Resources/html/apps/contacts/*.html --}}
<div class="toolbar d-flex flex-stack py-3 py-lg-5" id="kt_toolbar">
    <div id="kt_toolbar_container" class="container-xxl d-flex flex-stack flex-wrap">
        <div class="page-title d-flex flex-column me-3">
            <h1 class="d-flex text-gray-900 fw-bold my-1 fs-3">{{ $title ?? __('contact.contacts') }}</h1>
            <ul class="breadcrumb breadcrumb-dot fw-semibold text-gray-600 fs-7 my-1">
                <li class="breadcrumb-item text-gray-600">
                    <a href="{{ route('projectx.index') }}" class="text-gray-600 text-hover-primary">{{ __('projectx::lang.X-Projects') }}</a>
                </li>
                <li class="breadcrumb-item text-gray-600">
                    <a href="{{ route('projectx.contacts.index') }}" class="text-gray-600 text-hover-primary">{{ __('contact.contacts') }}</a>
                </li>
                @if(isset($breadcrumb))
                    <li class="breadcrumb-item text-gray-500">{{ $breadcrumb }}</li>
                @endif
            </ul>
        </div>
        <div class="d-flex align-items-center py-2">
            @if(isset($filterDropdown) && $filterDropdown)
                <div class="me-4">
                    <a href="#" class="btn btn-sm btn-flex btn-light btn-active-primary fw-bold" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
                        <i class="ki-duotone ki-filter fs-5 text-gray-500 me-1">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>{{ __('report.filters') }}</a>
                    <div class="menu menu-sub menu-sub-dropdown w-250px w-md-300px" data-kt-menu="true" id="kt_contacts_filter_menu">
                        <div class="px-7 py-5">
                            <div class="fs-5 text-gray-900 fw-bold">{{ __('report.filter_options') }}</div>
                        </div>
                        <div class="separator border-gray-200"></div>
                        <div class="px-7 py-5">
                            <div class="mb-10">
                                <label class="form-label fw-semibold">{{ __('lang_v1.contact_type') }}:</label>
                                <select class="form-select form-select-solid" id="filter_type">
                                    <option value="">{{ __('messages.please_select') }}</option>
                                    <option value="customer" {{ ($type ?? '') === 'customer' ? 'selected' : '' }}>{{ __('report.customer') }}</option>
                                    <option value="supplier" {{ ($type ?? '') === 'supplier' ? 'selected' : '' }}>{{ __('report.supplier') }}</option>
                                </select>
                            </div>
                            <div class="d-flex justify-content-end">
                                <button type="button" class="btn btn-sm btn-light btn-active-light-primary me-2" data-kt-menu-dismiss="true">{{ __('messages.reset') }}</button>
                                <a href="#" class="btn btn-sm btn-primary" data-kt-menu-dismiss="true" id="filter_apply">{{ __('messages.apply') }}</a>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
            @if(isset($primaryAction) && $primaryAction)
                {!! $primaryAction !!}
            @endif
        </div>
    </div>
</div>
