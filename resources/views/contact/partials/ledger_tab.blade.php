@php
    $transaction_types = [];
    if(in_array($contact->type, ['both', 'supplier'])){
        $transaction_types['purchase'] = __('lang_v1.purchase');
        $transaction_types['purchase_return'] = __('lang_v1.purchase_return');
    }

    if(in_array($contact->type, ['both', 'customer'])){
        $transaction_types['sell'] = __('sale.sale');
        $transaction_types['sell_return'] = __('lang_v1.sell_return');
    }

    $transaction_types['opening_balance'] = __('lang_v1.opening_balance');
@endphp
<style>
    .ledger-format-label {
        cursor: pointer;
        margin-bottom: 0;
    }
    .ledger-format-label:has(input:checked) {
        color: var(--bs-primary) !important;
        border-color: var(--bs-primary) !important;
        background-color: var(--bs-primary-light, rgba(0, 158, 247, 0.08)) !important;
    }
</style>

<div class="mb-10 p-10">
    {{-- Filters + actions --}}
    <div class="row g-4 g-lg-5 align-items-end mb-8 mb-lg-10">
        <div class="col-12 col-md-6 col-xl-3">
            {!! Form::label('ledger_date_range', __('report.date_range'), ['class' => 'form-label fw-semibold fs-6 text-gray-700']) !!}
            {!! Form::text('ledger_date_range', null, [
                'placeholder' => __('lang_v1.select_a_date_range'),
                'class' => 'form-control form-control-solid',
                'readonly' => true,
                'id' => 'ledger_date_range',
            ]) !!}
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            {!! Form::label('ledger_location', __('purchase.business_location'), ['class' => 'form-label fw-semibold fs-6 text-gray-700']) !!}
            {!! Form::select('ledger_location', $business_locations, null, [
                'class' => 'form-select form-select-solid select2',
                'id' => 'ledger_location',
            ]) !!}
        </div>
        <div class="col-12 col-xl-6">
            <div class="d-flex flex-wrap justify-content-xl-end gap-2">
                <button type="button"
                    data-href="{{ action([\App\Http\Controllers\ContactController::class, 'getLedger']) }}?contact_id={{ $contact->id }}&action=pdf"
                    class="btn btn-sm btn-light-danger"
                    id="print_ledger_pdf"
                    title="@lang('lang_v1.download_pdf')">
                    <i class="ki-duotone ki-file-down fs-3">
                        <span class="path1"></span><span class="path2"></span>
                    </i>
                </button>
                <button type="button" class="btn btn-sm btn-light-primary" id="send_ledger" title="@lang('lang_v1.send_ledger')">
                    <i class="ki-duotone ki-sms fs-3">
                        <span class="path1"></span><span class="path2"></span>
                    </i>
                </button>
            </div>
        </div>
    </div>

    {{-- Ledger format: Metronic nav + btn tiles (see public/html/index.html widget nav) --}}
    <label class="form-label fw-semibold fs-6 text-gray-700 d-block mb-4">@lang('lang_v1.ledger_format')</label>
    <ul class="nav row g-3 mb-8 mb-lg-10" role="tablist">
        <li class="nav-item col-6 col-md-3">
            <label class="nav-link btn btn-flex btn-color-muted btn-outline btn-outline-default btn-active-primary d-flex flex-grow-1 flex-column flex-center py-5 min-h-125px ledger-format-label">
                <input type="radio" name="ledger_format" value="format_1" class="d-none" checked autocomplete="off">
                <i class="ki-duotone ki-abstract-26 fs-2x mb-3 mx-0">
                    <span class="path1"></span><span class="path2"></span>
                </i>
                <span class="fs-6 fw-bold text-center">@lang('lang_v1.format_1')</span>
            </label>
        </li>
        <li class="nav-item col-6 col-md-3">
            <label class="nav-link btn btn-flex btn-color-muted btn-outline btn-outline-default btn-active-primary d-flex flex-grow-1 flex-column flex-center py-5 min-h-125px ledger-format-label">
                <input type="radio" name="ledger_format" value="format_2" class="d-none" autocomplete="off">
                <i class="ki-duotone ki-element-11 fs-2x mb-3 mx-0">
                    <span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span>
                </i>
                <span class="fs-6 fw-bold text-center">@lang('lang_v1.format_2')</span>
            </label>
        </li>
        <li class="nav-item col-6 col-md-3">
            <label class="nav-link btn btn-flex btn-color-muted btn-outline btn-outline-default btn-active-primary d-flex flex-grow-1 flex-column flex-center py-5 min-h-125px ledger-format-label">
                <input type="radio" name="ledger_format" value="format_3" class="d-none" autocomplete="off">
                <i class="ki-duotone ki-briefcase fs-2x mb-3 mx-0">
                    <span class="path1"></span><span class="path2"></span>
                </i>
                <span class="fs-6 fw-bold text-center">@lang('lang_v1.format_3')</span>
            </label>
        </li>
        <li class="nav-item col-6 col-md-3">
            <label class="nav-link btn btn-flex btn-color-muted btn-outline btn-outline-default btn-active-primary d-flex flex-grow-1 flex-column flex-center py-5 min-h-125px ledger-format-label">
                <input type="radio" name="ledger_format" value="format_4" class="d-none" autocomplete="off">
                <i class="ki-duotone ki-chart-simple fs-2x mb-3 mx-0">
                    <span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span>
                </i>
                <span class="fs-6 fw-bold text-center">@lang('lang_v1.format_4')</span>
            </label>
        </li>
    </ul>

    <div id="contact_ledger_div"></div>
</div>