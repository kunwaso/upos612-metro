<div class="card card-flush">
    <div class="card-header">
        <div class="card-title flex-column">
            <h3 class="fw-bold mb-1">{{ __('projectx::lang.fds_edit_form') }}</h3>
            <span class="text-muted fs-7">{{ __('projectx::lang.fds_edit_form_hint') }}</span>
        </div>
    </div>
    <form class="form" method="POST" action="{{ route('projectx.trim_manager.update', ['id' => $trim->id]) }}" enctype="multipart/form-data">
        @csrf
        @method('PATCH')
        <input type="hidden" name="redirect_tab" value="datasheet" />

        <div class="card-body">
            <div class="row g-5 g-xl-10">
                <div class="col-md-4 col-lg-3">
                    <ul class="nav nav-tabs nav-pills flex-row border border-dashed border-gray-300 rounded p-5 flex-md-column me-5 mb-3 mb-md-0 fs-6 min-w-lg-200px" role="tablist">
                        <li class="nav-item w-100 me-0 mb-md-2 bg-gray-100">
                            <a class="nav-link w-100 active btn btn-flex btn-active-light-primary" data-bs-toggle="tab" href="#kt_trim_datasheet_tab_identity" role="tab">
                                <i class="ki-duotone ki-information-5 fs-2 me-3 text-blue-600 text-dark"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                                <span class="d-flex flex-column align-items-start">
                                    <span class="fs-4 fw-bold">{{ __('projectx::lang.fds_identity_reference') }}</span>
                                    <span class="fs-7">{{ __('projectx::lang.trim_name') }}, {{ __('projectx::lang.part_number') }}, {{ __('projectx::lang.supplier') }}</span>
                                </span>
                            </a>
                        </li>
                        <li class="nav-item w-100 me-0 mb-md-2">
                            <a class="nav-link w-100 btn btn-flex btn-active-light-success text-dark" data-bs-toggle="tab" href="#kt_trim_datasheet_tab_specs" role="tab">
                                <i class="ki-duotone ki-setting-3 fs-2 me-3 text-teal-600"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                                <span class="d-flex flex-column align-items-start">
                                    <span class="fs-4 fw-bold">{{ __('projectx::lang.trim_specifications') }}</span>
                                    <span class="fs-7">{{ __('projectx::lang.material') }}, {{ __('projectx::lang.color_value') }}, {{ __('projectx::lang.unit_of_measure') }}</span>
                                </span>
                            </a>
                        </li>
                        <li class="nav-item w-100 me-0 mb-md-2">
                            <a class="nav-link w-100 btn btn-flex btn-active-light-info text-dark" data-bs-toggle="tab" href="#kt_trim_datasheet_tab_commercial" role="tab">
                                <i class="ki-duotone ki-dollar fs-2 me-3 text-amber-600"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                                <span class="d-flex flex-column align-items-start">
                                    <span class="fs-4 fw-bold">{{ __('projectx::lang.trim_commercial') }}</span>
                                    <span class="fs-7">{{ __('projectx::lang.unit_cost') }}, {{ __('projectx::lang.currency_label') }}, {{ __('projectx::lang.lead_time_days') }}</span>
                                </span>
                            </a>
                        </li>
                        <li class="nav-item w-100 me-0 mb-md-2">
                            <a class="nav-link w-100 btn btn-flex btn-active-light-warning text-dark" data-bs-toggle="tab" href="#kt_trim_datasheet_tab_care" role="tab">
                                <i class="ki-duotone ki-heart fs-2 me-3 text-red-600"><span class="path1"></span><span class="path2"></span></i>
                                <span class="d-flex flex-column align-items-start">
                                    <span class="fs-4 fw-bold">{{ __('projectx::lang.care_testing') }}</span>
                                    <span class="fs-7">{{ __('projectx::lang.quality_notes') }}, {{ __('projectx::lang.shrinkage') }}, {{ __('projectx::lang.rust_proof') }}</span>
                                </span>
                            </a>
                        </li>
                        <li class="nav-item w-100 me-0 mb-md-2">
                            <a class="nav-link w-100 btn btn-flex btn-active-light-danger text-dark" data-bs-toggle="tab" href="#kt_trim_datasheet_tab_lifecycle" role="tab">
                                <i class="ki-duotone ki-calendar fs-2 me-3 text-indigo-600"><span class="path1"></span><span class="path2"></span></i>
                                <span class="d-flex flex-column align-items-start">
                                    <span class="fs-4 fw-bold">{{ __('projectx::lang.lifecycle') }}</span>
                                    <span class="fs-7">{{ __('projectx::lang.approved_at') }}, {{ __('projectx::lang.qc_at') }}, {{ __('projectx::lang.qc_notes') }}</span>
                                </span>
                            </a>
                        </li>
                        <li class="nav-item w-100">
                            <a class="nav-link w-100 btn btn-flex btn-active-light-primary text-dark" data-bs-toggle="tab" href="#kt_trim_datasheet_tab_image" role="tab">
                                <i class="ki-duotone ki-picture fs-2 me-3 text-green-600"><span class="path1"></span><span class="path2"></span></i>
                                <span class="d-flex flex-column align-items-start">
                                    <span class="fs-4 fw-bold">{{ __('projectx::lang.trim_image') }}</span>
                                    <span class="fs-7">{{ __('projectx::lang.trim_image') }}, {{ __('projectx::lang.allowed_file_types_max') }}</span>
                                </span>
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="col-md-8 col-lg-9">
                    <div class="tab-content" id="trimDatasheetTabContent">
                        <div class="tab-pane fade show active" id="kt_trim_datasheet_tab_identity" role="tabpanel">
                            <div class="row mb-8">
                                <label class="col-lg-3 col-form-label required">{{ __('projectx::lang.trim_name') }}</label>
                                <div class="col-lg-9">
                                    <input type="text" name="name" class="form-control form-control-solid" value="{{ old('name', $trim->name) }}" />
                                    @error('name')<div class="text-danger fs-7 mt-2">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="row mb-8">
                                <label class="col-lg-3 col-form-label">{{ __('projectx::lang.part_number') }}</label>
                                <div class="col-lg-9">
                                    <input type="text" name="part_number" class="form-control form-control-solid" value="{{ old('part_number', $trim->part_number) }}" />
                                    @error('part_number')<div class="text-danger fs-7 mt-2">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="row mb-8">
                                <label class="col-lg-3 col-form-label">{{ __('projectx::lang.trim_category') }}</label>
                                <div class="col-lg-9">
                                    <select name="trim_category_id" class="form-select form-select-solid" data-control="select2">
                                        <option value="">{{ __('projectx::lang.select_category') }}</option>
                                        @foreach(($categories ?? []) as $category)
                                            <option value="{{ data_get($category, 'id') }}" {{ (string) old('trim_category_id', $trim->trim_category_id) === (string) data_get($category, 'id') ? 'selected' : '' }}>
                                                {{ data_get($category, 'name') }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('trim_category_id')<div class="text-danger fs-7 mt-2">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="row mb-8">
                                <label class="col-lg-3 col-form-label">{{ __('projectx::lang.category_group') }}</label>
                                <div class="col-lg-9">
                                    <input type="text" name="category_group" class="form-control form-control-solid" value="{{ old('category_group', $trim->category_group) }}" />
                                    @error('category_group')<div class="text-danger fs-7 mt-2">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="row mb-8">
                                <label class="col-lg-3 col-form-label">{{ __('projectx::lang.description') }}</label>
                                <div class="col-lg-9">
                                    <textarea name="description" class="form-control form-control-solid" rows="4">{{ old('description', $trim->description) }}</textarea>
                                    @error('description')<div class="text-danger fs-7 mt-2">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="row mb-8">
                                <label class="col-lg-3 col-form-label">{{ __('projectx::lang.status') }}</label>
                                <div class="col-lg-9">
                                    <select name="status" class="form-select form-select-solid" data-control="select2" data-hide-search="true">
                                        @foreach(($statusOptions ?? []) as $statusOption)
                                            <option value="{{ $statusOption['value'] }}" {{ old('status', $trim->status) === $statusOption['value'] ? 'selected' : '' }}>
                                                {{ $statusOption['label'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('status')<div class="text-danger fs-7 mt-2">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="row mb-0">
                                <label class="col-lg-3 col-form-label">{{ __('projectx::lang.supplier') }}</label>
                                <div class="col-lg-9">
                                    <select name="supplier_contact_id" class="form-select form-select-solid" data-control="select2">
                                        <option value="">{{ __('projectx::lang.select_supplier') }}</option>
                                        @foreach(($suppliers ?? []) as $supplierId => $supplierLabel)
                                            <option value="{{ $supplierId }}" {{ (string) old('supplier_contact_id', $trim->supplier_contact_id) === (string) $supplierId ? 'selected' : '' }}>
                                                {{ $supplierLabel }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('supplier_contact_id')<div class="text-danger fs-7 mt-2">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="kt_trim_datasheet_tab_specs" role="tabpanel">
                            <div class="row mb-8">
                                <label class="col-lg-3 col-form-label">{{ __('projectx::lang.material') }}</label>
                                <div class="col-lg-9">
                                    <input type="text" name="material" class="form-control form-control-solid" value="{{ old('material', $trim->material) }}" />
                                    @error('material')<div class="text-danger fs-7 mt-2">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="row mb-8">
                                <label class="col-lg-3 col-form-label">{{ __('projectx::lang.color_value') }}</label>
                                <div class="col-lg-9">
                                    <input type="text" name="color_value" class="form-control form-control-solid" value="{{ old('color_value', $trim->color_value) }}" />
                                    @error('color_value')<div class="text-danger fs-7 mt-2">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="row mb-8">
                                <label class="col-lg-3 col-form-label">{{ __('projectx::lang.size_dimension') }}</label>
                                <div class="col-lg-9">
                                    <input type="text" name="size_dimension" class="form-control form-control-solid" value="{{ old('size_dimension', $trim->size_dimension) }}" />
                                    @error('size_dimension')<div class="text-danger fs-7 mt-2">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="row mb-8">
                                <label class="col-lg-3 col-form-label">{{ __('projectx::lang.unit_of_measure') }}</label>
                                <div class="col-lg-9">
                                    <select name="unit_of_measure" class="form-select form-select-solid" data-control="select2" data-hide-search="true">
                                        @foreach(($uomOptions ?? []) as $uomOption)
                                            <option value="{{ $uomOption['value'] }}" {{ old('unit_of_measure', $trim->unit_of_measure) === $uomOption['value'] ? 'selected' : '' }}>
                                                {{ $uomOption['label'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('unit_of_measure')<div class="text-danger fs-7 mt-2">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="row mb-8">
                                <label class="col-lg-3 col-form-label">{{ __('projectx::lang.placement') }}</label>
                                <div class="col-lg-9">
                                    <input type="text" name="placement" class="form-control form-control-solid" value="{{ old('placement', $trim->placement) }}" />
                                    @error('placement')<div class="text-danger fs-7 mt-2">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="row mb-8">
                                <label class="col-lg-3 col-form-label">{{ __('projectx::lang.quantity_per_garment') }}</label>
                                <div class="col-lg-9">
                                    <input type="number" min="{{ $numberFieldMin }}" step="{{ $quantityFieldStep }}" name="quantity_per_garment" class="form-control form-control-solid" value="{{ old('quantity_per_garment', $trim->quantity_per_garment) }}" />
                                    @error('quantity_per_garment')<div class="text-danger fs-7 mt-2">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="row mb-8">
                                <label class="col-lg-3 col-form-label">{{ __('projectx::lang.label_sub_type') }}</label>
                                <div class="col-lg-9">
                                    <input type="text" name="label_sub_type" class="form-control form-control-solid" value="{{ old('label_sub_type', $trim->label_sub_type) }}" />
                                    @error('label_sub_type')<div class="text-danger fs-7 mt-2">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="row mb-8">
                                <label class="col-lg-3 col-form-label">{{ __('projectx::lang.purpose') }}</label>
                                <div class="col-lg-9">
                                    <input type="text" name="purpose" class="form-control form-control-solid" value="{{ old('purpose', $trim->purpose) }}" />
                                    @error('purpose')<div class="text-danger fs-7 mt-2">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="row mb-8">
                                <label class="col-lg-3 col-form-label">{{ __('projectx::lang.button_ligne') }}</label>
                                <div class="col-lg-9">
                                    <input type="text" name="button_ligne" class="form-control form-control-solid" value="{{ old('button_ligne', $trim->button_ligne) }}" />
                                    @error('button_ligne')<div class="text-danger fs-7 mt-2">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="row mb-8">
                                <label class="col-lg-3 col-form-label">{{ __('projectx::lang.button_holes') }}</label>
                                <div class="col-lg-9">
                                    <input type="text" name="button_holes" class="form-control form-control-solid" value="{{ old('button_holes', $trim->button_holes) }}" />
                                    @error('button_holes')<div class="text-danger fs-7 mt-2">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="row mb-8">
                                <label class="col-lg-3 col-form-label">{{ __('projectx::lang.button_material') }}</label>
                                <div class="col-lg-9">
                                    <input type="text" name="button_material" class="form-control form-control-solid" value="{{ old('button_material', $trim->button_material) }}" />
                                    @error('button_material')<div class="text-danger fs-7 mt-2">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="row mb-8">
                                <label class="col-lg-3 col-form-label">{{ __('projectx::lang.zipper_type') }}</label>
                                <div class="col-lg-9">
                                    <input type="text" name="zipper_type" class="form-control form-control-solid" value="{{ old('zipper_type', $trim->zipper_type) }}" />
                                    @error('zipper_type')<div class="text-danger fs-7 mt-2">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="row mb-8">
                                <label class="col-lg-3 col-form-label">{{ __('projectx::lang.zipper_slider') }}</label>
                                <div class="col-lg-9">
                                    <input type="text" name="zipper_slider" class="form-control form-control-solid" value="{{ old('zipper_slider', $trim->zipper_slider) }}" />
                                    @error('zipper_slider')<div class="text-danger fs-7 mt-2">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="row mb-0">
                                <label class="col-lg-3 col-form-label">{{ __('projectx::lang.interlining_type') }}</label>
                                <div class="col-lg-9">
                                    <input type="text" name="interlining_type" class="form-control form-control-solid" value="{{ old('interlining_type', $trim->interlining_type) }}" />
                                    @error('interlining_type')<div class="text-danger fs-7 mt-2">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="kt_trim_datasheet_tab_commercial" role="tabpanel">
                            <div class="row mb-8">
                                <label class="col-lg-3 col-form-label">{{ __('projectx::lang.unit_cost') }}</label>
                                <div class="col-lg-9">
                                    <div class="input-group">
                                        <span class="input-group-text">{{ $currencySymbol }}</span>
                                        <input type="number" min="{{ $numberFieldMin }}" step="{{ $currencyFieldStep }}" name="unit_cost" class="form-control form-control-solid" value="{{ old('unit_cost', $trim->unit_cost) }}" />
                                    </div>
                                    @error('unit_cost')<div class="text-danger fs-7 mt-2">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="row mb-8">
                                <label class="col-lg-3 col-form-label">{{ __('projectx::lang.currency_label') }}</label>
                                <div class="col-lg-9">
                                    <input type="text" name="currency" class="form-control form-control-solid" value="{{ old('currency', $defaultCurrencyCode) }}" />
                                    @error('currency')<div class="text-danger fs-7 mt-2">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="row mb-0">
                                <label class="col-lg-3 col-form-label">{{ __('projectx::lang.lead_time_days') }}</label>
                                <div class="col-lg-9">
                                    <input type="number" min="{{ $numberFieldMin }}" step="{{ $integerFieldStep }}" name="lead_time_days" class="form-control form-control-solid" value="{{ old('lead_time_days', $trim->lead_time_days) }}" />
                                    @error('lead_time_days')<div class="text-danger fs-7 mt-2">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="kt_trim_datasheet_tab_care" role="tabpanel">
                            <div class="row mb-8">
                                <label class="col-lg-3 col-form-label">{{ __('projectx::lang.care_testing') }}</label>
                                <div class="col-lg-9">
                                    <textarea name="care_testing" class="form-control form-control-solid" rows="4">{{ old('care_testing', $trim->care_testing) }}</textarea>
                                    @error('care_testing')<div class="text-danger fs-7 mt-2">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="row mb-8">
                                <label class="col-lg-3 col-form-label">{{ __('projectx::lang.quality_notes') }}</label>
                                <div class="col-lg-9">
                                    <textarea name="quality_notes" class="form-control form-control-solid" rows="4">{{ old('quality_notes', $trim->quality_notes) }}</textarea>
                                    @error('quality_notes')<div class="text-danger fs-7 mt-2">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="row mb-8">
                                <label class="col-lg-3 col-form-label">{{ __('projectx::lang.shrinkage') }}</label>
                                <div class="col-lg-9">
                                    <input type="text" name="shrinkage" class="form-control form-control-solid" value="{{ old('shrinkage', $trim->shrinkage) }}" />
                                    @error('shrinkage')<div class="text-danger fs-7 mt-2">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="row mb-8">
                                <label class="col-lg-3 col-form-label">{{ __('projectx::lang.rust_proof') }}</label>
                                <div class="col-lg-9">
                                    <input type="text" name="rust_proof" class="form-control form-control-solid" value="{{ old('rust_proof', $trim->rust_proof) }}" />
                                    @error('rust_proof')<div class="text-danger fs-7 mt-2">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="row mb-0">
                                <label class="col-lg-3 col-form-label">{{ __('projectx::lang.comfort_notes') }}</label>
                                <div class="col-lg-9">
                                    <input type="text" name="comfort_notes" class="form-control form-control-solid" value="{{ old('comfort_notes', $trim->comfort_notes) }}" />
                                    @error('comfort_notes')<div class="text-danger fs-7 mt-2">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="kt_trim_datasheet_tab_lifecycle" role="tabpanel">
                            <div class="row mb-8">
                                <label class="col-lg-3 col-form-label">{{ __('projectx::lang.approved_at') }}</label>
                                <div class="col-lg-9">
                                    <input type="text" class="form-control form-control-solid" value="{{ $approvedAtDisplay }}" readonly />
                                </div>
                            </div>
                            <div class="row mb-8">
                                <label class="col-lg-3 col-form-label">{{ __('projectx::lang.qc_at') }}</label>
                                <div class="col-lg-9">
                                    <input type="text" id="kt_projectx_trim_datasheet_qc_at" name="qc_at" class="form-control form-control-solid" value="{{ old('qc_at', $qcAtInputValue) }}" placeholder="{{ __('projectx::lang.qc_at') }}" />
                                    @error('qc_at')<div class="text-danger fs-7 mt-2">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="row mb-0">
                                <label class="col-lg-3 col-form-label">{{ __('projectx::lang.qc_notes') }}</label>
                                <div class="col-lg-9">
                                    <textarea name="qc_notes" class="form-control form-control-solid" rows="4">{{ old('qc_notes', $trim->qc_notes) }}</textarea>
                                    @error('qc_notes')<div class="text-danger fs-7 mt-2">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="kt_trim_datasheet_tab_image" role="tabpanel">
                            <div class="row mb-8">
                                <label class="col-lg-3 col-form-label">{{ __('projectx::lang.trim_image') }}</label>
                                <div class="col-lg-9">
                                    @if(!empty($currentTrimImageUrl))
                                        <div class="border border-gray-300 rounded p-4 mb-4 text-center">
                                            <img src="{{ $currentTrimImageUrl }}" alt="{{ $trim->name ?? __('projectx::lang.trim_name') }}" style="max-width: 100%; max-height: 320px;" />
                                        </div>
                                    @else
                                        <div class="border border-dashed border-gray-300 rounded d-flex align-items-center justify-content-center text-gray-500 mb-4" style="min-height: 220px;">
                                            {{ __('projectx::lang.no_trim_image_uploaded') }}
                                        </div>
                                    @endif

                                    <input type="file" name="image" class="form-control form-control-solid" accept=".png,.jpg,.jpeg,.webp" />
                                    <div class="form-text">{{ __('projectx::lang.allowed_file_types_max') }}</div>
                                    @error('image')<div class="text-danger fs-7 mt-2">{{ $message }}</div>@enderror
                                </div>
                            </div>
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
