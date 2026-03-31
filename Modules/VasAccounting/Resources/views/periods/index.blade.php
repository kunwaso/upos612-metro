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
        'subtitle' => data_get($vasAccountingPageMeta ?? [], 'subtitle'),
    ])

    <div class="row g-5 g-xl-10 mb-8">
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-gray-700 fw-semibold fs-7 mb-2">{{ __('vasaccounting::lang.views.periods.cards.open') }}</div>
                    <div class="text-gray-900 fw-bold fs-2">{{ $periodSummary['open'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-gray-700 fw-semibold fs-7 mb-2">{{ __('vasaccounting::lang.views.periods.cards.soft_locked') }}</div>
                    <div class="text-gray-900 fw-bold fs-2">{{ $periodSummary['soft_locked'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-gray-700 fw-semibold fs-7 mb-2">{{ __('vasaccounting::lang.views.periods.cards.closed') }}</div>
                    <div class="text-gray-900 fw-bold fs-2">{{ $periodSummary['closed'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-gray-700 fw-semibold fs-7 mb-2">{{ __('vasaccounting::lang.views.periods.cards.adjustment') }}</div>
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
                        <span>{{ __('vasaccounting::lang.views.periods.register.title') }}</span>
                        <span class="text-muted fw-semibold fs-8 mt-1">{{ __('vasaccounting::lang.views.periods.register.subtitle') }}</span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-6 gy-5">
                            <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                    <th>{{ __('vasaccounting::lang.views.periods.table.name') }}</th>
                                    <th>{{ __('vasaccounting::lang.views.periods.table.start') }}</th>
                                    <th>{{ __('vasaccounting::lang.views.periods.table.end') }}</th>
                                    <th>{{ __('vasaccounting::lang.views.periods.table.adjustment') }}</th>
                                    <th>{{ __('vasaccounting::lang.views.periods.table.status') }}</th>
                                    <th class="text-end">{{ __('vasaccounting::lang.views.periods.table.action') }}</th>
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
                                                {{ $period->is_adjustment_period ? __('vasaccounting::lang.views.shared.yes') : __('vasaccounting::lang.views.shared.no') }}
                                            </span>
                                        </td>
                                        <td><span class="badge {{ $period->status === 'closed' ? 'badge-light-danger' : ($period->status === 'soft_locked' ? 'badge-light-warning' : 'badge-light-success') }}">{{ $vasAccountingUtil->periodStatusLabel((string) $period->status) }}</span></td>
                                        <td class="text-end">
                                            @if ($period->status !== 'closed')
                                                <form method="POST" action="{{ route('vasaccounting.periods.close', $period->id) }}">
                                                    @csrf
                                                    <button type="submit" class="btn btn-light-primary btn-sm">{{ __('vasaccounting::lang.views.periods.actions.close') }}</button>
                                                </form>
                                            @else
                                                <span class="text-muted fs-8">{{ __('vasaccounting::lang.views.shared.closed') }}</span>
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
                    <div class="card-title">{{ __('vasaccounting::lang.views.periods.form.title') }}</div>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('vasaccounting.periods.store') }}">
                        @csrf
                        <div class="mb-5">
                            <label class="form-label required">{{ __('vasaccounting::lang.views.periods.form.name') }}</label>
                            <input type="text" class="form-control form-control-solid" name="name">
                        </div>
                        <div class="mb-5">
                            <label class="form-label required">{{ __('vasaccounting::lang.views.periods.form.start_date') }}</label>
                            <input type="text" class="form-control form-control-solid" name="start_date" placeholder="YYYY-MM-DD">
                        </div>
                        <div class="mb-5">
                            <label class="form-label required">{{ __('vasaccounting::lang.views.periods.form.end_date') }}</label>
                            <input type="text" class="form-control form-control-solid" name="end_date" placeholder="YYYY-MM-DD">
                        </div>
                        <div class="form-check form-check-custom form-check-solid mb-5">
                            <input class="form-check-input" type="checkbox" value="1" name="is_adjustment_period">
                            <label class="form-check-label">{{ __('vasaccounting::lang.views.periods.form.adjustment_period') }}</label>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary">{{ __('vasaccounting::lang.views.periods.form.save') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
