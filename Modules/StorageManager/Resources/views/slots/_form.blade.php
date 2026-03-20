{{-- Shared form fields for create / edit storage slot --}}
<div class="row gy-5">
    <div class="col-md-6">
        <label class="form-label required fw-semibold fs-6">@lang('business.location')</label>
        <select name="location_id" class="form-select form-select-solid @error('location_id') is-invalid @enderror" required>
            <option value="">— @lang('messages.select') —</option>
            @foreach($locations as $id => $name)
                <option value="{{ $id }}" @selected(old('location_id', $slot->location_id ?? '') == $id)>{{ $name }}</option>
            @endforeach
        </select>
        @error('location_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label class="form-label required fw-semibold fs-6">@lang('lang_v1.zone') ({{ __('lang_v1.rack') }})</label>
        <select name="category_id" class="form-select form-select-solid @error('category_id') is-invalid @enderror" required>
            <option value="">— @lang('messages.select') —</option>
            @foreach($categories as $id => $name)
                <option value="{{ $id }}" @selected(old('category_id', $slot->category_id ?? '') == $id)>{{ $name }}</option>
            @endforeach
        </select>
        @error('category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4">
        <label class="form-label required fw-semibold fs-6">@lang('lang_v1.row')</label>
        <input type="text"
               name="row"
               class="form-control form-control-solid @error('row') is-invalid @enderror"
               value="{{ old('row', $slot->row ?? '') }}"
               maxlength="50"
               required>
        @error('row')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4">
        <label class="form-label required fw-semibold fs-6">@lang('lang_v1.position')</label>
        <input type="text"
               name="position"
               class="form-control form-control-solid @error('position') is-invalid @enderror"
               value="{{ old('position', $slot->position ?? '') }}"
               maxlength="50"
               required>
        @error('position')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4">
        <label class="form-label fw-semibold fs-6">@lang('lang_v1.storage_slot_code') <span class="text-muted fs-7">(@lang('messages.optional') — auto-generated if blank)</span></label>
        <input type="text"
               name="slot_code"
               class="form-control form-control-solid @error('slot_code') is-invalid @enderror"
               value="{{ old('slot_code', $slot->slot_code ?? '') }}"
               maxlength="50">
        @error('slot_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4">
        <label class="form-label required fw-semibold fs-6">@lang('lang_v1.max_capacity')</label>
        <input type="number"
               name="max_capacity"
               class="form-control form-control-solid @error('max_capacity') is-invalid @enderror"
               value="{{ old('max_capacity', $slot->max_capacity ?? 0) }}"
               min="0"
               required>
        <div class="form-text text-muted">@lang('messages.zero_means_unlimited')</div>
        @error('max_capacity')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>
