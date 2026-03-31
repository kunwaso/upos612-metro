@extends('layouts.guest')
@section('title', $title)

@section('content')
<div class="container">
    <div class="spacer"></div>
    <div class="row">
        <div class="col-md-12 text-right mb-12">
            @if(!empty($payment_link))
                <a href="{{ $payment_link }}" class="btn btn-info no-print" style="margin-right: 20px;">
                    <i class="fas fa-money-check-alt" title="@lang('lang_v1.pay')"></i> @lang('lang_v1.pay')
                </a>
            @endif
            <button type="button" class="btn btn-primary btn-sm no-print" id="print_invoice" aria-label="Print">
                <i class="fas fa-print"></i> @lang('messages.print')
            </button>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8 col-md-offset-2 col-sm-12" style="border: 1px solid #ccc;">
            <div class="spacer"></div>
            <div id="invoice_content">
                <table class="table no-border">
                    <tr>
                        @if(!empty($business->logo))
                            <td class="width-50 text-center">
                                <img src="{{ asset('uploads/business_logos/' . $business->logo) }}" alt="Logo" style="max-width: 80%;">
                            </td>
                        @endif
                        <td class="text-center">
                            <address>
                                <strong>{{ $business->name }}</strong><br>
                                {{ $location->name ?? '' }}
                                @if(!empty($location?->landmark))
                                    <br>{{ $location->landmark }}
                                @endif
                                @if(!empty($location?->city) || !empty($location?->state) || !empty($location?->country))
                                    <br>{{ implode(',', array_filter([$location?->city, $location?->state, $location?->country])) }}
                                @endif
                                @if(!empty($business->tax_number_1))
                                    <br>{{ $business->tax_label_1 }}: {{ $business->tax_number_1 }}
                                @endif
                                @if(!empty($business->tax_number_2))
                                    <br>{{ $business->tax_label_2 }}: {{ $business->tax_number_2 }}
                                @endif
                            </address>
                        </td>
                    </tr>
                </table>

                <h4 class="box-title">@lang('sale.invoice_no'): {{ $invoice->reference ?: $invoice->voucher_no }}</h4>
                <table class="table no-border">
                    <tr>
                        <td><strong>@lang('sale.sale_date'):</strong> {{ optional($invoice->document_date)->format('d/m/Y') }}</td>
                    </tr>
                    <tr>
                        <td><strong>@lang('sale.total_amount'):</strong> {{ number_format((float) max($invoice->total_debit, $invoice->total_credit), 2) }} {{ $invoice->currency_code }}</td>
                    </tr>
                    <tr>
                        <td><strong>@lang('sale.total_payable'):</strong> {{ number_format((float) $outstandingAmount, 2) }} {{ $invoice->currency_code }}</td>
                    </tr>
                </table>

                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>@lang('account.account')</th>
                                <th>@lang('sale.description')</th>
                                <th class="text-right">@lang('account.debit')</th>
                                <th class="text-right">@lang('account.credit')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($invoice->lines as $line)
                                <tr>
                                    <td>{{ $line->line_no }}</td>
                                    <td>{{ optional($line->account)->account_code }} - {{ optional($line->account)->account_name }}</td>
                                    <td>{{ $line->description }}</td>
                                    <td class="text-right">{{ number_format((float) $line->debit, 2) }}</td>
                                    <td class="text-right">{{ number_format((float) $line->credit, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="spacer"></div>
        </div>
    </div>
    <div class="spacer"></div>
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
