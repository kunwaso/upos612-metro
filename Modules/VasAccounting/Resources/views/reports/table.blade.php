@extends('layouts.app')

@section('title', $title)

@section('content')
    @include('vasaccounting::partials.header', [
        'title' => $title,
        'subtitle' => __('vasaccounting::lang.views.report_table.page_subtitle'),
    ])

    <div class="row g-5 g-xl-10 mb-8">
        <div class="col-md-4">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-muted fs-7 fw-semibold mb-2">{{ __('vasaccounting::lang.views.report_table.cards.row_count') }}</div>
                    <div class="text-gray-900 fw-bold fs-2">{{ count($rows) }}</div>
                    <div class="text-gray-600 fs-7 mt-1">{{ __('vasaccounting::lang.views.report_table.cards.row_count_help') }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-muted fs-7 fw-semibold mb-2">{{ __('vasaccounting::lang.views.report_table.cards.column_count') }}</div>
                    <div class="text-gray-900 fw-bold fs-2">{{ count($columns) }}</div>
                    <div class="text-gray-600 fs-7 mt-1">{{ __('vasaccounting::lang.views.report_table.cards.column_count_help') }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="text-muted fs-7 fw-semibold mb-2">{{ __('vasaccounting::lang.views.report_table.cards.route_key') }}</div>
                    <div class="text-gray-900 fw-bold fs-4">{{ request()->route()?->getName() }}</div>
                    <div class="text-gray-600 fs-7 mt-1">{{ __('vasaccounting::lang.views.report_table.cards.route_key_help') }}</div>
                </div>
            </div>
        </div>
    </div>

    @if (!empty($summary))
        <div class="row g-5 g-xl-10 mb-8">
            @foreach ($summary as $metric)
                <div class="col-md-4 col-xl-3">
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
        <div class="card-header align-items-center py-5 gap-2 gap-md-5">
            <div class="card-title">
                <h3 class="fw-bold m-0">{{ __('vasaccounting::lang.views.report_table.dataset_title') }}</h3>
            </div>
            <div class="card-toolbar">
                <a href="{{ route('vasaccounting.reports.index') }}" class="btn btn-sm btn-light-primary">{{ $vasAccountingUtil->actionLabel('back_to_reports') }}</a>
            </div>
        </div>
        <div class="card-body pt-0">
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
                                @foreach ($row as $index => $cell)
                                    <td class="{{ $index === 0 ? 'fw-semibold text-gray-900' : 'text-gray-700' }}">{{ $cell }}</td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($columns) }}" class="text-muted">{{ __('vasaccounting::lang.views.report_table.empty') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @if (!empty($sections))
        <div class="row g-5 g-xl-10 mt-1">
            @foreach ($sections as $section)
                <div class="col-12">
                    <div class="card card-flush">
                        <div class="card-header">
                            <div class="card-title d-flex flex-column">
                                <span>{{ data_get($section, 'title') }}</span>
                                @if (data_get($section, 'subtitle'))
                                    <span class="text-muted fw-semibold fs-8 mt-1">{{ data_get($section, 'subtitle') }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="card-body pt-0">
                            <div class="table-responsive">
                                <table class="table align-middle table-row-dashed fs-6 gy-5">
                                    <thead>
                                        <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                            @foreach ((array) data_get($section, 'columns', []) as $column)
                                                <th>{{ $column }}</th>
                                            @endforeach
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ((array) data_get($section, 'rows', []) as $row)
                                            <tr>
                                                @foreach ($row as $index => $cell)
                                                    <td class="{{ $index === 0 ? 'fw-semibold text-gray-900' : 'text-gray-700' }}">{{ $cell }}</td>
                                                @endforeach
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="{{ count((array) data_get($section, 'columns', [])) ?: 1 }}" class="text-muted">
                                                    {{ data_get($section, 'empty') ?: __('vasaccounting::lang.views.report_table.empty') }}
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
@endsection
