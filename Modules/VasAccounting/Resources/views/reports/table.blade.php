@extends('layouts.app')

@section('title', $title)

@section('content')
    @include('vasaccounting::partials.header', [
        'title' => $title,
        'subtitle' => 'Report output generated from VAS vouchers, journal entries, and enterprise control tables.',
    ])

    @if (! empty($summary))
        <div class="row g-5 g-xl-10 mb-8">
            @foreach ($summary as $metric)
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
                            @foreach ($columns as $column)
                                <th>{{ $column }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            <tr>
                                @foreach ($row as $cell)
                                    <td>{{ $cell }}</td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($columns) }}" class="text-muted">No report rows are available for the current filters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
