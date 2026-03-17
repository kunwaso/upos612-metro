<div style="font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #181c32;">
    <table style="width: 100%; border-collapse: collapse; margin-bottom: 16px;">
        <tr>
            <td style="border: 1px solid #d9d9d9; padding: 10px;">
                <div style="font-size: 15px; font-weight: 700;">{{ data_get($fds ?? [], 'title', __('projectx::lang.trim_datasheet_document_title')) }}</div>
                <div style="font-size: 11px; color: #5e6278; margin-top: 4px;">{{ data_get($fds ?? [], 'subtitle', __('projectx::lang.trim_datasheet_document_subtitle')) }}</div>
            </td>
            <td style="border: 1px solid #d9d9d9; padding: 10px; width: 220px; text-align: right; vertical-align: top;">
                <div><strong>{{ __('projectx::lang.fds_date') }}:</strong> {{ data_get($fds ?? [], 'date', '-') }}</div>
                <div style="margin-top: 4px;"><strong>{{ __('projectx::lang.updated_at') }}:</strong> {{ data_get($fds ?? [], 'updated_at', data_get($fds ?? [], 'approved_at', '-')) }}</div>
            </td>
        </tr>
    </table>

    <table style="width: 100%; border-collapse: collapse;">
        @if(filled(data_get($fds ?? [], 'name')) && data_get($fds ?? [], 'name') !== '-')
            <tr>
                <td style="width: 36%; border: 1px solid #d9d9d9; padding: 7px; font-weight: 600; vertical-align: top;">{{ __('projectx::lang.trim_name') }}</td>
                <td style="border: 1px solid #d9d9d9; padding: 7px; vertical-align: top; white-space: pre-line;">{{ data_get($fds ?? [], 'name') }}</td>
            </tr>
        @endif
        @if(filled(data_get($fds ?? [], 'part_number')) && data_get($fds ?? [], 'part_number') !== '-')
            <tr>
                <td style="width: 36%; border: 1px solid #d9d9d9; padding: 7px; font-weight: 600; vertical-align: top;">{{ __('projectx::lang.part_number') }}</td>
                <td style="border: 1px solid #d9d9d9; padding: 7px; vertical-align: top; white-space: pre-line;">{{ data_get($fds ?? [], 'part_number') }}</td>
            </tr>
        @endif
        @if(filled(data_get($fds ?? [], 'trim_category')) && data_get($fds ?? [], 'trim_category') !== '-')
            <tr>
                <td style="width: 36%; border: 1px solid #d9d9d9; padding: 7px; font-weight: 600; vertical-align: top;">{{ __('projectx::lang.trim_category') }}</td>
                <td style="border: 1px solid #d9d9d9; padding: 7px; vertical-align: top; white-space: pre-line;">{{ data_get($fds ?? [], 'trim_category') }}</td>
            </tr>
        @endif
        @if(filled(data_get($fds ?? [], 'category_group')) && data_get($fds ?? [], 'category_group') !== '-')
            <tr>
                <td style="width: 36%; border: 1px solid #d9d9d9; padding: 7px; font-weight: 600; vertical-align: top;">{{ __('projectx::lang.category_group') }}</td>
                <td style="border: 1px solid #d9d9d9; padding: 7px; vertical-align: top; white-space: pre-line;">{{ data_get($fds ?? [], 'category_group') }}</td>
            </tr>
        @endif
        @if(filled(data_get($fds ?? [], 'description')) && data_get($fds ?? [], 'description') !== '-')
            <tr>
                <td style="width: 36%; border: 1px solid #d9d9d9; padding: 7px; font-weight: 600; vertical-align: top;">{{ __('projectx::lang.description') }}</td>
                <td style="border: 1px solid #d9d9d9; padding: 7px; vertical-align: top; white-space: pre-line;">{{ data_get($fds ?? [], 'description') }}</td>
            </tr>
        @endif
        @if(filled(data_get($fds ?? [], 'status_label')) && data_get($fds ?? [], 'status_label') !== '-')
            <tr>
                <td style="width: 36%; border: 1px solid #d9d9d9; padding: 7px; font-weight: 600; vertical-align: top;">{{ __('projectx::lang.status') }}</td>
                <td style="border: 1px solid #d9d9d9; padding: 7px; vertical-align: top; white-space: pre-line;">{{ data_get($fds ?? [], 'status_label') }}</td>
            </tr>
        @endif

        @if(filled(data_get($fds ?? [], 'material')) && data_get($fds ?? [], 'material') !== '-')
            <tr>
                <td style="width: 36%; border: 1px solid #d9d9d9; padding: 7px; font-weight: 600; vertical-align: top;">{{ __('projectx::lang.material') }}</td>
                <td style="border: 1px solid #d9d9d9; padding: 7px; vertical-align: top; white-space: pre-line;">{{ data_get($fds ?? [], 'material') }}</td>
            </tr>
        @endif
        @if(filled(data_get($fds ?? [], 'color_value')) && data_get($fds ?? [], 'color_value') !== '-')
            <tr>
                <td style="width: 36%; border: 1px solid #d9d9d9; padding: 7px; font-weight: 600; vertical-align: top;">{{ __('projectx::lang.color_value') }}</td>
                <td style="border: 1px solid #d9d9d9; padding: 7px; vertical-align: top; white-space: pre-line;">{{ data_get($fds ?? [], 'color_value') }}</td>
            </tr>
        @endif
        @if(filled(data_get($fds ?? [], 'size_dimension')) && data_get($fds ?? [], 'size_dimension') !== '-')
            <tr>
                <td style="width: 36%; border: 1px solid #d9d9d9; padding: 7px; font-weight: 600; vertical-align: top;">{{ __('projectx::lang.size_dimension') }}</td>
                <td style="border: 1px solid #d9d9d9; padding: 7px; vertical-align: top; white-space: pre-line;">{{ data_get($fds ?? [], 'size_dimension') }}</td>
            </tr>
        @endif
        @if(filled(data_get($fds ?? [], 'unit_of_measure')) && data_get($fds ?? [], 'unit_of_measure') !== '-')
            <tr>
                <td style="width: 36%; border: 1px solid #d9d9d9; padding: 7px; font-weight: 600; vertical-align: top;">{{ __('projectx::lang.unit_of_measure') }}</td>
                <td style="border: 1px solid #d9d9d9; padding: 7px; vertical-align: top; white-space: pre-line;">{{ data_get($fds ?? [], 'unit_of_measure') }}</td>
            </tr>
        @endif
        @if(filled(data_get($fds ?? [], 'placement')) && data_get($fds ?? [], 'placement') !== '-')
            <tr>
                <td style="width: 36%; border: 1px solid #d9d9d9; padding: 7px; font-weight: 600; vertical-align: top;">{{ __('projectx::lang.placement') }}</td>
                <td style="border: 1px solid #d9d9d9; padding: 7px; vertical-align: top; white-space: pre-line;">{{ data_get($fds ?? [], 'placement') }}</td>
            </tr>
        @endif
        @if(filled(data_get($fds ?? [], 'quantity_per_garment')) && data_get($fds ?? [], 'quantity_per_garment') !== '-')
            <tr>
                <td style="width: 36%; border: 1px solid #d9d9d9; padding: 7px; font-weight: 600; vertical-align: top;">{{ __('projectx::lang.quantity_per_garment') }}</td>
                <td style="border: 1px solid #d9d9d9; padding: 7px; vertical-align: top; white-space: pre-line;">{{ data_get($fds ?? [], 'quantity_per_garment') }}</td>
            </tr>
        @endif
        @if(filled(data_get($fds ?? [], 'label_sub_type')) && data_get($fds ?? [], 'label_sub_type') !== '-')
            <tr>
                <td style="width: 36%; border: 1px solid #d9d9d9; padding: 7px; font-weight: 600; vertical-align: top;">{{ __('projectx::lang.label_sub_type') }}</td>
                <td style="border: 1px solid #d9d9d9; padding: 7px; vertical-align: top; white-space: pre-line;">{{ data_get($fds ?? [], 'label_sub_type') }}</td>
            </tr>
        @endif
        @if(filled(data_get($fds ?? [], 'purpose')) && data_get($fds ?? [], 'purpose') !== '-')
            <tr>
                <td style="width: 36%; border: 1px solid #d9d9d9; padding: 7px; font-weight: 600; vertical-align: top;">{{ __('projectx::lang.purpose') }}</td>
                <td style="border: 1px solid #d9d9d9; padding: 7px; vertical-align: top; white-space: pre-line;">{{ data_get($fds ?? [], 'purpose') }}</td>
            </tr>
        @endif

        @if(filled(data_get($fds ?? [], 'button_ligne')) && data_get($fds ?? [], 'button_ligne') !== '-')
            <tr>
                <td style="width: 36%; border: 1px solid #d9d9d9; padding: 7px; font-weight: 600; vertical-align: top;">{{ __('projectx::lang.button_ligne') }}</td>
                <td style="border: 1px solid #d9d9d9; padding: 7px; vertical-align: top; white-space: pre-line;">{{ data_get($fds ?? [], 'button_ligne') }}</td>
            </tr>
        @endif
        @if(filled(data_get($fds ?? [], 'button_holes')) && data_get($fds ?? [], 'button_holes') !== '-')
            <tr>
                <td style="width: 36%; border: 1px solid #d9d9d9; padding: 7px; font-weight: 600; vertical-align: top;">{{ __('projectx::lang.button_holes') }}</td>
                <td style="border: 1px solid #d9d9d9; padding: 7px; vertical-align: top; white-space: pre-line;">{{ data_get($fds ?? [], 'button_holes') }}</td>
            </tr>
        @endif
        @if(filled(data_get($fds ?? [], 'button_material')) && data_get($fds ?? [], 'button_material') !== '-')
            <tr>
                <td style="width: 36%; border: 1px solid #d9d9d9; padding: 7px; font-weight: 600; vertical-align: top;">{{ __('projectx::lang.button_material') }}</td>
                <td style="border: 1px solid #d9d9d9; padding: 7px; vertical-align: top; white-space: pre-line;">{{ data_get($fds ?? [], 'button_material') }}</td>
            </tr>
        @endif
        @if(filled(data_get($fds ?? [], 'zipper_type')) && data_get($fds ?? [], 'zipper_type') !== '-')
            <tr>
                <td style="width: 36%; border: 1px solid #d9d9d9; padding: 7px; font-weight: 600; vertical-align: top;">{{ __('projectx::lang.zipper_type') }}</td>
                <td style="border: 1px solid #d9d9d9; padding: 7px; vertical-align: top; white-space: pre-line;">{{ data_get($fds ?? [], 'zipper_type') }}</td>
            </tr>
        @endif
        @if(filled(data_get($fds ?? [], 'zipper_slider')) && data_get($fds ?? [], 'zipper_slider') !== '-')
            <tr>
                <td style="width: 36%; border: 1px solid #d9d9d9; padding: 7px; font-weight: 600; vertical-align: top;">{{ __('projectx::lang.zipper_slider') }}</td>
                <td style="border: 1px solid #d9d9d9; padding: 7px; vertical-align: top; white-space: pre-line;">{{ data_get($fds ?? [], 'zipper_slider') }}</td>
            </tr>
        @endif
        @if(filled(data_get($fds ?? [], 'interlining_type')) && data_get($fds ?? [], 'interlining_type') !== '-')
            <tr>
                <td style="width: 36%; border: 1px solid #d9d9d9; padding: 7px; font-weight: 600; vertical-align: top;">{{ __('projectx::lang.interlining_type') }}</td>
                <td style="border: 1px solid #d9d9d9; padding: 7px; vertical-align: top; white-space: pre-line;">{{ data_get($fds ?? [], 'interlining_type') }}</td>
            </tr>
        @endif

        @if(filled(data_get($fds ?? [], 'supplier')) && data_get($fds ?? [], 'supplier') !== '-')
            <tr>
                <td style="width: 36%; border: 1px solid #d9d9d9; padding: 7px; font-weight: 600; vertical-align: top;">{{ __('projectx::lang.supplier') }}</td>
                <td style="border: 1px solid #d9d9d9; padding: 7px; vertical-align: top; white-space: pre-line;">{{ data_get($fds ?? [], 'supplier') }}</td>
            </tr>
        @endif
        @if(filled(data_get($fds ?? [], 'unit_cost')) && data_get($fds ?? [], 'unit_cost') !== '-')
            <tr>
                <td style="width: 36%; border: 1px solid #d9d9d9; padding: 7px; font-weight: 600; vertical-align: top;">{{ __('projectx::lang.unit_cost') }}</td>
                <td style="border: 1px solid #d9d9d9; padding: 7px; vertical-align: top; white-space: pre-line;">{{ data_get($fds ?? [], 'unit_cost') }}</td>
            </tr>
        @endif
        @if(filled(data_get($fds ?? [], 'currency')) && data_get($fds ?? [], 'currency') !== '-')
            <tr>
                <td style="width: 36%; border: 1px solid #d9d9d9; padding: 7px; font-weight: 600; vertical-align: top;">{{ __('projectx::lang.currency_label') }}</td>
                <td style="border: 1px solid #d9d9d9; padding: 7px; vertical-align: top; white-space: pre-line;">{{ data_get($fds ?? [], 'currency') }}</td>
            </tr>
        @endif
        @if(filled(data_get($fds ?? [], 'lead_time_days')) && data_get($fds ?? [], 'lead_time_days') !== '-')
            <tr>
                <td style="width: 36%; border: 1px solid #d9d9d9; padding: 7px; font-weight: 600; vertical-align: top;">{{ __('projectx::lang.lead_time_days') }}</td>
                <td style="border: 1px solid #d9d9d9; padding: 7px; vertical-align: top; white-space: pre-line;">{{ data_get($fds ?? [], 'lead_time_days') }}</td>
            </tr>
        @endif

        @if(filled(data_get($fds ?? [], 'care_testing')) && data_get($fds ?? [], 'care_testing') !== '-')
            <tr>
                <td style="width: 36%; border: 1px solid #d9d9d9; padding: 7px; font-weight: 600; vertical-align: top;">{{ __('projectx::lang.care_testing') }}</td>
                <td style="border: 1px solid #d9d9d9; padding: 7px; vertical-align: top; white-space: pre-line;">{{ data_get($fds ?? [], 'care_testing') }}</td>
            </tr>
        @endif
        @if(filled(data_get($fds ?? [], 'quality_notes')) && data_get($fds ?? [], 'quality_notes') !== '-')
            <tr>
                <td style="width: 36%; border: 1px solid #d9d9d9; padding: 7px; font-weight: 600; vertical-align: top;">{{ __('projectx::lang.quality_notes') }}</td>
                <td style="border: 1px solid #d9d9d9; padding: 7px; vertical-align: top; white-space: pre-line;">{{ data_get($fds ?? [], 'quality_notes') }}</td>
            </tr>
        @endif
        @if(filled(data_get($fds ?? [], 'color_fastness')) && data_get($fds ?? [], 'color_fastness') !== '-')
            <tr>
                <td style="width: 36%; border: 1px solid #d9d9d9; padding: 7px; font-weight: 600; vertical-align: top;">{{ __('projectx::lang.color_fastness') }}</td>
                <td style="border: 1px solid #d9d9d9; padding: 7px; vertical-align: top; white-space: pre-line;">{{ data_get($fds ?? [], 'color_fastness') }}</td>
            </tr>
        @endif
        @if(filled(data_get($fds ?? [], 'shrinkage')) && data_get($fds ?? [], 'shrinkage') !== '-')
            <tr>
                <td style="width: 36%; border: 1px solid #d9d9d9; padding: 7px; font-weight: 600; vertical-align: top;">{{ __('projectx::lang.shrinkage') }}</td>
                <td style="border: 1px solid #d9d9d9; padding: 7px; vertical-align: top; white-space: pre-line;">{{ data_get($fds ?? [], 'shrinkage') }}</td>
            </tr>
        @endif
        @if(filled(data_get($fds ?? [], 'rust_proof')) && data_get($fds ?? [], 'rust_proof') !== '-')
            <tr>
                <td style="width: 36%; border: 1px solid #d9d9d9; padding: 7px; font-weight: 600; vertical-align: top;">{{ __('projectx::lang.rust_proof') }}</td>
                <td style="border: 1px solid #d9d9d9; padding: 7px; vertical-align: top; white-space: pre-line;">{{ data_get($fds ?? [], 'rust_proof') }}</td>
            </tr>
        @endif
        @if(filled(data_get($fds ?? [], 'comfort_notes')) && data_get($fds ?? [], 'comfort_notes') !== '-')
            <tr>
                <td style="width: 36%; border: 1px solid #d9d9d9; padding: 7px; font-weight: 600; vertical-align: top;">{{ __('projectx::lang.comfort_notes') }}</td>
                <td style="border: 1px solid #d9d9d9; padding: 7px; vertical-align: top; white-space: pre-line;">{{ data_get($fds ?? [], 'comfort_notes') }}</td>
            </tr>
        @endif

        @if(filled(data_get($fds ?? [], 'approved_at')) && data_get($fds ?? [], 'approved_at') !== '-')
            <tr>
                <td style="width: 36%; border: 1px solid #d9d9d9; padding: 7px; font-weight: 600; vertical-align: top;">{{ __('projectx::lang.approved_at') }}</td>
                <td style="border: 1px solid #d9d9d9; padding: 7px; vertical-align: top; white-space: pre-line;">{{ data_get($fds ?? [], 'approved_at') }}</td>
            </tr>
        @endif
        @if(filled(data_get($fds ?? [], 'qc_at')) && data_get($fds ?? [], 'qc_at') !== '-')
            <tr>
                <td style="width: 36%; border: 1px solid #d9d9d9; padding: 7px; font-weight: 600; vertical-align: top;">{{ __('projectx::lang.qc_at') }}</td>
                <td style="border: 1px solid #d9d9d9; padding: 7px; vertical-align: top; white-space: pre-line;">{{ data_get($fds ?? [], 'qc_at') }}</td>
            </tr>
        @endif
        @if(filled(data_get($fds ?? [], 'qc_notes')) && data_get($fds ?? [], 'qc_notes') !== '-')
            <tr>
                <td style="width: 36%; border: 1px solid #d9d9d9; padding: 7px; font-weight: 600; vertical-align: top;">{{ __('projectx::lang.qc_notes') }}</td>
                <td style="border: 1px solid #d9d9d9; padding: 7px; vertical-align: top; white-space: pre-line;">{{ data_get($fds ?? [], 'qc_notes') }}</td>
            </tr>
        @endif
    </table>

    <div style="margin-top: 18px; border: 1px solid #d9d9d9; padding: 12px;">
        <div style="font-weight: 700; margin-bottom: 8px;">{{ __('projectx::lang.trim_image') }}</div>

        @if(data_get($fds ?? [], 'context', 'public') === 'pdf')
            @if(filled(data_get($fds ?? [], 'image_path')) || filled(data_get($fds ?? [], 'swatch_public_path')) || filled(data_get($fds ?? [], 'image_public_path')))
                <img src="{{ data_get($fds ?? [], 'image_path', data_get($fds ?? [], 'swatch_public_path', data_get($fds ?? [], 'image_public_path'))) }}" alt="{{ __('projectx::lang.trim_image') }}" style="max-width: 100%; max-height: 420px;" />
            @else
                <div style="height: 220px; border: 1px dashed #c4c4c4; display: table; width: 100%; text-align: center; color: #7e8299;">
                    <div style="display: table-cell; vertical-align: middle;">{{ __('projectx::lang.no_trim_image_uploaded') }}</div>
                </div>
            @endif
        @else
            @if(filled(data_get($fds ?? [], 'image_public_path')) || filled(data_get($fds ?? [], 'swatch_public_path')) || filled(data_get($fds ?? [], 'image_path')))
                <img src="{{ data_get($fds ?? [], 'image_public_path', data_get($fds ?? [], 'swatch_public_path', data_get($fds ?? [], 'image_path'))) }}" alt="{{ __('projectx::lang.trim_image') }}" style="max-width: 100%; max-height: 420px;" />
            @else
                <div style="height: 220px; border: 1px dashed #c4c4c4; display: table; width: 100%; text-align: center; color: #7e8299;">
                    <div style="display: table-cell; vertical-align: middle;">{{ __('projectx::lang.no_trim_image_uploaded') }}</div>
                </div>
            @endif
        @endif
    </div>
</div>
