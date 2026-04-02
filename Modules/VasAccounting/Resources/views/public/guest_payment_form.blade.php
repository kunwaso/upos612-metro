@extends('layouts.guest_metronic')
@section('title', $title)

@section('content')
<div class="row justify-content-center">
    <div class="col-12 col-xl-8">
        <div class="card card-flush shadow-sm">
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
                        </div>
                    </div>
                </div>

                <div class="text-gray-900 fw-bold fs-4 mb-5">@lang('lang_v1.payment_for_invoice_no'): {{ $invoice->reference ?: $invoice->voucher_no }}</div>

                <div class="row g-5 mb-8">
                    <div class="col-md-6">
                        <div class="card card-bordered h-100">
                            <div class="card-body p-5">
                                <div class="text-muted fs-8 fw-semibold mb-2">@lang('contact.customer')</div>
                                <div class="text-gray-900 fw-bold fs-6">{{ $contact->supplier_business_name ?? $contact->name ?? '-' }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card card-bordered h-100">
                            <div class="card-body p-5">
                                <div class="text-muted fs-8 fw-semibold mb-2">@lang('sale.sale_date')</div>
                                <div class="text-gray-900 fw-bold fs-6">{{ $date_formatted }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card card-bordered h-100">
                            <div class="card-body p-5">
                                <div class="text-muted fs-8 fw-semibold mb-2">@lang('sale.total_amount')</div>
                                <div class="text-gray-900 fw-bold fs-6">{{ $total_amount }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card card-bordered h-100">
                            <div class="card-body p-5">
                                <div class="text-muted fs-8 fw-semibold mb-2">@lang('sale.total_paid')</div>
                                <div class="text-gray-900 fw-bold fs-6">{{ $total_paid }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                @if($total_payable > 0.0001)
                    <div class="alert alert-light-primary d-flex align-items-center mb-8">
                        <i class="ki-outline ki-wallet fs-2 text-primary me-3"></i>
                        <div class="fw-semibold">@lang('sale.total_payable'): {{ $total_payable_formatted }}</div>
                    </div>

                    <div class="d-flex flex-wrap gap-4 align-items-start">
                        <div>
                            <form action="{{ route('confirm_payment', ['id' => 'vas-' . $invoice->id]) }}" method="POST">
                                {{ csrf_field() }}
                                <input type="hidden" name="token" value="{{ $token }}">
                                <input type="hidden" name="gateway" value="razorpay">
                                <script
                                    src="https://checkout.razorpay.com/v1/checkout.js"
                                    data-key="{{ $pos_settings['razor_pay_key_id'] ?? '' }}"
                                    data-amount="{{ $total_payable * 100 }}"
                                    data-buttontext="Pay with Razorpay"
                                    data-name="{{ $business->name }}"
                                    data-theme.color="#009ef7"
                                ></script>
                            </form>
                        </div>
                        @if(!empty($pos_settings['stripe_public_key']) && !empty($pos_settings['stripe_secret_key']))
                            @php($code = strtolower($business_details->currency_code))
                            <div>
                                <form action="{{ route('confirm_payment', ['id' => 'vas-' . $invoice->id]) }}" method="POST">
                                    {{ csrf_field() }}
                                    <input type="hidden" name="token" value="{{ $token }}">
                                    <input type="hidden" name="gateway" value="stripe">
                                    <script
                                        src="https://checkout.stripe.com/checkout.js"
                                        class="stripe-button"
                                        data-key="{{ $pos_settings['stripe_public_key'] }}"
                                        data-amount="@if(in_array($code, ['bif','clp','djf','gnf','jpy','kmf','krw','mga','pyg','rwf','ugx','vnd','vuv','xaf','xof','xpf'])) {{ $total_payable }} @else {{ $total_payable * 100 }} @endif"
                                        data-name="{{ $business->name }}"
                                        data-description="Pay with stripe"
                                        data-image="https://stripe.com/img/documentation/checkout/marketplace.png"
                                        data-locale="auto"
                                        data-currency="{{ $code }}"
                                    ></script>
                                </form>
                            </div>
                        @endif
                    </div>
                @else
                    <div class="alert alert-light-success d-flex align-items-center mb-0">
                        <i class="ki-outline ki-check fs-2 text-success me-3"></i>
                        <div class="fw-semibold">@lang('sale.payment_status'): @lang('lang_v1.paid')</div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
