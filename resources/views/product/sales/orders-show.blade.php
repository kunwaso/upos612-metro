@extends('layouts.app')

@section('title', __('product.sale_detail'))

@section('content')
<div class="d-none" data-projectx-transaction-id="{{ (int) $transaction->id }}"></div>
<div class="d-flex flex-wrap flex-stack mb-6">
    <div>
        <h1 class="text-gray-900 fw-bold mb-1">{{ __('product.sale_detail') }}</h1>
        <div class="text-muted fw-semibold fs-6">{{ __('product.invoice_no') }}: {{ $transaction->invoice_no }}</div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('product.sales.orders.index') }}" class="btn btn-light-primary btn-sm">
            <i class="ki-duotone ki-arrow-left fs-5 me-1"><span class="path1"></span><span class="path2"></span></i>
            {{ __('product.sales_orders') }}
        </a>
        <a href="{{ route('product.quotes.show', ['id' => $quote->id]) }}" class="btn btn-light-info btn-sm">
            <i class="ki-duotone ki-document fs-5 me-1"><span class="path1"></span><span class="path2"></span></i>
            {{ __('product.view_quote') }}
        </a>
        @if(auth()->user()->can('product_sales_order.edit'))
            <a href="{{ route('product.sales.orders.edit', ['id' => $transaction->id]) }}" class="btn btn-light-success btn-sm">
                <i class="ki-duotone ki-notepad-edit fs-5 me-1"><span class="path1"></span><span class="path2"></span></i>
                {{ __('product.edit_order') }}
            </a>
        @endif
        @if(auth()->user()->can('product_sales_order.update_status'))
            <form method="POST" action="{{ route('product.sales.orders.hold.update', ['id' => $transaction->id]) }}">
                @csrf
                @method('PATCH')
                <input type="hidden" name="is_on_hold" value="{{ $transaction->sub_status === 'on_hold' ? 0 : 1 }}">
                <button type="submit" class="btn btn-light-warning btn-sm">
                    {{ $transaction->sub_status === 'on_hold' ? __('product.remove_hold') : __('product.mark_on_hold') }}
                </button>
            </form>
        @endif
        @if(auth()->user()->can('sell.delete') || auth()->user()->can('direct_sell.delete') || auth()->user()->can('so.delete'))
            <form method="POST" action="{{ route('product.sales.orders.destroy', ['id' => $transaction->id]) }}" onsubmit="return confirm('{{ __('product.delete') }}?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-light-danger btn-sm">
                    <i class="ki-duotone ki-trash fs-5 me-1"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span></i>
                    {{ __('product.delete') }}
                </button>
            </form>
        @endif
    </div>
</div>

@if(session('status'))
    <div class="alert alert-{{ session('status.success') ? 'success' : 'danger' }} alert-dismissible fade show mb-5" role="alert">
        {{ session('status.msg') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('product.close') }}"></button>
    </div>
@endif

<div class="row g-5 g-xl-10 mb-5 mb-xl-10">
    <div class="col-xl-4">
        <div class="card card-flush h-lg-100">
            <div class="card-header pt-5">
                <h3 class="card-title align-items-start flex-column">
                    <span class="card-label fw-bold text-gray-800">{{ __('product.sale_detail') }}</span>
                </h3>
            </div>
            <div class="card-body pt-5">
                <div class="d-flex flex-stack mb-5">
                    <span class="text-gray-500 fw-semibold fs-6">{{ __('product.invoice_no') }}</span>
                    <span class="text-gray-800 fw-bold fs-6">{{ $transaction->invoice_no }}</span>
                </div>
                <div class="separator separator-dashed my-3"></div>
                <div class="d-flex flex-stack mb-5">
                    <span class="text-gray-500 fw-semibold fs-6">{{ __('product.quote_no') }}</span>
                    <span class="text-gray-800 fw-bold fs-6">{{ $quote->quote_number ?: $quote->uuid }}</span>
                </div>
                <div class="separator separator-dashed my-3"></div>
                <div class="d-flex flex-stack mb-5">
                    <span class="text-gray-500 fw-semibold fs-6">{{ __('product.date') }}</span>
                    <span class="text-gray-800 fw-bold fs-6">{{ $transactionDateFormatted ?? '-' }}</span>
                </div>
                @if(!empty($deliveryDateFormatted))
                    <div class="separator separator-dashed my-3"></div>
                    <div class="d-flex flex-stack mb-5">
                        <span class="text-gray-500 fw-semibold fs-6">{{ __('product.delivery_date') }}</span>
                        <span class="text-gray-800 fw-bold fs-6">{{ $deliveryDateFormatted }}</span>
                    </div>
                @endif
                <div class="separator separator-dashed my-3"></div>
                <div class="d-flex flex-stack mb-5">
                    <span class="text-gray-500 fw-semibold fs-6">{{ __('product.customer') }}</span>
                    <span class="text-gray-800 fw-bold fs-6">{{ $transaction->contact->name ?? '-' }}</span>
                </div>
                <div class="separator separator-dashed my-3"></div>
                <div class="d-flex flex-stack mb-5">
                    <span class="text-gray-500 fw-semibold fs-6">{{ __('product.location') }}</span>
                    <span class="text-gray-800 fw-bold fs-6">{{ $transaction->location->name ?? '-' }}</span>
                </div>
                <div class="separator separator-dashed my-3"></div>
                <div class="d-flex flex-stack mb-5">
                    <span class="text-gray-500 fw-semibold fs-6">{{ __('product.status') }}</span>
                    {!! $statusBadge !!}
                </div>
                <div class="separator separator-dashed my-3"></div>
                <div class="d-flex flex-stack mb-5">
                    <span class="text-gray-500 fw-semibold fs-6">{{ __('product.payment_status') }}</span>
                    <span class="badge {{ $transaction->payment_status === 'paid' ? 'badge-light-success' : ($transaction->payment_status === 'partial' ? 'badge-light-warning' : ($transaction->payment_status === 'due' || $transaction->payment_status === 'overdue' ? 'badge-light-danger' : 'badge-light-info')) }}">{{ __('product.' . $transaction->payment_status) }}</span>
                </div>
                <div class="separator separator-dashed my-3"></div>
                <div class="d-flex flex-stack">
                    <span class="text-gray-500 fw-semibold fs-6">{{ __('product.grand_total') }}</span>
                    <span class="text-gray-900 fw-bolder fs-3">
                        @format_currency((float) $transaction->final_total)
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-8">
        <div class="card card-flush mb-5 mb-xl-10">
            <div class="card-header pt-5">
                <h3 class="card-title align-items-start flex-column">
                    <span class="card-label fw-bold text-gray-800">{{ __('product.sale_items') }}</span>
                </h3>
            </div>
            <div class="card-body pt-0">
                <div class="table-responsive">
                    <table class="table table-row-dashed table-row-gray-300 align-middle gs-0 gy-4">
                        <thead>
                            <tr class="fw-bold text-muted">
                                <th class="min-w-200px">{{ __('product.product_name') }}</th>
                                <th>{{ __('product.quantity') }}</th>
                                <th>{{ __('product.unit_price_short') }}</th>
                                <th>{{ __('product.discount') }}</th>
                                <th>{{ __('product.tax') }}</th>
                                <th class="text-end">{{ __('product.subtotal') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($transaction->sell_lines as $line)
                                <tr>
                                    <td>
                                        <span class="text-gray-800 fw-bold d-block fs-6">{{ $line->product->name ?? '-' }}</span>
                                        @if($line->variations)
                                            <span class="text-muted fw-semibold d-block fs-7">{{ $line->variations->name }}</span>
                                        @endif
                                    </td>
                                    <td><span class="text-gray-700 fw-semibold fs-6">@format_quantity((float) $line->quantity)</span></td>
                                    <td><span class="text-gray-700 fw-semibold fs-6">@format_currency((float) $line->unit_price_inc_tax)</span></td>
                                    <td>
                                        <span class="text-gray-700 fw-semibold fs-6">
                                            @if($line->line_discount_amount > 0)
                                                @format_currency((float) $line->line_discount_amount)
                                            @else
                                                -
                                            @endif
                                        </span>
                                    </td>
                                    <td>
                                        <span class="text-gray-700 fw-semibold fs-6">
                                            @if($line->item_tax > 0)
                                                @format_currency((float) $line->item_tax)
                                            @else
                                                -
                                            @endif
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <span class="text-gray-900 fw-bold fs-6">@format_currency((float) ($line->unit_price_inc_tax * $line->quantity))</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="5" class="text-end fw-bold text-gray-700 fs-6 pe-3">{{ __('product.discount') }}</td>
                                <td class="text-end fw-bold text-gray-800 fs-6">@format_currency((float) ($transaction->discount_amount ?? 0))</td>
                            </tr>
                            <tr>
                                <td colspan="5" class="text-end fw-bold text-gray-700 fs-6 pe-3">{{ __('product.tax') }}</td>
                                <td class="text-end fw-bold text-gray-800 fs-6">@format_currency((float) ($transaction->tax_amount ?? 0))</td>
                            </tr>
                            <tr class="border-top border-gray-300">
                                <td colspan="5" class="text-end fw-bolder text-gray-900 fs-4 pe-3">{{ __('product.grand_total') }}</td>
                                <td class="text-end fw-bolder text-gray-900 fs-4">@format_currency((float) $transaction->final_total)</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        @if($transaction->payment_lines && $transaction->payment_lines->count())
            <div class="card card-flush">
                <div class="card-header pt-5">
                    <h3 class="card-title align-items-start flex-column">
                        <span class="card-label fw-bold text-gray-800">{{ __('product.payment_info') }}</span>
                    </h3>
                </div>
                <div class="card-body pt-0">
                    <div class="table-responsive">
                        <table class="table table-row-dashed table-row-gray-300 align-middle gs-0 gy-4">
                            <thead>
                                <tr class="fw-bold text-muted">
                                    <th>{{ __('product.payment_method') }}</th>
                                    <th>{{ __('product.amount') }}</th>
                                    <th>{{ __('product.paid_on') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($transaction->payment_lines as $payment)
                                    <tr>
                                        <td><span class="badge badge-light-primary">{{ ucfirst($payment->method) }}</span></td>
                                        <td><span class="text-gray-900 fw-bold fs-6">@format_currency((float) $payment->amount)</span></td>
                                        <td><span class="text-gray-700 fw-semibold fs-7">{{ \Carbon\Carbon::parse($payment->paid_on)->format('M d, Y h:i A') }}</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

