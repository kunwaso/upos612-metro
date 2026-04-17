@if ($can_manage_supplier_products)
    <div class="row g-5 mb-7">
        <div class="col-md-10">
            <label for="supplier_products_select" class="form-label fw-semibold fs-6 text-gray-700">
                @lang('lang_v1.supplier_products_add_label')
            </label>
            <select id="supplier_products_select" class="form-select form-select-solid" data-control="select2"
                multiple="multiple" data-placeholder="@lang('lang_v1.search_product')"></select>
            <div class="form-text">
                @lang('lang_v1.supplier_products_select_help', ['max' => (int) ($supplier_products_config['max_product_ids'] ?? 500)])
            </div>
        </div>
        <div class="col-md-2 d-flex align-items-end">
            <button type="button" class="btn btn-sm btn-primary w-100" id="add_supplier_products_btn">
                @lang('lang_v1.add_products')
            </button>
        </div>
    </div>
@else
    <div class="alert alert-light-info d-flex align-items-center mb-7">
        <i class="ki-duotone ki-information-5 fs-2 text-info me-2">
            <span class="path1"></span><span class="path2"></span><span class="path3"></span>
        </i>
        <div class="fw-semibold text-gray-700">@lang('lang_v1.supplier_products_read_only')</div>
    </div>
@endif

<div class="table-responsive">
    <table class="table align-middle table-row-dashed fs-6 gy-5" id="supplier_products_table" width="100%">
        <thead>
            <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                <th>@lang('sale.product')</th>
                <th>@lang('product.sku')</th>
                <th>@lang('messages.action')</th>
            </tr>
        </thead>
    </table>
</div>
