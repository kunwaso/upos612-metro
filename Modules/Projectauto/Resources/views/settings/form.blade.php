@extends('layouts.app')
@section('title', $rule->exists ? __('projectauto::lang.edit_rule') : __('projectauto::lang.create_rule'))

@section('content')
    <div class="toolbar d-flex flex-stack py-3 py-lg-5" id="kt_toolbar">
        <div id="kt_toolbar_container" class="container-xxl d-flex flex-stack flex-wrap">
            <div class="page-title d-flex flex-column me-3">
                <h1 class="d-flex text-gray-900 fw-bold my-1 fs-3">{{ $rule->exists ? __('projectauto::lang.edit_rule') : __('projectauto::lang.create_rule') }}</h1>
            </div>
            <div>
                <a href="{{ route('projectauto.settings.index') }}" class="btn btn-light">{{ __('projectauto::lang.cancel') }}</a>
            </div>
        </div>
    </div>

    <div class="container-xxl">
        <div class="card">
            <div class="card-body py-8">
                <form method="POST" action="{{ $rule->exists ? route('projectauto.settings.update', ['id' => $rule->id]) : route('projectauto.settings.store') }}">
                    @csrf
                    @if($rule->exists)
                        @method('PUT')
                    @endif

                    <div class="row g-5 mb-5">
                        <div class="col-md-6">
                            <label class="form-label">{{ __('projectauto::lang.rules') }}</label>
                            <input type="text" name="name" class="form-control" required value="{{ old('name', $rule->name) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('projectauto::lang.trigger_type') }}</label>
                            <select name="trigger_type" class="form-select" required>
                                <option value="payment_status_updated" {{ old('trigger_type', $rule->trigger_type) === 'payment_status_updated' ? 'selected' : '' }}>{{ __('projectauto::lang.payment_status_updated') }}</option>
                                <option value="sales_order_status_updated" {{ old('trigger_type', $rule->trigger_type) === 'sales_order_status_updated' ? 'selected' : '' }}>{{ __('projectauto::lang.sales_order_status_updated') }}</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('projectauto::lang.task_type') }}</label>
                            <select name="task_type" class="form-select" required>
                                <option value="create_invoice" {{ old('task_type', $rule->task_type) === 'create_invoice' ? 'selected' : '' }}>create_invoice</option>
                                <option value="add_product" {{ old('task_type', $rule->task_type) === 'add_product' ? 'selected' : '' }}>add_product</option>
                                <option value="adjust_stock" {{ old('task_type', $rule->task_type) === 'adjust_stock' ? 'selected' : '' }}>adjust_stock</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-5 mb-5">
                        <div class="col-md-4">
                            <label class="form-label">{{ __('projectauto::lang.priority') }}</label>
                            <input type="number" name="priority" class="form-control" value="{{ old('priority', $rule->priority ?? 100) }}" min="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label d-block">{{ __('projectauto::lang.is_active') }}</label>
                            <div class="form-check form-switch form-check-custom form-check-solid mt-2">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1" {{ old('is_active', $rule->exists ? (int) $rule->is_active : 1) ? 'checked' : '' }}>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label d-block">{{ __('projectauto::lang.stop_on_match') }}</label>
                            <div class="form-check form-switch form-check-custom form-check-solid mt-2">
                                <input class="form-check-input" type="checkbox" name="stop_on_match" value="1" {{ old('stop_on_match', (int) $rule->stop_on_match) ? 'checked' : '' }}>
                            </div>
                        </div>
                    </div>

                    <div class="mb-5">
                        <label class="form-label">{{ __('projectauto::lang.conditions_json') }}</label>
                        <textarea class="form-control font-monospace" name="conditions" rows="6">{{ old('conditions', $rule->conditions ? json_encode($rule->conditions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '') }}</textarea>
                    </div>

                    <div class="mb-8">
                        <label class="form-label">{{ __('projectauto::lang.payload_template_json') }}</label>
                        <textarea class="form-control font-monospace" name="payload_template" rows="10">{{ old('payload_template', $rule->payload_template ? json_encode($rule->payload_template, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '') }}</textarea>
                    </div>

                    <button type="submit" class="btn btn-primary">{{ __('projectauto::lang.save') }}</button>
                </form>
            </div>
        </div>
    </div>
@endsection
