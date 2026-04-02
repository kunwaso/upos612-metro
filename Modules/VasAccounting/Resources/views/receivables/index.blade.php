@extends('layouts.app')

@section('title', __('vasaccounting::lang.receivables'))

@section('content')
    @php($currency = config('vasaccounting.book_currency', 'VND'))

    @include('vasaccounting::partials.header', [
        'title' => __('vasaccounting::lang.receivables'),
        'subtitle' => data_get($vasAccountingPageMeta ?? [], 'subtitle'),
    ])

    <div class="row g-5 g-xl-10 mb-8">
        <div class="col-md-3"><div class="card card-flush h-100"><div class="card-body"><div class="text-gray-600 fw-semibold fs-7 mb-2">{{ __('vasaccounting::lang.views.receivables.cards.outstanding') }}</div><div class="text-gray-900 fw-bold fs-1">{{ number_format((float) $aging['total'], 2) }}</div><div class="text-muted fs-8">{{ $currency }}</div></div></div></div>
        <div class="col-md-3"><div class="card card-flush h-100"><div class="card-body"><div class="text-gray-600 fw-semibold fs-7 mb-2">{{ __('vasaccounting::lang.views.receivables.cards.days_1_30') }}</div><div class="text-gray-900 fw-bold fs-1">{{ number_format((float) $aging['days_1_30'], 2) }}</div><div class="text-muted fs-8">{{ $currency }}</div></div></div></div>
        <div class="col-md-3"><div class="card card-flush h-100"><div class="card-body"><div class="text-gray-600 fw-semibold fs-7 mb-2">{{ __('vasaccounting::lang.views.receivables.cards.days_31_60') }}</div><div class="text-gray-900 fw-bold fs-1">{{ number_format((float) $aging['days_31_60'], 2) }}</div><div class="text-muted fs-8">{{ $currency }}</div></div></div></div>
        <div class="col-md-3"><div class="card card-flush h-100"><div class="card-body"><div class="text-gray-600 fw-semibold fs-7 mb-2">{{ __('vasaccounting::lang.views.receivables.cards.days_90_plus') }}</div><div class="text-gray-900 fw-bold fs-1">{{ number_format((float) $aging['days_90_plus'], 2) }}</div><div class="text-muted fs-8">{{ $currency }}</div></div></div></div>
    </div>

    <div class="row g-5 g-xl-10 mb-8">
        <div class="col-xl-4">
            <div class="card card-flush h-100">
                <div class="card-header"><div class="card-title">{{ __('vasaccounting::lang.views.receivables.allocation.title') }}</div></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('vasaccounting.receivables.allocations.store') }}">
                        @csrf
                        <div class="mb-5">
                            <label class="form-label">{{ __('vasaccounting::lang.views.receivables.allocation.invoice_voucher') }}</label>
                            <select name="invoice_voucher_id" class="form-select form-select-solid" required>
                                <option value="">{{ __('vasaccounting::lang.views.receivables.allocation.select_invoice') }}</option>
                                @foreach ($openItems as $item)
                                    <option value="{{ $item->id }}">{{ $item->voucher_no }} | {{ $item->contact_name }} | {{ number_format((float) $item->outstanding_amount, 2) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-5">
                            <label class="form-label">{{ __('vasaccounting::lang.views.receivables.allocation.receipt_voucher') }}</label>
                            <select name="payment_voucher_id" class="form-select form-select-solid" required>
                                <option value="">{{ __('vasaccounting::lang.views.receivables.allocation.select_receipt') }}</option>
                                @foreach ($receiptItems as $item)
                                    <option value="{{ $item->id }}">{{ $item->voucher_no }} | {{ $item->contact_name }} | {{ number_format((float) $item->available_amount, 2) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-5">
                            <label class="form-label">{{ __('vasaccounting::lang.views.receivables.allocation.allocation_date') }}</label>
                            <input type="date" name="allocation_date" value="{{ now()->format('Y-m-d') }}" class="form-control form-control-solid" required>
                        </div>
                        <div class="mb-6">
                            <label class="form-label">{{ __('vasaccounting::lang.views.receivables.allocation.amount') }}</label>
                            <input type="number" name="amount" step="0.01" min="0.01" class="form-control form-control-solid" required>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm">{{ __('vasaccounting::lang.views.receivables.allocation.save') }}</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-xl-8">
            <div class="card card-flush h-100">
                <div class="card-header"><div class="card-title">{{ __('vasaccounting::lang.views.receivables.open_invoices.title') }}</div></div>
                <div class="card-body">
                    @include('vasaccounting::partials.workspace.table_toolbar', [
                        'searchId' => 'vas-receivables-open-search',
                    ])
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-7 gy-4" id="vas-receivables-open-table">
                            <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                    <th>{{ __('vasaccounting::lang.views.receivables.open_invoices.table.invoice') }}</th><th>{{ __('vasaccounting::lang.views.receivables.open_invoices.table.customer') }}</th><th>{{ __('vasaccounting::lang.views.receivables.open_invoices.table.posting_date') }}</th><th>{{ __('vasaccounting::lang.views.receivables.open_invoices.table.outstanding') }}</th><th>{{ __('vasaccounting::lang.views.receivables.open_invoices.table.age') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($openItems as $item)
                                    <tr>
                                        <td>{{ $item->voucher_no }}</td>
                                        <td>{{ $item->contact_name }}</td>
                                        <td>{{ $item->posting_date }}</td>
                                        <td>{{ number_format((float) $item->outstanding_amount, 2) }} {{ $currency }}</td>
                                        <td><span class="badge badge-light-warning">{{ __('vasaccounting::lang.views.receivables.age_days', ['days' => $item->age_days]) }}</span></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-muted">{{ __('vasaccounting::lang.views.receivables.open_invoices.empty') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-5 g-xl-10">
        <div class="col-xl-6">
            <div class="card card-flush h-100">
                <div class="card-header"><div class="card-title">{{ __('vasaccounting::lang.views.receivables.available_receipts.title') }}</div></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-7 gy-4" id="vas-receivables-receipts-table">
                            <thead><tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0"><th>{{ __('vasaccounting::lang.views.receivables.available_receipts.table.receipt') }}</th><th>{{ __('vasaccounting::lang.views.receivables.available_receipts.table.customer') }}</th><th>{{ __('vasaccounting::lang.views.receivables.available_receipts.table.available') }}</th></tr></thead>
                            <tbody>
                                @forelse ($receiptItems as $item)
                                    <tr>
                                        <td>{{ $item->voucher_no }}</td>
                                        <td>{{ $item->contact_name }}</td>
                                        <td>{{ number_format((float) $item->available_amount, 2) }} {{ $currency }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-muted">{{ __('vasaccounting::lang.views.receivables.available_receipts.empty') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="card card-flush h-100">
                <div class="card-header"><div class="card-title">{{ __('vasaccounting::lang.views.receivables.recent_allocations.title') }}</div></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-7 gy-4" id="vas-receivables-allocations-table">
                            <thead><tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0"><th>{{ __('vasaccounting::lang.views.receivables.recent_allocations.table.date') }}</th><th>{{ __('vasaccounting::lang.views.receivables.recent_allocations.table.customer') }}</th><th>{{ __('vasaccounting::lang.views.receivables.recent_allocations.table.invoice') }}</th><th>{{ __('vasaccounting::lang.views.receivables.recent_allocations.table.receipt') }}</th><th>{{ __('vasaccounting::lang.views.receivables.recent_allocations.table.amount') }}</th></tr></thead>
                            <tbody>
                                @forelse ($recentAllocations as $allocation)
                                    <tr>
                                        <td>{{ $allocation->allocation_date }}</td>
                                        <td>{{ $allocation->contact_name }}</td>
                                        <td>{{ $allocation->invoice_voucher_no }}</td>
                                        <td>{{ $allocation->payment_voucher_no }}</td>
                                        <td>{{ number_format((float) $allocation->amount, 2) }} {{ $currency }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-muted">{{ __('vasaccounting::lang.views.receivables.recent_allocations.empty') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('javascript')
    @include('vasaccounting::partials.workspace_scripts')
    <script>
        $(document).ready(function () {
            const openTable = window.VasWorkspace?.initLocalDataTable('#vas-receivables-open-table', {
                order: [[2, 'desc']],
                pageLength: 10
            });

            if (openTable) {
                $('#vas-receivables-open-search').on('keyup', function () {
                    openTable.search(this.value).draw();
                });
            }

            window.VasWorkspace?.initLocalDataTable('#vas-receivables-receipts-table', {
                order: [],
                pageLength: 10
            });
            window.VasWorkspace?.initLocalDataTable('#vas-receivables-allocations-table', {
                order: [[0, 'desc']],
                pageLength: 10
            });
        });
    </script>
@endsection
