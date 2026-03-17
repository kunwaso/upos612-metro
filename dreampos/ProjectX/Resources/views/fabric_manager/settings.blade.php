@extends('projectx::layouts.main')

@section('title', __('projectx::lang.fabric_settings'))

@section('content')
@include('projectx::fabric_manager._fabric_header')

<div class="card">
    <div class="card-header">
        <div class="card-title">
            <h3 class="fw-bold m-0">{{ __('projectx::lang.fabric_settings') }}</h3>
        </div>
    </div>
    <form class="form" method="POST" action="{{ route('projectx.fabric_manager.settings.update', ['fabric_id' => $fabric->id]) }}" enctype="multipart/form-data">
        @csrf
        <div class="card-body">
            <div class="row g-5 g-xl-10">
                {{-- Left: Rich Content Vertical Tabs nav --}}
                <div class="col-md-4 col-lg-3">
                    <ul class="nav nav-tabs nav-pills flex-row border border-dashed border-gray-300 rounded p-5 flex-md-column me-5 mb-3 mb-md-0 fs-6 min-w-lg-200px" role="tablist">
                        <li class="nav-item w-100 me-0 mb-md-2 bg-gray-100">
                            <a class="nav-link w-100 active btn btn-flex btn-active-light-primary text-dark" data-bs-toggle="tab" href="#kt_fabric_tab_overview" role="tab">
                                <i class="ki-duotone ki-information-5 fs-2 me-3 text-blue-600"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                                <span class="d-flex flex-column align-items-start">
                                    <span class="fs-4 fw-bold">{{ __('projectx::lang.fm_tab_overview') }}</span>
                                    <span class="fs-7">{{ __('projectx::lang.fm_tab_overview_desc') }}</span>
                                </span>
                            </a>
                        </li>
                        <li class="nav-item w-100 me-0 mb-md-2">
                            <a class="nav-link w-100 btn btn-flex btn-active-light-info text-dark" data-bs-toggle="tab" href="#kt_fabric_tab_suppliers" role="tab">
                                <i class="ki-duotone ki-people fs-2 me-3 text-cyan-600"><span class="path1"></span><span class="path2"></span></i>
                                <span class="d-flex flex-column align-items-start">
                                    <span class="fs-4 fw-bold">{{ __('projectx::lang.suppliers') }}</span>
                                    <span class="fs-7">{{ __('projectx::lang.fm_tab_suppliers_desc') }}</span>
                                </span>
                            </a>
                        </li>
                        <li class="nav-item w-100 me-0 mb-md-2">
                            <a class="nav-link w-100 btn btn-flex btn-active-light-warning text-dark" data-bs-toggle="tab" href="#kt_fabric_tab_composition_construction" role="tab">
                                <i class="ki-duotone ki-color-swatch fs-2 me-3 text-amber-600"><span class="path1"></span><span class="path2"></span></i>
                                <span class="d-flex flex-column align-items-start">
                                    <span class="fs-4 fw-bold">{{ __('projectx::lang.composition_construction') }}</span>
                                    <span class="fs-7">{{ __('projectx::lang.fm_tab_composition_construction_desc') }}</span>
                                </span>
                            </a>
                        </li>
                        <li class="nav-item w-100 me-0 mb-md-2">
                            <a class="nav-link w-100 btn btn-flex btn-active-light-danger text-dark" data-bs-toggle="tab" href="#kt_fabric_tab_technical_specifications" role="tab">
                                <i class="ki-duotone ki-size fs-2 me-3 text-red-600"><span class="path1"></span><span class="path2"></span></i>
                                <span class="d-flex flex-column align-items-start">
                                    <span class="fs-4 fw-bold">{{ __('projectx::lang.technical_specifications') }}</span>
                                    <span class="fs-7">{{ __('projectx::lang.fm_tab_technical_specifications_desc') }}</span>
                                </span>
                            </a>
                        </li>
                        <li class="nav-item w-100 me-0 mb-md-2">
                            <a class="nav-link w-100 btn btn-flex btn-active-light-primary text-dark" data-bs-toggle="tab" href="#kt_fabric_tab_pricing_commercial" role="tab">
                                <i class="ki-duotone ki-dollar fs-2 me-3 text-indigo-600"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                                <span class="d-flex flex-column align-items-start">
                                    <span class="fs-4 fw-bold">{{ __('projectx::lang.pricing_commercial_terms') }}</span>
                                    <span class="fs-7">{{ __('projectx::lang.fm_tab_pricing_commercial_desc') }}</span>
                                </span>
                            </a>
                        </li>
                        <li class="nav-item w-100 me-0 mb-md-2">
                            <a class="nav-link w-100 btn btn-flex btn-active-light-success text-dark" data-bs-toggle="tab" href="#kt_fabric_tab_lead_time_logistics" role="tab">
                                <i class="ki-duotone ki-delivery fs-2 me-3 text-green-600"><span class="path1"></span><span class="path2"></span></i>
                                <span class="d-flex flex-column align-items-start">
                                    <span class="fs-4 fw-bold">{{ __('projectx::lang.lead_time_logistics') }}</span>
                                    <span class="fs-7">{{ __('projectx::lang.fm_tab_lead_time_logistics_desc') }}</span>
                                </span>
                            </a>
                        </li>
                        <li class="nav-item w-100">
                            <a class="nav-link w-100 btn btn-flex btn-active-light-info text-dark" data-bs-toggle="tab" href="#kt_fabric_tab_quality_compliance" role="tab">
                                <i class="ki-duotone ki-shield-tick fs-2 me-3 text-teal-600"><span class="path1"></span><span class="path2"></span></i>
                                <span class="d-flex flex-column align-items-start">
                                    <span class="fs-4 fw-bold">{{ __('projectx::lang.quality_compliance_attachments') }}</span>
                                    <span class="fs-7">{{ __('projectx::lang.fm_tab_quality_compliance_desc') }}</span>
                                </span>
                            </a>
                        </li>
                    </ul>
                </div>
                {{-- Right: Tab content --}}
                <div class="col-md-8 col-lg-9">
                    <div class="tab-content" id="fabricSettingsTabContent">
                        {{-- Tab 1: Overview --}}
                        <div class="tab-pane fade show active" id="kt_fabric_tab_overview" role="tabpanel">
                            <div class="row mb-8">
                                <label class="col-lg-3 col-form-label">{{ __('projectx::lang.fm_fabric_image') }}</label>
                                <div class="col-lg-9">
                                    <div class="image-input image-input-outline" data-kt-image-input="true" style="background-image: url('{{ asset('modules/projectx/media/svg/avatars/blank.svg') }}')">
                                        <div class="image-input-wrapper w-125px h-125px" style="background-image: url('{{ $fabric->image_path ? asset('storage/' . $fabric->image_path) : asset('modules/projectx/media/svg/brand-logos/volicity-9.svg') }}')"></div>
                                        <label class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="change" data-bs-toggle="tooltip" title="{{ __('projectx::lang.change_logo') }}">
                                            <i class="ki-duotone ki-pencil fs-7"><span class="path1"></span><span class="path2"></span></i>
                                            <input type="file" name="image" accept=".png, .jpg, .jpeg, .webp" />
                                            <input type="hidden" name="avatar_remove" />
                                        </label>
                                        <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="cancel" data-bs-toggle="tooltip" title="{{ __('projectx::lang.cancel_logo') }}">
                                            <i class="ki-duotone ki-cross fs-2"><span class="path1"></span><span class="path2"></span></i>
                                        </span>
                                        <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="remove" data-bs-toggle="tooltip" title="{{ __('projectx::lang.remove_logo') }}">
                                            <i class="ki-duotone ki-cross fs-2"><span class="path1"></span><span class="path2"></span></i>
                                        </span>
                                    </div>
                                    <div class="form-text">{{ __('projectx::lang.allowed_file_types') }}</div>
                                    @error('image')
                                        <div class="text-danger fs-7 mt-2">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="row mb-8">
                                <label class="col-lg-3 col-form-label required">{{ __('projectx::lang.fm_fabric_name') }}</label>
                                <div class="col-lg-9">
                                    <input type="text" name="name" class="form-control form-control-solid" placeholder="{{ __('projectx::lang.fabric_name_placeholder') }}" value="{{ old('name', $fabric->name ?? '') }}" />
                                    @error('name')
                                        <div class="text-danger fs-7 mt-2">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="row mb-8">
                                <label class="col-lg-3 col-form-label">{{ __('projectx::lang.description') }}</label>
                                <div class="col-lg-9">
                                    <textarea name="description" class="form-control form-control-solid" rows="3" placeholder="{{ __('projectx::lang.fabric_description_placeholder') }}">{{ old('description', $fabric->description ?? '') }}</textarea>
                                    @error('description')
                                        <div class="text-danger fs-7 mt-2">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        {{-- Tab 3: Suppliers --}}
                        <div class="tab-pane fade" id="kt_fabric_tab_suppliers" role="tabpanel">
                            <div class="row mb-8">
                                <label class="col-lg-3 col-form-label">{{ __('projectx::lang.suppliers') }}</label>
                                <div class="col-lg-9">
                                    <select
                                        name="supplier_contact_ids[]"
                                        class="form-select form-select-solid"
                                        data-control="select2"
                                        data-close-on-select="false"
                                        data-placeholder="{{ __('projectx::lang.suppliers_placeholder') }}"
                                        multiple="multiple">
                                        @foreach($suppliers as $supplierId => $supplierLabel)
                                            <option value="{{ $supplierId }}" {{ in_array((string) $supplierId, (array) ($selectedSupplierIdsForForm ?? []), true) ? 'selected' : '' }}>{{ $supplierLabel }}</option>
                                        @endforeach
                                    </select>
                                    <div class="form-text">{{ __('projectx::lang.suppliers_placeholder') }}</div>
                                    @error('supplier_contact_ids')
                                        <div class="text-danger fs-7 mt-2">{{ $message }}</div>
                                    @enderror
                                    @error('supplier_contact_ids.*')
                                        <div class="text-danger fs-7 mt-2">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="row mb-8">
                                <label class="col-lg-3 col-form-label">{{ __('projectx::lang.mill_article_no') }}</label>
                                <div class="col-lg-9">
                                    <input type="text" name="mill_article_no" class="form-control form-control-solid" placeholder="{{ __('projectx::lang.mill_article_no_placeholder') }}" value="{{ old('mill_article_no', $fabric->mill_article_no ?? '') }}" />
                                    @error('mill_article_no')
                                        <div class="text-danger fs-7 mt-2">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="row mb-8">
                                <label class="col-lg-3 col-form-label">{{ __('projectx::lang.country_of_origin') }}</label>
                                <div class="col-lg-9">
                                    <input type="text" name="country_of_origin" class="form-control form-control-solid" placeholder="{{ __('projectx::lang.country_of_origin_placeholder') }}" value="{{ old('country_of_origin', $fabric->country_of_origin ?? '') }}" />
                                    @error('country_of_origin')
                                        <div class="text-danger fs-7 mt-2">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="row mb-8">
                                <label class="col-lg-3 col-form-label">{{ __('projectx::lang.fabric_sku_label') }}</label>
                                <div class="col-lg-9">
                                    <input type="text" name="fabric_sku" class="form-control form-control-solid" placeholder="{{ __('projectx::lang.fabric_sku_placeholder') }}" value="{{ old('fabric_sku', $fabric->fabric_sku ?? '') }}" />
                                    @error('fabric_sku')
                                        <div class="text-danger fs-7 mt-2">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        {{-- Tab 4: Composition & Construction --}}
                        <div class="tab-pane fade" id="kt_fabric_tab_composition_construction" role="tabpanel">
                            <div class="row mb-8">
                                <label class="col-lg-3 col-form-label">{{ __('projectx::lang.composition_summary') }}</label>
                                <div class="col-lg-9">
                                    <div class="d-flex flex-column">
                                        <div class="d-flex fs-6 fw-semibold align-items-center mb-2 text-gray-600">
                                            {{ trans_choice('projectx::lang.composition_count', $compositionView['count'] ?? 0, ['count' => $compositionView['count'] ?? 0]) }}
                                        </div>
                                        <div class="d-flex flex-column" data-role="composition-legend">
                                            @forelse(($compositionView['items'] ?? []) as $compositionItem)
                                                <div class="d-flex fs-6 fw-semibold align-items-center {{ ! $loop->last ? 'mb-2' : '' }}">
                                                    <div class="bullet {{ $compositionItem['bullet_class'] ?? 'bg-gray-300' }} me-3"></div>
                                                    <div class="text-gray-500">{{ $compositionItem['label'] }}</div>
                                                    <div class="ms-auto fw-bold text-gray-700">{{ rtrim(rtrim(number_format((float) ($compositionItem['percent'] ?? 0), 2), '0'), '.') }}%</div>
                                                </div>
                                            @empty
                                                <div class="d-flex fs-6 fw-semibold align-items-center">
                                                    <div class="bullet bg-gray-300 me-3"></div>
                                                    <div class="text-gray-500">{{ __('projectx::lang.no_compositions_added') }}</div>
                                                    <div class="ms-auto fw-bold text-gray-700">0%</div>
                                                </div>
                                            @endforelse
                                        </div>
                                        <div class="mt-3">
                                            <a href="{{ route('projectx.fabric_manager.fabric', ['fabric_id' => $fabric->id]) }}" class="fw-bold text-primary text-hover-primary fs-7">{{ __('projectx::lang.edit_composition') }}</a>
                                            <span class="text-gray-500 fs-7 ms-1">{{ __('projectx::lang.composition_workflow_message') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @include('projectx::fabric_manager.partials._construction_fields')
                            <div class="row mb-8">
                                <label class="col-lg-3 col-form-label">{{ __('projectx::lang.yarn_count_denier') }}</label>
                                <div class="col-lg-9">
                                    <input type="text" name="yarn_count_denier" list="yarn_list" class="form-control form-control-solid" placeholder="{{ __('projectx::lang.yarn_count_denier_placeholder') }}" value="{{ old('yarn_count_denier', $fabric->yarn_count_denier ?? '') }}" />
                                    <datalist id="yarn_list">
                                        <option value="20D"></option>
                                        <option value="30D"></option>
                                        <option value="40D"></option>
                                        <option value="50D"></option>
                                        <option value="70D"></option>
                                        <option value="75D"></option>
                                        <option value="100D"></option>
                                        <option value="150D"></option>
                                        <option value="Ne 30/1"></option>
                                        <option value="Ne 40/1"></option>
                                        <option value="Ne 60/1"></option>
                                    </datalist>
                                    @error('yarn_count_denier')
                                        <div class="text-danger fs-7 mt-2">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        {{-- Tab 5: Technical Specifications --}}
                        <div class="tab-pane fade" id="kt_fabric_tab_technical_specifications" role="tabpanel">
                            <div class="row mb-8">
                                <label class="col-lg-3 col-form-label">{{ __('projectx::lang.weight_gsm') }}</label>
                                <div class="col-lg-9">
                                    <input type="number" name="weight_gsm" list="gsm_list" class="form-control form-control-solid" placeholder="{{ __('projectx::lang.weight_gsm_placeholder') }}" value="{{ old('weight_gsm', $fabric->weight_gsm ?? '') }}" />
                                    <datalist id="gsm_list">
                                        <option value="120"></option>
                                        <option value="150"></option>
                                        <option value="180"></option>
                                        <option value="200"></option>
                                        <option value="220"></option>
                                        <option value="240"></option>
                                        <option value="260"></option>
                                        <option value="280"></option>
                                        <option value="300"></option>
                                        <option value="320"></option>
                                    </datalist>
                                    @error('weight_gsm')
                                        <div class="text-danger fs-7 mt-2">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="row mb-8">
                                <label class="col-lg-3 col-form-label">{{ __('projectx::lang.width_cm') }}</label>
                                <div class="col-lg-9">
                                    <input type="number" name="width_cm" list="width_list" class="form-control form-control-solid" placeholder="{{ __('projectx::lang.width_cm_placeholder') }}" value="{{ old('width_cm', $fabric->width_cm ?? '') }}" />
                                    <datalist id="width_list">
                                        <option value="110"></option>
                                        <option value="120"></option>
                                        <option value="140"></option>
                                        <option value="145"></option>
                                        <option value="150"></option>
                                        <option value="160"></option>
                                        <option value="180"></option>
                                        <option value="200"></option>
                                    </datalist>
                                    @error('width_cm')
                                        <div class="text-danger fs-7 mt-2">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="row mb-8">
                                <label class="col-lg-3 col-form-label">{{ __('projectx::lang.shrinkage_percent') }}</label>
                                <div class="col-lg-9">
                                    <input type="number" name="shrinkage_percent" step="0.01" class="form-control form-control-solid" placeholder="{{ __('projectx::lang.expected_shrinkage_placeholder') }}" value="{{ old('shrinkage_percent', $fabric->shrinkage_percent ?? '') }}" />
                                    @error('shrinkage_percent')
                                        <div class="text-danger fs-7 mt-2">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="row mb-8">
                                <label class="col-lg-3 col-form-label">{{ __('projectx::lang.usable_width_inch') }}</label>
                                <div class="col-lg-9">
                                    <input type="number" name="usable_width_inch" step="0.01" class="form-control form-control-solid" placeholder="{{ __('projectx::lang.usable_width_inch_placeholder') }}" value="{{ old('usable_width_inch', $fabric->usable_width_inch ?? '') }}" />
                                    @error('usable_width_inch')
                                        <div class="text-danger fs-7 mt-2">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        {{-- Tab 6: Pricing & Commercial Terms --}}
                        <div class="tab-pane fade" id="kt_fabric_tab_pricing_commercial" role="tabpanel">
                            <div class="row mb-8">
                                <label class="col-lg-3 col-form-label">{{ __('projectx::lang.purchase_price') }}</label>
                                <div class="col-lg-9">
                                    <input type="number" name="purchase_price" step="0.01" class="form-control form-control-solid" placeholder="{{ __('projectx::lang.purchase_price') }}" value="{{ old('purchase_price', $fabric->purchase_price ?? '') }}" />
                                    @error('purchase_price')
                                        <div class="text-danger fs-7 mt-2">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="row mb-8">
                                <label class="col-lg-3 col-form-label">{{ __('projectx::lang.sale_price') }}</label>
                                <div class="col-lg-9">
                                    <input type="number" name="sale_price" step="0.01" class="form-control form-control-solid" placeholder="{{ __('projectx::lang.sale_price') }}" value="{{ old('sale_price', $fabric->sale_price ?? '') }}" />
                                    @error('sale_price')
                                        <div class="text-danger fs-7 mt-2">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="row mb-8">
                                <label class="col-lg-3 col-form-label">{{ __('projectx::lang.price_per_meter') }}</label>
                                <div class="col-lg-9">
                                    <input type="number" name="price_per_meter" step="0.01" class="form-control form-control-solid" placeholder="{{ __('projectx::lang.price_per_meter_placeholder') }}" value="{{ old('price_per_meter', $fabric->price_per_meter ?? '') }}" />
                                    @error('price_per_meter')
                                        <div class="text-danger fs-7 mt-2">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="row mb-8">
                                <label class="col-lg-3 col-form-label">{{ __('projectx::lang.currency_label') }}</label>
                                <div class="col-lg-9">
                                    <input type="text" name="currency" class="form-control form-control-solid" placeholder="{{ __('projectx::lang.currency_placeholder') }}" value="{{ old('currency', $fabric->currency ?? '') }}" />
                                    @error('currency')
                                        <div class="text-danger fs-7 mt-2">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="row mb-8">
                                <label class="col-lg-3 col-form-label">{{ __('projectx::lang.minimum_order_quantity') }}</label>
                                <div class="col-lg-9">
                                    <input type="number" name="minimum_order_quantity" step="1" class="form-control form-control-solid" placeholder="{{ __('projectx::lang.moq_meters_placeholder') }}" value="{{ old('minimum_order_quantity', $fabric->minimum_order_quantity ?? '') }}" />
                                    @error('minimum_order_quantity')
                                        <div class="text-danger fs-7 mt-2">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="row mb-8">
                                <label class="col-lg-3 col-form-label">{{ __('projectx::lang.payment_terms_label') }}</label>
                                <div class="col-lg-9">
                                    <input type="text" name="payment_terms" class="form-control form-control-solid" placeholder="{{ __('projectx::lang.payment_terms_placeholder') }}" value="{{ old('payment_terms', $fabric->payment_terms ?? '') }}" />
                                    @error('payment_terms')
                                        <div class="text-danger fs-7 mt-2">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        {{-- Tab 7: Lead Time & Logistics --}}
                        <div class="tab-pane fade" id="kt_fabric_tab_lead_time_logistics" role="tabpanel">
                            <div class="row mb-8">
                                <label class="col-lg-3 col-form-label">{{ __('projectx::lang.sample_lead_time_days') }}</label>
                                <div class="col-lg-9">
                                    <input type="number" name="sample_lead_time_days" step="1" class="form-control form-control-solid" placeholder="{{ __('projectx::lang.sample_lead_time_placeholder') }}" value="{{ old('sample_lead_time_days', $fabric->sample_lead_time_days ?? '') }}" />
                                    @error('sample_lead_time_days')
                                        <div class="text-danger fs-7 mt-2">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="row mb-8">
                                <label class="col-lg-3 col-form-label">{{ __('projectx::lang.bulk_lead_time_days') }}</label>
                                <div class="col-lg-9">
                                    <input type="number" name="bulk_lead_time_days" step="1" class="form-control form-control-solid" placeholder="{{ __('projectx::lang.bulk_lead_time_placeholder') }}" value="{{ old('bulk_lead_time_days', $fabric->bulk_lead_time_days ?? '') }}" />
                                    @error('bulk_lead_time_days')
                                        <div class="text-danger fs-7 mt-2">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="row mb-8">
                                <label class="col-lg-3 col-form-label">{{ __('projectx::lang.shipment_mode') }}</label>
                                <div class="col-lg-9">
                                    <select name="shipment_mode" class="form-select form-select-solid">
                                        <option value="">{{ __('projectx::lang.select_shipment_mode') }}</option>
                                        <option value="Air" {{ old('shipment_mode', $fabric->shipment_mode ?? '') == 'Air' ? 'selected' : '' }}>{{ __('projectx::lang.air') }}</option>
                                        <option value="Sea" {{ old('shipment_mode', $fabric->shipment_mode ?? '') == 'Sea' ? 'selected' : '' }}>{{ __('projectx::lang.sea') }}</option>
                                        <option value="Land" {{ old('shipment_mode', $fabric->shipment_mode ?? '') == 'Land' ? 'selected' : '' }}>{{ __('projectx::lang.land') }}</option>
                                        <option value="Multimodal" {{ old('shipment_mode', $fabric->shipment_mode ?? '') == 'Multimodal' ? 'selected' : '' }}>{{ __('projectx::lang.multimodal') }}</option>
                                    </select>
                                    @error('shipment_mode')
                                        <div class="text-danger fs-7 mt-2">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="row mb-8">
                                <label class="col-lg-3 col-form-label">{{ __('projectx::lang.port_of_loading') }}</label>
                                <div class="col-lg-9">
                                    <input type="text" name="port_of_loading" class="form-control form-control-solid" placeholder="{{ __('projectx::lang.port_of_loading_placeholder') }}" value="{{ old('port_of_loading', $fabric->port_of_loading ?? '') }}" />
                                    @error('port_of_loading')
                                        <div class="text-danger fs-7 mt-2">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        {{-- Tab 8: Quality, Compliance & Attachments --}}
                        <div class="tab-pane fade" id="kt_fabric_tab_quality_compliance" role="tabpanel">
                            <div class="row mb-8">
                                <label class="col-lg-3 col-form-label">{{ __('projectx::lang.color_fastness') }}</label>
                                <div class="col-lg-9">
                                    <input type="text" name="color_fastness" list="color_fastness_list" class="form-control form-control-solid" placeholder="{{ __('projectx::lang.color_fastness_placeholder') }}" value="{{ old('color_fastness', $fabric->color_fastness ?? '') }}" />
                                    <datalist id="color_fastness_list">
                                        <option value="Wash: 3-4"></option>
                                        <option value="Wash: 4"></option>
                                        <option value="Rub(dry): 4"></option>
                                        <option value="Rub(wet): 3-4"></option>
                                        <option value="Light: 4"></option>
                                    </datalist>
                                    @error('color_fastness')
                                        <div class="text-danger fs-7 mt-2">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="row mb-8">
                                <label class="col-lg-3 col-form-label">{{ __('projectx::lang.abrasion_resistance') }}</label>
                                <div class="col-lg-9">
                                    <input type="text" name="abrasion_resistance" list="abrasion_list" class="form-control form-control-solid" placeholder="{{ __('projectx::lang.abrasion_resistance_placeholder') }}" value="{{ old('abrasion_resistance', $fabric->abrasion_resistance ?? '') }}" />
                                    <datalist id="abrasion_list">
                                        <option value="Martindale 10,000"></option>
                                        <option value="Martindale 20,000"></option>
                                        <option value="Martindale 50,000"></option>
                                        <option value="Martindale 80,000"></option>
                                        <option value="Wyzenbeek 15,000"></option>
                                        <option value="Wyzenbeek 30,000"></option>
                                        <option value="Wyzenbeek 50,000"></option>
                                    </datalist>
                                    @error('abrasion_resistance')
                                        <div class="text-danger fs-7 mt-2">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="row mb-8">
                                <label class="col-lg-3 col-form-label">{{ __('projectx::lang.handfeel_drape') }}</label>
                                <div class="col-lg-9">
                                    <input type="text" name="handfeel_drape" list="handfeel_list" class="form-control form-control-solid" placeholder="{{ __('projectx::lang.handfeel_drape_placeholder') }}" value="{{ old('handfeel_drape', $fabric->handfeel_drape ?? '') }}" />
                                    <datalist id="handfeel_list">
                                        <option value="Soft"></option>
                                        <option value="Crisp"></option>
                                        <option value="Smooth"></option>
                                        <option value="Silky"></option>
                                        <option value="Heavy drape"></option>
                                        <option value="Fluid drape"></option>
                                        <option value="Stiff"></option>
                                        <option value="Structured"></option>
                                        <option value="Stretchy"></option>
                                    </datalist>
                                    @error('handfeel_drape')
                                        <div class="text-danger fs-7 mt-2">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="row mb-8">
                                <label class="col-lg-3 col-form-label">{{ __('projectx::lang.finish_treatments') }}</label>
                                <div class="col-lg-9">
                                    <textarea id="finish_treatments" name="finish_treatments" class="form-control form-control-solid" rows="3" placeholder="{{ __('projectx::lang.finish_treatments_placeholder') }}">{{ old('finish_treatments', $fabric->finish_treatments ?? '') }}</textarea>
                                    <div class="form-text">{{ __('projectx::lang.type_freely_or_click') }}</div>
                                    <div class="d-flex flex-wrap mt-3">
                                        <button type="button" class="btn btn-sm btn-light me-2 mb-2" onclick="appendToField('finish_treatments', 'Anti-pill')">Anti-pill</button>
                                        <button type="button" class="btn btn-sm btn-light me-2 mb-2" onclick="appendToField('finish_treatments', 'Brushed')">Brushed</button>
                                        <button type="button" class="btn btn-sm btn-light me-2 mb-2" onclick="appendToField('finish_treatments', 'Pre-shrunk')">Pre-shrunk</button>
                                        <button type="button" class="btn btn-sm btn-light me-2 mb-2" onclick="appendToField('finish_treatments', 'Mercerized')">Mercerized</button>
                                        <button type="button" class="btn btn-sm btn-light me-2 mb-2" onclick="appendToField('finish_treatments', 'Enzyme wash')">Enzyme wash</button>
                                        <button type="button" class="btn btn-sm btn-light me-2 mb-2" onclick="appendToField('finish_treatments', 'Peach finish')">Peach finish</button>
                                        <button type="button" class="btn btn-sm btn-light me-2 mb-2" onclick="appendToField('finish_treatments', 'Waterproof / DWR')">Waterproof / DWR</button>
                                        <button type="button" class="btn btn-sm btn-light me-2 mb-2" onclick="appendToField('finish_treatments', 'Moisture wicking')">Moisture wicking</button>
                                        <button type="button" class="btn btn-sm btn-light me-2 mb-2" onclick="appendToField('finish_treatments', 'Anti-odor')">Anti-odor</button>
                                        <button type="button" class="btn btn-sm btn-light me-2 mb-2" onclick="appendToField('finish_treatments', 'UV protection')">UV protection</button>
                                        <button type="button" class="btn btn-sm btn-light me-2 mb-2" onclick="appendToField('finish_treatments', 'Flame retardant')">Flame retardant</button>
                                    </div>
                                    @error('finish_treatments')
                                        <div class="text-danger fs-7 mt-2">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="row mb-8">
                                <label class="col-lg-3 col-form-label">{{ __('projectx::lang.certifications') }}</label>
                                <div class="col-lg-9">
                                    <textarea id="certifications" name="certifications" class="form-control form-control-solid" rows="3" placeholder="{{ __('projectx::lang.certifications_placeholder') }}">{{ old('certifications', $fabric->certifications ?? '') }}</textarea>
                                    <div class="form-text">{{ __('projectx::lang.type_freely_or_click') }}</div>
                                    <div class="d-flex flex-wrap mt-3">
                                        <button type="button" class="btn btn-sm btn-light me-2 mb-2" onclick="appendToField('certifications', 'OEKO-TEX Standard 100')">OEKO-TEX Standard 100</button>
                                        <button type="button" class="btn btn-sm btn-light me-2 mb-2" onclick="appendToField('certifications', 'GOTS')">GOTS</button>
                                        <button type="button" class="btn btn-sm btn-light me-2 mb-2" onclick="appendToField('certifications', 'REACH')">REACH</button>
                                        <button type="button" class="btn btn-sm btn-light me-2 mb-2" onclick="appendToField('certifications', 'Bluesign')">Bluesign</button>
                                        <button type="button" class="btn btn-sm btn-light me-2 mb-2" onclick="appendToField('certifications', 'SGS Tested')">SGS Tested</button>
                                        <button type="button" class="btn btn-sm btn-light me-2 mb-2" onclick="appendToField('certifications', 'ISO 9001')">ISO 9001</button>
                                        <button type="button" class="btn btn-sm btn-light me-2 mb-2" onclick="appendToField('certifications', 'ISO 14001')">ISO 14001</button>
                                        <button type="button" class="btn btn-sm btn-light me-2 mb-2" onclick="appendToField('certifications', 'BCI (Better Cotton)')">BCI (Better Cotton)</button>
                                        <button type="button" class="btn btn-sm btn-light me-2 mb-2" onclick="appendToField('certifications', 'FSC (for viscose source)')">FSC (for viscose source)</button>
                                        <button type="button" class="btn btn-sm btn-light me-2 mb-2" onclick="appendToField('certifications', 'RCS / GRS')">RCS / GRS</button>
                                    </div>
                                    @error('certifications')
                                        <div class="text-danger fs-7 mt-2">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="row mb-8">
                                <label class="col-lg-3 col-form-label">{{ __('projectx::lang.attachments') }}</label>
                                <div class="col-lg-9">
                                    <input type="file" name="attachments[]" class="form-control form-control-solid" multiple accept=".pdf,.png,.jpg,.jpeg" />
                                    @error('attachments')
                                        <div class="text-danger fs-7 mt-2">{{ $message }}</div>
                                    @enderror
                                    @error('attachments.*')
                                        <div class="text-danger fs-7 mt-2">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="row mb-8">
                                <label class="col-lg-3 col-form-label">{{ __('projectx::lang.due_date') }}</label>
                                <div class="col-lg-9">
                                    <input type="text" name="due_date" class="form-control form-control-solid" placeholder="{{ __('projectx::lang.pick_a_date') }}" value="{{ old('due_date', $fabric->due_date ? $fabric->due_date->format('Y-m-d') : '') }}" />
                                    @error('due_date')
                                        <div class="text-danger fs-7 mt-2">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="row mb-8">
                                <label class="col-lg-3 col-form-label">{{ __('projectx::lang.notifications') }}</label>
                                <div class="col-lg-9">
                                    <div class="d-flex fw-semibold h-100 align-items-center">
                                        <div class="form-check form-check-custom form-check-solid me-10">
                                            <input class="form-check-input" type="checkbox" name="notification_email" value="1" id="email" {{ old('notification_email', (int) ($fabric->notification_email ?? 1)) ? 'checked' : '' }} />
                                            <label class="form-check-label" for="email">{{ __('projectx::lang.email') }}</label>
                                        </div>
                                        <div class="form-check form-check-custom form-check-solid">
                                            <input class="form-check-input" type="checkbox" name="notification_phone" value="1" id="phone" {{ old('notification_phone', (int) ($fabric->notification_phone ?? 0)) ? 'checked' : '' }} />
                                            <label class="form-check-label" for="phone">{{ __('projectx::lang.phone') }}</label>
                                        </div>
                                    </div>
                                    @error('notification_email')
                                        <div class="text-danger fs-7 mt-2">{{ $message }}</div>
                                    @enderror
                                    @error('notification_phone')
                                        <div class="text-danger fs-7 mt-2">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="row">
                                <label class="col-lg-3 col-form-label">{{ __('projectx::lang.status') }}</label>
                                <div class="col-lg-9">
                                    <div class="form-check form-switch form-check-custom form-check-solid">
                                        <input type="hidden" name="status" value="{{ old('status', ($fabric->status ?? '') === 'active' ? 'draft' : ($fabric->status ?? 'draft')) }}" />
                                        <input class="form-check-input" type="checkbox" name="status" value="active" id="status" {{ old('status', $fabric->status ?? 'draft') === 'active' ? 'checked' : '' }} />
                                        <label class="form-check-label fw-semibold text-muted" for="status">{{ __('projectx::lang.active') }}</label>
                                    </div>
                                    @error('status')
                                        <div class="text-danger fs-7 mt-2">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer d-flex justify-content-end py-6 px-9">
            <button type="reset" class="btn btn-light btn-active-light-primary me-2">{{ __('projectx::lang.discard') }}</button>
            <button type="submit" class="btn btn-primary">{{ __('projectx::lang.save_changes') }}</button>
        </div>
    </form>
</div>
@endsection

@section('page_javascript')
<script src="{{ asset('modules/projectx/js/custom/apps/projects/settings/settings.js') }}"></script>
<script>
    function appendToField(fieldId, text) {
        var field = document.getElementById(fieldId);

        if (!field) {
            return;
        }

        var trimmedValue = field.value.trim();

        if (trimmedValue === '') {
            field.value = text;
        } else {
            var separator = trimmedValue.indexOf("\n") !== -1 ? "\n" : ", ";
            field.value = trimmedValue + separator + text;
        }

        field.dispatchEvent(new Event('input', { bubbles: true }));
        field.focus();
    }
</script>
@endsection
