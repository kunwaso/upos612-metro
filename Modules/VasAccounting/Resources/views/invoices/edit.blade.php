@extends('layouts.app')

@section('title', 'Edit Invoice')

@section('content')
    @php
        $document = $voucher ?? $invoice ?? null;
        $invoiceMeta = (array) data_get($document?->meta, 'invoice', []);
        $lineItems = old('line_items', data_get($prefill, 'line_items', data_get($invoiceMeta, 'line_items', [['account_id' => '', 'description' => '', 'net_amount' => '', 'tax_amount' => '', 'tax_code_id' => '']])));
        $selectedKind = old('invoice_kind', $document->voucher_type ?? $invoiceKind ?? data_get($prefill, 'invoice_kind', 'purchase_invoice'));
        $isSales = in_array($selectedKind, ['sales_invoice', 'sales_credit_note'], true);
        $selectedSupplierId = old('contact_id', data_get($prefill, 'contact_id', $document->contact_id ?? ''));
        $selectedLocationId = old('business_location_id', data_get($prefill, 'business_location_id', $document->business_location_id ?? ''));
        $selectedDocumentDate = old('document_date', data_get($prefill, 'document_date', optional($document?->document_date)->toDateString() ?: now()->toDateString()));
        $selectedPostingDate = old('posting_date', data_get($prefill, 'posting_date', optional($document?->posting_date)->toDateString() ?: now()->toDateString()));
        $selectedDueDate = old('due_date', data_get($prefill, 'due_date', data_get($invoiceMeta, 'due_date', now()->toDateString())));
        $selectedCurrency = old('currency_code', $document->currency_code ?? data_get($prefill, 'currency_code', 'VND'));
        $selectedExchangeRate = old('exchange_rate', $document->exchange_rate ?? data_get($prefill, 'exchange_rate', 1));
        $selectedReference = old('reference', data_get($prefill, 'reference', $document->reference ?? ''));
        $selectedExternalReference = old('external_reference', data_get($prefill, 'external_reference', $document->external_reference ?? data_get($invoiceMeta, 'vendor_invoice_no', '')));
        $selectedDescription = old('description', data_get($prefill, 'description', $document->description ?? ''));
        $selectedPayTermNumber = old('pay_term_number', data_get($prefill, 'pay_term_number', data_get($invoiceMeta, 'payment_terms.term_days')));
        $selectedPayTermType = old('pay_term_type', data_get($prefill, 'pay_term_type', 'days'));
        $selectedSchemeId = old('invoice_scheme_id', data_get($prefill, 'invoice_scheme_id', data_get($invoiceMeta, 'scheme_id')));
        $selectedLayoutId = old('invoice_layout_id', data_get($prefill, 'invoice_layout_id', data_get($invoiceMeta, 'layout_id')));
        $immediatePayment = old('immediate_payment', data_get($prefill, 'immediate_payment', []));
        $effectiveTaxCodes = $isSales ? ($outputTaxCodes ?? $taxCodes ?? collect()) : ($inputTaxCodes ?? $taxCodes ?? collect());
    @endphp

    @include('vasaccounting::partials.header', [
        'title' => 'Edit Native Invoice',
        'subtitle' => $document->voucher_no ?? 'Native invoice voucher',
    ])

    @if ($errors->any())
        <div class="alert alert-danger mb-8">
            <div class="fw-semibold mb-2">Please correct the highlighted fields.</div>
            <ul class="mb-0 ps-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row g-5 g-xl-10">
        <div class="col-xl-8">
            <div class="card card-flush">
                <div class="card-header">
                    <div class="card-title">Purchase invoice details</div>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('vasaccounting.invoices.update', $document->id) }}">
                        @csrf
                        @method('PUT')

                        <div class="row g-5 mb-8">
                            <div class="col-md-4">
                                <label class="form-label required">Invoice kind</label>
                                <select class="form-select form-select-solid" name="invoice_kind" id="invoice-kind-select">
                                    @foreach (($invoiceKindOptions ?? $invoiceKinds ?? []) as $kindValue => $kindLabel)
                                        <option value="{{ $kindValue }}" {{ $selectedKind === $kindValue ? 'selected' : '' }}>{{ $kindLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label required">Counterparty</label>
                                <select class="form-select form-select-solid counterparty-select" name="contact_id" id="purchase-counterparty-select" {{ $isSales ? 'disabled' : '' }}>
                                    <option value="">Select supplier</option>
                                    @foreach (($contactOptions['purchase'] ?? $supplierOptions ?? []) as $supplierId => $supplierLabel)
                                        <option value="{{ $supplierId }}" {{ ! $isSales && (string) $selectedSupplierId === (string) $supplierId ? 'selected' : '' }}>{{ $supplierLabel }}</option>
                                    @endforeach
                                </select>
                                <select class="form-select form-select-solid counterparty-select d-none" name="contact_id" id="sales-counterparty-select" {{ $isSales ? '' : 'disabled' }}>
                                    <option value="">Select customer</option>
                                    @foreach (($contactOptions['sales'] ?? $customerOptions ?? []) as $customerId => $customerLabel)
                                        <option value="{{ $customerId }}" {{ $isSales && (string) $selectedSupplierId === (string) $customerId ? 'selected' : '' }}>{{ $customerLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Branch</label>
                                <select class="form-select form-select-solid" name="business_location_id">
                                    <option value="">Select branch</option>
                                    @foreach ($locationOptions as $locationId => $locationName)
                                        <option value="{{ $locationId }}" {{ (string) $selectedLocationId === (string) $locationId ? 'selected' : '' }}>{{ $locationName }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="row g-5 mb-8">
                            <div class="col-md-3">
                                <label class="form-label required">Document date</label>
                                <input type="date" class="form-control form-control-solid" name="document_date" value="{{ $selectedDocumentDate }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label required">Posting date</label>
                                <input type="date" class="form-control form-control-solid" name="posting_date" value="{{ $selectedPostingDate }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Due date</label>
                                <input type="date" class="form-control form-control-solid" name="due_date" value="{{ $selectedDueDate }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Currency</label>
                                <input type="text" class="form-control form-control-solid" name="currency_code" value="{{ $selectedCurrency }}">
                            </div>
                        </div>

                        <div class="row g-5 mb-8">
                            <div class="col-md-3">
                                <label class="form-label">Exchange rate</label>
                                <input type="number" min="0.000001" step="0.000001" class="form-control form-control-solid text-end" name="exchange_rate" value="{{ $selectedExchangeRate }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Internal reference</label>
                                <input type="text" class="form-control form-control-solid" name="reference" value="{{ $selectedReference }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">External reference</label>
                                <input type="text" class="form-control form-control-solid" name="external_reference" value="{{ $selectedExternalReference }}">
                            </div>
                        </div>

                        <div class="row g-5 mb-8">
                            <div class="col-md-4">
                                <label class="form-label">Invoice scheme</label>
                                <select class="form-select form-select-solid" name="invoice_scheme_id">
                                    <option value="">Auto by branch/default</option>
                                    @foreach (($invoiceSchemeOptions ?? []) as $schemeId => $schemeName)
                                        <option value="{{ $schemeId }}" {{ (string) $selectedSchemeId === (string) $schemeId ? 'selected' : '' }}>{{ $schemeName }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Invoice layout</label>
                                <select class="form-select form-select-solid" name="invoice_layout_id">
                                    <option value="">Auto by branch/default</option>
                                    @foreach (($invoiceLayoutOptions ?? []) as $layoutId => $layoutName)
                                        <option value="{{ $layoutId }}" {{ (string) $selectedLayoutId === (string) $layoutId ? 'selected' : '' }}>{{ $layoutName }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="row g-5 mb-8">
                            <div class="col-md-4">
                                <label class="form-label">Pay term number</label>
                                <input type="number" min="0" class="form-control form-control-solid" name="pay_term_number" value="{{ $selectedPayTermNumber }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Pay term type</label>
                                <select class="form-select form-select-solid" name="pay_term_type">
                                    <option value="">Select term type</option>
                                    <option value="days" {{ $selectedPayTermType === 'days' ? 'selected' : '' }}>Days</option>
                                    <option value="months" {{ $selectedPayTermType === 'months' ? 'selected' : '' }}>Months</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-8">
                            <label class="form-label">Description</label>
                            <input type="text" class="form-control form-control-solid" name="description" value="{{ $selectedDescription }}" placeholder="Supplier bill description, purchase memo, debit note reason">
                        </div>

                        <div class="table-responsive mb-8">
                            <table class="table align-middle table-row-dashed fs-6 gy-4" id="invoice-lines-table">
                                <thead>
                                    <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                        <th style="min-width: 240px;">Expense / inventory account</th>
                                        <th style="min-width: 220px;">Description</th>
                                        <th style="min-width: 140px;" class="text-end">Net amount</th>
                                        <th style="min-width: 140px;" class="text-end">Tax amount</th>
                                        <th style="min-width: 180px;">Tax code</th>
                                        <th style="width: 56px;"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($lineItems as $index => $line)
                                        <tr>
                                            <td>
                                                <select class="form-select form-select-solid" name="line_items[{{ $index }}][account_id]">
                                                    <option value="">Select account</option>
                                                    @foreach ($accountOptions as $account)
                                                        <option value="{{ $account->id }}" {{ (string) data_get($line, 'account_id') === (string) $account->id ? 'selected' : '' }}>{{ $account->account_code }} - {{ $account->account_name }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td>
                                                <input type="text" class="form-control form-control-solid" name="line_items[{{ $index }}][description]" value="{{ data_get($line, 'description') }}">
                                            </td>
                                            <td>
                                                <input type="number" min="0.0001" step="0.0001" class="form-control form-control-solid text-end" name="line_items[{{ $index }}][net_amount]" value="{{ data_get($line, 'net_amount', data_get($line, 'amount')) }}">
                                            </td>
                                            <td>
                                                <input type="number" min="0" step="0.0001" class="form-control form-control-solid text-end" name="line_items[{{ $index }}][tax_amount]" value="{{ data_get($line, 'tax_amount', 0) }}">
                                            </td>
                                            <td>
                                                <select class="form-select form-select-solid" name="line_items[{{ $index }}][tax_code_id]">
                                                    <option value="">No tax code</option>
                                                    @foreach ($effectiveTaxCodes as $taxCode)
                                                        <option value="{{ $taxCode->id }}" {{ (string) data_get($line, 'tax_code_id') === (string) $taxCode->id ? 'selected' : '' }}>{{ $taxCode->code }} - {{ $taxCode->name ?? $taxCode->description ?? '' }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td class="text-end">
                                                <button type="button" class="btn btn-icon btn-light-danger btn-sm remove-line">&times;</button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-8">
                            <button type="button" class="btn btn-light-primary" id="add-invoice-line">Add line</button>
                            <div class="text-muted fs-7">The AP control entry is generated automatically by the posting service.</div>
                        </div>

                        <div class="card card-bordered mb-8" id="immediate-payment-card">
                            <div class="card-header">
                                <div class="card-title fs-6">Immediate settlement after posting</div>
                            </div>
                            <div class="card-body">
                                <div class="row g-5">
                                    <div class="col-md-3">
                                        <label class="form-label">Amount</label>
                                        <input type="number" min="0" step="0.01" class="form-control form-control-solid text-end" name="immediate_payment[amount]" value="{{ data_get($immediatePayment, 'amount') }}">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Settlement kind</label>
                                        <select class="form-select form-select-solid" name="immediate_payment[payment_kind]">
                                            <option value="bank_payment" {{ data_get($immediatePayment, 'payment_kind', 'bank_payment') === 'bank_payment' ? 'selected' : '' }}>Bank payment</option>
                                            <option value="cash_payment" {{ data_get($immediatePayment, 'payment_kind') === 'cash_payment' ? 'selected' : '' }}>Cash payment</option>
                                            <option value="bank_receipt" {{ data_get($immediatePayment, 'payment_kind') === 'bank_receipt' ? 'selected' : '' }}>Bank receipt</option>
                                            <option value="cash_receipt" {{ data_get($immediatePayment, 'payment_kind') === 'cash_receipt' ? 'selected' : '' }}>Cash receipt</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Paid on</label>
                                        <input type="date" class="form-control form-control-solid" name="immediate_payment[paid_on]" value="{{ data_get($immediatePayment, 'paid_on', now()->toDateString()) }}">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Payment method</label>
                                        <select class="form-select form-select-solid" name="immediate_payment[payment_method]">
                                            <option value="bank_transfer" {{ data_get($immediatePayment, 'payment_method', 'bank_transfer') === 'bank_transfer' ? 'selected' : '' }}>Bank transfer</option>
                                            <option value="cash" {{ data_get($immediatePayment, 'payment_method') === 'cash' ? 'selected' : '' }}>Cash</option>
                                            <option value="cheque" {{ data_get($immediatePayment, 'payment_method') === 'cheque' ? 'selected' : '' }}>Cheque</option>
                                            <option value="card" {{ data_get($immediatePayment, 'payment_method') === 'card' ? 'selected' : '' }}>Card</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row g-5 mt-1">
                                    <div class="col-md-4">
                                        <label class="form-label">Cashbook</label>
                                        <select class="form-select form-select-solid" name="immediate_payment[cashbook_id]">
                                            <option value="">Optional</option>
                                            @foreach ($cashbooks as $cashbook)
                                                <option value="{{ $cashbook->id }}" {{ (string) data_get($immediatePayment, 'cashbook_id') === (string) $cashbook->id ? 'selected' : '' }}>{{ $cashbook->code }} - {{ $cashbook->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Bank account</label>
                                        <select class="form-select form-select-solid" name="immediate_payment[bank_account_id]">
                                            <option value="">Optional</option>
                                            @foreach ($bankAccounts as $bankAccount)
                                                <option value="{{ $bankAccount->id }}" {{ (string) data_get($immediatePayment, 'bank_account_id') === (string) $bankAccount->id ? 'selected' : '' }}>{{ $bankAccount->account_code }} - {{ $bankAccount->bank_name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Payment note</label>
                                        <input type="text" class="form-control form-control-solid" name="immediate_payment[notes]" value="{{ data_get($immediatePayment, 'notes') }}">
                                    </div>
                                </div>
                                <div class="row g-5 mt-1">
                                    <div class="col-md-4">
                                        <label class="form-label">External reference</label>
                                        <input type="text" class="form-control form-control-solid" name="immediate_payment[external_reference]" value="{{ data_get($immediatePayment, 'external_reference') }}">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center">
                            <a href="{{ route('vasaccounting.invoices.index') }}" class="btn btn-light">Back to register</a>
                            <div class="d-flex gap-3">
                                <button type="submit" name="action" value="save_draft" class="btn btn-light-primary">Update draft</button>
                                <button type="submit" name="action" value="submit" class="btn btn-light-warning">Submit</button>
                                <button type="submit" name="action" value="save_and_post" class="btn btn-primary">Save and post</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card card-flush mb-5">
                <div class="card-header">
                    <div class="card-title">Workflow notes</div>
                </div>
                <div class="card-body">
                    <div class="text-muted fs-7 mb-3">Update the line snapshot here and the posting service will rebuild the AR/AP voucher on save.</div>
                    <div class="text-muted fs-7 mb-3">Immediate settlement remains available for sales and purchase invoices after a successful post.</div>
                    <div class="text-muted fs-7">The invoice kind controls counterparty type and settlement-card visibility.</div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const tableBody = document.querySelector('#invoice-lines-table tbody');
            const addButton = document.getElementById('add-invoice-line');
            const invoiceKindSelect = document.getElementById('invoice-kind-select');
            const immediatePaymentCard = document.getElementById('immediate-payment-card');
            const purchaseCounterpartySelect = document.getElementById('purchase-counterparty-select');
            const salesCounterpartySelect = document.getElementById('sales-counterparty-select');

            function bindRemove(button) {
                button.addEventListener('click', function () {
                    if (tableBody.querySelectorAll('tr').length <= 1) {
                        return;
                    }

                    button.closest('tr').remove();
                });
            }

            function syncImmediatePayment() {
                const settlementAllowedKinds = ['purchase_invoice', 'sales_invoice'];
                const isSalesKind = ['sales_invoice', 'sales_credit_note'].includes(invoiceKindSelect.value);

                immediatePaymentCard.style.display = settlementAllowedKinds.includes(invoiceKindSelect.value) ? '' : 'none';
                purchaseCounterpartySelect.disabled = isSalesKind;
                salesCounterpartySelect.disabled = ! isSalesKind;
                purchaseCounterpartySelect.classList.toggle('d-none', isSalesKind);
                salesCounterpartySelect.classList.toggle('d-none', ! isSalesKind);
            }

            document.querySelectorAll('.remove-line').forEach(bindRemove);

            addButton.addEventListener('click', function () {
                const index = tableBody.querySelectorAll('tr').length;
                const firstRow = tableBody.querySelector('tr');
                const newRow = firstRow.cloneNode(true);

                newRow.querySelectorAll('select, input').forEach(function (element) {
                    element.name = element.name.replace(/\[\d+\]/, '[' + index + ']');
                    element.value = '';
                });

                tableBody.appendChild(newRow);
                bindRemove(newRow.querySelector('.remove-line'));
            });

            invoiceKindSelect.addEventListener('change', syncImmediatePayment);
            syncImmediatePayment();
        });
    </script>
@endsection
