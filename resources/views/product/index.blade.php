@extends('layouts.app')
@section('title', __('sale.products'))

@section('content')

    <!--begin::Toolbar-->
    <div class="toolbar d-flex flex-stack py-3 py-lg-5" id="kt_toolbar">
        <!--begin::Container-->
        <div id="kt_toolbar_container" class="container-xxl d-flex flex-stack flex-wrap">
            <!--begin::Page title-->
            <div class="page-title d-flex flex-column me-3">
                <!--begin::Title-->
                <h1 class="d-flex text-gray-900 fw-bold my-1 fs-3">Products</h1>
                <!--end::Title-->
                <!--begin::Breadcrumb-->
                <ul class="breadcrumb breadcrumb-dot fw-semibold text-gray-600 fs-7 my-1">
                    <!--begin::Item-->
                    <li class="breadcrumb-item text-gray-600">
                        <a href="{{ route('home') }}" class="text-gray-600 text-hover-primary">Home</a>
                    </li>
                    <!--end::Item-->
                    <!--begin::Item-->
                    <li class="breadcrumb-item text-gray-600">eCommerce</li>
                    <!--end::Item-->
                    <!--begin::Item-->
                    <li class="breadcrumb-item text-gray-600">Catalog</li>
                    <!--end::Item-->
                    <!--begin::Item-->
                    <li class="breadcrumb-item text-gray-500">Products</li>
                    <!--end::Item-->
                </ul>
                <!--end::Breadcrumb-->
            </div>
            <!--end::Page title-->
            <!--begin::Actions-->
            <div class="d-flex align-items-center py-2">
                <!--begin::Wrapper-->
                <div class="me-4">
                    <!--begin::Menu-->
                    <a href="#" class="btn btn-sm btn-flex btn-light btn-active-primary fw-bold" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
                    <i class="ki-duotone ki-filter fs-5 text-gray-500 me-1">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>Filter</a>
                    <!--begin::Menu 1-->
                    <div class="menu menu-sub menu-sub-dropdown w-250px w-md-300px" data-kt-menu="true" id="kt_menu_6998247d59409">
                        <!--begin::Header-->
                        <div class="px-7 py-5">
                            <div class="fs-5 text-gray-900 fw-bold">Filter Options</div>
                        </div>
                        <!--end::Header-->
                        <!--begin::Menu separator-->
                        <div class="separator border-gray-200"></div>
                        <!--end::Menu separator-->
                        <!--begin::Form-->
                        <div class="px-7 py-5">
                            <!--begin::Input group-->
                            <div class="mb-10">
                                <!--begin::Label-->
                                <label class="form-label fw-semibold">Status:</label>
                                <!--end::Label-->
                                <!--begin::Input-->
                                <div>
                                    <select class="form-select form-select-solid" multiple="multiple" data-kt-select2="true" data-close-on-select="false" data-placeholder="Select option" data-dropdown-parent="#kt_menu_6998247d59409" data-allow-clear="true" data-kt-product-filter="menu-status">
                                        <option></option>
                                        <option value="active">Published</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>
                                <!--end::Input-->
                            </div>
                            <!--end::Input group-->
                            <!--begin::Input group-->
                            <div class="mb-10">
                                <!--begin::Label-->
                                <label class="form-label fw-semibold">Member Type:</label>
                                <!--end::Label-->
                                <!--begin::Options-->
                                <div class="d-flex">
                                    <!--begin::Options-->
                                    <label class="form-check form-check-sm form-check-custom form-check-solid me-5">
                                        <input class="form-check-input" type="checkbox" value="1" />
                                        <span class="form-check-label">Author</span>
                                    </label>
                                    <!--end::Options-->
                                    <!--begin::Options-->
                                    <label class="form-check form-check-sm form-check-custom form-check-solid">
                                        <input class="form-check-input" type="checkbox" value="2" checked="checked" />
                                        <span class="form-check-label">Customer</span>
                                    </label>
                                    <!--end::Options-->
                                </div>
                                <!--end::Options-->
                            </div>
                            <!--end::Input group-->
                            <!--begin::Input group-->
                            <div class="mb-10">
                                <!--begin::Label-->
                                <label class="form-label fw-semibold">Notifications:</label>
                                <!--end::Label-->
                                <!--begin::Switch-->
                                <div class="form-check form-switch form-switch-sm form-check-custom form-check-solid">
                                    <input class="form-check-input" type="checkbox" value="" name="notifications" checked="checked" />
                                    <label class="form-check-label">Enabled</label>
                                </div>
                                <!--end::Switch-->
                            </div>
                            <!--end::Input group-->
                            <!--begin::Actions-->
                            <div class="d-flex justify-content-end">
                                <button type="button" class="btn btn-sm btn-light btn-active-light-primary me-2" data-kt-menu-dismiss="true" id="kt_product_filter_reset">Reset</button>
                                <button type="button" class="btn btn-sm btn-primary" data-kt-menu-dismiss="true" id="kt_product_filter_apply">Apply</button>
                            </div>
                            <!--end::Actions-->
                        </div>
                        <!--end::Form-->
                    </div>
                    <!--end::Menu 1-->
                    <!--end::Menu-->
                </div>
                <!--end::Wrapper-->
                <!--begin::Button-->
                <a href="{{ route('products.create') }}" class="btn btn-sm btn-primary" id="kt_toolbar_primary_button">Create</a>
                <!--end::Button-->
            </div>
            <!--end::Actions-->
        </div>
        <!--end::Container-->
    </div>
    <!--end::Toolbar-->
    <!--begin::Container-->
    <div id="kt_content_container" class="d-flex flex-column-fluid align-items-start container-xxl">
        <!--begin::Post-->
        <div class="content flex-row-fluid" id="kt_content">
            <!--begin::Products-->
            <div class="card card-flush">
                <!--begin::Card header-->
                <div class="card-header align-items-center py-5 gap-2 gap-md-5">
                    <!--begin::Card title-->
                    <div class="card-title">
                        <!--begin::Search-->
                        <div class="d-flex align-items-center position-relative my-1">
                            <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-4">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            <input type="text" data-kt-ecommerce-product-filter="search" class="form-control form-control-solid w-250px ps-12" placeholder="Search Product" />
                        </div>
                        <!--end::Search-->
                    </div>
                    <!--end::Card title-->
                    <!--begin::Card toolbar-->
                    <div class="card-toolbar flex-row-fluid justify-content-end gap-5">
                        <div class="w-100 mw-150px">
                            <!--begin::Select2-->
                            <select class="form-select form-select-solid" data-control="select2" data-hide-search="true" data-placeholder="Status" data-kt-ecommerce-product-filter="status">
                                <option></option>
                                <option value="all">All</option>
                                <option value="published">Published</option>
                                <option value="inactive">Inactive</option>
                            </select>
                            <!--end::Select2-->
                        </div>
                        <!--begin::Add product-->
                        <a href="{{ route('products.create') }}" class="btn btn-primary">Add Product</a>
                        <!--end::Add product-->
                    </div>
                    <!--end::Card toolbar-->
                </div>
                <!--end::Card header-->
                <!--begin::Card body-->
                <div class="card-body pt-0">
                    <!--begin::Table-->
                    <table class="table align-middle table-row-dashed fs-6 gy-5" id="kt_ecommerce_products_table">
                        <thead>
                            <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                                <th class="w-10px pe-2">
                                    <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                                        <input class="form-check-input" type="checkbox" data-kt-check="true" data-kt-check-target="#kt_ecommerce_products_table .row-select" value="1" />
                                    </div>
                                </th>
                                <th class="min-w-200px">Product</th>
                                <th class="text-end min-w-100px">SKU</th>
                                <th class="text-end min-w-70px">Qty</th>
                                <th class="text-end min-w-100px">Price</th>
                                <th class="text-end min-w-125px">{{ __('lang_v1.storage_slot_code') }}</th>
                                <th class="text-end min-w-100px">Status</th>
                                <th class="text-end min-w-70px">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="fw-semibold text-gray-600"></tbody>
                    </table>
                    <!--end::Table-->
                </div>
                <!--end::Card body-->
            </div>
            <!--end::Products-->
        </div>
        <!--end::Post-->
    </div>
    <!--end::Container-->
@endsection

@section('javascript')
<script>
    $(document).ready(function () {
        var $table = $('#kt_ecommerce_products_table');
        if (!$table.length) {
            return;
        }

        var $searchInput = $('[data-kt-ecommerce-product-filter="search"]');
        var $statusSelect = $('[data-kt-ecommerce-product-filter="status"]');
        var $menuStatusSelect = $('[data-kt-product-filter="menu-status"]');
        var activeStateFilter = '';

        var normalizeStatus = function (value) {
            if (!value || value === 'all') {
                return '';
            }

            if (value === 'published' || value === 'active') {
                return 'active';
            }

            if (value === 'inactive') {
                return 'inactive';
            }

            return '';
        };

        var getMenuStatusValue = function () {
            var value = $menuStatusSelect.val();

            if ($.isArray(value)) {
                if (value.length !== 1) {
                    return '';
                }

                value = value[0];
            }

            return normalizeStatus(value);
        };

        var productTable = $table.DataTable({
            processing: true,
            serverSide: true,
            scrollX: true,
            autoWidth: false,
            ajax: {
                url: '{{ route('products.index') }}',
                type: 'GET',
                data: function (d) {
                    d.active_state = activeStateFilter;
                }
            },
            columns: [
                {
                    data: 'mass_delete',
                    name: 'mass_delete',
                    orderable: false,
                    searchable: false,
                    className: 'w-10px pe-2',
                    render: function (data) {
                        return '<div class="form-check form-check-sm form-check-custom form-check-solid me-3">' + (data || '') + '</div>';
                    }
                },
                {
                    data: 'product',
                    name: 'products.name',
                    className: 'min-w-200px',
                    render: function (data, type, row) {
                        var imageHtml = row.image || '<span class="symbol symbol-50px"><span class="symbol-label"></span></span>';
                        var productHtml = data || '';

                        return '<div class="d-flex align-items-center">' +
                            imageHtml +
                            '<div class="ms-5">' + productHtml + '</div>' +
                            '</div>';
                    }
                },
                {
                    data: 'sku',
                    name: 'products.sku',
                    className: 'text-end pe-0',
                    render: function (data) {
                        return '<span class="fw-bold">' + (data || '') + '</span>';
                    }
                },
                {
                    data: 'current_stock',
                    name: 'current_stock',
                    className: 'text-end pe-0',
                    searchable: false
                },
                {
                    data: 'selling_price',
                    name: 'min_price',
                    className: 'text-end pe-0',
                    searchable: false
                },
                {
                    data: 'slot_codes',
                    name: 'slot_codes',
                    className: 'text-end pe-0',
                    orderable: false,
                    searchable: false,
                    defaultContent: '--'
                },
                {
                    data: 'status',
                    name: 'products.is_inactive',
                    className: 'text-end pe-0',
                    searchable: false
                },
                {
                    data: 'action',
                    name: 'action',
                    className: 'text-end',
                    orderable: false,
                    searchable: false
                }
            ],
            order: [[1, 'asc']],
            drawCallback: function () {
                if (typeof KTMenu !== 'undefined') {
                    KTMenu.createInstances();
                }
            }
        });

        var searchTimeout = null;
        $searchInput.on('keyup', function () {
            var value = $(this).val();
            clearTimeout(searchTimeout);

            searchTimeout = setTimeout(function () {
                productTable.search(value).draw();
            }, 300);
        });

        $statusSelect.on('change', function () {
            activeStateFilter = normalizeStatus($(this).val());
            productTable.ajax.reload();
        });

        $('#kt_product_filter_apply').on('click', function (e) {
            e.preventDefault();
            activeStateFilter = getMenuStatusValue();
            productTable.ajax.reload();
        });

        $('#kt_product_filter_reset').on('click', function (e) {
            e.preventDefault();

            activeStateFilter = '';
            $menuStatusSelect.val(null).trigger('change');
            $statusSelect.val('all').trigger('change.select2');
            $searchInput.val('');

            productTable.search('').draw();
        });

        $(document).on('click', '.delete-product', function (e) {
            e.preventDefault();

            var href = $(this).data('href');
            if (!href) {
                return;
            }

            swal({
                title: LANG.sure,
                text: LANG.remove_product,
                icon: 'warning',
                buttons: true,
                dangerMode: true,
            }).then(function (willDelete) {
                if (willDelete) {
                    $.ajax({
                        method: 'DELETE',
                        url: href,
                        dataType: 'json',
                        success: function (result) {
                            if (result.success === true) {
                                toastr.success(result.msg);
                                productTable.ajax.reload(null, false);
                            } else {
                                toastr.error(result.msg);
                            }
                        },
                    });
                }
            });
        });
    });
</script>
@endsection

