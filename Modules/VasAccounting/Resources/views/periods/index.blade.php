@extends('layouts.app')

@section('title', __('vasaccounting::lang.periods'))

@section('content')
    @php
        $periodSummary = [
            'open' => $periods->where('status', 'open')->count(),
            'soft_locked' => $periods->where('status', 'soft_locked')->count(),
            'closed' => $periods->where('status', 'closed')->count(),
            'adjustment' => $periods->where('is_adjustment_period', true)->count(),
        ];
    @endphp

    @include('vasaccounting::partials.header', [
        'title' => __('vasaccounting::lang.periods'),
        'subtitle' => 'Open, adjustment, and closed periods used by the VAS ledger and close center.',
    ])

    <div class="row g-5 g-xl-10 mb-8">
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-gray-700 fw-semibold fs-7 mb-2">Open</div>
                    <div class="text-gray-900 fw-bold fs-2">{{ $periodSummary['open'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-gray-700 fw-semibold fs-7 mb-2">Soft locked</div>
                    <div class="text-gray-900 fw-bold fs-2">{{ $periodSummary['soft_locked'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-gray-700 fw-semibold fs-7 mb-2">Closed</div>
                    <div class="text-gray-900 fw-bold fs-2">{{ $periodSummary['closed'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-gray-700 fw-semibold fs-7 mb-2">Adjustment periods</div>
                    <div class="text-gray-900 fw-bold fs-2">{{ $periodSummary['adjustment'] }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-5 g-xl-10">
        <div class="col-xl-8">
            <div class="card card-flush">
                <div class="card-header">
                    <div class="card-title d-flex flex-column">
                        <span>Period Register</span>
                        <span class="text-muted fw-semibold fs-8 mt-1">Control posting windows and close transitions.</span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-6 gy-5">
                            <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                    <th>Name</th>
                                    <th>Start</th>
                                    <th>End</th>
                                    <th>Adjustment</th>
                                    <th>Status</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($periods as $period)
                                    <tr>
                                        <td class="text-gray-900 fw-semibold">{{ $vasAccountingUtil->localizedPeriodName($period->name) }}</td>
                                        <td>{{ $period->start_date }}</td>
                                        <td>{{ $period->end_date }}</td>
                                        <td>
                                            <span class="badge {{ $period->is_adjustment_period ? 'badge-light-warning' : 'badge-light-secondary' }}">
                                                {{ $period->is_adjustment_period ? 'Yes' : 'No' }}
                                            </span>
                                        </td>
                                        <td><span class="badge {{ $period->status === 'closed' ? 'badge-light-danger' : ($period->status === 'soft_locked' ? 'badge-light-warning' : 'badge-light-success') }}">{{ $vasAccountingUtil->periodStatusLabel((string) $period->status) }}</span></td>
                                        <td class="text-end">
                                            @if ($period->status !== 'closed')
                                                <form method="POST" action="{{ route('vasaccounting.periods.close', $period->id) }}">
                                                    @csrf
                                                    <button type="submit" class="btn btn-light-primary btn-sm">Close</button>
                                                </form>
                                            @else
                                                <span class="text-muted fs-8">Closed</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card card-flush">
                <div class="card-header">
                    <div class="card-title">Add Period</div>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('vasaccounting.periods.store') }}">
                        @csrf
                        <div class="mb-5">
                            <label class="form-label required">Name</label>
                            <input type="text" class="form-control form-control-solid" name="name">
                        </div>
                        <div class="mb-5">
                            <label class="form-label required">Start date</label>
                            <input type="text" class="form-control form-control-solid" name="start_date" placeholder="YYYY-MM-DD">
                        </div>
                        <div class="mb-5">
                            <label class="form-label required">End date</label>
                            <input type="text" class="form-control form-control-solid" name="end_date" placeholder="YYYY-MM-DD">
                        </div>
                        <div class="form-check form-check-custom form-check-solid mb-5">
                            <input class="form-check-input" type="checkbox" value="1" name="is_adjustment_period">
                            <label class="form-check-label">Adjustment period</label>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary">Save period</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
