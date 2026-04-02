@extends('layouts.app')

@section('title', $title)

@section('content')
    @php
        $workspaceActions = collect($actions ?? [])->values()->all();
        $workspaceActions[] = [
            'label' => $vasAccountingUtil->actionLabel('back_to_reports'),
            'url' => route('vasaccounting.reports.index'),
            'style' => 'light-primary',
            'method' => 'GET',
        ];
        $datatableUrl = !empty($reportKey)
            ? route('vasaccounting.ui.reports.datatable', ['reportKey' => $reportKey])
            : null;
        unset($actions);
    @endphp

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

    @if (!empty($reportManagement))
        <div class="card card-flush mb-8">
            <div class="card-header">
                <div class="card-title d-flex flex-column">
                    <span>{{ data_get($reportManagement, 'title') }}</span>
                    @if (data_get($reportManagement, 'subtitle'))
                        <span class="text-muted fw-semibold fs-8 mt-1">{{ data_get($reportManagement, 'subtitle') }}</span>
                    @endif
                </div>
            </div>
            <div class="card-body pt-0">
                <form method="POST" action="{{ data_get($reportManagement, 'route') }}" class="d-flex flex-column flex-md-row gap-3 align-items-md-end">
                    @csrf
                    <div class="flex-grow-1">
                        <label class="form-label fw-semibold fs-7">{{ data_get($reportManagement, 'owner_label') }}</label>
                        <select name="owner_id" class="form-select" required>
                            <option value="">{{ data_get($reportManagement, 'owner_placeholder') }}</option>
                            @foreach ((array) data_get($reportManagement, 'owner_options', []) as $ownerId => $ownerLabel)
                                <option value="{{ $ownerId }}">{{ $ownerLabel }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <button type="submit" class="btn btn-light-warning">{{ data_get($reportManagement, 'assign_label') }}</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <div class="card card-flush">
        <div class="card-header align-items-center py-5 gap-2 gap-md-5">
            <div class="card-title">
                <h3 class="fw-bold m-0">{{ __('vasaccounting::lang.views.report_table.dataset_title') }}</h3>
            </div>
        </div>
        <div class="card-body pt-0">
            @include('vasaccounting::partials.workspace.table_toolbar', [
                'searchId' => 'vas-report-table-search',
                'actions' => $workspaceActions,
            ])
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-6 gy-5" id="vas-report-main-table">
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
                                    <td class="{{ $index === 0 ? 'fw-semibold text-gray-900' : 'text-gray-700' }}">{!! nl2br(e($cell)) !!}</td>
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
                                <table class="table align-middle table-row-dashed fs-6 gy-5" data-vas-local-table="1">
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

@section('javascript')
    @include('vasaccounting::partials.workspace_scripts')
    <script>
        $(document).ready(function () {
            const datatableUrl = @json($datatableUrl);
            const columns = @json(array_values(array_map(fn ($index) => ['data' => $index], array_keys($columns))));

            let reportTable = null;
            if (datatableUrl) {
                reportTable = window.VasWorkspace?.initAjaxDataTable('#vas-report-main-table', datatableUrl, columns, {
                    order: [],
                    pageLength: 25
                });
            } else {
                reportTable = window.VasWorkspace?.initLocalDataTable('#vas-report-main-table', {
                    order: [],
                    pageLength: 25
                });
            }

            if (reportTable) {
                $('#vas-report-table-search').on('keyup', function () {
                    reportTable.search(this.value).draw();
                });
            }

            $('table[data-vas-local-table="1"]').each(function () {
                const tableId = $(this).attr('id') || ('vas-report-section-table-' + Math.floor(Math.random() * 1000000));
                $(this).attr('id', tableId);
                window.VasWorkspace?.initLocalDataTable('#' + tableId, {
                    order: []
                });
            });
        });
    </script>
@endsection
