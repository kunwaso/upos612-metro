@extends('projectx::layouts.main')

@section('title', __('projectx::lang.create_fabric'))

@section('content')
<div class="d-flex flex-wrap flex-stack mb-6">
    <h2 class="fs-2 fw-semibold">{{ __('projectx::lang.create_fabric') }}</h2>
    <a href="{{ route('projectx.fabric_manager.list') }}" class="btn btn-sm btn-light">
        <i class="ki-duotone ki-arrow-left fs-4 me-1"><span class="path1"></span><span class="path2"></span></i>
        {{ __('projectx::lang.fabrics') }}
    </a>
</div>

@if(session('status'))
<div class="alert alert-{{ session('status.success') ? 'success' : 'danger' }} alert-dismissible fade show" role="alert">
    {{ session('status.msg') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('projectx::lang.close') }}"></button>
</div>
@endif

<div class="card">
    <div class="card-header">
        <div class="card-title">
            <h3 class="fw-bold m-0">{{ __('projectx::lang.create_fabric') }}</h3>
        </div>
    </div>
    <form class="form" method="POST" action="{{ route('projectx.fabric_manager.store') }}" enctype="multipart/form-data">
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
                <label class="col-lg-3 col-form-label">{{ __('projectx::lang.fabric_image') }}</label>
                <div class="col-lg-9">
                    <input type="file" name="image" class="form-control form-control-solid" accept=".png,.jpg,.jpeg,.webp" />
                    <div class="form-text">{{ __('projectx::lang.allowed_file_types_max') }}</div>
                </div>
            </div>

            <div class="row mb-8">
                <label class="col-lg-3 col-form-label required">{{ __('projectx::lang.fabric_name') }}</label>
                <div class="col-lg-9">
                    <input type="text" name="name" class="form-control form-control-solid" placeholder="{{ __('projectx::lang.fabric_name') }}" value="{{ old('name') }}" required />
                </div>
            </div>

            <div class="row mb-8">
                <label class="col-lg-3 col-form-label required">{{ __('projectx::lang.fabric_status') }}</label>
                <div class="col-lg-9">
                    <select name="status" class="form-select form-select-solid" data-control="select2" data-hide-search="true" required>
                        <option value="">{{ __('projectx::lang.select_status') }}</option>
                        <option value="active" {{ old('status') === 'active' ? 'selected' : '' }}>{{ __('projectx::lang.active') }}</option>
                        <option value="draft" {{ old('status', 'draft') === 'draft' ? 'selected' : '' }}>{{ __('projectx::lang.draft') }}</option>
                        <option value="needs_approval" {{ old('status') === 'needs_approval' ? 'selected' : '' }}>{{ __('projectx::lang.needs_approval') }}</option>
                    </select>
                </div>
            </div>

            <div class="row mb-8">
                <label class="col-lg-3 col-form-label">{{ __('projectx::lang.fabric_fiber') }}</label>
                <div class="col-lg-9">
                    <input type="text" name="fiber" class="form-control form-control-solid" placeholder="{{ __('projectx::lang.fiber_placeholder') }}" value="{{ old('fiber') }}" />
                </div>
            </div>

            <div class="row mb-8">
                <label class="col-lg-3 col-form-label required">{{ __('projectx::lang.purchase_price') }}</label>
                <div class="col-lg-9">
                    <div class="input-group">
                        <span class="input-group-text">{{ $currency['symbol'] ?? '$' }}</span>
                        <input type="number" name="purchase_price" class="form-control form-control-solid" step="0.01" min="0" placeholder="0.00" value="{{ old('purchase_price') }}" required />
                    </div>
                </div>
            </div>

            <div class="row mb-8">
                <label class="col-lg-3 col-form-label required">{{ __('projectx::lang.sale_price') }}</label>
                <div class="col-lg-9">
                    <div class="input-group">
                        <span class="input-group-text">{{ $currency['symbol'] ?? '$' }}</span>
                        <input type="number" name="sale_price" class="form-control form-control-solid" step="0.01" min="0" placeholder="0.00" value="{{ old('sale_price') }}" required />
                    </div>
                </div>
            </div>

            <div class="row mb-8">
                <label class="col-lg-3 col-form-label">{{ __('projectx::lang.supplier') }}</label>
                <div class="col-lg-9">
                    <select name="supplier_contact_id" class="form-select form-select-solid" data-control="select2" data-placeholder="{{ __('projectx::lang.select_supplier') }}">
                        @foreach($suppliers as $id => $name)
                        <option value="{{ $id }}" {{ old('supplier_contact_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="row mb-8">
                <label class="col-lg-3 col-form-label">{{ __('projectx::lang.due_date') }}</label>
                <div class="col-lg-9">
                    <input type="text" id="kt_projectx_fabric_create_due_date" name="due_date" class="form-control form-control-solid" value="{{ old('due_date') }}" placeholder="{{ __('projectx::lang.due_date') }}" />
                </div>
            </div>

            <div class="row mb-8">
                <label class="col-lg-3 col-form-label">{{ __('projectx::lang.progress') }} (%)</label>
                <div class="col-lg-9">
                    <input type="number" name="progress_percent" class="form-control form-control-solid" min="0" max="100" placeholder="0" value="{{ old('progress_percent', 0) }}" />
                </div>
            </div>
        </div>

        <div class="card-footer d-flex justify-content-end py-6 px-9">
            <a href="{{ route('projectx.fabric_manager.list') }}" class="btn btn-light btn-active-light-primary me-2">{{ __('projectx::lang.cancel') }}</a>
            <button type="submit" class="btn btn-primary">{{ __('projectx::lang.save') }}</button>
        </div>
    </form>
</div>
@endsection

@section('page_javascript')
    <script>
        (function () {
            const dueDateInput = document.getElementById('kt_projectx_fabric_create_due_date');
            if (!dueDateInput) {
                return;
            }

            const config = {
                altInput: true,
                altFormat: 'd M, Y',
                dateFormat: 'Y-m-d',
                allowInput: false
            };

            if (typeof window.flatpickr === 'function') {
                window.flatpickr(dueDateInput, config);
                return;
            }

            if (window.jQuery && window.jQuery.fn && typeof window.jQuery.fn.flatpickr === 'function') {
                window.jQuery(dueDateInput).flatpickr(config);
            }
        })();
    </script>
@endsection
