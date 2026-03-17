<div class="modal fade" id="projectx_trim_category_modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-650px">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">{{ __('projectx::lang.manage_trim_categories') }}</h3>
                <button type="button" class="btn btn-sm btn-icon btn-active-color-primary" data-bs-dismiss="modal" aria-label="{{ __('projectx::lang.close') }}">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </button>
            </div>

            <div class="modal-body">
                @can('projectx.trim.create')
                    <form id="trim_category_add_form" class="mb-8">
                        <label class="form-label required">{{ __('projectx::lang.new_trim_category_name') }}</label>
                        <div class="input-group">
                            <input type="text" class="form-control form-control-solid" id="trim_category_name_input" name="name" maxlength="191" />
                            <button type="submit" id="trim_category_add_btn" class="btn btn-primary">
                                {{ __('projectx::lang.add_trim_category') }}
                            </button>
                        </div>
                    </form>
                @endcan

                <div class="mb-3">
                    <h4 class="fw-bold mb-0">{{ __('projectx::lang.trim_categories') }}</h4>
                </div>

                <div id="trim_category_empty_state" class="{{ count($categories ?? []) > 0 ? 'd-none' : '' }}">
                    <span class="text-muted">{{ __('projectx::lang.no_trim_categories_available') }}</span>
                </div>

                <div id="trim_category_list" class="d-flex flex-column gap-3">
                    @foreach(($categories ?? []) as $category)
                        <div class="d-flex align-items-center justify-content-between border border-gray-300 border-dashed rounded px-4 py-3" data-role="trim-category-item" data-id="{{ $category->id }}">
                            <span class="fw-semibold text-gray-800">{{ $category->name }}</span>
                            @can('projectx.trim.delete')
                                <button
                                    type="button"
                                    class="btn btn-light-danger btn-sm"
                                    data-action="remove-trim-category"
                                    data-id="{{ $category->id }}"
                                >
                                    {{ __('projectx::lang.remove_trim_category') }}
                                </button>
                            @endcan
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('projectx::lang.close') }}</button>
            </div>
        </div>
    </div>
</div>

