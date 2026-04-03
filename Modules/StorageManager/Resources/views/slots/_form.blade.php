{{-- Shared form fields for create / edit storage slot --}}
@php($slot = $slot ?? null)
<div class="row gy-5">
    <div class="col-md-6">
        <label class="form-label required fw-semibold fs-6">@lang('business.location')</label>
        <select name="location_id" id="storage_slot_location_id" class="form-select form-select-solid @error('location_id') is-invalid @enderror" required>
            <option value="">— @lang('messages.select') —</option>
            @foreach($locations as $id => $name)
                <option value="{{ $id }}" @selected(old('location_id', data_get($slot, 'location_id')) == $id)>{{ $name }}</option>
            @endforeach
        </select>
        @error('location_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label class="form-label required fw-semibold fs-6">@lang('lang_v1.zone') ({{ __('lang_v1.rack') }})</label>
        <select name="category_id" class="form-select form-select-solid @error('category_id') is-invalid @enderror" required>
            <option value="">— @lang('messages.select') —</option>
            @foreach($categories as $id => $name)
                <option value="{{ $id }}" @selected(old('category_id', data_get($slot, 'category_id')) == $id)>{{ $name }}</option>
            @endforeach
        </select>
        @error('category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label class="form-label fw-semibold fs-6">@lang('lang_v1.storage_area')</label>
        <select name="area_id" id="storage_slot_area_id" class="form-select form-select-solid @error('area_id') is-invalid @enderror">
            <option value="">— @lang('messages.select') —</option>
            @foreach($areas as $area)
                <option value="{{ $area['id'] }}" data-location-id="{{ $area['location_id'] }}" @selected(old('area_id', data_get($slot, 'area_id')) == $area['id'])>{{ $area['label'] }}</option>
            @endforeach
        </select>
        <div class="form-text text-muted">@lang('lang_v1.storage_area_help')</div>
        @error('area_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label class="form-label fw-semibold fs-6">@lang('lang_v1.barcode')</label>
        <input type="text"
               name="barcode"
               class="form-control form-control-solid @error('barcode') is-invalid @enderror"
               value="{{ old('barcode', data_get($slot, 'barcode')) }}"
               maxlength="120">
        @error('barcode')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4">
        <label class="form-label required fw-semibold fs-6">@lang('lang_v1.row')</label>
        <input type="text"
               name="row"
               class="form-control form-control-solid @error('row') is-invalid @enderror"
               value="{{ old('row', data_get($slot, 'row')) }}"
               maxlength="50"
               required>
        @error('row')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4">
        <label class="form-label required fw-semibold fs-6">@lang('lang_v1.position')</label>
        <input type="text"
               name="position"
               class="form-control form-control-solid @error('position') is-invalid @enderror"
               value="{{ old('position', data_get($slot, 'position')) }}"
               maxlength="50"
               required>
        @error('position')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4">
        <label class="form-label fw-semibold fs-6">@lang('lang_v1.storage_slot_code') <span class="text-muted fs-7">(@lang('messages.optional') — auto-generated if blank)</span></label>
        <input type="text"
               name="slot_code"
               class="form-control form-control-solid @error('slot_code') is-invalid @enderror"
               value="{{ old('slot_code', data_get($slot, 'slot_code')) }}"
               maxlength="50">
        @error('slot_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4">
        <label class="form-label required fw-semibold fs-6">@lang('lang_v1.max_capacity')</label>
        <input type="number"
               name="max_capacity"
               class="form-control form-control-solid @error('max_capacity') is-invalid @enderror"
               value="{{ old('max_capacity', data_get($slot, 'max_capacity', 0)) }}"
               min="0"
               required>
        <div class="form-text text-muted">@lang('messages.zero_means_unlimited')</div>
        @error('max_capacity')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4">
        <label class="form-label fw-semibold fs-6">@lang('lang_v1.slot_type')</label>
        <select name="slot_type" class="form-select form-select-solid @error('slot_type') is-invalid @enderror">
            @foreach(['standard', 'receiving', 'staging', 'forward_pick', 'reserve', 'quarantine'] as $slotType)
                <option value="{{ $slotType }}" @selected(old('slot_type', data_get($slot, 'slot_type', 'standard')) === $slotType)>{{ ucwords(str_replace('_', ' ', $slotType)) }}</option>
            @endforeach
        </select>
        @error('slot_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4">
        <label class="form-label fw-semibold fs-6">@lang('lang_v1.status')</label>
        <select name="status" class="form-select form-select-solid @error('status') is-invalid @enderror">
            <option value="active" @selected(old('status', data_get($slot, 'status', 'active')) === 'active')>@lang('lang_v1.active')</option>
            <option value="inactive" @selected(old('status', data_get($slot, 'status', 'active')) === 'inactive')>@lang('lang_v1.inactive')</option>
        </select>
        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4">
        <label class="form-label fw-semibold fs-6">@lang('lang_v1.pick_sequence')</label>
        <input type="number"
               name="pick_sequence"
               class="form-control form-control-solid @error('pick_sequence') is-invalid @enderror"
               value="{{ old('pick_sequence', data_get($slot, 'pick_sequence', 0)) }}"
               min="0">
        @error('pick_sequence')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4">
        <label class="form-label fw-semibold fs-6">@lang('lang_v1.putaway_sequence')</label>
        <input type="number"
               name="putaway_sequence"
               class="form-control form-control-solid @error('putaway_sequence') is-invalid @enderror"
               value="{{ old('putaway_sequence', data_get($slot, 'putaway_sequence', 0)) }}"
               min="0">
        @error('putaway_sequence')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label class="form-label fw-semibold fs-6">@lang('lang_v1.slot_mix_policy')</label>
        <div class="d-flex flex-wrap gap-6 pt-2">
            <label class="form-check form-check-sm form-check-custom form-check-solid">
                <input class="form-check-input" type="checkbox" name="allows_mixed_sku" value="1" @checked(old('allows_mixed_sku', data_get($slot, 'allows_mixed_sku', false)))>
                <span class="form-check-label">@lang('lang_v1.allows_mixed_sku')</span>
            </label>
            <label class="form-check form-check-sm form-check-custom form-check-solid">
                <input class="form-check-input" type="checkbox" name="allows_mixed_lot" value="1" @checked(old('allows_mixed_lot', data_get($slot, 'allows_mixed_lot', false)))>
                <span class="form-check-label">@lang('lang_v1.allows_mixed_lot')</span>
            </label>
        </div>
    </div>
</div>

@once
    @push('javascript')
    <script>
        $(function () {
            function filterAreaOptions() {
                var selectedLocation = $('#storage_slot_location_id').val();
                $('#storage_slot_area_id option').each(function () {
                    var optionLocation = $(this).data('location-id');
                    if (!optionLocation || !selectedLocation || String(optionLocation) === String(selectedLocation)) {
                        $(this).prop('disabled', false).show();
                    } else {
                        if ($(this).is(':selected')) {
                            $('#storage_slot_area_id').val('');
                        }
                        $(this).prop('disabled', true).hide();
                    }
                });
            }

            filterAreaOptions();
            $(document).on('change', '#storage_slot_location_id', filterAreaOptions);
        });
    </script>
    @endpush
@endonce
