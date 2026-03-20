{{--
    Rack / Row / Position + Storage Slot picker section.

    Variables expected from the including view (all prepared by ProductController):
      $show_racks              (bool)
      $show_row                (bool)
      $show_position           (bool)
      $business_locations      (Collection, keyed location_id => name)
      $rack_details            (array|null, keyed location_id => [rack, row, position, slot_id, slot_label, ...])
      $storage_manager_enabled (bool)
      $available_slots_url     (string|null)  — AJAX endpoint URL
      $is_edit                 (bool)  — passed as local variable by the including view
--}}
@if($show_racks || $show_row || $show_position)
<div class="mb-7" id="rack_details_section">
    <div class="d-flex align-items-center gap-2 mb-4">
        <span class="fw-bold text-gray-800">@lang('lang_v1.rack_details')</span>
        @show_tooltip(__('lang_v1.tooltip_rack_details'))
        @if($storage_manager_enabled)
            <a href="{{ route('storage-manager.index') }}" target="_blank"
               class="btn btn-sm btn-light-primary ms-auto py-1 px-3">
                <i class="ki-duotone ki-map fs-6 me-1"><span class="path1"></span><span class="path2"></span></i>
                @lang('lang_v1.warehouse_map')
            </a>
        @endif
    </div>

    <div class="row g-5">
        @foreach($business_locations as $loc_id => $loc_name)
            @php
                $locDetail    = (!empty($rack_details) && isset($rack_details[$loc_id])) ? $rack_details[$loc_id] : [];
                $hasExisting  = $is_edit && !empty($locDetail);
                $fieldPrefix  = $hasExisting ? 'product_racks_update' : 'product_racks';
                $currentSlotId    = $locDetail['slot_id'] ?? '';
                $currentSlotLabel = $locDetail['slot_label'] ?? '';
            @endphp

            <div class="col-md-6 col-lg-4" data-rack-location="{{ $loc_id }}">

                <div class="fw-semibold text-gray-700 mb-3 fs-7 text-uppercase">{{ $loc_name }}</div>

                {{-- Storage Slot picker (only when StorageManager is enabled) --}}
                @if($storage_manager_enabled)
                <div class="mb-3">
                    <label class="form-label fs-7 text-muted">
                        <i class="ki-duotone ki-abstract-26 fs-7 me-1"><span class="path1"></span><span class="path2"></span></i>
                        @lang('lang_v1.storage_slot')
                    </label>
                    <select
                        class="form-select form-select-solid slot-picker-select"
                        name="{{ $fieldPrefix }}[{{ $loc_id }}][slot_id]"
                        id="slot_picker_{{ $loc_id }}"
                        data-location-id="{{ $loc_id }}"
                        data-available-slots-url="{{ $available_slots_url }}"
                        data-current-slot-id="{{ $currentSlotId }}"
                        data-current-slot-text="{{ $currentSlotLabel }}"
                    >
                        @if($currentSlotId)
                            <option value="{{ $currentSlotId }}" selected>{{ $currentSlotLabel ?: $currentSlotId }}</option>
                        @endif
                    </select>
                    <div class="text-muted fs-8 mt-1" style="display:none">
                        @lang('lang_v1.no_slots_defined')
                    </div>
                </div>
                @else
                    {{-- hidden slot_id placeholder so rack/row/position still submit under the same key --}}
                    <input type="hidden" name="{{ $fieldPrefix }}[{{ $loc_id }}][slot_id]" value="{{ $currentSlotId }}">
                @endif

                {{-- Rack / Row / Position text inputs (auto-filled when a slot is picked) --}}
                @if($show_racks)
                    <input
                        type="text"
                        class="form-control form-control-solid mb-3 slot-rack-input"
                        name="{{ $fieldPrefix }}[{{ $loc_id }}][rack]"
                        id="{{ $is_edit ? '' : 'rack_' . $loc_id }}"
                        value="{{ $locDetail['rack'] ?? '' }}"
                        placeholder="@lang('lang_v1.rack')"
                    >
                @endif

                @if($show_row)
                    <input
                        type="text"
                        class="form-control form-control-solid mb-3 slot-row-input"
                        name="{{ $fieldPrefix }}[{{ $loc_id }}][row]"
                        value="{{ $locDetail['row'] ?? '' }}"
                        placeholder="@lang('lang_v1.row')"
                    >
                @endif

                @if($show_position)
                    <input
                        type="text"
                        class="form-control form-control-solid slot-position-input"
                        name="{{ $fieldPrefix }}[{{ $loc_id }}][position]"
                        value="{{ $locDetail['position'] ?? '' }}"
                        placeholder="@lang('lang_v1.position')"
                    >
                @endif

            </div>
        @endforeach
    </div>
</div>
@endif

@if($storage_manager_enabled)
@once
@push('scripts')
<script>
$(function () {
    var $noSlotsHint = '#rack_details_section .text-muted.fs-8';

    /**
     * Initialise one slot-picker select2 element.
     * Uses AJAX to load available (not-full) slots for the given location.
     */
    function initSlotPicker($el) {
        var locationId = $el.data('location-id');
        var baseUrl    = $el.data('available-slots-url');

        $el.select2({
            placeholder: '— {{ __("lang_v1.storage_slot") }} —',
            allowClear: true,
            dropdownParent: $el.parent(),
            ajax: {
                url: baseUrl,
                dataType: 'json',
                delay: 150,
                data: function () {
                    return { location_id: locationId };
                },
                processResults: function (data) {
                    // Show/hide the "no slots" hint
                    var $wrapper = $el.closest('[data-rack-location]');
                    if (!data.results || data.results.length === 0) {
                        $wrapper.find('.text-muted.fs-8').show();
                    } else {
                        $wrapper.find('.text-muted.fs-8').hide();
                    }
                    return { results: data.results || [] };
                },
                cache: true
            }
        });

        // Pre-select the current slot (edit mode).
        // The option is already rendered in the DOM as `selected`, so Select2
        // picks it up automatically on init — no manual trigger needed.
    }

    // Initialise all pickers on page load
    $('.slot-picker-select').each(function () {
        initSlotPicker($(this));
    });

    // Auto-fill rack/row/position when a slot is chosen
    $(document).on('select2:select', '.slot-picker-select', function (e) {
        var d        = e.params.data;
        var $wrapper = $(this).closest('[data-rack-location]');
        if (d.rack     !== undefined) { $wrapper.find('.slot-rack-input').val(d.rack); }
        if (d.row      !== undefined) { $wrapper.find('.slot-row-input').val(d.row); }
        if (d.position !== undefined) { $wrapper.find('.slot-position-input').val(d.position); }
    });

    // Clear rack/row/position when slot is cleared
    $(document).on('select2:unselect', '.slot-picker-select', function () {
        var $wrapper = $(this).closest('[data-rack-location]');
        $wrapper.find('.slot-rack-input, .slot-row-input, .slot-position-input').val('');
    });
});
</script>
@endpush
@endonce
@endif
