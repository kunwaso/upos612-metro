@extends('layouts.app')

@section('title', $domainConfig['title'])

@section('content')
    @include('vasaccounting::partials.header', [
        'title' => $domainConfig['title'],
        'subtitle' => $domainConfig['subtitle'],
    ])

    <div class="row g-5 g-xl-10 mb-8">
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-gray-700 fw-semibold fs-7 mb-2">{{ $summary['record_label'] }}</div>
                    <div class="text-gray-900 fw-bold fs-2">{{ $summary['records'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-gray-700 fw-semibold fs-7 mb-2">{{ __('vasaccounting::lang.views.enterprise.cards.posted_vouchers') }}</div>
                    <div class="text-gray-900 fw-bold fs-2">{{ $summary['posted_vouchers'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-gray-700 fw-semibold fs-7 mb-2">{{ __('vasaccounting::lang.views.enterprise.cards.workflow_backlog') }}</div>
                    <div class="text-gray-900 fw-bold fs-2">{{ $summary['workflow_backlog'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-gray-700 fw-semibold fs-7 mb-2">{{ __('vasaccounting::lang.views.enterprise.cards.connected_adapters') }}</div>
                    <div class="text-gray-900 fw-bold fs-2">{{ $summary['provider_count'] }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-5 g-xl-10">
        <div class="col-xl-7">
            <div class="card card-flush h-100">
                <div class="card-header">
                    <div class="card-title">{{ __('vasaccounting::lang.views.enterprise.capabilities.title') }}</div>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        @foreach ($domainConfig['capabilities'] as $capability)
                            <div class="col-md-6">
                                <div class="border border-gray-300 rounded p-4 h-100">
                                    <div class="fw-bold text-gray-900 mb-2">{{ $capability['title'] }}</div>
                                    <div class="text-muted fs-7">{{ $capability['description'] }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-5">
            <div class="card card-flush h-100">
                <div class="card-header">
                    <div class="card-title">{{ __('vasaccounting::lang.views.enterprise.recent_vouchers.title') }}</div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-6 gy-5">
                            <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                    <th>{{ __('vasaccounting::lang.views.shared.voucher') }}</th>
                                    <th>{{ __('vasaccounting::lang.views.shared.status') }}</th>
                                    <th>{{ __('vasaccounting::lang.views.shared.date') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($recentVouchers as $voucher)
                                    <tr>
                                        <td>
                                            <a href="{{ route('vasaccounting.vouchers.show', $voucher->id) }}" class="text-gray-900 fw-semibold">
                                                {{ $voucher->voucher_no }}
                                            </a>
                                        </td>
                                        <td><span class="badge badge-light-primary">{{ $vasAccountingUtil->documentStatusLabel((string) $voucher->status) }}</span></td>
                                        <td>{{ optional($voucher->posting_date)->format('Y-m-d') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-muted">{{ __('vasaccounting::lang.views.enterprise.recent_vouchers.empty') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
