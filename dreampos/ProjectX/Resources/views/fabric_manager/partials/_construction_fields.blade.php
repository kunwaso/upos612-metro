@php
    $constructionTypeValue = old('construction_type', $fabric->construction_type ?? '');
    $constructionConfig = config('projectx.fabric_construction', []);
    $constructionTypes = $constructionConfig['types'] ?? [];
    $constructionDetailOptions = $constructionConfig['lists'] ?? [];
    $constructionDetailListByType = $constructionConfig['map'] ?? [];
    $defaultConstructionDetailList = $constructionDetailListByType[$constructionTypeValue] ?? 'construction-detail-woven';

    $constructionTypeLabels = [
        'Knit' => __('projectx::lang.knit'),
        'Woven' => __('projectx::lang.woven'),
        'Non-woven' => __('projectx::lang.non_woven'),
    ];
@endphp

<div class="row mb-8">
    <label class="col-lg-3 col-form-label">{{ __('projectx::lang.construction_type') }}</label>
    <div class="col-lg-9">
        <select name="construction_type" id="construction_type_select" class="form-select form-select-solid">
            <option value="">{{ __('projectx::lang.select_construction_type') }}</option>
            @foreach($constructionTypes as $constructionTypeOption)
                <option value="{{ $constructionTypeOption }}" {{ $constructionTypeValue === $constructionTypeOption ? 'selected' : '' }}>
                    {{ $constructionTypeLabels[$constructionTypeOption] ?? $constructionTypeOption }}
                </option>
            @endforeach
        </select>
        <div class="form-text">{{ __('projectx::lang.select_base_type_or_custom') }}</div>
        <input type="hidden" name="construction_type_other" value="" />
        @error('construction_type')
            <div class="text-danger fs-7 mt-2">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="row mb-8">
    <label class="col-lg-3 col-form-label">{{ __('projectx::lang.weave_pattern') }}</label>
    <div class="col-lg-9">
        <input
            type="text"
            id="construction_detail_input"
            name="weave_pattern"
            list="{{ $defaultConstructionDetailList }}"
            class="form-control form-control-solid"
            placeholder="{{ __('projectx::lang.weave_pattern_placeholder') }}"
            value="{{ old('weave_pattern', $fabric->weave_pattern ?? '') }}" />

        @foreach($constructionDetailOptions as $listId => $detailOptions)
            <datalist id="{{ $listId }}">
                @foreach($detailOptions as $detailOption)
                    <option value="{{ $detailOption }}"></option>
                @endforeach
            </datalist>
        @endforeach

        @error('weave_pattern')
            <div class="text-danger fs-7 mt-2">{{ $message }}</div>
        @enderror
    </div>
</div>
