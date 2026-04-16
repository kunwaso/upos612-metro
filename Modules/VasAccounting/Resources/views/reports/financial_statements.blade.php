@extends('layouts.app')

@section('title', $title)

@section('content')
    @include('vasaccounting::partials.header', [
        'title' => $title,
        'subtitle' => __('vasaccounting::lang.views.report_table.page_subtitle'),
    ])

    <div class="card card-flush mb-8">
        <div class="card-body pt-6">
            <form method="GET" action="{{ route('vasaccounting.reports.financial_statements') }}" class="row g-5 align-items-end">
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Statement</label>
                    <select class="form-select form-select-solid" name="statement">
                        @foreach (config('vasaccounting.financial_statement_types', []) as $statementKey => $statementMeta)
                            <option value="{{ $statementKey }}" @selected(request('statement', $statement ?? 'balance_sheet') === $statementKey)>
                                {{ data_get($statementMeta, 'label', $statementKey) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Period</label>
                    <select class="form-select form-select-solid" name="period_id">
                        <option value="">Latest</option>
                        @foreach (($periodOptions ?? []) as $periodOption)
                            <option value="{{ $periodOption->id }}" @selected((string) request('period_id', $period_id ?? '') === (string) $periodOption->id)>
                                {{ $periodOption->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Comparative period</label>
                    <select class="form-select form-select-solid" name="comparative_period_id">
                        <option value="">Auto previous period</option>
                        @foreach (($periodOptions ?? []) as $periodOption)
                            <option value="{{ $periodOption->id }}" @selected((string) request('comparative_period_id', $comparative_period_id ?? '') === (string) $periodOption->id)>
                                {{ $periodOption->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold">Format</label>
                    <select class="form-select form-select-solid" name="format">
                        <option value="html" @selected(request('format', 'html') === 'html')>HTML</option>
                        <option value="pdf" @selected(request('format') === 'pdf')>PDF</option>
                        <option value="xlsx" @selected(request('format') === 'xlsx')>XLSX</option>
                    </select>
                </div>
                <input type="hidden" name="standard_profile" value="{{ $standard_profile }}">
                <div class="col-md-1 d-grid">
                    <button type="submit" class="btn btn-light-primary">{{ __('vasaccounting::lang.actions.apply') }}</button>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-5 g-xl-10 mb-8">
        @foreach ($summary as $metric)
            <div class="col-md-3">
                <div class="card card-flush h-100">
                    <div class="card-body">
                        <div class="text-muted fs-7 fw-semibold mb-2">{{ $metric['label'] }}</div>
                        <div class="text-gray-900 fw-bold fs-5">{{ $metric['value'] }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card card-flush">
        <div class="card-header">
            <div class="card-title d-flex flex-column">
                <span>{{ __('vasaccounting::lang.views.report_table.dataset_title') }}</span>
                <span class="text-muted fs-8 mt-1">Profile: {{ $standard_profile }}</span>
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
                                <td class="fw-semibold text-gray-900">{{ $row[0] ?? '' }}</td>
                                <td>{{ $row[1] ?? '' }}</td>
                                <td class="text-end">{{ $row[2] ?? '-' }}</td>
                                <td class="text-end">{{ $row[3] ?? '-' }}</td>
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
@endsection

