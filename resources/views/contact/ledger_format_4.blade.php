@if(!empty($for_pdf))
    <link rel="stylesheet" href="{{ asset('assets/app/css/app.css?v=' . $asset_v) }}">
@endif

@php
    $amount_due       = 0;
    $current_due      = 0;
    $due_1_30_days    = 0;
    $due_30_60_days   = 0;
    $due_60_90_days   = 0;
    $due_over_90_days = 0;
@endphp

<div class="card @if(!empty($for_pdf)) border-0 shadow-none @endif">
    <div class="card-body p-lg-20 @if(!empty($for_pdf)) p-4 @endif">

        {{-- Three-column header --}}
        <div class="row g-3 pb-8 mb-8 border-bottom border-gray-300 align-items-center">
            {{-- Business info (left) --}}
            <div class="col-md-4">
                <div class="fw-bold fs-5 text-gray-900 mb-1">{{ $contact->business->name }}</div>
                <div class="fw-semibold fs-7 text-gray-600">
                    @if(!empty($location))
                        {!! $location->location_address !!}
                    @else
                        {!! $contact->business->business_address !!}
                    @endif
                </div>
            </div>
            {{-- Title (center) --}}
            <div class="col-md-4 text-center">
                <div class="fw-bold fs-1 text-uppercase text-gray-900">@lang('lang_v1.partner_ledger')</div>
            </div>
            {{-- Contact ID + name (right) --}}
            <div class="col-md-4 text-end">
                <div class="fw-semibold fs-7 text-gray-500">{{ $contact->contact_id ?? 'N/A' }}</div>
                <div class="fw-bold fs-5 text-gray-800 mt-1">{{ $contact->name }}</div>
                <div class="fw-semibold fs-7 text-gray-600">@lang('contact.mobile'): {{ $contact->mobile }}</div>
            </div>
        </div>

        {{-- Account Summary (right-aligned) --}}
        <div class="row g-5 mb-10 justify-content-end">
            <div class="col-md-6">
                <div class="border border-gray-300 border-dashed rounded p-5">
                    <div class="fw-bold fs-6 text-gray-700 mb-3">@lang('lang_v1.account_summary')</div>
                    <div class="separator mb-4"></div>
                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <td class="fw-semibold text-gray-600 ps-0 py-1">@lang('lang_v1.opening_balance')</td>
                            <td class="fw-bold text-end text-gray-800 pe-0 py-1">@format_currency($ledger_details['beginning_balance'])</td>
                        </tr>
                        @if(in_array($contact->type, ['supplier', 'both']))
                            <tr>
                                <td class="fw-semibold text-gray-600 ps-0 py-1">@lang('report.total_purchase')</td>
                                <td class="fw-bold text-end text-gray-800 pe-0 py-1">@format_currency($ledger_details['total_purchase'])</td>
                            </tr>
                        @endif
                        @if(in_array($contact->type, ['customer', 'both']))
                            <tr>
                                <td class="fw-semibold text-gray-600 ps-0 py-1">@lang('lang_v1.total_invoice')</td>
                                <td class="fw-bold text-end text-gray-800 pe-0 py-1">@format_currency($ledger_details['total_invoice'])</td>
                            </tr>
                        @endif
                        <tr>
                            <td class="fw-semibold text-gray-600 ps-0 py-1">@lang('sale.total_paid')</td>
                            <td class="fw-bold text-end text-gray-800 pe-0 py-1">@format_currency($ledger_details['total_paid'])</td>
                        </tr>
                        <tr>
                            <td class="fw-semibold text-gray-600 ps-0 py-1">@lang('lang_v1.advance_balance')</td>
                            <td class="fw-bold text-end text-gray-800 pe-0 py-1">@format_currency($contact->balance - $ledger_details['total_reverse_payment'])</td>
                        </tr>
                        @if($ledger_details['ledger_discount'] > 0)
                            <tr>
                                <td class="fw-semibold text-gray-600 ps-0 py-1">@lang('lang_v1.ledger_discount')</td>
                                <td class="fw-bold text-end text-gray-800 pe-0 py-1">@format_currency($ledger_details['ledger_discount'])</td>
                            </tr>
                        @endif
                        <tr class="border-top border-gray-200">
                            <td class="fw-bold text-gray-900 ps-0 pt-3 pb-0">@lang('lang_v1.balance_due')</td>
                            <td class="fw-bold text-end text-danger pt-3 pb-0 pe-0">@format_currency($ledger_details['balance_due'] - $ledger_details['ledger_discount'])</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        {{-- Table heading --}}
        <div class="fw-bold fs-6 text-gray-800 mb-5 text-center">
            @lang('lang_v1.ledger_table_heading', [
                'start_date' => $ledger_details['start_date'],
                'end_date'   => $ledger_details['end_date'],
            ])
        </div>

        {{-- Transactions table --}}
        <div class="table-responsive border-bottom mb-9">
            <table class="table align-middle table-row-dashed fs-6 gy-4 @if(!empty($for_pdf)) table-pdf td-border @endif"
                id="ledger_table">
                <thead>
                    <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0 border-bottom border-gray-200">
                        <th class="min-w-125px">@lang('lang_v1.date')</th>
                        <th class="min-w-80px">@lang('lang_v1.type')</th>
                        <th class="min-w-110px">@lang('purchase.ref_no')</th>
                        <th class="min-w-110px">@lang('lang_v1.payment_method')</th>
                        <th class="min-w-100px text-end">@lang('account.debit')</th>
                        <th class="min-w-100px text-end">@lang('account.credit')</th>
                        <th class="min-w-100px text-end">@lang('lang_v1.balance')</th>
                        <th class="min-w-125px">@lang('report.others')</th>
                    </tr>
                </thead>
                <tbody class="fw-semibold text-gray-600">
                    @foreach($ledger_details['ledger'] as $data)
                        @php
                            if (!empty($data['total_due']) && $data['payment_status'] != 'paid' && !empty($data['due_date'])) {
                                $due_val = $data['total_due'];
                                if (!empty($data['transaction_type']) && $data['transaction_type'] == 'ledger_discount') {
                                    $due_val = -1 * $due_val;
                                }
                                $amount_due += $due_val;
                                $days_diff = $data['due_date']->diffInDays();
                                if ($days_diff == 0) {
                                    $current_due += $due_val;
                                } elseif ($days_diff > 0 && $days_diff <= 30) {
                                    $due_1_30_days += $due_val;
                                } elseif ($days_diff > 30 && $days_diff <= 60) {
                                    $due_30_60_days += $due_val;
                                } elseif ($days_diff > 60 && $days_diff <= 90) {
                                    $due_60_90_days += $due_val;
                                } elseif ($days_diff > 90) {
                                    $due_over_90_days += $due_val;
                                }
                            }
                        @endphp
                        <tr @if(!empty($for_pdf) && $loop->iteration % 2 == 0) class="bg-light" @endif>
                            <td class="text-nowrap">{{ format_datetime_value($data['date']) }}</td>
                            <td>{{ $data['type'] }}</td>
                            <td>{{ $data['ref_no'] }}</td>
                            <td>{{ $data['payment_method'] }}</td>
                            <td class="text-end text-nowrap">
                                @if($data['debit'] != '') @format_currency($data['debit']) @endif
                            </td>
                            <td class="text-end text-nowrap">
                                @if($data['credit'] != '') @format_currency($data['credit']) @endif
                            </td>
                            <td class="text-end text-nowrap fw-bold text-gray-800">{{ $data['balance'] }}</td>
                            <td>{!! $data['others'] !!}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Aging Summary --}}
        <div>
            <div class="fw-bold fs-6 text-gray-800 mb-4">@lang('lang_v1.aging_report')</div>
            <div class="row g-3">
                <div class="col-sm-4 col-lg-2">
                    <div class="border border-gray-300 border-dashed rounded text-center py-3 px-2">
                        <div class="fw-bold fs-6 text-gray-900 mb-1">@format_currency($current_due)</div>
                        <div class="fw-semibold fs-8 text-gray-500 text-uppercase">@lang('lang_v1.current')</div>
                    </div>
                </div>
                <div class="col-sm-4 col-lg-2">
                    <div class="border border-dashed border-success rounded text-center py-3 px-2">
                        <div class="fw-bold fs-6 text-success mb-1">@format_currency($due_1_30_days)</div>
                        <div class="fw-semibold fs-8 text-success text-uppercase">{{ strtoupper(__('lang_v1.1_30_days_past_due')) }}</div>
                    </div>
                </div>
                <div class="col-sm-4 col-lg-2">
                    <div class="border border-dashed border-warning rounded text-center py-3 px-2">
                        <div class="fw-bold fs-6 text-warning mb-1">@format_currency($due_30_60_days)</div>
                        <div class="fw-semibold fs-8 text-warning text-uppercase">{{ strtoupper(__('lang_v1.30_60_days_past_due')) }}</div>
                    </div>
                </div>
                <div class="col-sm-4 col-lg-2">
                    <div class="border border-dashed rounded text-center py-3 px-2" style="border-color:#ffa100!important">
                        <div class="fw-bold fs-6 mb-1" style="color:#ffa100">@format_currency($due_60_90_days)</div>
                        <div class="fw-semibold fs-8 text-uppercase" style="color:#ffa100">{{ strtoupper(__('lang_v1.60_90_days_past_due')) }}</div>
                    </div>
                </div>
                <div class="col-sm-4 col-lg-2">
                    <div class="border border-dashed border-danger rounded text-center py-3 px-2">
                        <div class="fw-bold fs-6 text-danger mb-1">@format_currency($due_over_90_days)</div>
                        <div class="fw-semibold fs-8 text-danger text-uppercase">{{ strtoupper(__('lang_v1.over_90_days_past_due')) }}</div>
                    </div>
                </div>
                <div class="col-sm-4 col-lg-2">
                    <div class="border border-gray-400 border-dashed rounded text-center py-3 px-2 bg-light-primary">
                        <div class="fw-bold fs-6 text-primary mb-1">@format_currency($amount_due)</div>
                        <div class="fw-semibold fs-8 text-primary text-uppercase">{{ strtoupper(__('lang_v1.amount_due')) }}</div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
