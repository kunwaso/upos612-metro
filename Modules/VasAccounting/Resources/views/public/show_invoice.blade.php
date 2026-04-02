@extends('layouts.guest_metronic')
@section('title', $title)

@section('content')
<div class="row justify-content-center">
    <div class="col-12 col-xl-10">
        <div class="d-flex justify-content-end gap-3 mb-6 no-print">
            @if(!empty($payment_link))
                <a href="{{ $payment_link }}" class="btn btn-light-success">
                    <i class="ki-outline ki-dollar fs-5 me-1"></i>@lang('lang_v1.pay')
                </a>
            @endif
            <button type="button" class="btn btn-primary" id="print_invoice" aria-label="Print">
                <i class="ki-outline ki-printer fs-5 me-1"></i>@lang('messages.print')
            </button>
        </div>

        <div id="invoice_content" class="card card-flush shadow-sm">
            <div class="card-body p-8">
                <div class="row g-6 align-items-center mb-8">
                    <div class="col-md-4 text-center text-md-start">
                        @if(!empty($business->logo))
                            <img src="{{ asset('uploads/business_logos/' . $business->logo) }}" alt="Logo" class="mw-100 h-70px object-fit-contain">
                        @endif
                    </div>
                    <div class="col-md-8">
                        <div class="text-gray-900 fw-bold fs-3">{{ $business->name }}</div>
                        <div class="text-muted fs-7 mt-2">
                            {{ $location->name ?? '' }}
                            @if(!empty($location?->landmark))
                                <div>{{ $location->landmark }}</div>
                            @endif
                            @if(!empty($location?->city) || !empty($location?->state) || !empty($location?->country))
                                <div>{{ implode(',', array_filter([$location?->city, $location?->state, $location?->country])) }}</div>
                            @endif
                            @if(!empty($business->tax_number_1))
                                <div>{{ $business->tax_label_1 }}: {{ $business->tax_number_1 }}</div>
                            @endif
                            @if(!empty($business->tax_number_2))
                                <div>{{ $business->tax_label_2 }}: {{ $business->tax_number_2 }}</div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="row g-5 mb-8">
                    <div class="col-md-4">
                        <div class="card card-bordered h-100">
                            <div class="card-body p-5">
                                <div class="text-muted fs-8 fw-semibold mb-2">@lang('sale.invoice_no')</div>
                                <div class="text-gray-900 fw-bold fs-6">{{ $invoice->reference ?: $invoice->voucher_no }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card card-bordered h-100">
                            <div class="card-body p-5">
                                <div class="text-muted fs-8 fw-semibold mb-2">@lang('sale.sale_date')</div>
                                <div class="text-gray-900 fw-bold fs-6">{{ optional($invoice->document_date)->format('d/m/Y') }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card card-bordered h-100">
                            <div class="card-body p-5">
                                <div class="text-muted fs-8 fw-semibold mb-2">@lang('sale.total_payable')</div>
                                <div class="text-gray-900 fw-bold fs-6">{{ number_format((float) $outstandingAmount, 2) }} {{ $invoice->currency_code }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed fs-7 gy-4">
                        <thead>
                            <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                <th>#</th>
                                <th>@lang('account.account')</th>
                                <th>@lang('sale.description')</th>
                                <th class="text-end">@lang('account.debit')</th>
                                <th class="text-end">@lang('account.credit')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($invoice->lines as $line)
                                <tr>
                                    <td>{{ $line->line_no }}</td>
                                    <td>{{ optional($line->account)->account_code }} - {{ optional($line->account)->account_name }}</td>
                                    <td>{{ $line->description }}</td>
                                    <td class="text-end">{{ number_format((float) $line->debit, 2) }}</td>
                                    <td class="text-end">{{ number_format((float) $line->credit, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('javascript')
<script type="text/javascript">
    $(document).ready(function () {
        $(document).on('click', '#print_invoice', function () {
            $('#invoice_content').printThis();
        });
    });
</script>
@endsection
