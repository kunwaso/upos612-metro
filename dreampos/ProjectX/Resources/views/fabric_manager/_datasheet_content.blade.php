@php
    $context = $fds['context'] ?? 'public';
    $swatchImage = $context === 'pdf'
        ? ($fds['swatch_file_path'] ?? $fds['swatch_public_path'])
        : ($fds['swatch_public_path'] ?? null);

    $rows = [
        ['label' => __('projectx::lang.fabric_name'), 'value' => $fds['name'] ?? '-'],
        ['label' => __('projectx::lang.fabric_composition'), 'value' => $fds['component_summary'] ?? $fds['composition'] ?? '-'],
        ['label' => __('projectx::lang.fds_label_br_rd_number'), 'value' => $fds['fabric_sku'] ?? '-'],
        ['label' => __('projectx::lang.fds_label_season_department'), 'value' => $fds['season_department'] ?? '-'],
        ['label' => __('projectx::lang.fds_label_supplier_mill'), 'value' => $fds['suppliers'] ?? '-'],
        ['label' => __('projectx::lang.fds_label_article_number'), 'value' => $fds['mill_article_no'] ?? '-'],
        ['label' => __('projectx::lang.fds_label_br_pattern_color'), 'value' => $fds['pattern_color_name_number'] ?? '-'],
        ['label' => __('projectx::lang.fds_label_mill_pattern_color'), 'value' => $fds['mill_pattern_color'] ?? '-'],
        ['label' => __('projectx::lang.fds_label_sustainability'), 'value' => $fds['certifications'] ?? '-'],
        ['label' => __('projectx::lang.fds_label_performance_claims'), 'value' => $fds['performance_claims'] ?? '-'],
        ['label' => __('projectx::lang.color_fastness'), 'value' => $fds['color_fastness'] ?? '-'],
        ['label' => __('projectx::lang.abrasion_resistance'), 'value' => $fds['abrasion_resistance'] ?? '-'],
        ['label' => __('projectx::lang.handfeel_drape'), 'value' => $fds['handfeel_drape'] ?? '-'],
        ['label' => __('projectx::lang.finish_treatments'), 'value' => $fds['finish_treatments'] ?? '-'],
        ['label' => __('projectx::lang.fds_label_dyeing_technique'), 'value' => $fds['dyeing_technique'] ?? '-'],
        ['label' => __('projectx::lang.construction_type'), 'value' => $fds['construction_type'] ?? '-'],
        ['label' => __('projectx::lang.fds_label_fabric_type'), 'value' => $fds['weave_pattern'] ?? '-'],
        ['label' => __('projectx::lang.fds_label_submit_type'), 'value' => $fds['submit_type'] ?? '-'],
        ['label' => __('projectx::lang.fds_label_content'), 'value' => $fds['component_summary'] ?? $fds['composition'] ?? '-'],
        ['label' => __('projectx::lang.fds_label_yarn_count'), 'value' => $fds['yarn_count_denier'] ?? '-'],
        ['label' => __('projectx::lang.fds_label_construction_ypi'), 'value' => $fds['construction_ypi'] ?? '-'],
        ['label' => __('projectx::lang.fds_label_weight_gsm'), 'value' => $fds['weight_gsm'] ?? '-'],
        ['label' => __('projectx::lang.fds_label_cuttable_width'), 'value' => $fds['cuttable_width'] ?? '-'],
        ['label' => __('projectx::lang.fds_label_fabric_finish'), 'value' => $fds['fabric_finish'] ?? '-'],
        ['label' => __('projectx::lang.fds_label_care_label'), 'value' => $fds['care_label'] ?? '-'],
        ['label' => __('projectx::lang.fds_label_shrinkage'), 'value' => $fds['shrinkage_percent'] ?? '-'],
        ['label' => __('projectx::lang.fds_label_country_of_origin'), 'value' => $fds['country_of_origin'] ?? '-'],
        ['label' => __('projectx::lang.fds_label_elongation'), 'value' => $fds['elongation'] ?? '-'],
        ['label' => __('projectx::lang.fds_label_growth'), 'value' => $fds['growth'] ?? '-'],
        ['label' => __('projectx::lang.fds_label_recovery'), 'value' => $fds['recovery'] ?? '-'],
        ['label' => __('projectx::lang.fds_label_elongation_25_fixed'), 'value' => $fds['elongation_25_fixed'] ?? '-'],
        ['label' => __('projectx::lang.fds_label_wool_type'), 'value' => $fds['wool_type'] ?? '-'],
        ['label' => __('projectx::lang.fds_label_raw_material_origin'), 'value' => $fds['raw_material_origin'] ?? '-'],
        ['label' => __('projectx::lang.fds_label_bulk_production_lead_time'), 'value' => $fds['bulk_lead_time_days'] ?? '-'],
        ['label' => __('projectx::lang.fds_label_dyeing_type'), 'value' => $fds['dyeing_type'] ?? '-'],
        ['label' => __('projectx::lang.fds_label_price_500_yds'), 'value' => $fds['price_500_yds'] ?? '-'],
        ['label' => __('projectx::lang.fds_label_price_3k'), 'value' => $fds['price_3k'] ?? '-'],
        ['label' => __('projectx::lang.fds_label_price_10k'), 'value' => $fds['price_10k'] ?? '-'],
        ['label' => __('projectx::lang.fds_label_price_25k'), 'value' => $fds['price_25k'] ?? '-'],
        ['label' => __('projectx::lang.fds_label_price_50k_plus'), 'value' => $fds['price_50k_plus'] ?? '-'],
        ['label' => __('projectx::lang.fds_label_season'), 'value' => $fds['fds_season'] ?? '-'],
        ['label' => __('projectx::lang.fds_label_minimum_order'), 'value' => $fds['minimum_order_quantity'] ?? '-'],
        ['label' => __('projectx::lang.fds_label_minimum_color'), 'value' => $fds['minimum_color_quantity'] ?? '-'],
        ['label' => __('projectx::lang.fds_label_monthly_capacity'), 'value' => $fds['monthly_capacity'] ?? '-'],
    ];
@endphp

<div style="font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #181c32;">
    <table style="width: 100%; border-collapse: collapse; margin-bottom: 16px;">
        <tr>
            <td style="border: 1px solid #d9d9d9; padding: 10px;">
                <div style="font-size: 15px; font-weight: 700;">{{ $fds['title'] ?? __('projectx::lang.fds_document_title') }}</div>
                <div style="font-size: 11px; color: #5e6278; margin-top: 4px;">{{ $fds['subtitle'] ?? __('projectx::lang.fds_document_subtitle') }}</div>
            </td>
            <td style="border: 1px solid #d9d9d9; padding: 10px; width: 220px; text-align: right; vertical-align: top;">
                <div><strong>{{ __('projectx::lang.fds_date') }}:</strong> {{ $fds['date'] ?? '-' }}</div>
                <div style="margin-top: 4px;"><strong>{{ __('projectx::lang.swatch_submit_date') }}:</strong> {{ $fds['swatch_submit_date'] ?? '-' }}</div>
            </td>
        </tr>
    </table>

    <table style="width: 100%; border-collapse: collapse;">
        @foreach($rows as $row)
            @php
                $displayValue = $row['value'] ?? '-';
                $showRow = $displayValue !== '' && $displayValue !== '-';
            @endphp
            @if($showRow)
            <tr>
                <td style="width: 36%; border: 1px solid #d9d9d9; padding: 7px; font-weight: 600; vertical-align: top;">{{ $row['label'] }}</td>
                <td style="border: 1px solid #d9d9d9; padding: 7px; vertical-align: top; white-space: pre-line;">{{ $displayValue }}</td>
            </tr>
            @endif
        @endforeach
    </table>

    <div style="margin-top: 18px; border: 1px solid #d9d9d9; padding: 12px;">
        <div style="font-weight: 700; margin-bottom: 8px;">{{ __('projectx::lang.fds_face_of_fabric') }}</div>
        @if($swatchImage)
            <img src="{{ $swatchImage }}" alt="{{ __('projectx::lang.fds_face_of_fabric') }}" style="max-width: 100%; max-height: 420px;" />
        @else
            <div style="height: 220px; border: 1px dashed #c4c4c4; display: table; width: 100%; text-align: center; color: #7e8299;">
                <div style="display: table-cell; vertical-align: middle;">{{ __('projectx::lang.fds_no_swatch_uploaded') }}</div>
            </div>
        @endif
    </div>
</div>
