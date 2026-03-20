<table class="table table-row-dashed table-row-gray-300 align-middle gs-0 gy-4 ajax_view" id="purchase_table" style="width: 100%;">
    <thead>
        <tr class="fw-semibold text-gray-800 border-bottom border-gray-200">
            <th>@lang('messages.action')</th>
            <th>@lang('messages.date')</th>
            <th>@lang('purchase.ref_no')</th>
            <th>@lang('purchase.location')</th>
            <th>@lang('purchase.supplier')</th>
            <th>@lang('purchase.purchase_status')</th>
            <th>@lang('purchase.payment_status')</th>
            <th>@lang('purchase.grand_total')</th>
            <th>@lang('purchase.payment_due') &nbsp;&nbsp;<i class="fas fa-info-circle text-primary no-print" data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="bottom" title="{{ __('messages.purchase_due_tooltip') }}" aria-hidden="true"></i></th>
            <th>{{ ($purchase_custom_labels ?? [])['custom_field_1'] ?? '' }}</th>
            <th>{{ ($purchase_custom_labels ?? [])['custom_field_2'] ?? '' }}</th>
            <th>{{ ($purchase_custom_labels ?? [])['custom_field_3'] ?? '' }}</th>
            <th>{{ ($purchase_custom_labels ?? [])['custom_field_4'] ?? '' }}</th>
            <th>@lang('lang_v1.added_by')</th>
        </tr>
    </thead>
    <tfoot>
        <tr class="fw-bold text-gray-700 border-top border-gray-300 footer-total">
            <td colspan="5"><strong>@lang('sale.total'):</strong></td>
            <td class="footer_status_count"></td>
            <td class="footer_payment_status_count"></td>
            <td class="footer_purchase_total"></td>
            <td class="text-start"><small>@lang('report.purchase_due') - <span class="footer_total_due"></span><br>
            @lang('lang_v1.purchase_return') - <span class="footer_total_purchase_return_due"></span>
            </small></td>
            <td colspan="5"></td>
        </tr>
    </tfoot>
</table>
