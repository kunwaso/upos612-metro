<div class="row g-5">
    <div class="col-md-6">
        <label class="form-label required">@lang('business.location')</label>
        <select class="form-select form-select-solid @error('location_id') is-invalid @enderror" name="location_id" required>
            <option value="">@lang('messages.select')</option>
            @foreach($locations as $id => $name)
                <option value="{{ $id }}" @selected(old('location_id', data_get($area, 'location_id')) == $id)>{{ $name }}</option>
            @endforeach
        </select>
        @error('location_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">@lang('lang_v1.zone')</label>
        <select class="form-select form-select-solid @error('category_id') is-invalid @enderror" name="category_id">
            <option value="">@lang('lang_v1.none')</option>
            @foreach($categories as $id => $name)
                <option value="{{ $id }}" @selected(old('category_id', data_get($area, 'category_id')) == $id)>{{ $name }}</option>
            @endforeach
        </select>
        <div class="form-text">@lang('lang_v1.legacy_zone_mapping_help')</div>
        @error('category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label required">@lang('lang_v1.code')</label>
        <input type="text" class="form-control form-control-solid @error('code') is-invalid @enderror" name="code" value="{{ old('code', data_get($area, 'code')) }}" required>
        @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label required">@lang('messages.name')</label>
        <input type="text" class="form-control form-control-solid @error('name') is-invalid @enderror" name="name" value="{{ old('name', data_get($area, 'name')) }}" required>
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label required">@lang('lang_v1.area_type')</label>
        <select class="form-select form-select-solid @error('area_type') is-invalid @enderror" name="area_type" required>
            @foreach($areaTypes as $value => $label)
                <option value="{{ $value }}" @selected(old('area_type', data_get($area, 'area_type')) === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('area_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">@lang('lang_v1.status')</label>
        <select class="form-select form-select-solid @error('status') is-invalid @enderror" name="status">
            <option value="active" @selected(old('status', data_get($area, 'status', 'active')) === 'active')>@lang('lang_v1.active')</option>
            <option value="inactive" @selected(old('status', data_get($area, 'status', 'active')) === 'inactive')>@lang('lang_v1.inactive')</option>
        </select>
        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">@lang('lang_v1.sort_order')</label>
        <input type="number" class="form-control form-control-solid @error('sort_order') is-invalid @enderror" name="sort_order" value="{{ old('sort_order', data_get($area, 'sort_order', 0)) }}" min="0">
        @error('sort_order')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">@lang('lang_v1.barcode')</label>
        <input type="text" class="form-control form-control-solid @error('barcode') is-invalid @enderror" name="barcode" value="{{ old('barcode', data_get($area, 'barcode')) }}">
        @error('barcode')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-12">
        <label class="form-label">@lang('lang_v1.notes')</label>
        <textarea class="form-control form-control-solid @error('notes') is-invalid @enderror" name="notes" rows="4">{{ old('notes', data_get($area, 'meta.notes', '')) }}</textarea>
        @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>
