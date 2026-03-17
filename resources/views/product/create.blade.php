@extends('layouts.app')
@section('title', __('product.add_new_product'))

@section('css')
<style>
    .product-image-input-placeholder {
        background-image: url('{{ asset('assets/media/svg/files/blank-image.svg') }}');
    }

    [data-bs-theme="dark"] .product-image-input-placeholder {
        background-image: url('{{ asset('assets/media/svg/files/blank-image-dark.svg') }}');
    }

    .product-image-input .image-input-wrapper {
        background-position: center;
        background-repeat: no-repeat;
        background-size: cover;
    }

    .product-image-input.image-input-empty .image-input-wrapper {
        background-size: 72px;
    }
</style>
@endsection

@section('toolbar')
<div id="kt_toolbar" class="toolbar d-flex flex-stack py-3 py-lg-5">
    <div id="kt_toolbar_container" class="container-xxl d-flex flex-stack flex-wrap">
        <div class="page-title d-flex flex-column justify-content-center me-3">
            <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">@lang('product.add_new_product')</h1>
            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                @foreach($breadcrumb as $crumb)
                    <li class="breadcrumb-item text-muted">
                        @if(!$loop->last && !empty($crumb['url']))
                            <a href="{{ $crumb['url'] }}" class="text-muted text-hover-primary">{{ $crumb['label'] }}</a>
                        @else
                            <span class="text-gray-700">{{ $crumb['label'] }}</span>
                        @endif
                    </li>
                    @if(!$loop->last)
                        <li class="breadcrumb-item">
                            <span class="bullet bg-gray-500 w-5px h-2px"></span>
                        </li>
                    @endif
                @endforeach
            </ul>
        </div>
        <div class="d-flex align-items-center gap-2 gap-lg-3">
            <a href="{{ $cancel_url }}" class="btn btn-light">@lang('messages.cancel')</a>
            <button type="submit" form="product_add_form" value="submit" class="btn btn-primary submit_product_form">@lang('messages.save')</button>
        </div>
    </div>
</div>
@endsection

@section('content')
<form id="product_add_form" class="product_form {{ $form_class }} form d-flex flex-column flex-lg-row" action="{{ $form_action }}" method="POST" enctype="multipart/form-data">
    @csrf
    <input type="hidden" name="submit_type" id="submit_type">
    <input type="hidden" id="variation_counter" value="1">
    <input type="hidden" id="default_profit_percent" value="{{ $default_profit_percent }}">

    <div class="d-flex flex-column gap-7 gap-lg-10 w-100 w-lg-300px mb-7 me-lg-10">
        <div class="card card-flush py-4">
            <div class="card-header">
                <div class="card-title">
                    <h2>@lang('lang_v1.product_image')</h2>
                </div>
            </div>
            <div class="card-body text-center pt-0">
                <div id="product_image_input" class="image-input image-input-outline product-image-input product-image-input-placeholder mb-3 {{ !empty($duplicate_product) && !empty($duplicate_product->image) ? '' : 'image-input-empty' }}" data-kt-image-input="true">
                    <div class="image-input-wrapper w-150px h-150px" style="{{ !empty($duplicate_product) && !empty($duplicate_product->image) ? 'background-image: url(\'' . e($duplicate_product->image_url) . '\');' : '' }}"></div>

                    <label class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="change" data-bs-toggle="tooltip" title="Change image">
                        <i class="ki-duotone ki-pencil fs-7">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                        <input type="file" name="image" id="upload_image" accept="image/*" {{ $is_image_required ? 'required' : '' }}>
                        <input type="hidden" name="remove_image" id="remove_image" value="0">
                    </label>

                    <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="cancel" data-bs-toggle="tooltip" title="@lang('messages.cancel')">
                        <i class="ki-duotone ki-cross fs-2">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                    </span>

                    <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="remove" data-bs-toggle="tooltip" title="Remove image">
                        <i class="ki-duotone ki-cross fs-2">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                    </span>
                </div>
                <div class="text-muted fs-7 mt-2">
                    @lang('purchase.max_file_size', ['size' => $document_size_limit_mb])<br>
                    @lang('lang_v1.aspect_ratio_should_be_1_1')
                </div>
            </div>
        </div>

        <div class="card card-flush py-4">
            <div class="card-header">
                <div class="card-title">
                    <h2>Product Details</h2>
                </div>
            </div>
            <div class="card-body pt-0">
                <div class="mb-7">
                    <label class="form-label required" for="unit_id">@lang('product.unit')</label>
                    <div class="input-group">
                        <select name="unit_id" id="unit_id" class="form-select form-select-solid select2" data-control="select2" required>
                            <option value="">@lang('messages.please_select')</option>
                            @foreach($units as $unit_id => $unit_label)
                                <option value="{{ $unit_id }}" {{ (string) (!empty($duplicate_product->unit_id) ? $duplicate_product->unit_id : session('business.default_unit')) === (string) $unit_id ? 'selected' : '' }}>
                                    {{ $unit_label }}
                                </option>
                            @endforeach
                        </select>
                        <button type="button" class="btn btn-light btn-icon btn-modal" data-href="{{ $quick_add_unit_url }}" data-container=".view_modal" title="@lang('unit.add_unit')" {{ $can_unit_create ? '' : 'disabled' }}>
                            <i class="ki-duotone ki-plus fs-3 text-primary"><span class="path1"></span><span class="path2"></span></i>
                        </button>
                    </div>
                </div>

                @if($show_sub_units)
                    <div class="mb-7">
                        <label class="form-label" for="sub_unit_ids">@lang('lang_v1.related_sub_units') @show_tooltip(__('lang_v1.sub_units_tooltip'))</label>
                        <select name="sub_unit_ids[]" id="sub_unit_ids" class="form-select form-select-solid select2" data-control="select2" multiple></select>
                    </div>
                @endif

                @if($show_secondary_unit)
                    <div class="mb-7">
                        <label class="form-label" for="secondary_unit_id">@lang('lang_v1.secondary_unit') @show_tooltip(__('lang_v1.secondary_unit_help'))</label>
                        <select name="secondary_unit_id" id="secondary_unit_id" class="form-select form-select-solid select2" data-control="select2">
                            <option value="">@lang('messages.please_select')</option>
                            @foreach($units as $unit_id => $unit_label)
                                <option value="{{ $unit_id }}" {{ (string) (!empty($duplicate_product->secondary_unit_id) ? $duplicate_product->secondary_unit_id : '') === (string) $unit_id ? 'selected' : '' }}>
                                    {{ $unit_label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                @if($show_brand)
                    <div class="mb-7">
                        <label class="form-label" for="brand_id">@lang('product.brand')</label>
                        <div class="input-group">
                            <select name="brand_id" id="brand_id" class="form-select form-select-solid select2" data-control="select2">
                                <option value="">@lang('messages.please_select')</option>
                                @foreach($brands as $brand_id => $brand_label)
                                    <option value="{{ $brand_id }}" {{ (string) (!empty($duplicate_product->brand_id) ? $duplicate_product->brand_id : '') === (string) $brand_id ? 'selected' : '' }}>
                                        {{ $brand_label }}
                                    </option>
                                @endforeach
                            </select>
                            <button type="button" class="btn btn-light btn-icon btn-modal" data-href="{{ $quick_add_brand_url }}" data-container=".view_modal" title="@lang('brand.add_brand')" {{ $can_brand_create ? '' : 'disabled' }}>
                                <i class="ki-duotone ki-plus fs-3 text-primary"><span class="path1"></span><span class="path2"></span></i>
                            </button>
                        </div>
                    </div>
                @endif

                @if($show_category)
                    <div class="mb-7">
                        <label class="form-label" for="category_id">@lang('product.category')</label>
                        <select name="category_id" id="category_id" class="form-select form-select-solid select2" data-control="select2">
                            <option value="">@lang('messages.please_select')</option>
                            @foreach($categories as $category_id => $category_label)
                                <option value="{{ $category_id }}" {{ (string) (!empty($duplicate_product->category_id) ? $duplicate_product->category_id : '') === (string) $category_id ? 'selected' : '' }}>
                                    {{ $category_label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                @if($show_sub_category)
                    <div class="mb-7">
                        <label class="form-label" for="sub_category_id">@lang('product.sub_category')</label>
                        <select name="sub_category_id" id="sub_category_id" class="form-select form-select-solid select2" data-control="select2">
                            <option value="">@lang('messages.please_select')</option>
                            @foreach($sub_categories as $sub_category_id => $sub_category_label)
                                <option value="{{ $sub_category_id }}" {{ (string) (!empty($duplicate_product->sub_category_id) ? $duplicate_product->sub_category_id : '') === (string) $sub_category_id ? 'selected' : '' }}>
                                    {{ $sub_category_label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div class="mb-7">
                    <label class="form-label" for="product_locations">@lang('business.business_locations') @show_tooltip(__('lang_v1.product_location_help'))</label>
                    <select name="product_locations[]" id="product_locations" class="form-select form-select-solid select2" data-control="select2" multiple>
                        @foreach($business_locations as $location_id => $location_name)
                            <option value="{{ $location_id }}" {{ in_array($location_id, $default_product_locations ?? []) ? 'selected' : '' }}>{{ $location_name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-7">
                    <label class="form-label required" for="barcode_type">@lang('product.barcode_type')</label>
                    <select name="barcode_type" id="barcode_type" class="form-select form-select-solid select2" data-control="select2" required>
                        @foreach($barcode_types as $barcode_type_key => $barcode_type_name)
                            <option value="{{ $barcode_type_key }}" {{ (string) (!empty($duplicate_product->barcode_type) ? $duplicate_product->barcode_type : $barcode_default) === (string) $barcode_type_key ? 'selected' : '' }}>{{ $barcode_type_name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-7">
                    <label class="form-check form-check-custom form-check-solid">
                        <input class="form-check-input input-icheck" type="checkbox" name="enable_stock" id="enable_stock" value="1" {{ !empty($duplicate_product) ? (!empty($duplicate_product->enable_stock) ? 'checked' : '') : 'checked' }}>
                        <span class="form-check-label fw-semibold text-gray-700">@lang('product.manage_stock')</span>
                    </label>
                    @show_tooltip(__('tooltip.enable_stock'))
                    <div class="text-muted fs-7 mt-2"><i>@lang('product.enable_stock_help')</i></div>
                </div>

                <div class="mb-7" id="alert_quantity_div" style="{{ $alert_quantity_div_visible ? '' : 'display:none;' }}">
                    <label class="form-label" for="alert_quantity">@lang('product.alert_quantity') @show_tooltip(__('tooltip.alert_quantity'))</label>
                    <input type="text" class="form-control form-control-solid input_number" name="alert_quantity" id="alert_quantity" value="{{ !empty($duplicate_product->alert_quantity) ? format_quantity_value($duplicate_product->alert_quantity) : '' }}" min="0" placeholder="@lang('product.alert_quantity')">
                </div>

                @if($show_warranty)
                    <div class="mb-7">
                        <label class="form-label" for="warranty_id">@lang('lang_v1.warranty')</label>
                        <select name="warranty_id" id="warranty_id" class="form-select form-select-solid select2" data-control="select2">
                            <option value="">@lang('messages.please_select')</option>
                            @foreach($warranties as $warranty_id => $warranty_name)
                                <option value="{{ $warranty_id }}">{{ $warranty_name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                @if(!empty($pos_module_data))
                    @foreach($pos_module_data as $value)
                        @if(!empty($value['view_path']))
                            @includeIf($value['view_path'], ['view_data' => $value['view_data']])
                        @endif
                    @endforeach
                @endif
            </div>
        </div>
    </div>

    <div class="d-flex flex-column flex-row-fluid gap-7 gap-lg-10">
        <ul class="nav nav-custom nav-tabs nav-line-tabs nav-line-tabs-2x border-0 fs-4 fw-semibold">
            <li class="nav-item">
                <a class="nav-link text-active-primary pb-4 active" data-bs-toggle="tab" href="#product_general_tab">General</a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-active-primary pb-4" data-bs-toggle="tab" href="#product_advanced_tab">Advanced</a>
            </li>
        </ul>

        <div class="tab-content">
            <div class="tab-pane fade show active" id="product_general_tab" role="tabpanel">
                <div class="card card-flush py-4 mb-7">
                    <div class="card-header">
                        <div class="card-title">
                            <h2>General</h2>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <div class="mb-7">
                            <label class="form-label required" for="name">@lang('product.product_name')</label>
                            <input type="text" class="form-control form-control-solid" name="name" id="name" value="{{ !empty($duplicate_product->name) ? $duplicate_product->name : '' }}" placeholder="@lang('product.product_name')" required>
                        </div>

                        <div class="mb-7">
                            <label class="form-label" for="sku">@lang('product.sku') @show_tooltip(__('tooltip.sku'))</label>
                            <input type="text" class="form-control form-control-solid" name="sku" id="sku" value="" placeholder="@lang('product.sku')">
                        </div>

                        <div class="mb-7">
                            <label class="form-label" for="product_description">@lang('lang_v1.product_description')</label>
                            <textarea class="form-control form-control-solid" rows="4" name="product_description" id="product_description">{{ !empty($duplicate_product->product_description) ? $duplicate_product->product_description : '' }}</textarea>
                        </div>

                        <div class="mb-0">
                            <label class="form-label" for="product_brochure">@lang('lang_v1.product_brochure')</label>
                            <input type="file" class="form-control form-control-solid" name="product_brochure" id="product_brochure" accept="{{ implode(',', array_keys(config('constants.document_upload_mimes_types'))) }}">
                            <div class="text-muted fs-7 mt-2">
                                @lang('purchase.max_file_size', ['size' => $document_size_limit_mb])
                                @if(!empty($brochure_mimes_help))
                                    <br>{{ $brochure_mimes_help }}
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card card-flush py-4">
                    <div class="card-header">
                        <div class="card-title">
                            <h2>@lang('product.tax') &amp; @lang('product.product_type')</h2>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <div class="row g-5">
                            <div class="col-md-6 {{ $show_price_tax ? '' : 'd-none' }}">
                                <label class="form-label" for="tax">@lang('product.applicable_tax')</label>
                                <select name="tax" id="tax" class="form-select form-select-solid select2" data-control="select2">
                                    <option value="">@lang('messages.please_select')</option>
                                    @foreach($taxes as $tax_id => $tax_label)
                                        <option value="{{ $tax_id }}" data-rate="{{ data_get($tax_attributes, $tax_id . '.data-rate') }}" {{ (string) (!empty($duplicate_product->tax) ? $duplicate_product->tax : '') === (string) $tax_id ? 'selected' : '' }}>{{ $tax_label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6 {{ $show_price_tax ? '' : 'd-none' }}">
                                <label class="form-label required" for="tax_type">@lang('product.selling_price_tax_type')</label>
                                <select name="tax_type" id="tax_type" class="form-select form-select-solid select2" data-control="select2" required>
                                    <option value="inclusive" {{ (string) (!empty($duplicate_product->tax_type) ? $duplicate_product->tax_type : 'exclusive') === 'inclusive' ? 'selected' : '' }}>@lang('product.inclusive')</option>
                                    <option value="exclusive" {{ (string) (!empty($duplicate_product->tax_type) ? $duplicate_product->tax_type : 'exclusive') === 'exclusive' ? 'selected' : '' }}>@lang('product.exclusive')</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label required" for="type">@lang('product.product_type') @show_tooltip(__('tooltip.product_type'))</label>
                                <select name="type" id="type" class="form-select form-select-solid select2" data-control="select2" data-action="{{ !empty($duplicate_product) ? 'duplicate' : 'add' }}" data-product_id="{{ !empty($duplicate_product) ? $duplicate_product->id : '0' }}" required>
                                    @foreach($product_types as $type_key => $type_label)
                                        <option value="{{ $type_key }}" {{ (string) (!empty($duplicate_product->type) ? $duplicate_product->type : 'single') === (string) $type_key ? 'selected' : '' }}>{{ $type_label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="mt-7" id="product_form_part">
                            @include('product.partials.single_product_form_part', ['profit_percent' => $default_profit_percent])
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="product_advanced_tab" role="tabpanel">
                <div class="card card-flush py-4">
                    <div class="card-header">
                        <div class="card-title">
                            <h2>Advanced</h2>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        @if($show_expiry)
                            <div class="mb-7 {{ $expiry_config['hide'] ? 'd-none' : '' }}">
                                <label class="form-label" for="expiry_period">@lang('product.expires_in')</label>
                                <div class="row g-3">
                                    <div class="col-8">
                                        <input type="text" class="form-control form-control-solid input_number" name="expiry_period" id="expiry_period" value="{{ !empty($duplicate_product->expiry_period) ? num_format_value($duplicate_product->expiry_period) : $expiry_config['default_period'] }}" placeholder="@lang('product.expiry_period')">
                                    </div>
                                    <div class="col-4">
                                        <select name="expiry_period_type" id="expiry_period_type" class="form-select form-select-solid select2" data-control="select2">
                                            <option value="months" {{ (string) (!empty($duplicate_product->expiry_period_type) ? $duplicate_product->expiry_period_type : $expiry_config['default_type']) === 'months' ? 'selected' : '' }}>@lang('product.months')</option>
                                            <option value="days" {{ (string) (!empty($duplicate_product->expiry_period_type) ? $duplicate_product->expiry_period_type : $expiry_config['default_type']) === 'days' ? 'selected' : '' }}>@lang('product.days')</option>
                                            <option value="" {{ (string) (!empty($duplicate_product->expiry_period_type) ? $duplicate_product->expiry_period_type : $expiry_config['default_type']) === '' ? 'selected' : '' }}>@lang('product.not_applicable')</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="mb-7">
                            <label class="form-check form-check-custom form-check-solid">
                                <input class="form-check-input input-icheck" type="checkbox" name="enable_sr_no" value="1" {{ !empty($duplicate_product) && !empty($duplicate_product->enable_sr_no) ? 'checked' : '' }}>
                                <span class="form-check-label fw-semibold text-gray-700">@lang('lang_v1.enable_imei_or_sr_no')</span>
                            </label>
                            @show_tooltip(__('lang_v1.tooltip_sr_no'))
                        </div>

                        <div class="mb-7">
                            <label class="form-check form-check-custom form-check-solid">
                                <input class="form-check-input input-icheck" type="checkbox" name="not_for_selling" value="1" {{ !empty($duplicate_product) && !empty($duplicate_product->not_for_selling) ? 'checked' : '' }}>
                                <span class="form-check-label fw-semibold text-gray-700">@lang('lang_v1.not_for_selling')</span>
                            </label>
                            @show_tooltip(__('lang_v1.tooltip_not_for_selling'))
                        </div>

                        @if($show_racks || $show_row || $show_position)
                            <div class="mb-7">
                                <div class="fw-bold text-gray-800 mb-4">@lang('lang_v1.rack_details') @show_tooltip(__('lang_v1.tooltip_rack_details'))</div>
                                <div class="row g-5">
                                    @foreach($business_locations as $id => $location)
                                        <div class="col-md-6 col-lg-4">
                                            <label class="form-label" for="rack_{{ $id }}">{{ $location }}</label>
                                            @if($show_racks)
                                                <input type="text" class="form-control form-control-solid mb-3" name="product_racks[{{ $id }}][rack]" id="rack_{{ $id }}" value="{{ !empty($rack_details[$id]['rack']) ? $rack_details[$id]['rack'] : '' }}" placeholder="@lang('lang_v1.rack')">
                                            @endif
                                            @if($show_row)
                                                <input type="text" class="form-control form-control-solid mb-3" name="product_racks[{{ $id }}][row]" value="{{ !empty($rack_details[$id]['row']) ? $rack_details[$id]['row'] : '' }}" placeholder="@lang('lang_v1.row')">
                                            @endif
                                            @if($show_position)
                                                <input type="text" class="form-control form-control-solid" name="product_racks[{{ $id }}][position]" value="{{ !empty($rack_details[$id]['position']) ? $rack_details[$id]['position'] : '' }}" placeholder="@lang('lang_v1.position')">
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <div class="mb-7">
                            <label class="form-label" for="weight">@lang('lang_v1.weight')</label>
                            <input type="text" class="form-control form-control-solid" name="weight" id="weight" value="{{ !empty($duplicate_product->weight) ? $duplicate_product->weight : '' }}" placeholder="@lang('lang_v1.weight')">
                        </div>

                        <div class="row g-5">
                            @foreach($custom_fields_config as $customField)
                                <div class="col-md-6 col-lg-4">
                                    <label class="form-label" for="{{ $customField['name'] }}">{{ $customField['label'] }}</label>
                                    @if($customField['type'] === 'dropdown')
                                        <select name="{{ $customField['name'] }}" id="{{ $customField['name'] }}" class="form-select form-select-solid select2" data-control="select2">
                                            <option value="">{{ $customField['label'] }}</option>
                                            @foreach($customField['dropdown_options'] as $option)
                                                <option value="{{ $option }}" {{ (!empty($duplicate_product) && (string) data_get($duplicate_product, $customField['name']) === (string) $option) ? 'selected' : '' }}>{{ $option }}</option>
                                            @endforeach
                                        </select>
                                    @else
                                        <input type="text" class="form-control form-control-solid" name="{{ $customField['name'] }}" id="{{ $customField['name'] }}" value="{{ !empty($duplicate_product) ? data_get($duplicate_product, $customField['name']) : '' }}" placeholder="{{ $customField['label'] }}">
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-7">
                            <label class="form-label" for="preparation_time_in_minutes">@lang('lang_v1.preparation_time_in_minutes')</label>
                            <input type="number" class="form-control form-control-solid" name="preparation_time_in_minutes" id="preparation_time_in_minutes" value="{{ !empty($duplicate_product->preparation_time_in_minutes) ? $duplicate_product->preparation_time_in_minutes : '' }}" placeholder="@lang('lang_v1.preparation_time_in_minutes')">
                        </div>

                        <div class="mt-7">
                            @include('layouts.partials.module_form_part')
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card card-flush py-4">
            <div class="card-body pt-0">
                <div class="d-flex flex-wrap gap-2 justify-content-end">
                    @if($selling_price_group_count)
                        <button type="submit" value="submit_n_add_selling_prices" class="btn btn-warning submit_product_form">@lang('lang_v1.save_n_add_selling_price_group_prices')</button>
                    @endif

                    @can('product.opening_stock')
                        <button type="submit" id="opening_stock_button" value="submit_n_add_opening_stock" class="btn btn-primary submit_product_form" {{ !empty($duplicate_product) && empty($duplicate_product->enable_stock) ? 'disabled' : '' }}>
                            @lang('lang_v1.save_n_add_opening_stock')
                        </button>
                    @endcan

                    <button type="submit" value="save_n_add_another" class="btn btn-light submit_product_form">@lang('lang_v1.save_n_add_another')</button>
                    <button type="submit" value="submit" class="btn btn-primary submit_product_form">@lang('messages.save')</button>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@section('javascript')
<script src="{{ asset('assets/app/js/product.js?v=' . $asset_v) }}"></script>
<script type="text/javascript">
    $(document).ready(function() {
        var imageInputElement = document.getElementById('product_image_input');
        if (imageInputElement && typeof KTImageInput !== 'undefined') {
            new KTImageInput(imageInputElement);
        }

        __page_leave_confirmation('#product_add_form');
        onScan.attachTo(document, {
            suffixKeyCodes: [13],
            reactToPaste: true,
            onScan: function(sCode) {
                $('input#sku').val(sCode);
            },
            onScanError: function(oDebug) {
                console.log(oDebug);
            },
            minLength: 2,
            ignoreIfFocusOn: ['input', '.form-control']
        });
    });
</script>
@endsection
