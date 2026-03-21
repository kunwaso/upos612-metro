@if(!empty($for_pdf))
    <link rel="stylesheet" href="{{ asset('assets/app/css/app.css?v=' . $asset_v) }}">
@endif

<div class="card @if(!empty($for_pdf)) border-0 shadow-none @endif">
    <div class="card-body p-lg-20 @if(!empty($for_pdf)) p-4 @endif">

        {{-- Header: Business name + address --}}
        <div class="d-flex flex-stack pb-8 mb-8 border-bottom border-gray-300">
            <div>
                <div class="fw-bold fs-3 text-gray-800">@lang('lang_v1.account_statement')</div>
                <div class="fw-semibold fs-6 text-gray-500">
                    {{ $ledger_details['start_date'] }} &mdash; {{ $ledger_details['end_date'] }}
                </div>
            </div>
            <div class="text-end">
                <div class="fw-bold fs-5 text-gray-900">{{ $contact->business->name }}</div>
                <div class="fw-semibold fs-7 text-gray-600 mt-1">
                    @if(!empty($location))
                        {!! $location->location_address !!}
                    @else
                        {!! $contact->business->business_address !!}
                    @endif
                </div>
            </div>
        </div>

        {{-- Contact info + Account Summary --}}
        <div class="row g-5 mb-10">
            {{-- To: (contact) --}}
            <div class="col-md-6">
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

            {{-- Account Summary --}}
            <div class="col-md-6">
                <div class="border border-gray-300 border-dashed rounded p-5">
                    <div class="fw-bold fs-6 text-gray-700 mb-3">@lang('lang_v1.account_summary')</div>
                    <div class="fw-semibold fs-7 text-gray-500 mb-3">
                        {{ $ledger_details['start_date'] }} &mdash; {{ $ledger_details['end_date'] }}
                    </div>
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
        <div class="table-responsive">
            <table class="table align-middle table-row-dashed fs-6 gy-4 @if(!empty($for_pdf)) table-pdf td-border @endif"
                id="ledger_table">
                <thead>
                    <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0 border-bottom border-gray-200">
                        <th class="min-w-125px">@lang('lang_v1.date')</th>
                        <th class="min-w-90px">@lang('purchase.ref_no')</th>
                        <th class="min-w-80px">@lang('lang_v1.type')</th>
                        <th class="min-w-100px">@lang('sale.location')</th>
                        <th class="min-w-90px">@lang('sale.payment_status')</th>
                        <th class="min-w-100px text-end">@lang('account.debit')</th>
                        <th class="min-w-100px text-end">@lang('account.credit')</th>
                        <th class="min-w-100px text-end">@lang('lang_v1.balance')</th>
                        <th class="min-w-90px">@lang('lang_v1.payment_method')</th>
                        <th class="min-w-125px">@lang('report.others')</th>
                    </tr>
                </thead>
                <tbody class="fw-semibold text-gray-600">
                    @foreach($ledger_details['ledger'] as $data)
                        {{-- Transaction row --}}
                        <tr @if(!empty($data['transaction_type']) && in_array($data['transaction_type'], ['sell', 'purchase']))
                                class="bg-light @if(!empty($for_pdf)) text-dark @endif"
                            @elseif(!empty($for_pdf) && $loop->iteration % 2 == 0)
                                class="bg-light"
                            @endif>
                            <td class="text-nowrap">{{ format_datetime_value($data['date']) }}</td>
                            <td>{{ $data['ref_no'] }}</td>
                            <td>{{ $data['type'] }}</td>
                            <td>{{ $data['location'] }}</td>
                            <td>
                                @if(!empty($data['payment_status']))
                                    @php
                                        $ps = $data['payment_status'];
                                        $badgeClass = $ps === 'paid' ? 'success' : ($ps === 'partial' ? 'warning' : 'danger');
                                    @endphp
                                    <span class="badge badge-light-{{ $badgeClass }}">{{ $ps }}</span>
                                @endif
                            </td>
                            <td class="text-end text-nowrap">
                                @if($data['debit'] != '') @format_currency($data['debit']) @endif
                            </td>
                            <td class="text-end text-nowrap">
                                @if($data['credit'] != '') @format_currency($data['credit']) @endif
                            </td>
                            <td class="text-end text-nowrap fw-bold text-gray-800">{{ $data['balance'] }}</td>
                            <td>{{ $data['payment_method'] }}</td>
                            <td>
                                {!! $data['others'] !!}
                                @if(!empty($is_admin) && !empty($data['transaction_id']) && $data['transaction_type'] == 'ledger_discount')
                                    <div class="d-flex gap-1 mt-1">
                                        <button type="button"
                                            class="btn btn-icon btn-sm btn-light-danger delete_ledger_discount"
                                            data-href="{{ action([\App\Http\Controllers\LedgerDiscountController::class, 'destroy'], ['ledger_discount' => $data['transaction_id']]) }}">
                                            <i class="ki-duotone ki-trash fs-4"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span></i>
                                        </button>
                                        <button type="button"
                                            class="btn btn-icon btn-sm btn-light-primary btn-modal"
                                            data-href="{{ action([\App\Http\Controllers\LedgerDiscountController::class, 'edit'], ['ledger_discount' => $data['transaction_id']]) }}"
                                            data-container="#edit_ledger_discount_modal">
                                            <i class="ki-duotone ki-pencil fs-4"><span class="path1"></span><span class="path2"></span></i>
                                        </button>
                                    </div>
                                @endif
                            </td>
                        </tr>

                        {{-- Expanded sell lines --}}
                        @if(!empty($data['transaction_type']) && $data['transaction_type'] == 'sell')
                            <tr>
                                <td colspan="10" class="bg-light px-5 pb-4 pt-0">
                                    @include('sale_pos.partials.sale_line_details', [
                                        'sell'                => (object) $data,
                                        'enabled_modules'     => [],
                                        'is_warranty_enabled' => false,
                                        'for_ledger'          => true,
                                    ])
                                </td>
                            </tr>
                        @endif

                        {{-- Expanded purchase lines --}}
                        @if(!empty($data['transaction_type']) && $data['transaction_type'] == 'purchase')
                            <tr>
                                <td colspan="10" class="bg-light px-5 pb-4 pt-0">
                                    @include('contact.partials.ledger_purchase_lines_details', [
                                        'purchase' => (object) $data,
                                    ])
                                </td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>

    </div>
</div>
