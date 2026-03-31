<div class="card card-flush mb-8">
    <div class="card-body py-8">
        <div class="d-flex flex-column gap-8">
            <div class="d-flex flex-column flex-xl-row justify-content-between align-items-xl-start gap-8">
                <div class="d-flex flex-column flex-sm-row align-items-sm-start gap-5">
                    <div class="symbol symbol-60px symbol-circle">
                        <span class="symbol-label bg-{{ data_get($vasAccountingPageMeta ?? [], 'badge_variant', 'light-primary') }}">
                            <i class="{{ data_get($vasAccountingPageMeta ?? [], 'icon', 'ki-outline ki-chart-simple-2') }} fs-2x text-{{ str_replace('light-', '', data_get($vasAccountingPageMeta ?? [], 'badge_variant', 'primary')) }}"></i>
                        </span>
                    </div>
                    <div class="d-flex flex-column gap-4">
                        <div class="d-flex flex-wrap gap-2">
                            <span class="badge badge-light-dark">{{ data_get($vasAccountingBusinessContext ?? [], 'label', $vasAccountingUtil->uiLabel('business_scope')) }}</span>
                            <span class="badge badge-{{ data_get($vasAccountingPageMeta ?? [], 'badge_variant', 'light-primary') }}">
                                {{ data_get($vasAccountingPageMeta ?? [], 'section_label', __('vasaccounting::lang.module_name')) }}
                            </span>
                            @if (data_get($vasAccountingCurrentPeriod ?? [], 'name'))
                                <span class="badge badge-light-info">
                                    {{ $vasAccountingUtil->localizedPeriodName((string) data_get($vasAccountingCurrentPeriod, 'name')) }}
                                </span>
                            @endif
                            @if (data_get($vasAccountingCurrentPeriod ?? [], 'status'))
                                <span class="badge badge-light-{{ data_get($vasAccountingCurrentPeriod, 'status') === 'open' ? 'success' : 'warning' }}">
                                    {{ data_get($vasAccountingCurrentPeriod, 'status_label', $vasAccountingUtil->periodStatusLabel((string) data_get($vasAccountingCurrentPeriod, 'status'))) }}
                                </span>
                            @endif
                        </div>
                        <div>
                            <div class="text-gray-900 fw-bolder fs-2hx">
                                {{ $title ?? data_get($vasAccountingPageMeta ?? [], 'title', __('vasaccounting::lang.module_name')) }}
                            </div>
                            @if (!empty($subtitle) || data_get($vasAccountingPageMeta ?? [], 'subtitle'))
                                <div class="text-gray-600 fw-semibold fs-6 mt-2">
                                    {{ $subtitle ?? data_get($vasAccountingPageMeta ?? [], 'subtitle') }}
                                </div>
                            @endif
                        </div>
                        @if (data_get($vasAccountingCurrentPeriod ?? [], 'start_date') && data_get($vasAccountingCurrentPeriod ?? [], 'end_date'))
                            <div class="d-flex flex-wrap gap-5">
                                <div>
                                    <div class="text-muted fs-8 fw-semibold text-uppercase mb-1">{{ $vasAccountingUtil->uiLabel('period_window') }}</div>
                                    <div class="text-gray-800 fw-semibold fs-7">
                                        {{ \Carbon\Carbon::parse((string) data_get($vasAccountingCurrentPeriod, 'start_date'))->locale(app()->getLocale())->translatedFormat('d M Y') }}
                                        -
                                        {{ \Carbon\Carbon::parse((string) data_get($vasAccountingCurrentPeriod, 'end_date'))->locale(app()->getLocale())->translatedFormat('d M Y') }}
                                    </div>
                                </div>
                                <div>
                                    <div class="text-muted fs-8 fw-semibold text-uppercase mb-1">{{ $vasAccountingUtil->uiLabel('route') }}</div>
                                    <div class="text-gray-800 fw-semibold fs-7">{{ request()->route()?->getName() }}</div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="d-flex flex-column gap-4 min-w-xl-325px">
                    @if (data_get($vasAccountingPageMeta ?? [], 'supports_location_filter') && count($locationOptions ?? $branchOptions ?? []) > 0)
                        <div class="card bg-light-primary">
                            <div class="card-body py-5">
                                <form method="GET" action="{{ url()->current() }}" class="d-flex flex-column gap-4">
                                    <div>
                                        <label class="form-label fw-semibold fs-8 text-uppercase text-gray-700 mb-2">{{ $vasAccountingUtil->uiLabel('location_filter') }}</label>
                                        <select name="location_id" class="form-select form-select-solid" data-control="select2" data-hide-search="{{ count($locationOptions ?? $branchOptions ?? []) < 8 ? 'true' : 'false' }}">
                                            <option value="">{{ $vasAccountingUtil->uiLabel('all_locations') }}</option>
                                            @foreach (($locationOptions ?? $branchOptions ?? []) as $locationId => $locationLabel)
                                                <option value="{{ $locationId }}" {{ (string) ($selectedLocationId ?? request()->query('location_id')) === (string) $locationId ? 'selected' : '' }}>
                                                    {{ $locationLabel }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    @foreach (request()->except(['location_id', 'page']) as $queryKey => $queryValue)
                                        @if (is_array($queryValue))
                                            @foreach ($queryValue as $arrayValue)
                                                <input type="hidden" name="{{ $queryKey }}[]" value="{{ $arrayValue }}">
                                            @endforeach
                                        @else
                                            <input type="hidden" name="{{ $queryKey }}" value="{{ $queryValue }}">
                                        @endif
                                    @endforeach

                                    <div class="d-flex gap-3">
                                        <button type="submit" class="btn btn-primary btn-sm flex-grow-1">{{ $vasAccountingUtil->actionLabel('apply') }}</button>
                                        @if (!empty($selectedLocationId) || request()->filled('location_id'))
                                            <a href="{{ url()->current() . (count(request()->except(['location_id', 'page'])) ? '?' . http_build_query(request()->except(['location_id', 'page'])) : '') }}" class="btn btn-light btn-sm">{{ $vasAccountingUtil->actionLabel('clear') }}</a>
                                        @endif
                                    </div>
                                </form>
                            </div>
                        </div>
                    @endif

                    @if (!empty(data_get($vasAccountingPageMeta ?? [], 'quick_actions', [])))
                        <div class="d-flex flex-wrap gap-3 justify-content-xl-end">
                            @foreach (data_get($vasAccountingPageMeta ?? [], 'quick_actions', []) as $action)
                                @if (!empty($action['route']) && Route::has($action['route']))
                                    <a href="{{ route($action['route']) }}" class="btn btn-sm btn-{{ $action['style'] ?? 'light-primary' }}">
                                        {{ $action['label'] }}
                                    </a>
                                @endif
                            @endforeach
                        </div>
                    @endif

                    @isset($actions)
                        <div class="d-flex justify-content-xl-end">
                            {!! $actions !!}
                        </div>
                    @endisset
                </div>
            </div>

            <div>
                @include('vasaccounting::partials.nav')
            </div>
        </div>
    </div>
</div>
