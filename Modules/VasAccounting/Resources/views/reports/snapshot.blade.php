@extends('layouts.app')

@section('title', $snapshot->snapshot_name ?: $snapshot->report_key)

@section('content')
    @php
        $snapshotCards = [
            [
                'key' => 'report_key',
                'label' => __('vasaccounting::lang.views.report_snapshot.cards.report_key'),
                'value' => $vasAccountingUtil->reportKeyLabel((string) $snapshot->report_key),
                'delta' => null,
                'direction' => 'flat',
                'hint' => null,
                'icon' => 'ki-outline ki-chart-line',
                'badgeVariant' => 'light-primary',
            ],
            [
                'key' => 'status',
                'label' => __('vasaccounting::lang.views.report_snapshot.cards.status'),
                'value' => $vasAccountingUtil->genericStatusLabel((string) $snapshot->status),
                'delta' => null,
                'direction' => 'flat',
                'hint' => null,
                'icon' => 'ki-outline ki-information-4',
                'badgeVariant' => $snapshot->status === 'ready' ? 'light-success' : ($snapshot->status === 'failed' ? 'light-danger' : 'light-warning'),
            ],
            [
                'key' => 'generated_at',
                'label' => __('vasaccounting::lang.views.report_snapshot.cards.generated_at'),
                'value' => optional($snapshot->generated_at)->format('Y-m-d H:i') ?: '-',
                'delta' => null,
                'direction' => 'flat',
                'hint' => null,
                'icon' => 'ki-outline ki-calendar-8',
                'badgeVariant' => 'light-info',
            ],
            [
                'key' => 'row_count',
                'label' => __('vasaccounting::lang.views.report_snapshot.cards.row_count'),
                'value' => count($payload['rows'] ?? []),
                'delta' => null,
                'direction' => 'flat',
                'hint' => null,
                'icon' => 'ki-outline ki-row-horizontal',
                'badgeVariant' => 'light-warning',
            ],
        ];
        $sectionWidgetItems = collect((array) ($payload['sections'] ?? []))->take(5)->map(function ($section) {
            return [
                'title' => (string) data_get($section, 'title', 'Section'),
                'description' => __('vasaccounting::lang.views.report_snapshot.cards.row_count') . ': ' . count((array) data_get($section, 'rows', [])),
                'icon' => 'ki-outline ki-abstract-26',
                'badgeVariant' => 'light-primary',
            ];
        })->all();
    @endphp

    @include('vasaccounting::partials.header', [
        'title' => $snapshot->snapshot_name ?: $snapshot->report_key,
        'subtitle' => __('vasaccounting::lang.views.report_snapshot.page_subtitle'),
    ])

    <div class="mb-8" id="vas-report-snapshot-kpis">
        @include('vasaccounting::partials.workspace.kpi_strip', ['cards' => $snapshotCards])
    </div>

    @if ($snapshot->status !== 'ready')
        <div class="alert alert-warning d-flex align-items-start gap-3 mb-8">
            <i class="ki-outline ki-information-4 fs-2 text-warning mt-1"></i>
            <div>
                <div class="fw-bold">{{ __('vasaccounting::lang.views.report_snapshot.not_ready.title') }}</div>
                <div class="text-muted">{{ $snapshot->error_message ?: __('vasaccounting::lang.views.report_snapshot.not_ready.body') }}</div>
            </div>
        </div>
    @else
        @if (!empty($payload['summary']))
            <div class="row g-5 g-xl-10 mb-8">
                @foreach ($payload['summary'] as $metric)
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

        <div class="row g-5 g-xl-10 mb-8">
            <div class="col-xl-8">
                <div class="card card-flush">
                    <div class="card-header align-items-center py-5 gap-2 gap-md-5">
                        <div class="card-title">
                            <h3 class="fw-bold m-0">{{ __('vasaccounting::lang.views.report_snapshot.dataset_title') }}</h3>
                        </div>
                        <div class="card-toolbar">
                            <a href="{{ route('vasaccounting.reports.index') }}" class="btn btn-sm btn-light-primary">{{ $vasAccountingUtil->actionLabel('back_to_reports') }}</a>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        @include('vasaccounting::partials.workspace.table_toolbar', [
                            'searchId' => 'vas-report-snapshot-search',
                            'actions' => [],
                        ])
                        <div class="table-responsive">
                            <table class="table align-middle table-row-dashed fs-6 gy-5" id="vas-report-snapshot-table">
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
                                            @foreach ($row as $index => $cell)
                                                <td class="{{ $index === 0 ? 'fw-semibold text-gray-900' : 'text-gray-700' }}">{{ $cell }}</td>
                                            @endforeach
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="{{ count($payload['columns'] ?? []) ?: 1 }}" class="text-muted">{{ __('vasaccounting::lang.views.report_snapshot.empty') }}</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-4">
                @include('vasaccounting::partials.workspace.side_widget', [
                    'title' => __('vasaccounting::lang.views.reports.recent_snapshots.title'),
                    'subtitle' => __('vasaccounting::lang.views.report_snapshot.page_subtitle'),
                    'items' => $sectionWidgetItems,
                ])
            </div>
        </div>

        @if (!empty($payload['sections']))
            <div class="row g-5 g-xl-10 mt-1">
                @foreach ($payload['sections'] as $section)
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
                                    <table class="table align-middle table-row-dashed fs-6 gy-5" data-vas-snapshot-section-table="1">
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
                                                        {{ data_get($section, 'empty') ?: __('vasaccounting::lang.views.report_snapshot.empty') }}
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
    @endif
@endsection

@section('javascript')
    @include('vasaccounting::partials.workspace_scripts')
    <script>
        $(document).ready(function () {
            const snapshotTable = window.VasWorkspace?.initLocalDataTable('#vas-report-snapshot-table', {
                order: [],
                pageLength: 25
            });

            if (snapshotTable) {
                $('#vas-report-snapshot-search').on('keyup', function () {
                    snapshotTable.search(this.value).draw();
                });
            }

            $('table[data-vas-snapshot-section-table="1"]').each(function () {
                const tableId = $(this).attr('id') || ('vas-snapshot-section-table-' + Math.floor(Math.random() * 1000000));
                $(this).attr('id', tableId);
                window.VasWorkspace?.initLocalDataTable('#' + tableId, {
                    order: []
                });
            });
        });
    </script>
@endsection
