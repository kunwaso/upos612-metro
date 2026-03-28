@extends('layouts.app')

@section('title', __('vasaccounting::lang.reports'))

@section('content')
    @include('vasaccounting::partials.header', [
        'title' => __('vasaccounting::lang.reports'),
        'subtitle' => 'Enterprise reporting hub with live views, queued snapshots, and close-ready packs generated from VAS journals.',
    ])

    <div class="row g-5 g-xl-10 mb-8">
        @foreach ($hubSummary as $metric)
            <div class="col-md-3">
                <div class="card card-flush h-100">
                    <div class="card-body">
                        <div class="text-muted fs-7 fw-semibold mb-2">{{ $metric['label'] }}</div>
                        <div class="text-gray-900 fw-bold fs-2">{{ $metric['value'] }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    @foreach (collect($reportDefinitions)->groupBy('group', true) as $group => $reports)
        <div class="card card-flush mb-8">
            <div class="card-header">
                <div class="card-title">{{ $group }}</div>
            </div>
            <div class="card-body">
                <div class="row g-5">
                    @foreach ($reports as $reportKey => $report)
                        <div class="col-xl-4 col-md-6">
                            <div class="border border-gray-300 rounded p-6 h-100 d-flex flex-column">
                                <div class="fw-bold fs-4 text-gray-900 mb-2">{{ $report['title'] }}</div>
                                <div class="text-muted fs-7 mb-6 flex-grow-1">{{ $report['description'] }}</div>
                                <div class="d-flex flex-wrap gap-2">
                                    <a href="{{ route($report['route']) }}" class="btn btn-light-primary btn-sm">Open live report</a>
                                    <form method="POST" action="{{ route('vasaccounting.reports.snapshots.store') }}">
                                        @csrf
                                        <input type="hidden" name="report_key" value="{{ $reportKey }}">
                                        <button type="submit" class="btn btn-light btn-sm">Queue snapshot</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endforeach

    <div class="card card-flush">
        <div class="card-header">
            <div class="card-title">Recent snapshots</div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-6 gy-5">
                    <thead>
                        <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                            <th>Snapshot</th>
                            <th>Report</th>
                            <th>Status</th>
                            <th>Generated</th>
                            <th class="text-end"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentSnapshots as $snapshot)
                            <tr>
                                <td class="fw-semibold text-gray-900">{{ $snapshot->snapshot_name ?: $snapshot->report_key }}</td>
                                <td>{{ str_replace('_', ' ', $snapshot->report_key) }}</td>
                                <td>
                                    <span class="badge {{ $snapshot->status === 'ready' ? 'badge-light-success' : ($snapshot->status === 'failed' ? 'badge-light-danger' : 'badge-light-warning') }}">
                                        {{ ucfirst($snapshot->status) }}
                                    </span>
                                </td>
                                <td>{{ optional($snapshot->generated_at)->format('Y-m-d H:i') ?: '-' }}</td>
                                <td class="text-end">
                                    @if ($snapshot->status === 'ready')
                                        <a href="{{ route('vasaccounting.reports.snapshots.show', $snapshot->id) }}" class="btn btn-light-primary btn-sm">Open</a>
                                    @else
                                        <span class="text-muted fs-8">{{ $snapshot->error_message ?: 'Waiting for queue.' }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-muted">No report snapshots have been queued yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
