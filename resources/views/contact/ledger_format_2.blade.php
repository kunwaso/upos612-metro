@if(!empty($for_pdf))
    <link rel="stylesheet" href="{{ asset('assets/app/css/app.css?v=' . $asset_v) }}">
@endif

@php
    $amount_due      = 0;
    $current_due     = 0;
    $due_1_30_days   = 0;
    $due_30_60_days  = 0;
    $due_60_90_days  = 0;
    $due_over_90_days = 0;
@endphp

<div class="card @if(!empty($for_pdf)) border-0 shadow-none @endif">
    <div class="card-body p-lg-20 @if(!empty($for_pdf)) p-4 @endif">

        {{-- Header --}}
        <div class="d-flex flex-stack pb-8 mb-8 border-bottom border-gray-300">
            {{-- Business info --}}
            <div>
                <div class="fw-bold fs-5 text-gray-900 mb-1">{{ $contact->business->name }}</div>
                <div class="fw-semibold fs-7 text-gray-600">
                    @if(!empty($location))
                        {!! $location->location_address !!}
                    @else
                        {!! $contact->business->business_address !!}
                    @endif
                </div>
            </div>
            {{-- Statement title + date --}}
            <div class="text-end">
                <div class="fw-bold fs-1 text-uppercase text-gray-800 mb-2">@lang('lang_v1.statement')</div>
                <div class="fw-semibold fs-7 text-muted text-uppercase">@lang('lang_v1.date')</div>
                <div class="fw-bold fs-6 text-gray-700">
                    {{ $ledger_details['start_date'] }} &mdash; {{ $ledger_details['end_date'] }}
                </div>
            </div>
        </div>

        {{-- To: contact --}}
        <div class="mb-10">
            <div class="fw-semibold fs-7 text-gray-500 text-uppercase mb-2">@lang('lang_v1.to')</div>
            <div class="fw-bold fs-5 text-gray-800 mb-1">{{ $contact->name }}</div>
            <div class="fw-semibold fs-6 text-gray-600">
                {!! $contact->contact_address !!}
                @if(!empty($contact->email))
                    <br>@lang('business.email'): {{ $contact->email }}
                @endif
                <br>@lang('contact.mobile'): {{ $contact->mobile }}
                @if(!empty($contact->tax_number))
                    <br>@lang('contact.tax_no'): {{ $contact->tax_number }}
                @endif
            </div>
        </div>

        {{-- Transactions table --}}
        <div class="table-responsive border-bottom mb-9">
            <table class="table align-middle table-row-dashed fs-6 gy-4 @if(!empty($for_pdf)) table-pdf td-border @endif"
                id="ledger_table">
                <thead>
                    <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0 border-bottom border-gray-200">
                        <th class="min-w-125px">@lang('lang_v1.date')</th>
                        <th class="min-w-175px">@lang('lang_v1.transaction')</th>
                        <th class="min-w-100px text-end">@lang('sale.amount')</th>
                        <th class="min-w-100px text-end">@lang('lang_v1.balance')</th>
                    </tr>
                </thead>
                <tbody class="fw-semibold text-gray-600">
                    @foreach($ledger_details['ledger'] as $data)
                        @php
                            if (empty($data['total_due'])) {
                                // still render row but skip aging calc below
                            } else {
                                if ($data['payment_status'] != 'paid' && !empty($data['due_date'])) {
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
                            }
                        @endphp
                        @if(!empty($data['total_due']))
                            <tr @if(!empty($for_pdf) && $loop->iteration % 2 == 0) class="bg-light" @endif>
                                <td class="text-nowrap">{{ format_datetime_value($data['date']) }}</td>
                                <td>
                                    @if($loop->index == 0) {{ $data['type'] }} @endif
                                    {{ $data['ref_no'] }}
                                    @if(!empty($data['due_date']) && $data['payment_status'] != 'paid')
                                        <br><span class="text-muted fs-7">@lang('lang_v1.due') {{ format_date_value($data['due_date']) }}</span>
                                    @endif
                                </td>
                                <td class="text-end text-nowrap">@format_currency($data['final_total'])</td>
                                <td class="text-end text-nowrap fw-bold text-gray-800">@format_currency($data['total_due'])</td>
                            </tr>
                        @endif
                    @endforeach

                    {{-- Padding rows if very few entries (print/PDF layout) --}}
                    @if(count($ledger_details['ledger']) < 5)
                        @for($i = 0; $i < 5; $i++)
                            <tr><td colspan="4" class="border-0 py-2">&nbsp;</td></tr>
                        @endfor
                    @endif
                </tbody>
            </table>
        </div>

        {{-- Aging Summary --}}
        <div class="mb-2">
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
