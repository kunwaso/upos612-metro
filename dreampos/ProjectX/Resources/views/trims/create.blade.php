@extends('projectx::layouts.main')

@section('title', __('projectx::lang.create_trim'))

@section('content')
    <div class="d-flex flex-wrap flex-stack mb-6">
        <h2 class="fs-2 fw-semibold">{{ __('projectx::lang.create_trim') }}</h2>
        <a href="{{ Route::has('projectx.trim_manager.list') ? route('projectx.trim_manager.list') : '#' }}" class="btn btn-sm btn-light">
            <i class="ki-duotone ki-arrow-left fs-4 me-1"><span class="path1"></span><span class="path2"></span></i>
            {{ __('projectx::lang.trims_and_accessories') }}
        </a>
    </div>

    @if(session('status'))
        <div class="alert alert-{{ session('status.success') ? 'success' : 'danger' }} alert-dismissible fade show mb-5" role="alert">
            {{ session('status.msg') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('projectx::lang.close') }}"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <h3 class="fw-bold m-0">{{ __('projectx::lang.create_trim') }}</h3>
            </div>
        </div>

        <form class="form" method="POST" action="{{ Route::has('projectx.trim_manager.store') ? route('projectx.trim_manager.store') : '#' }}" enctype="multipart/form-data">
            @csrf

            <div class="card-body">
                @if($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="row mb-8">
                    <label class="col-lg-3 col-form-label required">{{ __('projectx::lang.trim_name') }}</label>
                    <div class="col-lg-9">
                        <input type="text" name="name" class="form-control form-control-solid" value="{{ old('name') }}" required />
                    </div>
                </div>

                <div class="row mb-8">
                    <label class="col-lg-3 col-form-label">{{ __('projectx::lang.part_number') }}</label>
                    <div class="col-lg-9">
                        <input type="text" name="part_number" class="form-control form-control-solid" value="{{ old('part_number') }}" />
                    </div>
                </div>

                <div class="row mb-8">
                    <label class="col-lg-3 col-form-label">{{ __('projectx::lang.trim_category') }}</label>
                    <div class="col-lg-9">
                        <div class="d-flex gap-3 align-items-start">
                            <div class="flex-grow-1">
                                <select name="trim_category_id" id="trim_category_select" class="form-select form-select-solid" data-control="select2">
                                    <option value="">{{ __('projectx::lang.select_category') }}</option>
                                    @foreach(($categories ?? []) as $categoryKey => $categoryItem)
                                        <option value="{{ data_get($categoryItem, 'id', $categoryKey) }}" {{ (string) old('trim_category_id') === (string) data_get($categoryItem, 'id', $categoryKey) ? 'selected' : '' }}>
                                            {{ data_get($categoryItem, 'name', is_scalar($categoryItem) ? $categoryItem : $categoryKey) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            @if(auth()->user()->can('projectx.trim.create') || auth()->user()->can('projectx.trim.delete'))
                                <button type="button" class="btn btn-light-primary" data-bs-toggle="modal" data-bs-target="#projectx_trim_category_modal">
                                    {{ __('projectx::lang.manage_trim_categories') }}
                                </button>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="row mb-8">
                    <label class="col-lg-3 col-form-label">{{ __('projectx::lang.description') }}</label>
                    <div class="col-lg-9">
                        <textarea name="description" class="form-control form-control-solid" rows="3">{{ old('description') }}</textarea>
                    </div>
                </div>

                <div class="separator separator-dashed my-10"></div>

                <div class="row mb-8">
                    <label class="col-lg-3 col-form-label">{{ __('projectx::lang.material') }}</label>
                    <div class="col-lg-9">
                        <input type="text" name="material" class="form-control form-control-solid" value="{{ old('material') }}" />
                    </div>
                </div>

                <div class="row mb-8">
                    <label class="col-lg-3 col-form-label">{{ __('projectx::lang.color_value') }}</label>
                    <div class="col-lg-9">
                        <input type="text" name="color_value" class="form-control form-control-solid" value="{{ old('color_value') }}" />
                    </div>
                </div>

                <div class="row mb-8">
                    <label class="col-lg-3 col-form-label">{{ __('projectx::lang.size_dimension') }}</label>
                    <div class="col-lg-9">
                        <input type="text" name="size_dimension" class="form-control form-control-solid" value="{{ old('size_dimension') }}" />
                    </div>
                </div>

                <div class="row mb-8">
                    <label class="col-lg-3 col-form-label">{{ __('projectx::lang.unit_of_measure') }}</label>
                    <div class="col-lg-9">
                        <select name="unit_of_measure" class="form-select form-select-solid" data-control="select2" data-hide-search="true">
                            <option value="pcs" {{ old('unit_of_measure', 'pcs') === 'pcs' ? 'selected' : '' }}>{{ __('projectx::lang.uom_pcs') }}</option>
                            <option value="cm" {{ old('unit_of_measure') === 'cm' ? 'selected' : '' }}>{{ __('projectx::lang.uom_cm') }}</option>
                            <option value="inches" {{ old('unit_of_measure') === 'inches' ? 'selected' : '' }}>{{ __('projectx::lang.uom_inches') }}</option>
                            <option value="yards" {{ old('unit_of_measure') === 'yards' ? 'selected' : '' }}>{{ __('projectx::lang.uom_yards') }}</option>
                            <option value="sets" {{ old('unit_of_measure') === 'sets' ? 'selected' : '' }}>{{ __('projectx::lang.uom_sets') }}</option>
                        </select>
                    </div>
                </div>

                <div class="row mb-8">
                    <label class="col-lg-3 col-form-label">{{ __('projectx::lang.placement') }}</label>
                    <div class="col-lg-9">
                        <input type="text" name="placement" class="form-control form-control-solid" value="{{ old('placement') }}" />
                    </div>
                </div>

                <div class="row mb-8">
                    <label class="col-lg-3 col-form-label">{{ __('projectx::lang.quantity_per_garment') }}</label>
                    <div class="col-lg-9">
                        <input type="number" min="{{ $projectxZeroMin }}" step="{{ $projectxQuantityStep }}" name="quantity_per_garment" class="form-control form-control-solid" value="{{ old('quantity_per_garment') }}" />
                    </div>
                </div>

                <div class="separator separator-dashed my-10"></div>

                <div class="row mb-8">
                    <label class="col-lg-3 col-form-label">{{ __('projectx::lang.supplier') }}</label>
                    <div class="col-lg-9">
                        <select name="supplier_contact_id" class="form-select form-select-solid" data-control="select2">
                            <option value="">{{ __('projectx::lang.select_supplier') }}</option>
                            @foreach(($suppliers ?? []) as $supplierKey => $supplierItem)
                                <option value="{{ data_get($supplierItem, 'id', $supplierKey) }}" {{ (string) old('supplier_contact_id') === (string) data_get($supplierItem, 'id', $supplierKey) ? 'selected' : '' }}>
                                    {{ data_get($supplierItem, 'name', data_get($supplierItem, 'supplier_business_name', is_scalar($supplierItem) ? $supplierItem : $supplierKey)) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="row mb-8">
                    <label class="col-lg-3 col-form-label">{{ __('projectx::lang.unit_cost') }}</label>
                    <div class="col-lg-9">
                        <div class="input-group">
                            <span class="input-group-text">{{ data_get($currency ?? [], 'symbol', '$') }}</span>
                            <input type="number" min="{{ $projectxZeroMin }}" step="{{ $projectxCurrencyStep }}" name="unit_cost" class="form-control form-control-solid" value="{{ old('unit_cost') }}" />
                        </div>
                    </div>
                </div>

                <div class="row mb-8">
                    <label class="col-lg-3 col-form-label">{{ __('projectx::lang.currency_label') }}</label>
                    <div class="col-lg-9">
                        <input type="text" name="currency" class="form-control form-control-solid" value="{{ old('currency', data_get($currency ?? [], 'code', 'USD')) }}" />
                    </div>
                </div>

                <div class="row mb-8">
                    <label class="col-lg-3 col-form-label">{{ __('projectx::lang.lead_time_days') }}</label>
                    <div class="col-lg-9">
                        <input type="number" min="0" step="1" name="lead_time_days" class="form-control form-control-solid" value="{{ old('lead_time_days') }}" />
                    </div>
                </div>

                <div class="separator separator-dashed my-10"></div>

                <div class="row mb-8">
                    <label class="col-lg-3 col-form-label">{{ __('projectx::lang.status') }}</label>
                    <div class="col-lg-9">
                        <select name="status" class="form-select form-select-solid" data-control="select2" data-hide-search="true">
                            <option value="draft" {{ old('status', 'draft') === 'draft' ? 'selected' : '' }}>{{ __('projectx::lang.trim_status_draft') }}</option>
                            <option value="sample_requested" {{ old('status') === 'sample_requested' ? 'selected' : '' }}>{{ __('projectx::lang.trim_status_sample_requested') }}</option>
                            <option value="sample_received" {{ old('status') === 'sample_received' ? 'selected' : '' }}>{{ __('projectx::lang.trim_status_sample_received') }}</option>
                            <option value="approved" {{ old('status') === 'approved' ? 'selected' : '' }}>{{ __('projectx::lang.trim_status_approved') }}</option>
                            <option value="bulk_ordered" {{ old('status') === 'bulk_ordered' ? 'selected' : '' }}>{{ __('projectx::lang.trim_status_bulk_ordered') }}</option>
                            <option value="bulk_received" {{ old('status') === 'bulk_received' ? 'selected' : '' }}>{{ __('projectx::lang.trim_status_bulk_received') }}</option>
                            <option value="qc_passed" {{ old('status') === 'qc_passed' ? 'selected' : '' }}>{{ __('projectx::lang.trim_status_qc_passed') }}</option>
                            <option value="qc_failed" {{ old('status') === 'qc_failed' ? 'selected' : '' }}>{{ __('projectx::lang.trim_status_qc_failed') }}</option>
                        </select>
                    </div>
                </div>

                <div class="row mb-8">
                    <label class="col-lg-3 col-form-label">{{ __('projectx::lang.care_testing') }}</label>
                    <div class="col-lg-9">
                        <textarea name="care_testing" class="form-control form-control-solid" rows="3">{{ old('care_testing') }}</textarea>
                    </div>
                </div>

                <div class="row mb-0">
                    <label class="col-lg-3 col-form-label">{{ __('projectx::lang.trim_image') }}</label>
                    <div class="col-lg-9">
                        <input type="file" name="image" class="form-control form-control-solid" accept=".png,.jpg,.jpeg,.webp" />
                        <div class="form-text">{{ __('projectx::lang.allowed_file_types_max') }}</div>
                    </div>
                </div>
            </div>

            <div class="card-footer d-flex justify-content-end py-6 px-9">
                <a href="{{ Route::has('projectx.trim_manager.list') ? route('projectx.trim_manager.list') : '#' }}" class="btn btn-light btn-active-light-primary me-2">
                    {{ __('projectx::lang.cancel') }}
                </a>
                <button type="submit" class="btn btn-primary">{{ __('projectx::lang.save') }}</button>
            </div>
        </form>
    </div>

    @include('projectx::trims._category_modal', ['categories' => $categories ?? []])
@endsection

@section('page_javascript')
    <script>
        (function () {
            const modalEl = document.getElementById('projectx_trim_category_modal');
            const selectEl = document.getElementById('trim_category_select');
            const listEl = document.getElementById('trim_category_list');
            const emptyStateEl = document.getElementById('trim_category_empty_state');
            const addFormEl = document.getElementById('trim_category_add_form');
            const addInputEl = document.getElementById('trim_category_name_input');
            const addButtonEl = document.getElementById('trim_category_add_btn');

            if (!modalEl || !selectEl || !listEl || !emptyStateEl) {
                return;
            }

            const storeUrl = @json(route('projectx.trim_manager.categories.store'));
            const destroyUrlTemplate = @json(route('projectx.trim_manager.categories.destroy', ['id' => '__ID__']));
            const selectCategoryLabel = @json(__('projectx::lang.select_category'));
            const removeCategoryLabel = @json(__('projectx::lang.remove_trim_category'));
            const noCategoriesLabel = @json(__('projectx::lang.no_trim_categories_available'));
            const deleteConfirmLabel = @json(__('projectx::lang.trim_category_delete_confirm'));
            const deleteAffectedTemplate = @json(__('projectx::lang.trim_category_delete_affected_trims', ['count' => '__COUNT__']));
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || @json(csrf_token());
            const canDelete = {{ auth()->user()->can('projectx.trim.delete') ? 'true' : 'false' }};

            const showMessage = (message, type) => {
                if (window.Swal && typeof window.Swal.fire === 'function') {
                    window.Swal.fire({
                        icon: type === 'success' ? 'success' : 'error',
                        text: message,
                        confirmButtonColor: '#3085d6'
                    });
                    return;
                }
                window.alert(message);
            };

            const getErrorMessage = async (response) => {
                let fallback = @json(__('projectx::lang.something_went_wrong'));

                try {
                    const payload = await response.json();
                    if (payload?.msg) {
                        return payload.msg;
                    }
                    if (payload?.message) {
                        return payload.message;
                    }
                    if (payload?.errors) {
                        const firstKey = Object.keys(payload.errors)[0];
                        if (firstKey && payload.errors[firstKey] && payload.errors[firstKey][0]) {
                            return payload.errors[firstKey][0];
                        }
                    }
                } catch (e) {
                    return fallback;
                }

                return fallback;
            };

            const setBusyState = (el, isBusy) => {
                if (!el) {
                    return;
                }
                el.disabled = isBusy;
            };

            const refreshSelect2 = () => {
                if (window.jQuery && window.jQuery.fn && window.jQuery.fn.select2) {
                    window.jQuery(selectEl).trigger('change.select2');
                    return;
                }
                selectEl.dispatchEvent(new Event('change'));
            };

            const renderCategories = (categories, selectedId = null) => {
                const normalized = Array.isArray(categories) ? categories : [];
                const currentSelected = selectedId !== null && selectedId !== undefined
                    ? String(selectedId)
                    : String(selectEl.value || '');

                selectEl.innerHTML = '';
                const placeholderOption = document.createElement('option');
                placeholderOption.value = '';
                placeholderOption.textContent = selectCategoryLabel;
                selectEl.appendChild(placeholderOption);

                normalized.forEach((category) => {
                    const option = document.createElement('option');
                    option.value = String(category.id);
                    option.textContent = category.name;
                    selectEl.appendChild(option);
                });

                const hasSelection = normalized.some((category) => String(category.id) === currentSelected);
                selectEl.value = hasSelection ? currentSelected : '';
                refreshSelect2();

                listEl.innerHTML = '';
                if (normalized.length === 0) {
                    emptyStateEl.classList.remove('d-none');
                } else {
                    emptyStateEl.classList.add('d-none');
                }

                normalized.forEach((category) => {
                    const wrapper = document.createElement('div');
                    wrapper.className = 'd-flex align-items-center justify-content-between border border-gray-300 border-dashed rounded px-4 py-3';
                    wrapper.setAttribute('data-role', 'trim-category-item');
                    wrapper.setAttribute('data-id', String(category.id));

                    const name = document.createElement('span');
                    name.className = 'fw-semibold text-gray-800';
                    name.textContent = category.name;
                    wrapper.appendChild(name);

                    if (canDelete) {
                        const btn = document.createElement('button');
                        btn.type = 'button';
                        btn.className = 'btn btn-light-danger btn-sm';
                        btn.setAttribute('data-action', 'remove-trim-category');
                        btn.setAttribute('data-id', String(category.id));
                        btn.textContent = removeCategoryLabel;
                        wrapper.appendChild(btn);
                    }

                    listEl.appendChild(wrapper);
                });
            };

            if (addFormEl && addInputEl) {
                addFormEl.addEventListener('submit', async function (event) {
                    event.preventDefault();
                    const nameValue = addInputEl.value.trim();

                    if (!nameValue) {
                        showMessage(@json(__('projectx::lang.trim_category_name_required')), 'error');
                        return;
                    }

                    setBusyState(addButtonEl, true);

                    try {
                        const body = new URLSearchParams();
                        body.set('name', nameValue);

                        const response = await fetch(storeUrl, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json',
                                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                            },
                            body: body.toString(),
                        });

                        if (!response.ok) {
                            const errorMessage = await getErrorMessage(response);
                            showMessage(errorMessage, 'error');
                            return;
                        }

                        const payload = await response.json();
                        renderCategories(payload?.data?.categories || [], payload?.data?.selected_category_id ?? null);
                        addInputEl.value = '';
                        showMessage(payload?.msg || @json(__('projectx::lang.trim_category_added_success')), 'success');
                    } catch (error) {
                        showMessage(@json(__('projectx::lang.something_went_wrong')), 'error');
                    } finally {
                        setBusyState(addButtonEl, false);
                    }
                });
            }

            listEl.addEventListener('click', async function (event) {
                const button = event.target.closest('[data-action="remove-trim-category"]');
                if (!button) {
                    return;
                }

                const categoryId = String(button.getAttribute('data-id') || '').trim();
                if (!categoryId) {
                    return;
                }

                if (!window.confirm(deleteConfirmLabel)) {
                    return;
                }

                setBusyState(button, true);

                try {
                    const url = destroyUrlTemplate.replace('__ID__', categoryId);
                    const body = new URLSearchParams();
                    body.set('_method', 'DELETE');

                    const response = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                        },
                        body: body.toString(),
                    });

                    if (!response.ok) {
                        const errorMessage = await getErrorMessage(response);
                        showMessage(errorMessage, 'error');
                        return;
                    }

                    const payload = await response.json();
                    renderCategories(payload?.data?.categories || []);

                    let successMessage = payload?.msg || @json(__('projectx::lang.trim_category_deleted_success'));
                    const affectedCount = Number(payload?.data?.affected_trim_count || 0);
                    if (affectedCount > 0) {
                        successMessage += '\n' + deleteAffectedTemplate.replace('__COUNT__', String(affectedCount));
                    }
                    showMessage(successMessage, 'success');
                } catch (error) {
                    showMessage(@json(__('projectx::lang.something_went_wrong')), 'error');
                } finally {
                    setBusyState(button, false);
                }
            });

            emptyStateEl.textContent = noCategoriesLabel;
        })();
    </script>
@endsection
