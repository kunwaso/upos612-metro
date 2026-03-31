@extends('layouts.app')

@section('title', __('vasaccounting::lang.payables'))

@section('content')
    @php($currency = config('vasaccounting.book_currency', 'VND'))

    @include('vasaccounting::partials.header', [
        'title' => __('vasaccounting::lang.payables'),
        'subtitle' => data_get($vasAccountingPageMeta ?? [], 'subtitle'),
    ])

    <div class="row g-5 g-xl-10 mb-8">
        <div class="col-md-3"><div class="card card-flush h-100"><div class="card-body"><div class="text-gray-600 fw-semibold fs-7 mb-2">{{ __('vasaccounting::lang.views.payables.cards.outstanding') }}</div><div class="text-gray-900 fw-bold fs-1">{{ number_format((float) $aging['total'], 2) }}</div><div class="text-muted fs-8">{{ $currency }}</div></div></div></div>
        <div class="col-md-3"><div class="card card-flush h-100"><div class="card-body"><div class="text-gray-600 fw-semibold fs-7 mb-2">{{ __('vasaccounting::lang.views.payables.cards.days_1_30') }}</div><div class="text-gray-900 fw-bold fs-1">{{ number_format((float) $aging['days_1_30'], 2) }}</div><div class="text-muted fs-8">{{ $currency }}</div></div></div></div>
        <div class="col-md-3"><div class="card card-flush h-100"><div class="card-body"><div class="text-gray-600 fw-semibold fs-7 mb-2">{{ __('vasaccounting::lang.views.payables.cards.days_31_60') }}</div><div class="text-gray-900 fw-bold fs-1">{{ number_format((float) $aging['days_31_60'], 2) }}</div><div class="text-muted fs-8">{{ $currency }}</div></div></div></div>
        <div class="col-md-3"><div class="card card-flush h-100"><div class="card-body"><div class="text-gray-600 fw-semibold fs-7 mb-2">{{ __('vasaccounting::lang.views.payables.cards.days_90_plus') }}</div><div class="text-gray-900 fw-bold fs-1">{{ number_format((float) $aging['days_90_plus'], 2) }}</div><div class="text-muted fs-8">{{ $currency }}</div></div></div></div>
    </div>

    <div class="row g-5 g-xl-10 mb-8">
        <div class="col-xl-4">
            <div class="card card-flush h-100">
                <div class="card-header"><div class="card-title">{{ __('vasaccounting::lang.views.payables.allocation.title') }}</div></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('vasaccounting.payables.allocations.store') }}">
                        @csrf
                        <div class="mb-5">
                            <label class="form-label">{{ __('vasaccounting::lang.views.payables.allocation.bill_voucher') }}</label>
                            <select name="bill_voucher_id" class="form-select form-select-solid" required>
                                <option value="">{{ __('vasaccounting::lang.views.payables.allocation.select_bill') }}</option>
                                @foreach ($openItems as $item)
                                    <option value="{{ $item->id }}">{{ $item->voucher_no }} | {{ $item->contact_name }} | {{ number_format((float) $item->outstanding_amount, 2) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-5">
                            <label class="form-label">{{ __('vasaccounting::lang.views.payables.allocation.payment_voucher') }}</label>
                            <select name="payment_voucher_id" class="form-select form-select-solid" required>
                                <option value="">{{ __('vasaccounting::lang.views.payables.allocation.select_payment') }}</option>
                                @foreach ($paymentItems as $item)
                                    <option value="{{ $item->id }}">{{ $item->voucher_no }} | {{ $item->contact_name }} | {{ number_format((float) $item->available_amount, 2) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-5">
                            <label class="form-label">{{ __('vasaccounting::lang.views.payables.allocation.allocation_date') }}</label>
                            <input type="date" name="allocation_date" value="{{ now()->format('Y-m-d') }}" class="form-control form-control-solid" required>
                        </div>
                        <div class="mb-6">
                            <label class="form-label">{{ __('vasaccounting::lang.views.payables.allocation.amount') }}</label>
                            <input type="number" name="amount" step="0.01" min="0.01" class="form-control form-control-solid" required>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm">{{ __('vasaccounting::lang.views.payables.allocation.save') }}</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-xl-8">
            <div class="card card-flush h-100">
                <div class="card-header"><div class="card-title">{{ __('vasaccounting::lang.views.payables.open_bills.title') }}</div></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-7 gy-4">
                            <thead><tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0"><th>{{ __('vasaccounting::lang.views.payables.open_bills.table.bill') }}</th><th>{{ __('vasaccounting::lang.views.payables.open_bills.table.vendor') }}</th><th>{{ __('vasaccounting::lang.views.payables.open_bills.table.posting_date') }}</th><th>{{ __('vasaccounting::lang.views.payables.open_bills.table.outstanding') }}</th><th>{{ __('vasaccounting::lang.views.payables.open_bills.table.age') }}</th></tr></thead>
                            <tbody>
                                @forelse ($openItems as $item)
                                    <tr>
                                        <td>{{ $item->voucher_no }}</td>
                                        <td>{{ $item->contact_name }}</td>
                                        <td>{{ $item->posting_date }}</td>
                                        <td>{{ number_format((float) $item->outstanding_amount, 2) }} {{ $currency }}</td>
                                        <td><span class="badge badge-light-warning">{{ __('vasaccounting::lang.views.payables.age_days', ['days' => $item->age_days]) }}</span></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-muted">{{ __('vasaccounting::lang.views.payables.open_bills.empty') }}</td></tr>
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
                <div class="card-header"><div class="card-title">{{ __('vasaccounting::lang.views.payables.available_payments.title') }}</div></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-7 gy-4">
                            <thead><tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0"><th>{{ __('vasaccounting::lang.views.payables.available_payments.table.payment') }}</th><th>{{ __('vasaccounting::lang.views.payables.available_payments.table.vendor') }}</th><th>{{ __('vasaccounting::lang.views.payables.available_payments.table.available') }}</th></tr></thead>
                            <tbody>
                                @forelse ($paymentItems as $item)
                                    <tr>
                                        <td>{{ $item->voucher_no }}</td>
                                        <td>{{ $item->contact_name }}</td>
                                        <td>{{ number_format((float) $item->available_amount, 2) }} {{ $currency }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-muted">{{ __('vasaccounting::lang.views.payables.available_payments.empty') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="card card-flush h-100">
                <div class="card-header"><div class="card-title">{{ __('vasaccounting::lang.views.payables.recent_allocations.title') }}</div></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-7 gy-4">
                            <thead><tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0"><th>{{ __('vasaccounting::lang.views.payables.recent_allocations.table.date') }}</th><th>{{ __('vasaccounting::lang.views.payables.recent_allocations.table.vendor') }}</th><th>{{ __('vasaccounting::lang.views.payables.recent_allocations.table.bill') }}</th><th>{{ __('vasaccounting::lang.views.payables.recent_allocations.table.payment') }}</th><th>{{ __('vasaccounting::lang.views.payables.recent_allocations.table.amount') }}</th></tr></thead>
                            <tbody>
                                @forelse ($recentAllocations as $allocation)
                                    <tr>
                                        <td>{{ $allocation->allocation_date }}</td>
                                        <td>{{ $allocation->contact_name }}</td>
                                        <td>{{ $allocation->bill_voucher_no }}</td>
                                        <td>{{ $allocation->payment_voucher_no }}</td>
                                        <td>{{ number_format((float) $allocation->amount, 2) }} {{ $currency }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-muted">{{ __('vasaccounting::lang.views.payables.recent_allocations.empty') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
