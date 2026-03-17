@extends('projectx::layouts.main')

@section('title', __('projectx::lang.fabric_datasheet'))

@section('content')
@include('projectx::fabric_manager._fabric_header')

<div class="row g-5 g-xl-8">
    <div class="col-12">
        <div class="card card-flush">
            <div class="card-header align-items-center">
                <div class="card-title flex-column">
                    <h3 class="fw-bold mb-1">{{ __('projectx::lang.fds_actions') }}</h3>
                    <span class="text-muted fs-7">{{ __('projectx::lang.fds_actions_hint') }}</span>
                </div>
                <div class="card-toolbar d-flex flex-wrap gap-3">
                    <a href="{{ route('projectx.fabric_manager.datasheet.pdf', ['fabric_id' => $fabric->id]) }}" class="btn btn-primary btn-sm">
                        <i class="ki-duotone ki-file-down fs-4 me-1"><span class="path1"></span><span class="path2"></span></i>
                        {{ __('projectx::lang.download_pdf') }}
                    </a>
                    <a href="#projectx_share_settings_card" class="btn btn-light-primary btn-sm">
                        <i class="ki-duotone ki-share fs-4 me-1"><span class="path1"></span><span class="path2"></span></i>
                        {{ __('projectx::lang.share_link') }}
                    </a>
                    @if(!empty($shareSettings['share_url']))
                        <a href="{{ $shareSettings['share_url'] }}" target="_blank" class="btn btn-light btn-sm">
                            <i class="ki-duotone ki-eye fs-4 me-1"><span class="path1"></span><span class="path2"></span></i>
                            {{ __('projectx::lang.open_public_link') }}
                        </a>
                    @endif
                </div>
            </div>
            <div class="card-body pt-0">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-4">
                    <div class="d-flex align-items-center gap-3">
                        <span class="text-gray-600 fw-semibold">{{ __('projectx::lang.current_status') }}:</span>
                        <span class="badge {{ $fabric->badge_class }}">{{ $fabric->status_label }}</span>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        @if($fabric->status === \Modules\ProjectX\Entities\Fabric::STATUS_DRAFT && (auth()->user()->can('projectx.fabric.submit') || auth()->user()->can('product.create')))
                            <form method="POST" action="{{ route('projectx.fabric_manager.submit_for_approval', ['fabric_id' => $fabric->id]) }}">
                                @csrf
                                <button type="submit" class="btn btn-light-primary btn-sm">{{ __('projectx::lang.submit_for_approval') }}</button>
                            </form>
                        @endif

                        @if($fabric->status === \Modules\ProjectX\Entities\Fabric::STATUS_NEEDS_APPROVAL && (auth()->user()->can('projectx.fabric.approve') || auth()->user()->can('product.create')))
                            <form method="POST" action="{{ route('projectx.fabric_manager.approve', ['fabric_id' => $fabric->id]) }}">
                                @csrf
                                <button type="submit" class="btn btn-success btn-sm">{{ __('projectx::lang.approve') }}</button>
                            </form>
                        @endif

                        @if($fabric->status === \Modules\ProjectX\Entities\Fabric::STATUS_NEEDS_APPROVAL && (auth()->user()->can('projectx.fabric.reject') || auth()->user()->can('product.create')))
                            <form method="POST" action="{{ route('projectx.fabric_manager.reject', ['fabric_id' => $fabric->id]) }}">
                                @csrf
                                <button type="submit" class="btn btn-light-danger btn-sm">{{ __('projectx::lang.reject') }}</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12" id="projectx_share_settings_card">
        <!--begin::Accordion-->
        <div class="accordion" id="kt_accordion_share_settings">
            <div class="accordion-item">
                <h2 class="accordion-header" id="kt_accordion_share_settings_header_1">
                    <button class="accordion-button fs-4 fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#kt_accordion_share_settings_body_1" aria-expanded="true" aria-controls="kt_accordion_share_settings_body_1">
                        <i class="ki-duotone ki-lock-3 fs-2 me-3 text-indigo-600">
                            <span class="path1"></span>
                            <span class="path2"></span>
                            <span class="path3"></span>
                        </i>
                        {{ __('projectx::lang.share_settings') }}
                    </button>
                </h2>
                <div id="kt_accordion_share_settings_body_1" class="accordion-collapse collapse show" aria-labelledby="kt_accordion_share_settings_header_1" data-bs-parent="#kt_accordion_share_settings">
                    <div class="accordion-body">
                        <p class="text-muted fs-7 mb-5">{{ __('projectx::lang.share_settings_hint') }}</p>
                        <form method="POST" action="{{ route('projectx.fabric_manager.share_settings.update', ['fabric_id' => $fabric->id]) }}">
                            @csrf
                            @method('PATCH')
                            <div class="row mb-8">
                                <label class="col-lg-3 col-form-label">{{ __('projectx::lang.enable_share_link') }}</label>
                                <div class="col-lg-9">
                                    <div class="form-check form-switch form-check-custom form-check-solid">
                                        <input type="hidden" name="share_enabled" value="0">
                                        <input class="form-check-input" type="checkbox" name="share_enabled" value="1" id="share_enabled" {{ old('share_enabled', $shareSettings['share_enabled']) ? 'checked' : '' }} />
                                        <label class="form-check-label fw-semibold text-gray-700" for="share_enabled">{{ __('projectx::lang.enable_share_link_description') }}</label>
                                    </div>
                                    @error('share_enabled')
                                        <div class="text-danger fs-7 mt-2">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="row mb-8">
                                <label class="col-lg-3 col-form-label">{{ __('projectx::lang.share_url') }}</label>
                                <div class="col-lg-9">
                                    @if(!empty($shareSettings['share_url']))
                                        <div class="input-group">
                                            <input type="text" class="form-control form-control-solid" value="{{ $shareSettings['share_url'] }}" readonly id="projectx_share_url_input" />
                                            <button class="btn btn-light-primary" type="button" data-copy-target="#projectx_share_url_input">{{ __('projectx::lang.copy_link') }}</button>
                                        </div>
                                        <div class="form-text">{{ __('projectx::lang.share_url_note') }}</div>
                                    @else
                                        <div class="text-muted fs-7">{{ __('projectx::lang.share_url_not_available') }}</div>
                                    @endif
                                </div>
                            </div>

                            <div class="row mb-8">
                                <label class="col-lg-3 col-form-label">{{ __('projectx::lang.password_protect') }}</label>
                                <div class="col-lg-9">
                                    <input type="password" name="share_password" class="form-control form-control-solid" autocomplete="new-password" placeholder="{{ __('projectx::lang.password_protect_placeholder') }}" />
                                    <div class="form-text">{{ __('projectx::lang.password_protect_hint') }}</div>
                                    @error('share_password')
                                        <div class="text-danger fs-7 mt-2">{{ $message }}</div>
                                    @enderror
                                    <div class="form-check form-check-custom form-check-solid mt-4">
                                        <input class="form-check-input" type="checkbox" value="1" id="clear_share_password" name="clear_share_password" {{ old('clear_share_password') ? 'checked' : '' }}>
                                        <label class="form-check-label" for="clear_share_password">{{ __('projectx::lang.clear_share_password') }}</label>
                                    </div>
                                </div>
                            </div>

                            <div class="row mb-8">
                                <label class="col-lg-3 col-form-label">{{ __('projectx::lang.share_rate_limit_per_day') }}</label>
                                <div class="col-lg-9">
                                    <input type="number" name="share_rate_limit_per_day" class="form-control form-control-solid" min="1" step="1" value="{{ old('share_rate_limit_per_day', $shareSettings['share_rate_limit_per_day']) }}" />
                                    @error('share_rate_limit_per_day')
                                        <div class="text-danger fs-7 mt-2">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="row mb-8">
                                <label class="col-lg-3 col-form-label">{{ __('projectx::lang.share_expires_at') }}</label>
                                <div class="col-lg-9">
                                    <input type="text" id="kt_projectx_fabric_datasheet_share_expires_at" name="share_expires_at" class="form-control form-control-solid" value="{{ old('share_expires_at', $shareSettings['share_expires_at']) }}" placeholder="{{ __('projectx::lang.share_expires_at') }}" />
                                </div>
                            </div>

                            <div class="row mb-5">
                                <label class="col-lg-3 col-form-label">{{ __('projectx::lang.regenerate_link') }}</label>
                                <div class="col-lg-9">
                                    <div class="form-check form-check-custom form-check-solid">
                                        <input class="form-check-input" type="checkbox" value="1" id="regenerate_share_token" name="regenerate_share_token" {{ old('regenerate_share_token') ? 'checked' : '' }}>
                                        <label class="form-check-label" for="regenerate_share_token">{{ __('projectx::lang.regenerate_link_hint') }}</label>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary">{{ __('projectx::lang.save_share_settings') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <!--end::Accordion-->
    </div>

    <div class="col-12">
        <div class="card card-flush">
            <div class="card-header">
                <div class="card-title flex-column">
                    <h3 class="fw-bold mb-1">{{ __('projectx::lang.fds_edit_form') }}</h3>
                    <span class="text-muted fs-7">{{ __('projectx::lang.fds_edit_form_hint') }}</span>
                </div>
            </div>
            <form class="form" method="POST" action="{{ route('projectx.fabric_manager.settings.update', ['fabric_id' => $fabric->id]) }}">
                @csrf
                <input type="hidden" name="redirect_tab" value="datasheet" />
                <input type="hidden" name="status" value="{{ old('status', $fabric->status) }}" />
                <div class="card-body">
                    <div class="row g-5 g-xl-10">
                        {{-- Left: Rich Content Vertical Tabs nav --}}
                        <div class="col-md-4 col-lg-3">
                            <ul class="nav nav-tabs nav-pills flex-row border border-dashed border-gray-300 rounded p-5 flex-md-column me-5 mb-3 mb-md-0 fs-6 min-w-lg-200px" role="tablist">
                                <li class="nav-item w-100 me-0 mb-md-2 bg-gray-100">
                                    <a class="nav-link w-100 active btn btn-flex btn-active-light-primary" data-bs-toggle="tab" href="#kt_fds_tab_identity" role="tab">
                                        <i class="ki-duotone ki-information-5 fs-2 me-3 text-blue-600 text-dark"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                                        <span class="d-flex flex-column align-items-start">
                                            <span class="fs-4 fw-bold">{{ __('projectx::lang.fds_identity_reference') }}</span>
                                            <span class="fs-7">{{ __('projectx::lang.fabric_name') }}, {{ __('projectx::lang.fabric_sku_label') }}, {{ __('projectx::lang.suppliers') }}</span>
                                        </span>
                                    </a>
                                </li>
                                <li class="nav-item w-100 me-0 mb-md-2">
                                    <a class="nav-link w-100 btn btn-flex btn-active-light-success text-dark" data-bs-toggle="tab" href="#kt_fds_tab_sustainability" role="tab">
                                        <i class="ki-duotone ki-shield-tick fs-2 me-3 text-teal-600"><span class="path1"></span><span class="path2"></span></i>
                                        <span class="d-flex flex-column align-items-start">
                                            <span class="fs-4 fw-bold">{{ __('projectx::lang.fds_sustainability') }}</span>
                                            <span class="fs-7">{{ __('projectx::lang.certifications') }}, {{ __('projectx::lang.performance_claims') }}</span>
                                        </span>
                                    </a>
                                </li>
                                <li class="nav-item w-100 me-0 mb-md-2">
                                    <a class="nav-link w-100 btn btn-flex btn-active-light-info text-dark" data-bs-toggle="tab" href="#kt_fds_tab_construction" role="tab">
                                        <i class="ki-duotone ki-color-swatch fs-2 me-3 text-amber-600"><span class="path1"></span><span class="path2"></span></i>
                                        <span class="d-flex flex-column align-items-start">
                                            <span class="fs-4 fw-bold">{{ __('projectx::lang.fds_construction') }}</span>
                                            <span class="fs-7">{{ __('projectx::lang.composition') }}, {{ __('projectx::lang.weave_pattern') }}, {{ __('projectx::lang.dyeing_technique') }}</span>
                                        </span>
                                    </a>
                                </li>
                                <li class="nav-item w-100 me-0 mb-md-2">
                                    <a class="nav-link w-100 btn btn-flex btn-active-light-warning text-dark" data-bs-toggle="tab" href="#kt_fds_tab_specs" role="tab">
                                        <i class="ki-duotone ki-size fs-2 me-3 text-red-600"><span class="path1"></span><span class="path2"></span></i>
                                        <span class="d-flex flex-column align-items-start">
                                            <span class="fs-4 fw-bold">{{ __('projectx::lang.fds_specs') }}</span>
                                            <span class="fs-7">{{ __('projectx::lang.weight_gsm') }}, {{ __('projectx::lang.width_cm') }}, {{ __('projectx::lang.care_label') }}</span>
                                        </span>
                                    </a>
                                </li>
                                <li class="nav-item w-100 me-0 mb-md-2">
                                    <a class="nav-link w-100 btn btn-flex btn-active-light-danger text-dark " data-bs-toggle="tab" href="#kt_fds_tab_stretch_wool" role="tab">
                                        <i class="ki-duotone ki-graph-3 fs-2 me-3 text-indigo-600"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span></i>
                                        <span class="d-flex flex-column align-items-start">
                                            <span class="fs-4 fw-bold">{{ __('projectx::lang.fds_stretch_wool') }}</span>
                                            <span class="fs-7">{{ __('projectx::lang.elongation') }}, {{ __('projectx::lang.growth') }}, {{ __('projectx::lang.wool_type') }}</span>
                                        </span>
                                    </a>
                                </li>
                                <li class="nav-item w-100">
                                    <a class="nav-link w-100 btn btn-flex btn-active-light-primary text-dark" data-bs-toggle="tab" href="#kt_fds_tab_lead_time_capacity" role="tab">
                                        <i class="ki-duotone ki-delivery fs-2 me-3 text-green-600"><span class="path1"></span><span class="path2"></span></i>
                                        <span class="d-flex flex-column align-items-start">
                                            <span class="fs-4 fw-bold">{{ __('projectx::lang.fds_lead_time_dyeing') }} & {{ __('projectx::lang.fds_capacity') }}</span>
                                            <span class="fs-7">{{ __('projectx::lang.bulk_lead_time_days') }}, {{ __('projectx::lang.fds_pricing') }}, {{ __('projectx::lang.minimum_order_quantity') }}</span>
                                        </span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                        {{-- Right: Tab content --}}
                        <div class="col-md-8 col-lg-9">
                            <div class="tab-content" id="fdsTargetsTabContent">
                                {{-- Tab 1: Identity & Reference --}}
                                <div class="tab-pane fade show active" id="kt_fds_tab_identity" role="tabpanel">
                                    <div class="row mb-8"><label class="col-lg-3 col-form-label required">{{ __('projectx::lang.fabric_name') }}</label><div class="col-lg-9"><input type="text" name="name" class="form-control form-control-solid" value="{{ old('name', $fabric->name) }}" />@error('name')<div class="text-danger fs-7 mt-2">{{ $message }}</div>@enderror</div></div>
                                    <div class="row mb-8"><label class="col-lg-3 col-form-label">{{ __('projectx::lang.fds_date') }}</label><div class="col-lg-9"><input type="text" id="kt_projectx_fabric_datasheet_fds_date" name="fds_date" class="form-control form-control-solid" value="{{ old('fds_date', optional($fabric->fds_date)->format('Y-m-d')) }}" placeholder="{{ __('projectx::lang.fds_date') }}" />@error('fds_date')<div class="text-danger fs-7 mt-2">{{ $message }}</div>@enderror</div></div>
                                    <div class="row mb-8"><label class="col-lg-3 col-form-label">{{ __('projectx::lang.swatch_submit_date') }}</label><div class="col-lg-9"><input type="text" id="kt_projectx_fabric_datasheet_swatch_submit_date" name="swatch_submit_date" class="form-control form-control-solid" value="{{ old('swatch_submit_date', optional($fabric->swatch_submit_date)->format('Y-m-d')) }}" placeholder="{{ __('projectx::lang.swatch_submit_date') }}" />@error('swatch_submit_date')<div class="text-danger fs-7 mt-2">{{ $message }}</div>@enderror</div></div>
                                    <div class="row mb-8"><label class="col-lg-3 col-form-label">{{ __('projectx::lang.fabric_sku_label') }}</label><div class="col-lg-9"><input type="text" name="fabric_sku" class="form-control form-control-solid" value="{{ old('fabric_sku', $fabric->fabric_sku) }}" />@error('fabric_sku')<div class="text-danger fs-7 mt-2">{{ $message }}</div>@enderror</div></div>
                                    <div class="row mb-8"><label class="col-lg-3 col-form-label">{{ __('projectx::lang.season_department') }}</label><div class="col-lg-9"><input type="text" name="season_department" class="form-control form-control-solid" value="{{ old('season_department', $fabric->season_department) }}" />@error('season_department')<div class="text-danger fs-7 mt-2">{{ $message }}</div>@enderror</div></div>
                                    <div class="row mb-8">
                                        <label class="col-lg-3 col-form-label">{{ __('projectx::lang.suppliers') }}</label>
                                        <div class="col-lg-9">
                                            <select name="supplier_contact_ids[]" class="form-select form-select-solid" data-control="select2" data-close-on-select="false" multiple="multiple">
                                                @foreach($suppliers as $supplierId => $supplierLabel)
                                                    <option value="{{ $supplierId }}" {{ in_array((string) $supplierId, $selectedSupplierIdsForForm, true) ? 'selected' : '' }}>{{ $supplierLabel }}</option>
                                                @endforeach
                                            </select>
                                            @error('supplier_contact_ids')<div class="text-danger fs-7 mt-2">{{ $message }}</div>@enderror
                                        </div>
                                    </div>
                                    <div class="row mb-8"><label class="col-lg-3 col-form-label">{{ __('projectx::lang.mill_article_no') }}</label><div class="col-lg-9"><input type="text" name="mill_article_no" class="form-control form-control-solid" value="{{ old('mill_article_no', $fabric->mill_article_no) }}" /></div></div>
                                    <div class="row mb-8"><label class="col-lg-3 col-form-label">{{ __('projectx::lang.pattern_color_name_number') }}</label><div class="col-lg-9"><input type="text" name="pattern_color_name_number" class="form-control form-control-solid" value="{{ old('pattern_color_name_number', $fabric->pattern_color_name_number) }}" /></div></div>
                                    <div class="row mb-8">
                                        <label class="col-lg-3 col-form-label">{{ __('projectx::lang.mill_pattern_color') }}</label>
                                        <div class="col-lg-9">
                                            <div class="d-flex flex-column gap-3" data-role="mill-pattern-color-list" data-placeholder="{{ __('projectx::lang.mill_pattern_color_hint') }}" data-remove-title="{{ __('projectx::lang.remove_item') }}">
                                                @foreach(\Illuminate\Support\Arr::wrap(old('mill_pattern_color', $millPatternColorsForForm)) ?: [''] as $value)
                                                <div class="d-flex align-items-center gap-2" data-role="mill-pattern-color-row">
                                                    <input type="text" name="mill_pattern_color[]" list="mill_pattern_color_list" class="form-control form-control-solid flex-grow-1" value="{{ is_scalar($value) ? $value : '' }}" placeholder="{{ __('projectx::lang.mill_pattern_color_hint') }}" />
                                                    <button type="button" class="btn btn-sm btn-icon btn-light-danger" data-action="remove-mill-pattern-color" title="{{ __('projectx::lang.remove_item') }}">
                                                        <i class="ki-duotone ki-trash fs-5"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span></i>
                                                    </button>
                                                </div>
                                                @endforeach
                                            </div>
                                            <button type="button" class="btn btn-sm btn-light-primary mt-2" data-action="add-mill-pattern-color">
                                                <i class="ki-duotone ki-plus fs-4 me-1"></i>{{ __('projectx::lang.add_another_mill_pattern_color') }}
                                            </button>
                                            <datalist id="mill_pattern_color_list">
                                                @foreach($pantoneItems as $pantoneItem)
                                                    <option value="{{ $pantoneItem['name'] }} ({{ $pantoneItem['code'] }})"></option>
                                                    <option value="{{ $pantoneItem['code'] }}"></option>
                                                @endforeach
                                            </datalist>
                                        </div>
                                    </div>
                                </div>

                                {{-- Tab 2: Sustainability & Claims --}}
                                <div class="tab-pane fade" id="kt_fds_tab_sustainability" role="tabpanel">
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
                                    <div class="row mb-8"><label class="col-lg-3 col-form-label">{{ __('projectx::lang.performance_claims') }}</label><div class="col-lg-9"><input type="text" name="performance_claims" list="performance_claims_list" class="form-control form-control-solid" value="{{ old('performance_claims', $fabric->performance_claims) }}" /><datalist id="performance_claims_list">@foreach(($fdsDatalists['performance_claims'] ?? []) as $option)<option value="{{ $option }}"></option>@endforeach</datalist></div></div>
                                </div>

                                {{-- Tab 3: Construction --}}
                                <div class="tab-pane fade" id="kt_fds_tab_construction" role="tabpanel">
                                    @include('projectx::fabric_manager.partials._construction_fields')
                                    <div class="row mb-8"><label class="col-lg-3 col-form-label">{{ __('projectx::lang.dyeing_technique') }}</label><div class="col-lg-9"><input type="text" name="dyeing_technique" list="dyeing_technique_list" class="form-control form-control-solid" value="{{ old('dyeing_technique', $fabric->dyeing_technique) }}" /><datalist id="dyeing_technique_list">@foreach(($fdsDatalists['dyeing_technique'] ?? []) as $option)<option value="{{ $option }}"></option>@endforeach</datalist></div></div>
                                    <div class="row mb-8"><label class="col-lg-3 col-form-label">{{ __('projectx::lang.submit_type') }}</label><div class="col-lg-9"><input type="text" name="submit_type" list="submit_type_list" class="form-control form-control-solid" value="{{ old('submit_type', $fabric->submit_type) }}" /><datalist id="submit_type_list">@foreach(($fdsDatalists['submit_type'] ?? []) as $option)<option value="{{ $option }}"></option>@endforeach</datalist></div></div>
                                    <div class="row mb-8">
                                        <label class="col-lg-3 col-form-label">{{ __('projectx::lang.composition') }}</label>
                                        <div class="col-lg-9">
                                            <div class="d-flex flex-column">
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
                                                <div class="mt-2">
                                                    <a href="{{ route('projectx.fabric_manager.fabric', ['fabric_id' => $fabric->id]) }}" class="fw-bold text-primary text-hover-primary fs-7">{{ __('projectx::lang.edit_composition') }}</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
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
                                    <div class="row mb-8"><label class="col-lg-3 col-form-label">{{ __('projectx::lang.construction_ypi') }}</label><div class="col-lg-9"><input type="text" name="construction_ypi" list="construction_ypi_list" class="form-control form-control-solid" value="{{ old('construction_ypi', $fabric->construction_ypi) }}" /><datalist id="construction_ypi_list">@foreach(($fdsDatalists['construction_ypi'] ?? []) as $option)<option value="{{ $option }}"></option>@endforeach</datalist></div></div>
                                </div>

                                {{-- Tab 4: Specifications --}}
                                <div class="tab-pane fade" id="kt_fds_tab_specs" role="tabpanel">
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
                                    <div class="row mb-8"><label class="col-lg-3 col-form-label">{{ __('projectx::lang.usable_width_inch') }}</label><div class="col-lg-9"><input type="number" step="{{ $projectxQuantityStep }}" min="{{ $projectxZeroMin }}" name="usable_width_inch" class="form-control form-control-solid" value="{{ old('usable_width_inch', $fabric->usable_width_inch) }}" /></div></div>
                                    <div class="row mb-8"><label class="col-lg-3 col-form-label">{{ __('projectx::lang.fabric_finish') }}</label><div class="col-lg-9"><input type="text" name="fabric_finish" list="fabric_finish_list" class="form-control form-control-solid" value="{{ old('fabric_finish', $fabric->fabric_finish) }}" /><datalist id="fabric_finish_list">@foreach(($fdsDatalists['fabric_finish'] ?? []) as $option)<option value="{{ $option }}"></option>@endforeach</datalist></div></div>
                                    <div class="row mb-8"><label class="col-lg-3 col-form-label">{{ __('projectx::lang.care_label') }}</label><div class="col-lg-9"><textarea name="care_label" class="form-control form-control-solid" rows="3">{{ old('care_label', $fabric->care_label) }}</textarea></div></div>
                                    <div class="row mb-8"><label class="col-lg-3 col-form-label">{{ __('projectx::lang.shrinkage_percent') }}</label><div class="col-lg-9"><input type="number" step="{{ $projectxRateStep }}" min="{{ $projectxZeroMin }}" name="shrinkage_percent" class="form-control form-control-solid" value="{{ old('shrinkage_percent', $fabric->shrinkage_percent) }}" /></div></div>
                                    <div class="row mb-8"><label class="col-lg-3 col-form-label">{{ __('projectx::lang.country_of_origin') }}</label><div class="col-lg-9"><input type="text" name="country_of_origin" list="country_of_origin_list" class="form-control form-control-solid" value="{{ old('country_of_origin', $fabric->country_of_origin) }}" /><datalist id="country_of_origin_list">@foreach(($fdsDatalists['country_of_origin'] ?? []) as $option)<option value="{{ $option }}"></option>@endforeach</datalist></div></div>
                                </div>

                                {{-- Tab 5: Stretch / Wool --}}
                                <div class="tab-pane fade" id="kt_fds_tab_stretch_wool" role="tabpanel">
                                    <div class="row mb-8"><label class="col-lg-3 col-form-label">{{ __('projectx::lang.elongation') }}</label><div class="col-lg-9"><input type="text" name="elongation" list="elongation_list" class="form-control form-control-solid" value="{{ old('elongation', $fabric->elongation) }}" /><datalist id="elongation_list">@foreach(($fdsDatalists['elongation'] ?? []) as $option)<option value="{{ $option }}"></option>@endforeach</datalist></div></div>
                                    <div class="row mb-8"><label class="col-lg-3 col-form-label">{{ __('projectx::lang.growth') }}</label><div class="col-lg-9"><input type="text" name="growth" list="growth_list" class="form-control form-control-solid" value="{{ old('growth', $fabric->growth) }}" /><datalist id="growth_list">@foreach(($fdsDatalists['growth'] ?? []) as $option)<option value="{{ $option }}"></option>@endforeach</datalist></div></div>
                                    <div class="row mb-8"><label class="col-lg-3 col-form-label">{{ __('projectx::lang.recovery') }}</label><div class="col-lg-9"><input type="text" name="recovery" class="form-control form-control-solid" value="{{ old('recovery', $fabric->recovery) }}" /></div></div>
                                    <div class="row mb-8"><label class="col-lg-3 col-form-label">{{ __('projectx::lang.elongation_25_fixed') }}</label><div class="col-lg-9"><input type="text" name="elongation_25_fixed" class="form-control form-control-solid" value="{{ old('elongation_25_fixed', $fabric->elongation_25_fixed) }}" /></div></div>
                                    <div class="row mb-8"><label class="col-lg-3 col-form-label">{{ __('projectx::lang.wool_type') }}</label><div class="col-lg-9"><input type="text" name="wool_type" list="wool_type_list" class="form-control form-control-solid" value="{{ old('wool_type', $fabric->wool_type) }}" /><datalist id="wool_type_list">@foreach(($fdsDatalists['wool_type'] ?? []) as $option)<option value="{{ $option }}"></option>@endforeach</datalist></div></div>
                                    <div class="row mb-8"><label class="col-lg-3 col-form-label">{{ __('projectx::lang.raw_material_origin') }}</label><div class="col-lg-9"><input type="text" name="raw_material_origin" class="form-control form-control-solid" value="{{ old('raw_material_origin', $fabric->raw_material_origin) }}" /></div></div>
                                </div>

                                {{-- Tab 6: Lead Time & Dyeing Type Capacity (includes Pricing) --}}
                                <div class="tab-pane fade" id="kt_fds_tab_lead_time_capacity" role="tabpanel">
                                    <div class="fs-6 fw-bold text-gray-900 mb-4">{{ __('projectx::lang.fds_lead_time_dyeing') }}</div>
                                    <div class="row mb-8"><label class="col-lg-3 col-form-label">{{ __('projectx::lang.bulk_lead_time_days') }}</label><div class="col-lg-9"><input type="number" step="1" min="0" name="bulk_lead_time_days" class="form-control form-control-solid" value="{{ old('bulk_lead_time_days', $fabric->bulk_lead_time_days) }}" /></div></div>
                                    <div class="row mb-8"><label class="col-lg-3 col-form-label">{{ __('projectx::lang.dyeing_type') }}</label><div class="col-lg-9"><input type="text" name="dyeing_type" list="dyeing_type_list" class="form-control form-control-solid" value="{{ old('dyeing_type', $fabric->dyeing_type) }}" /><datalist id="dyeing_type_list">@foreach(($fdsDatalists['dyeing_type'] ?? []) as $option)<option value="{{ $option }}"></option>@endforeach</datalist></div></div>
                                    <div class="fs-6 fw-bold text-gray-900 mb-4 mt-6">{{ __('projectx::lang.fds_capacity') }}</div>
                                    <div class="row mb-8"><label class="col-lg-3 col-form-label">{{ __('projectx::lang.minimum_order_quantity') }}</label><div class="col-lg-9"><input type="number" step="{{ $projectxQuantityStep }}" min="{{ $projectxZeroMin }}" name="minimum_order_quantity" class="form-control form-control-solid" value="{{ old('minimum_order_quantity', $fabric->minimum_order_quantity) }}" /></div></div>
                                    <div class="row mb-8"><label class="col-lg-3 col-form-label">{{ __('projectx::lang.minimum_color_quantity') }}</label><div class="col-lg-9"><input type="number" step="{{ $projectxQuantityStep }}" min="{{ $projectxZeroMin }}" name="minimum_color_quantity" class="form-control form-control-solid" value="{{ old('minimum_color_quantity', $fabric->minimum_color_quantity) }}" /></div></div>
                                    <div class="row mb-8"><label class="col-lg-3 col-form-label">{{ __('projectx::lang.monthly_capacity') }}</label><div class="col-lg-9"><input type="number" step="{{ $projectxQuantityStep }}" min="{{ $projectxZeroMin }}" name="monthly_capacity" class="form-control form-control-solid" value="{{ old('monthly_capacity', $fabric->monthly_capacity) }}" /></div></div>
                                    <div class="fs-6 fw-bold text-gray-900 mb-4 mt-6">{{ __('projectx::lang.fds_pricing') }}</div>
                                    <div class="row mb-8"><label class="col-lg-3 col-form-label">{{ __('projectx::lang.price_500_yds') }}</label><div class="col-lg-9"><input type="number" step="{{ $projectxCurrencyStep }}" min="{{ $projectxZeroMin }}" name="price_500_yds" class="form-control form-control-solid" value="{{ old('price_500_yds', $fabric->price_500_yds) }}" /></div></div>
                                    <div class="row mb-8"><label class="col-lg-3 col-form-label">{{ __('projectx::lang.price_3k') }}</label><div class="col-lg-9"><input type="number" step="{{ $projectxCurrencyStep }}" min="{{ $projectxZeroMin }}" name="price_3k" class="form-control form-control-solid" value="{{ old('price_3k', $fabric->price_3k) }}" /></div></div>
                                    <div class="row mb-8"><label class="col-lg-3 col-form-label">{{ __('projectx::lang.price_10k') }}</label><div class="col-lg-9"><input type="number" step="{{ $projectxCurrencyStep }}" min="{{ $projectxZeroMin }}" name="price_10k" class="form-control form-control-solid" value="{{ old('price_10k', $fabric->price_10k) }}" /></div></div>
                                    <div class="row mb-8"><label class="col-lg-3 col-form-label">{{ __('projectx::lang.price_25k') }}</label><div class="col-lg-9"><input type="number" step="{{ $projectxCurrencyStep }}" min="{{ $projectxZeroMin }}" name="price_25k" class="form-control form-control-solid" value="{{ old('price_25k', $fabric->price_25k) }}" /></div></div>
                                    <div class="row mb-8"><label class="col-lg-3 col-form-label">{{ __('projectx::lang.price_50k_plus') }}</label><div class="col-lg-9"><input type="number" step="{{ $projectxCurrencyStep }}" min="{{ $projectxZeroMin }}" name="price_50k_plus" class="form-control form-control-solid" value="{{ old('price_50k_plus', $fabric->price_50k_plus) }}" /></div></div>
                                    <div class="row mb-8"><label class="col-lg-3 col-form-label">{{ __('projectx::lang.currency_label') }}</label><div class="col-lg-9"><input type="text" name="currency" class="form-control form-control-solid" value="{{ old('currency', $fabric->currency) }}" /></div></div>
                                    <div class="row mb-0"><label class="col-lg-3 col-form-label">{{ __('projectx::lang.fds_season') }}</label><div class="col-lg-9"><input type="text" name="fds_season" class="form-control form-control-solid" value="{{ old('fds_season', $fabric->fds_season) }}" /></div></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-footer d-flex justify-content-end py-6 px-9">
                    <button type="submit" class="btn btn-primary">{{ __('projectx::lang.save_changes') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('page_javascript')
<script>
    function appendToField(fieldId, text) {
        var field = document.getElementById(fieldId);
        if (!field) { return; }
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

    (function () {
        var initFlatpickr = function (element, config) {
            if (!element) {
                return;
            }

            if (typeof window.flatpickr === 'function') {
                window.flatpickr(element, config);
                return;
            }

            if (window.jQuery && window.jQuery.fn && typeof window.jQuery.fn.flatpickr === 'function') {
                window.jQuery(element).flatpickr(config);
            }
        };

        initFlatpickr(document.getElementById('kt_projectx_fabric_datasheet_share_expires_at'), {
            enableTime: true,
            time_24hr: true,
            altInput: true,
            altFormat: 'd M, Y H:i',
            dateFormat: 'Y-m-d\\TH:i',
            allowInput: false
        });
        initFlatpickr(document.getElementById('kt_projectx_fabric_datasheet_fds_date'), {
            altInput: true,
            altFormat: 'd M, Y',
            dateFormat: 'Y-m-d',
            allowInput: false
        });
        initFlatpickr(document.getElementById('kt_projectx_fabric_datasheet_swatch_submit_date'), {
            altInput: true,
            altFormat: 'd M, Y',
            dateFormat: 'Y-m-d',
            allowInput: false
        });

        document.querySelectorAll('[data-copy-target]').forEach(function (button) {
            button.addEventListener('click', function () {
                var input = document.querySelector(button.getAttribute('data-copy-target'));
                if (!input) {
                    return;
                }
                if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
                    navigator.clipboard.writeText(input.value || '');
                } else {
                    input.select();
                    document.execCommand('copy');
                    input.blur();
                }
            });
        });

        var listEl = document.querySelector('[data-role="mill-pattern-color-list"]');
        if (listEl) {
            listEl.addEventListener('click', function (e) {
                var btn = e.target.closest('[data-action="remove-mill-pattern-color"]');
                if (btn) {
                    e.preventDefault();
                    var row = btn.closest('[data-role="mill-pattern-color-row"]');
                    if (row && listEl.querySelectorAll('[data-role="mill-pattern-color-row"]').length > 1) {
                        row.remove();
                    }
                }
            });

            document.querySelector('[data-action="add-mill-pattern-color"]')?.addEventListener('click', function () {
                var placeholder = listEl.getAttribute('data-placeholder') || '';
                var removeTitle = listEl.getAttribute('data-remove-title') || '';
                var row = document.createElement('div');
                row.className = 'd-flex align-items-center gap-2';
                row.setAttribute('data-role', 'mill-pattern-color-row');
                row.innerHTML = '<input type="text" name="mill_pattern_color[]" list="mill_pattern_color_list" class="form-control form-control-solid flex-grow-1" placeholder="' + placeholder.replace(/"/g, '&quot;') + '" />' +
                    '<button type="button" class="btn btn-sm btn-icon btn-light-danger" data-action="remove-mill-pattern-color" title="' + removeTitle.replace(/"/g, '&quot;') + '">' +
                    '<i class="ki-duotone ki-trash fs-5"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span></i></button>';
                listEl.appendChild(row);
            });
        }
    })();
</script>
@endsection
