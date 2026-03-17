@extends('layouts.app')

@section('title', __('product.quote_detail'))

@section('content')
<div class="d-none" data-projectx-quote-id="{{ (int) $quote->id }}"></div>

<div class="d-flex flex-wrap flex-stack mb-6">
    <div>
        <h1 class="text-gray-900 fw-bold mb-1">{{ __('product.quote_detail') }}</h1>
        <div class="text-muted fw-semibold fs-6">{{ __('product.quote_no') }}: {{ $quoteDisplay['quoteNumber'] ?? ($quote->quote_number ?: $quote->uuid) }}</div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('product.quotes.index') }}" class="btn btn-light-primary btn-sm">
            <i class="ki-duotone ki-arrow-left fs-5 me-1"><span class="path1"></span><span class="path2"></span></i>
            {{ __('product.back_to_quotes') }}
        </a>
        @if($quoteActionFlags['showEdit'] ?? false)
            <a href="{{ route('product.quotes.edit', ['id' => $quote->id]) }}" class="btn btn-light-info btn-sm">
                <i class="ki-duotone ki-notepad-edit fs-5 me-1"><span class="path1"></span><span class="path2"></span></i>
                {{ __('product.edit') }}
            </a>
        @endif
        @if($quoteActionFlags['canCreateSaleFromQuote'] ?? false)
            <a href="{{ route('sells.create', ['product_quote_id' => $quote->id]) }}" class="btn btn-primary btn-sm">
                <i class="ki-duotone ki-basket fs-5 me-1"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span></i>
                {{ __('product.create_sale_from_quote') }}
            </a>
        @endif
        @if($quote->transaction_id)
            <a href="{{ route('product.sales.orders.show', ['id' => $quote->transaction_id]) }}" class="btn btn-light-success btn-sm">
                <i class="ki-duotone ki-document fs-5 me-1"><span class="path1"></span><span class="path2"></span></i>
                {{ __('product.view_order') }}
            </a>
        @endif
    </div>
</div>

<div class="row g-5 g-xl-10">
    <div class="col-xl-4">
        <div class="card card-flush mb-5">
            <div class="card-header pt-7">
                <h3 class="card-title fw-bold text-gray-900">{{ __('product.quote_summary') }}</h3>
            </div>
            <div class="card-body pt-5">
                @if(session('status'))
                    <div class="alert alert-{{ session('status.success') ? 'success' : 'danger' }} mb-5">
                        {{ session('status.msg') }}
                    </div>
                @endif

                <div class="d-flex flex-stack mb-4">
                    <span class="text-gray-500 fw-semibold">{{ __('product.customer') }}</span>
                    <span class="text-gray-900 fw-bold">{{ $quoteDisplay['customerName'] ?? '-' }}</span>
                </div>
                <div class="d-flex flex-stack mb-4">
                    <span class="text-gray-500 fw-semibold">{{ __('product.customer_email') }}</span>
                    <span class="text-gray-900 fw-bold">{{ $quoteDisplay['customerEmail'] ?? '-' }}</span>
                </div>
                <div class="d-flex flex-stack mb-4">
                    <span class="text-gray-500 fw-semibold">{{ __('product.location') }}</span>
                    <span class="text-gray-900 fw-bold">{{ $quoteDisplay['locationName'] ?? '-' }}</span>
                </div>
                <div class="d-flex flex-stack mb-4">
                    <span class="text-gray-500 fw-semibold">{{ __('product.status') }}</span>
                    <span><span class="badge {{ $quoteDisplay['quoteStateBadgeClass'] ?? 'badge-light-secondary' }}">{{ $quoteDisplay['quoteStateLabel'] ?? __('product.quote_state_draft') }}</span></span>
                </div>
                <div class="d-flex flex-stack mb-4">
                    <span class="text-gray-500 fw-semibold">{{ __('product.lines') }}</span>
                    <span class="text-gray-900 fw-bold">{{ $quoteDisplay['quoteLineCount'] ?? 0 }}</span>
                </div>
                <div class="d-flex flex-stack mb-4">
                    <span class="text-gray-500 fw-semibold">{{ __('product.currency_label') }}</span>
                    <span class="text-gray-900 fw-bold">{{ $quoteDisplay['currencyCode'] ?? '-' }}</span>
                </div>
                <div class="d-flex flex-stack mb-4">
                    <span class="text-gray-500 fw-semibold">{{ __('product.incoterm') }}</span>
                    <span class="text-gray-900 fw-bold">{{ $quote->incoterm ?: '-' }}</span>
                </div>
                <div class="d-flex flex-stack mb-4">
                    <span class="text-gray-500 fw-semibold">{{ __('product.quote_date') }}</span>
                    <span class="text-gray-900 fw-bold">{{ $quoteDisplay['quoteDateDisplay'] ?? '-' }}</span>
                </div>
                <div class="d-flex flex-stack mb-4">
                    <span class="text-gray-500 fw-semibold">{{ __('product.valid_until') }}</span>
                    <span class="text-gray-900 fw-bold">{{ $quoteDisplay['validUntilDisplay'] ?? '-' }}</span>
                </div>
                @if($quote->shipment_port)
                <div class="d-flex flex-stack mb-4">
                    <span class="text-gray-500 fw-semibold">{{ __('product.shipment_port') }}</span>
                    <span class="text-gray-900 fw-bold">{{ $quote->shipment_port }}</span>
                </div>
                @endif
                <div class="d-flex flex-stack mb-6">
                    <span class="text-gray-500 fw-semibold">{{ __('product.grand_total') }}</span>
                    <span class="text-gray-900 fw-bolder fs-2">@format_currency($quoteDisplay['quoteGrandTotalValue'] ?? 0)</span>
                </div>

                <div class="separator separator-dashed mb-6"></div>

                @if($quoteActionFlags['showOverrideNotice'] ?? false)
                    <div class="alert alert-warning d-flex align-items-start mb-4">
                        <i class="ki-duotone ki-information-5 fs-2x text-warning me-3"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                        <div class="fw-semibold">{{ __('product.quote_admin_override_notice') }}</div>
                    </div>
                @endif

                @if($quoteActionFlags['canSendQuote'] ?? false)
                    <form method="POST" action="{{ route('product.quotes.send', ['id' => $quote->id]) }}" class="mb-4">
                        @csrf
                        <label class="form-label">{{ __('product.send_to_email') }}</label>
                        <div class="input-group">
                            <input type="email" class="form-control form-control-solid" name="to_email" value="{{ $recipientEmail }}" placeholder="email@example.com">
                            <button type="submit" class="btn btn-primary">{{ __('product.send_quote') }}</button>
                        </div>
                    </form>
                @endif

                @if($quoteActionFlags['showDelete'] ?? false)
                    <form method="POST" action="{{ route('product.quotes.destroy', ['id' => $quote->id]) }}" class="mb-4" onsubmit="return confirm('{{ __('product.delete_quote_confirm') }}');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-light-danger w-100">{{ __('product.delete') }}</button>
                    </form>
                @endif

                @if($quoteActionFlags['showRevertToDraft'] ?? false)
                    <form method="POST" action="{{ route('product.quotes.revert_draft', ['id' => $quote->id]) }}" class="mb-4" onsubmit="return confirm('{{ __('product.revert_quote_to_draft_confirm') }}');">
                        @csrf
                        <button type="submit" class="btn btn-light-warning w-100">{{ __('product.revert_quote_to_draft') }}</button>
                    </form>
                @endif

                @if($quoteActionFlags['showClearSignature'] ?? false)
                    <form method="POST" action="{{ route('product.quotes.clear_signature', ['id' => $quote->id]) }}" class="mb-4" onsubmit="return confirm('{{ __('product.clear_quote_signature_confirm') }}');">
                        @csrf
                        <button type="submit" class="btn btn-light-danger w-100">{{ __('product.clear_quote_signature') }}</button>
                    </form>
                @endif

                <div class="d-grid gap-2 mb-6">
                    <a href="{{ $publicUrl }}" target="_blank" class="btn btn-light-primary">{{ __('product.open_public_quote') }}</a>
                    <input type="text" class="form-control form-control-solid" readonly value="{{ $publicUrl }}">
                </div>

                <div class="separator separator-dashed mb-6"></div>

                <div class="mb-3 d-flex flex-stack">
                    <span class="text-gray-500 fw-semibold">{{ __('product.public_link_password') }}</span>
                    @if(!empty($quote->public_link_password))
                        <span class="badge badge-light-success">{{ __('product.password_set') }}</span>
                    @else
                        <span class="badge badge-light-secondary">{{ __('product.password_not_set') }}</span>
                    @endif
                </div>

                <form method="POST" action="{{ route('product.quotes.set_public_password', ['id' => $quote->id]) }}" class="mb-4">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">{{ __('product.password') }}</label>
                        <input type="password" name="password" class="form-control form-control-solid" autocomplete="new-password">
                        @error('password')
                            <div class="text-danger fs-7 mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-4">
                        <label class="form-label">{{ __('product.password_confirmation') }}</label>
                        <input type="password" name="password_confirmation" class="form-control form-control-solid" autocomplete="new-password">
                        @error('password_confirmation')
                            <div class="text-danger fs-7 mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                    <button type="submit" class="btn btn-primary w-100">{{ __('product.set_password') }}</button>
                </form>

                <form method="POST" action="{{ route('product.quotes.set_public_password', ['id' => $quote->id]) }}">
                    @csrf
                    <input type="hidden" name="remove_password" value="1">
                    <button type="submit" class="btn btn-light-danger w-100">{{ __('product.remove_password') }}</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-xl-8">
        <div class="card card-flush">
            <div class="card-header pt-7">
                <h3 class="card-title fw-bold text-gray-900">{{ __('product.quote_lines') }}</h3>
            </div>
            <div class="card-body pt-0">
                <div class="table-responsive">
                    <table class="table table-row-dashed table-row-gray-300 align-middle gs-0 gy-4">
                        <thead>
                            <tr class="fw-bold text-muted text-uppercase fs-7">
                                <th>{{ __('product.quote_line_item') }}</th>
                                <th>{{ __('product.category') }}</th>
                                <th>{{ __('product.quantity') }}</th>
                                <th>{{ __('product.purchase_uom') }}</th>
                                <th>{{ __('product.unit_cost') }}</th>
                                <th class="text-end">{{ __('product.total') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse(($quoteDisplay['quoteDisplayLines'] ?? []) as $line)
                                <tr>
                                    <td>
                                        <div class="text-gray-900 fw-bold">{{ $line['itemName'] ?? '-' }}</div>
                                        @if(!empty($line['itemCode']))
                                            <div class="text-muted fs-7">{{ $line['itemCodeLabel'] ?? __('product.sku') }}: {{ $line['itemCode'] }}</div>
                                        @endif
                                    </td>
                                    <td>{{ $line['categoryName'] ?? '' }}</td>
                                    <td>@num_format($line['quantity'] ?? 0)</td>
                                    <td>{{ $line['purchaseUom'] ?? '' }}</td>
                                    <td>@format_currency($line['unitCost'] ?? 0)</td>
                                    <td class="text-end fw-bold">@format_currency($line['totalCost'] ?? 0)</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted">{{ __('product.no_records_found') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr class="border-top border-gray-300">
                                <td colspan="5" class="text-end fw-bolder text-gray-900 fs-4">{{ __('product.grand_total') }}</td>
                                <td class="text-end fw-bolder text-gray-900 fs-4">@format_currency($quoteDisplay['quoteGrandTotalValue'] ?? 0)</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                @if(!empty($quoteDisplay['remarkDisplay']))
                    <div class="card card-flush mt-8">
                        <div class="card-header pt-6">
                            <h3 class="card-title fw-bold text-gray-900">{{ __('product.remark') }}</h3>
                        </div>
                        <div class="card-body pt-5">
                            <div class="text-gray-700 fs-7">{!! nl2br(e($quoteDisplay['remarkDisplay'])) !!}</div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection


