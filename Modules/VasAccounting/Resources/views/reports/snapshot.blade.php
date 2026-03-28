@extends('layouts.app')

@section('title', $snapshot->snapshot_name ?: $snapshot->report_key)

@section('content')
    @include('vasaccounting::partials.header', [
        'title' => $snapshot->snapshot_name ?: $snapshot->report_key,
        'subtitle' => 'Persisted report snapshot generated from the VAS reporting queue.',
    ])

    <div class="row g-5 g-xl-10 mb-8">
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-muted fs-7 fw-semibold mb-2">Report key</div>
                    <div class="text-gray-900 fw-bold fs-4">{{ $snapshot->report_key }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-muted fs-7 fw-semibold mb-2">Status</div>
                    <div class="text-gray-900 fw-bold fs-4">{{ ucfirst($snapshot->status) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-muted fs-7 fw-semibold mb-2">Generated</div>
                    <div class="text-gray-900 fw-bold fs-4">{{ optional($snapshot->generated_at)->format('Y-m-d H:i') ?: '-' }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-muted fs-7 fw-semibold mb-2">Period</div>
                    <div class="text-gray-900 fw-bold fs-4">{{ $snapshot->accounting_period_id ?: '-' }}</div>
                </div>
            </div>
        </div>
    </div>

    @if ($snapshot->status !== 'ready')
        <div class="alert alert-warning">
            {{ $snapshot->error_message ?: 'This snapshot is still waiting for queue processing.' }}
        </div>
    @else
        @if (! empty($payload['summary']))
            <div class="row g-5 g-xl-10 mb-8">
                @foreach ($payload['summary'] as $metric)
                    <div class="col-md-4">
                        <div class="card card-flush h-100">
                            <div class="card-body">
                                <div class="text-muted fs-7 fw-semibold mb-2">{{ $metric['label'] }}</div>
                                <div class="text-gray-900 fw-bold fs-2">{{ $metric['value'] }}</div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <div class="card card-flush">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed fs-6 gy-5">
                        <thead>
                            <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                @foreach (($payload['columns'] ?? []) as $column)
                                    <th>{{ $column }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @forelse (($payload['rows'] ?? []) as $row)
                                <tr>
                                    @foreach ($row as $cell)
                                        <td>{{ $cell }}</td>
                                    @endforeach
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ count($payload['columns'] ?? []) ?: 1 }}" class="text-muted">No rows were stored in this snapshot.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
@endsection
