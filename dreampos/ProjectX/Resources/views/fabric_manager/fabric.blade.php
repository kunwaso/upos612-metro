@extends('projectx::layouts.main')

@section('title', __('projectx::lang.fabric_overview'))

@section('content')
@include('projectx::fabric_manager._fabric_header')

<div class="row gx-6 gx-xl-9">
    <div class="col-lg-6">
        <div class="card card-flush h-lg-100">
            <div class="card-header mt-6">
                <div class="card-title flex-column">
                    <h3 class="fw-bold mb-1">{{ __('projectx::lang.composition_summary') }}</h3>
                    <div class="fs-6 fw-semibold text-gray-500" data-role="composition-count-label">{{ trans_choice('projectx::lang.composition_count', $compositionView['count'], ['count' => $compositionView['count']]) }}</div>
                </div>
                <div class="card-toolbar">
                    <a href="#" class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#kt_modal_edit_composition">{{ __('projectx::lang.add_composition') }}</a>
                </div>
            </div>
            <div class="card-body p-9 pt-5">
                <div class="d-flex flex-wrap">
                    <div class="position-relative d-flex flex-center h-175px w-175px me-15 mb-7">
                        <div class="position-absolute translate-middle start-50 top-50 d-flex flex-column flex-center">
                            <span class="fs-2qx fw-bold" data-role="composition-count">{{ $compositionView['count'] }}</span>
                            <span class="fs-6 fw-semibold text-gray-500">{{ __('projectx::lang.total_compositions') }}</span>
                        </div>
                        <canvas id="project_overview_chart" data-chart='@json($compositionView['chart'])'></canvas>
                    </div>
                    <div class="d-flex flex-column justify-content-center flex-row-fluid pe-11 mb-5" data-role="composition-legend">
                        @forelse($compositionView['items'] as $compositionItem)
                            <div class="d-flex fs-6 fw-semibold align-items-center {{ ! $loop->last ? 'mb-3' : '' }}">
                                <div class="bullet {{ $compositionItem['bullet_class'] ?? 'bg-gray-300' }} me-3"></div>
                                <div class="text-gray-500">{{ $compositionItem['label'] }}</div>
                                <div class="ms-auto fw-bold text-gray-700">{{ rtrim(rtrim(number_format((float) ($compositionItem['percent'] ?? 0), 2), '0'), '.') }}%</div>
                            </div>
                        @empty
                            <div class="d-flex fs-6 fw-semibold align-items-center">
                                <div class="bullet bg-gray-300 me-3"></div>
                                <div class="text-gray-500">{{ __('projectx::lang.no_compositions_added') }}</div>
                                <div class="ms-auto fw-bold text-gray-700">0%</div>
                            </div>
                        @endforelse
                    </div>
                </div>
                <div class="notice d-flex bg-light-primary rounded border-primary border border-dashed p-6">
                    <div class="d-flex flex-stack flex-grow-1">
                        <div class="fw-semibold">
                            <div class="fs-6 text-gray-700">
                                <a href="#" class="fw-bold me-1" data-bs-toggle="modal" data-bs-target="#kt_modal_edit_composition">{{ __('projectx::lang.edit_composition') }}</a>{{ __('projectx::lang.composition_workflow_message') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- pantone txc component --}}
    <div class="col-lg-6">
        <div class="card card-xl-stretch mb-5 mb-xl-8">
            <!--begin::Header-->
            <div class="card-header border-0">
                <h3 class="card-title fw-bold text-gray-900">{{ __('projectx::lang.pantone_txc') }}</h3>
                <div class="card-toolbar">
                    <!--begin::Menu-->
                    <button type="button" class="btn btn-sm btn-icon btn-color-primary btn-active-light-primary" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
                        <i class="ki-duotone ki-category fs-6">
                            <span class="path1"></span>
                            <span class="path2"></span>
                            <span class="path3"></span>
                            <span class="path4"></span>
                        </i>
                    </button>
                    <!--begin::Menu 3-->
                    <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg-light-primary fw-semibold w-200px py-3" data-kt-menu="true" style="">
                        <!--begin::Heading-->
                        <div class="menu-item px-3">
                            <div class="menu-content text-muted pb-2 px-3 fs-7 text-uppercase">{{ __('projectx::lang.created_pantone_txc') }}</div>
                        </div>
                        <!--end::Heading-->
                        <!--begin::Menu item-->
                        <div class="menu-item px-3">
                            <a href="#" class="menu-link px-3" data-bs-toggle="modal" data-bs-target="#kt_modal_edit_pantone_txc">{{ __('projectx::lang.add_pantone_txc') }}</a>
                        </div>
                        <div class="menu-item px-3">
                            <a href="#" class="menu-link px-3" data-bs-toggle="modal" data-bs-target="#kt_modal_edit_pantone_txc">{{ __('projectx::lang.edit_pantone_txc') }}</a>
                        </div>
                        
                    </div>
                    <!--end::Menu 3-->
                    <!--end::Menu-->
                </div>
            </div>
            <!--end::Header-->
            <!--begin::Body-->
            <div class="card-body pt-0" data-role="pantone-list">
                @forelse($pantoneView ?? [] as $pantone)
                <!--begin::Item-->
                <div class="d-flex align-items-center rounded p-5 mb-7" style="background-color: {{ $pantone['hex'] }};">
                    <i class="ki-duotone ki-abstract-26 fs-1 me-5">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                    <!--begin::Title-->
                    <div class="flex-grow-1 me-2">
                        <a href="#" class="fw-bold text-hover-primary fs-6">{{ $pantone['name'] }}</a>
                        <span class="text-muted fw-semibold d-block">{{ $pantone['code'] }}</span>
                    </div>
                    <!--end::Title-->
                    <!--begin::Lable-->
                    <span class="fw-bold py-1">{{ $pantone['hex'] }}</span>
                    <!--end::Lable-->
                </div>
                <!--end::Item-->
                @empty
                <div class="text-muted fw-semibold py-5">{{ __('projectx::lang.no_pantone_added') }}</div>
                @endforelse
            </div>
            <!--end::Body-->
        </div>
    </div>
    
    <div class="col-lg-6">
        <div class="card card-flush h-lg-100">
            <div class="card-header mt-6">
                <div class="card-title flex-column">
                    <h3 class="fw-bold mb-1">{{ __('projectx::lang.tasks_over_time') }}</h3>
                    <div class="fs-6 d-flex text-gray-500 fs-6 fw-semibold">
                        <div class="d-flex align-items-center me-6">
                            <span class="menu-bullet d-flex align-items-center me-2"><span class="bullet bg-success"></span></span>{{ __('projectx::lang.complete') }}
                        </div>
                        <div class="d-flex align-items-center">
                            <span class="menu-bullet d-flex align-items-center me-2"><span class="bullet bg-primary"></span></span>{{ __('projectx::lang.incomplete') }}
                        </div>
                    </div>
                </div>
                <div class="card-toolbar">
                    <select name="status" data-control="select2" data-hide-search="true" class="form-select form-select-solid form-select-sm fw-bold w-100px">
                        <option value="1">2026 Q1</option>
                        <option value="2">2026 Q2</option>
                        <option value="3" selected="selected">2026 Q3</option>
                        <option value="4">2026 Q4</option>
                    </select>
                </div>
            </div>
            <div class="card-body pt-10 pb-0 px-5">
                <div id="kt_project_overview_graph" class="card-rounded-bottom" style="height: 300px"></div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card card-flush h-lg-100">
            <div class="card-header mt-6">
                <div class="card-title flex-column">
                    <h3 class="fw-bold mb-1">{{ __('projectx::lang.whats_on_the_road') }}</h3>
                    <div class="fs-6 text-gray-500">{{ __('projectx::lang.total_participants', ['count' => 482]) }}</div>
                </div>
                <div class="card-toolbar">
                    <select name="status" data-control="select2" data-hide-search="true" class="form-select form-select-solid form-select-sm fw-bold w-100px">
                        <option value="1" selected="selected">{{ __('projectx::lang.options') }}</option>
                        <option value="2">{{ __('projectx::lang.option_1') }}</option>
                        <option value="3">{{ __('projectx::lang.option_2') }}</option>
                    </select>
                </div>
            </div>
            <div class="card-body p-9 pt-4">
                <ul class="nav nav-pills d-flex flex-nowrap hover-scroll-x py-2">
                    @for($i = 0; $i < 7; $i++)
                    <li class="nav-item me-1">
                        <a class="nav-link btn d-flex flex-column flex-center rounded-pill min-w-45px me-2 py-4 px-3 btn-active-primary {{ $i === 1 ? 'active' : '' }}" data-bs-toggle="tab" href="#kt_schedule_day_{{ $i }}">
                            <span class="opacity-50 fs-7 fw-semibold">{{ ['Su','Mo','Tu','We','Th','Fr','Sa'][$i] }}</span>
                            <span class="fs-6 fw-bold">{{ 22 + $i }}</span>
                        </a>
                    </li>
                    @endfor
                </ul>
                <div class="tab-content">
                    <div id="kt_schedule_day_1" class="tab-pane fade show active">
                        <div class="d-flex flex-stack position-relative mt-8">
                            <div class="position-absolute h-100 w-4px bg-secondary rounded top-0 start-0"></div>
                            <div class="fw-semibold ms-5 text-gray-600">
                                <div class="fs-5">13:00 - 14:00 <span class="fs-7 text-gray-500 text-uppercase">pm</span></div>
                                <a href="#" class="fs-5 fw-bold text-gray-800 text-hover-primary mb-2">Creative Content Initiative</a>
                                <div class="text-gray-500">Lead by <a href="#">Michael Walters</a></div>
                            </div>
                            <a href="#" class="btn btn-bg-light btn-active-color-primary btn-sm">{{ __('projectx::lang.view') }}</a>
                        </div>
                        <div class="d-flex flex-stack position-relative mt-8">
                            <div class="position-absolute h-100 w-4px bg-secondary rounded top-0 start-0"></div>
                            <div class="fw-semibold ms-5 text-gray-600">
                                <div class="fs-5">9:00 - 10:00 <span class="fs-7 text-gray-500 text-uppercase">am</span></div>
                                <a href="#" class="fs-5 fw-bold text-gray-800 text-hover-primary mb-2">Sales Pitch Proposal</a>
                                <div class="text-gray-500">Lead by <a href="#">Bob Harris</a></div>
                            </div>
                            <a href="#" class="btn btn-bg-light btn-active-color-primary btn-sm">{{ __('projectx::lang.view') }}</a>
                        </div>
                        <div class="d-flex flex-stack position-relative mt-8">
                            <div class="position-absolute h-100 w-4px bg-secondary rounded top-0 start-0"></div>
                            <div class="fw-semibold ms-5 text-gray-600">
                                <div class="fs-5">14:30 - 15:30 <span class="fs-7 text-gray-500 text-uppercase">pm</span></div>
                                <a href="#" class="fs-5 fw-bold text-gray-800 text-hover-primary mb-2">Committee Review Approvals</a>
                                <div class="text-gray-500">Lead by <a href="#">Naomi Hayabusa</a></div>
                            </div>
                            <a href="#" class="btn btn-bg-light btn-active-color-primary btn-sm">{{ __('projectx::lang.view') }}</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card card-flush h-lg-100">
            <div class="card-header mt-6">
                <div class="card-title flex-column">
                    <h3 class="fw-bold mb-1">{{ __('projectx::lang.team_members') }}</h3>
                    <div class="fs-6 fw-semibold text-gray-500">{{ __('projectx::lang.active_members_count', ['count' => 16]) }}</div>
                </div>
                <div class="card-toolbar">
                    <a href="{{ route('projectx.fabric_manager.users', ['fabric_id' => $fabric->id]) }}" class="btn btn-light btn-sm">{{ __('projectx::lang.view_all') }}</a>
                </div>
            </div>
            <div class="card-body p-9 pt-5">
                @php
                    $members = [
                        ['name' => 'Karina Clark', 'role' => 'Team Lead', 'avatar' => '300-6.jpg'],
                        ['name' => 'Robert Doe', 'role' => 'Marketing Analyst', 'avatar' => null, 'initial' => 'R', 'bg' => 'bg-light-danger text-danger'],
                        ['name' => 'John Miller', 'role' => 'Project Manager', 'avatar' => '300-13.jpg'],
                        ['name' => 'Lucy Kunic', 'role' => 'QA Engineer', 'avatar' => null, 'initial' => 'L', 'bg' => 'bg-light-success text-success'],
                        ['name' => 'Ethan Wilder', 'role' => 'Designer', 'avatar' => '300-21.jpg'],
                    ];
                @endphp
                @foreach($members as $member)
                <div class="d-flex flex-stack {{ !$loop->last ? 'mb-5' : '' }}">
                    <div class="d-flex align-items-center">
                        <div class="symbol symbol-40px symbol-circle me-4">
                            @if(!empty($member['avatar']))
                                <img alt="Pic" src="{{ asset('modules/projectx/media/avatars/' . $member['avatar']) }}" />
                            @else
                                <span class="symbol-label {{ $member['bg'] ?? 'bg-light-primary text-primary' }} fw-bold">{{ $member['initial'] ?? '' }}</span>
                            @endif
                        </div>
                        <div>
                            <a href="#" class="fs-5 fw-bold text-gray-900 text-hover-primary mb-2">{{ $member['name'] }}</a>
                            <div class="fw-semibold text-muted">{{ $member['role'] }}</div>
                        </div>
                    </div>
                    <div class="d-flex">
                        <div class="btn btn-sm btn-icon btn-bg-light btn-active-color-primary w-30px h-30px">
                            <i class="ki-duotone ki-sms fs-3"><span class="path1"></span><span class="path2"></span></i>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="kt_modal_edit_composition" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-650px">
        <div class="modal-content">
            <div class="modal-header pb-0 border-0 justify-content-end">
                <div class="btn btn-sm btn-icon btn-active-color-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body scroll-y mx-5 mx-xl-15 my-7">
                <div class="text-center mb-8">
                    <h2 class="fw-bold">{{ __('projectx::lang.edit_composition_title') }}</h2>
                </div>
                <form class="form" id="kt_fabric_composition_form" novalidate>
                    @csrf
                    <div class="alert alert-danger d-none mb-6" data-role="form-error"></div>
                    <div class="d-flex flex-column" data-role="composition-rows"></div>
                    <div class="d-flex align-items-center justify-content-between mt-4">
                        <button type="button" class="btn btn-light-primary btn-sm" data-action="add-row">
                            <i class="ki-duotone ki-plus fs-4 me-1"><span class="path1"></span><span class="path2"></span></i>{{ __('projectx::lang.add_composition_row') }}
                        </button>
                        <div class="fs-7 fw-semibold text-gray-600" data-role="total-label"></div>
                    </div>
                    <div class="alert alert-warning d-none mt-4 mb-0" data-role="total-warning"></div>
                    <div class="text-center pt-10">
                        <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">{{ __('projectx::lang.cancel') }}</button>
                        <button type="button" class="btn btn-primary" data-action="save-composition">
                            <span class="indicator-label">{{ __('projectx::lang.save') }}</span>
                            <span class="indicator-progress">{{ __('projectx::lang.please_wait') }}...
                                <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- Pantone TXC edit modal --}}
<div class="modal fade" id="kt_modal_edit_pantone_txc" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-650px">
        <div class="modal-content">
            <div class="modal-header pb-0 border-0 justify-content-end">
                <div class="btn btn-sm btn-icon btn-active-color-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body scroll-y mx-5 mx-xl-15 my-7">
                <div class="text-center mb-8">
                    <h2 class="fw-bold">{{ __('projectx::lang.edit_pantone_txc') }}</h2>
                </div>
                <form class="form" id="kt_fabric_pantone_form" novalidate>
                    @csrf
                    <div class="alert alert-danger d-none mb-6" data-role="pantone-form-error"></div>
                    <div class="mb-4 position-relative">
                        <label class="form-label">{{ __('projectx::lang.add_pantone_txc') }}</label>
                        <input type="text" class="form-control form-control-solid" data-role="pantone-search" placeholder="{{ __('projectx::lang.search_by_code_hex_name') }}" autocomplete="off" />
                        <div class="dropdown-menu w-100 position-absolute mt-1 d-none" data-role="pantone-dropdown" style="max-height: 200px; overflow-y: auto; z-index: 1060;"></div>
                    </div>
                    <div class="d-flex flex-column gap-3" data-role="pantone-selected-list"></div>
                    <div class="text-center pt-10">
                        <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">{{ __('projectx::lang.cancel') }}</button>
                        <button type="button" class="btn btn-primary" data-action="save-pantone">
                            <span class="indicator-label">{{ __('projectx::lang.save') }}</span>
                            <span class="indicator-progress">{{ __('projectx::lang.please_wait') }}...
                                <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('page_javascript')
<script src="{{ asset('modules/projectx/js/custom/apps/projects/project/project.js') }}"></script>
<script>
    (function($) {
        'use strict';

        if (typeof $ === 'undefined') {
            return;
        }

        var config = @json($compositionFrontendConfig);
        var bulletClasses = ['bg-primary', 'bg-success', 'bg-danger', 'bg-gray-300'];
        var bulletColors = ['#00A3FF', '#50CD89', '#F1416C', '#E4E6EF'];
        var compositionPrecision = 2;
        var state = {
            catalog: Array.isArray(config.initialCatalog) ? config.initialCatalog : [],
            composition: normalizeComposition(config.initialComposition || {})
        };

        var $modal = $('#kt_modal_edit_composition');
        var $rowsContainer = $modal.find('[data-role="composition-rows"]');
        var $formError = $modal.find('[data-role="form-error"]');
        var $totalLabel = $modal.find('[data-role="total-label"]');
        var $totalWarning = $modal.find('[data-role="total-warning"]');
        var $saveButton = $modal.find('[data-action="save-composition"]');

        function message(key) {
            return (config.messages && config.messages[key]) ? config.messages[key] : '';
        }

        function escapeHtml(text) {
            return $('<div/>').text(text === null || typeof text === 'undefined' ? '' : String(text)).html();
        }

        function interpolate(template, replacements) {
            var output = template || '';
            $.each(replacements, function(token, value) {
                output = output.replace(token, value);
            });

            return output;
        }

        function parsePercent(value) {
            var raw = $.trim(String(value || ''));
            if (raw === '') {
                return null;
            }

            var number = parseFloat(raw);

            return isNaN(number) ? null : number;
        }

        function formatPercent(value) {
            var numericValue = parseFloat(value || 0);
            if (isNaN(numericValue)) {
                return '0';
            }

            var fixed = numericValue.toFixed(compositionPrecision);

            return fixed.replace(/\.00$/, '').replace(/(\.\d*[1-9])0+$/, '$1');
        }

        function findOtherCatalogId() {
            var otherCatalogId = null;

            $.each(state.catalog, function(_, component) {
                if (String(component.label || '').toLowerCase() === 'other') {
                    otherCatalogId = parseInt(component.id, 10);

                    return false;
                }
            });

            return otherCatalogId;
        }

        function normalizeComposition(payload) {
            var items = Array.isArray(payload.items) ? payload.items : [];
            var normalizedItems = $.map(items, function(item, index) {
                var percent = parseFloat(item.percent || 0);
                if (isNaN(percent)) {
                    percent = 0;
                }

                return {
                    id: item.id || null,
                    catalog_id: item.catalog_id || null,
                    label: item.label || '',
                    label_override: item.label_override || '',
                    percent: percent,
                    bullet_class: item.bullet_class || bulletClasses[index % bulletClasses.length],
                    color: item.color || bulletColors[index % bulletColors.length]
                };
            });

            var totalPercent = 0;
            $.each(normalizedItems, function(_, item) {
                totalPercent += parseFloat(item.percent || 0);
            });
            totalPercent = parseFloat(totalPercent.toFixed(compositionPrecision));

            return {
                items: normalizedItems,
                composition_count: typeof payload.composition_count === 'number' ? payload.composition_count : normalizedItems.length,
                total_percent: typeof payload.total_percent === 'number' ? payload.total_percent : totalPercent,
                warning_total_not_100: !!payload.warning_total_not_100
            };
        }

        function buildCompositionCountLabel(count) {
            return count + ' ' + (count === 1 ? message('compositionSingular') : message('compositionPlural'));
        }

        function compositionMatcher(params, data) {
            if ($.trim(params.term) === '') {
                return data;
            }

            if (typeof data.text === 'undefined') {
                return null;
            }

            var term = params.term.toLowerCase();
            if (String(data.text).toLowerCase().indexOf(term) > -1) {
                return data;
            }

            var aliases = [];
            if (data.element) {
                try {
                    aliases = JSON.parse(data.element.getAttribute('data-aliases') || '[]');
                } catch (e) {
                    aliases = [];
                }
            }

            var isAliasMatch = aliases.some(function(alias) {
                return String(alias).toLowerCase().indexOf(term) > -1;
            });

            return isAliasMatch ? data : null;
        }

        function populateCompositionSelect($select, selectedId) {
            $select.empty();
            $select.append(new Option('', '', false, false));

            $.each(state.catalog, function(_, component) {
                var option = new Option(component.label, component.id, false, false);
                option.setAttribute('data-aliases', JSON.stringify(component.aliases || []));
                $select.append(option);
            });

            $select.select2({
                placeholder: message('compositionName'),
                dropdownParent: $modal,
                matcher: compositionMatcher,
                width: '100%'
            });

            if (selectedId) {
                $select.val(String(selectedId)).trigger('change');
            }
        }

        function compositionRowTemplate() {
            return '' +
                '<div class="row g-5 align-items-end mb-6" data-role="composition-row">' +
                '    <div class="col-md-4">' +
                '        <label class="form-label fw-semibold">' + escapeHtml(message('compositionName')) + '</label>' +
                '        <select class="form-select form-select-solid" data-role="catalog-select"></select>' +
                '        <div class="text-danger fs-8 mt-1 d-none" data-role="catalog-error"></div>' +
                '    </div>' +
                '    <div class="col-md-3">' +
                '        <label class="form-label fw-semibold">' + escapeHtml(message('compositionPercent')) + '</label>' +
                '        <input type="number" class="form-control form-control-solid" step="0.01" min="0" max="100" data-role="percent-input">' +
                '        <div class="text-danger fs-8 mt-1 d-none" data-role="percent-error"></div>' +
                '    </div>' +
                '    <div class="col-md-4 d-none" data-role="other-label-wrap">' +
                '        <label class="form-label fw-semibold">' + escapeHtml(message('compositionCustomLabel')) + '</label>' +
                '        <input type="text" class="form-control form-control-solid" data-role="label-override">' +
                '        <div class="text-danger fs-8 mt-1 d-none" data-role="label-error"></div>' +
                '    </div>' +
                '    <div class="col-md-1 d-flex justify-content-end">' +
                '        <button type="button" class="btn btn-icon btn-light-danger btn-sm" data-action="remove-row" title="' + escapeHtml(message('removeComposition')) + '">' +
                '            <i class="ki-duotone ki-trash fs-4"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>' +
                '        </button>' +
                '    </div>' +
                '</div>';
        }

        function appendComponentRow(item) {
            var normalizedItem = item || {
                catalog_id: null,
                label_override: '',
                percent: null
            };

            var $row = $(compositionRowTemplate());
            var $catalogSelect = $row.find('[data-role="catalog-select"]');
            var $percentInput = $row.find('[data-role="percent-input"]');
            var $labelOverrideInput = $row.find('[data-role="label-override"]');

            populateCompositionSelect($catalogSelect, normalizedItem.catalog_id);

            if (normalizedItem.percent !== null && typeof normalizedItem.percent !== 'undefined') {
                $percentInput.val(formatPercent(normalizedItem.percent));
            }

            if (normalizedItem.label_override) {
                $labelOverrideInput.val(normalizedItem.label_override);
            }

            $rowsContainer.append($row);
            toggleOtherLabelInput($row);
        }

        function resetInlineErrors($row) {
            $row.find('[data-role="catalog-error"], [data-role="percent-error"], [data-role="label-error"]')
                .addClass('d-none')
                .text('');
        }

        function setFieldError($row, role, errorText) {
            $row.find('[data-role="' + role + '"]')
                .removeClass('d-none')
                .text(errorText);
        }

        function toggleOtherLabelInput($row) {
            var otherCatalogId = findOtherCatalogId();
            var selectedCatalogId = parseInt($row.find('[data-role="catalog-select"]').val(), 10);
            var shouldShow = !isNaN(otherCatalogId) && selectedCatalogId === otherCatalogId;
            var $otherWrap = $row.find('[data-role="other-label-wrap"]');

            if (shouldShow) {
                $otherWrap.removeClass('d-none');
            } else {
                $otherWrap.addClass('d-none');
                $row.find('[data-role="label-override"]').val('');
                $row.find('[data-role="label-error"]').addClass('d-none').text('');
            }
        }

        function collectRows() {
            var rows = [];

            $rowsContainer.find('[data-role="composition-row"]').each(function() {
                var $row = $(this);
                rows.push({
                    catalog_id: $row.find('[data-role="catalog-select"]').val() || null,
                    label_override: $.trim($row.find('[data-role="label-override"]').val() || ''),
                    percent: parsePercent($row.find('[data-role="percent-input"]').val())
                });
            });

            return rows;
        }

        function showFormError(errorMessage) {
            $formError.removeClass('d-none').text(errorMessage);
        }

        function hideFormError() {
            $formError.addClass('d-none').text('');
        }

        function validateRows() {
            hideFormError();

            var rows = collectRows();
            var rowElements = $rowsContainer.find('[data-role="composition-row"]');
            var hasError = false;
            var total = 0;
            var duplicateMap = {};
            var otherCatalogId = findOtherCatalogId();

            rowElements.each(function(index) {
                var $row = $(this);
                var rowData = rows[index];
                var catalogId = rowData.catalog_id ? parseInt(rowData.catalog_id, 10) : null;
                var percent = rowData.percent;
                var isOther = !isNaN(otherCatalogId) && catalogId === otherCatalogId;
                var key = isOther ? catalogId + '::' + rowData.label_override.toLowerCase() : String(catalogId);

                resetInlineErrors($row);

                if (!catalogId) {
                    hasError = true;
                    setFieldError($row, 'catalog-error', message('compositionRequiredError'));
                }

                if (percent === null || isNaN(percent) || percent < 0 || percent > 100) {
                    hasError = true;
                    setFieldError($row, 'percent-error', message('compositionPercentError'));
                } else {
                    total += percent;
                }

                if (isOther && rowData.label_override === '') {
                    hasError = true;
                    setFieldError($row, 'label-error', message('compositionOtherLabelRequired'));
                }

                if (catalogId) {
                    if (duplicateMap[key]) {
                        hasError = true;
                        setFieldError($row, 'catalog-error', message('compositionDuplicateError'));
                        setFieldError(duplicateMap[key], 'catalog-error', message('compositionDuplicateError'));
                    } else {
                        duplicateMap[key] = $row;
                    }
                }
            });

            if (rows.length === 0) {
                hasError = true;
                showFormError(message('compositionItemsRequired'));
            }

            total = parseFloat(total.toFixed(compositionPrecision));
            $totalLabel.text(interpolate(message('compositionTotal'), {
                ':total': formatPercent(total)
            }));

            if (rows.length > 0 && Math.abs(total - 100) > 0.01) {
                $totalWarning
                    .removeClass('d-none')
                    .text(interpolate(message('compositionTotalWarning'), {
                        ':total': formatPercent(total)
                    }));
            } else {
                $totalWarning.addClass('d-none').text('');
            }

            $saveButton.prop('disabled', hasError);

            return !hasError;
        }

        function renderRows(items) {
            $rowsContainer.empty();

            if (!items || !items.length) {
                appendComponentRow();
            } else {
                $.each(items, function(_, item) {
                    appendComponentRow(item);
                });
            }

            validateRows();
        }

        function applyCompositionToCard(composition) {
            state.composition = normalizeComposition(composition);

            var compositionCount = state.composition.composition_count;
            var $legend = $('[data-role="composition-legend"]');

            $('[data-role="composition-count"]').text(compositionCount);
            $('[data-role="composition-count-label"]').text(buildCompositionCountLabel(compositionCount));

            $legend.empty();

            if (state.composition.items.length === 0) {
                $legend.append(
                    '<div class="d-flex fs-6 fw-semibold align-items-center">' +
                        '<div class="bullet bg-gray-300 me-3"></div>' +
                        '<div class="text-gray-500">' + escapeHtml(message('noCompositions')) + '</div>' +
                        '<div class="ms-auto fw-bold text-gray-700">0%</div>' +
                    '</div>'
                );
            } else {
                $.each(state.composition.items, function(index, item) {
                    var rowClass = 'd-flex fs-6 fw-semibold align-items-center';
                    if (index < state.composition.items.length - 1) {
                        rowClass += ' mb-3';
                    }

                    var percentText = formatPercent(item.percent) + '%';
                    var bulletClass = item.bullet_class || bulletClasses[index % bulletClasses.length];
                    var labelText = item.label || message('noCompositions');

                    $legend.append(
                        '<div class="' + rowClass + '">' +
                            '<div class="bullet ' + bulletClass + ' me-3"></div>' +
                            '<div class="text-gray-500">' + escapeHtml(labelText) + '</div>' +
                            '<div class="ms-auto fw-bold text-gray-700">' + escapeHtml(percentText) + '</div>' +
                        '</div>'
                    );
                });
            }

            var chartPayload = {
                labels: $.map(state.composition.items, function(item) {
                    return item.label;
                }),
                data: $.map(state.composition.items, function(item) {
                    return parseFloat(item.percent || 0);
                }),
                colors: $.map(state.composition.items, function(item, index) {
                    return item.color || bulletColors[index % bulletColors.length];
                })
            };

            $('#project_overview_chart').attr('data-chart', JSON.stringify(chartPayload));

            if (window.KTProjectOverviewFabricComponents && typeof window.KTProjectOverviewFabricComponents.refreshChart === 'function') {
                window.KTProjectOverviewFabricComponents.refreshChart(chartPayload);
            }
        }

        function setSaveLoading(isLoading) {
            $saveButton.prop('disabled', isLoading);

            if (isLoading) {
                $saveButton.attr('data-kt-indicator', 'on');
            } else {
                $saveButton.removeAttr('data-kt-indicator');
            }
        }

        function resolveErrorMessage(error) {
            if (!error) {
                return message('somethingWentWrong');
            }

            if (typeof error === 'string') {
                return error;
            }

            if (error.responseJSON) {
                if (error.responseJSON.msg) {
                    return error.responseJSON.msg;
                }

                if (error.responseJSON.message) {
                    return error.responseJSON.message;
                }
            }

            return message('somethingWentWrong');
        }

        function fetchComposition() {
            return $.ajax({
                url: config.fetchUrl,
                type: 'GET',
                dataType: 'json'
            }).then(function(response) {
                if (!response || response.success !== true) {
                    throw new Error(response && response.msg ? response.msg : message('somethingWentWrong'));
                }

                var compositionPayload = normalizeComposition(response);
                state.composition = compositionPayload;

                return compositionPayload;
            });
        }

        function fetchCatalog() {
            if (state.catalog.length > 0) {
                return $.Deferred().resolve(state.catalog).promise();
            }

            return $.ajax({
                url: config.catalogUrl,
                type: 'GET',
                dataType: 'json'
            }).then(function(response) {
                if (!response || response.success !== true) {
                    throw new Error(response && response.msg ? response.msg : message('somethingWentWrong'));
                }

                state.catalog = Array.isArray(response.catalog) ? response.catalog : [];

                return state.catalog;
            });
        }

        function loadModalData() {
            hideFormError();

            return $.when(fetchCatalog(), fetchComposition())
                .then(function() {
                    renderRows(state.composition.items);
                })
                .fail(function(error) {
                    showFormError(resolveErrorMessage(error));
                });
        }

        function applyValidationErrors(errors) {
            var hasMappedError = false;

            $.each(errors || {}, function(key, messages) {
                var messageText = Array.isArray(messages) ? messages[0] : messages;
                var match = key.match(/^items\.(\d+)\.(catalog_id|percent|label_override)$/);

                if (!match) {
                    showFormError(messageText);

                    return;
                }

                var rowIndex = parseInt(match[1], 10);
                var field = match[2];
                var $row = $rowsContainer.find('[data-role="composition-row"]').eq(rowIndex);

                if (!$row.length) {
                    showFormError(messageText);

                    return;
                }

                var errorRoleMap = {
                    catalog_id: 'catalog-error',
                    percent: 'percent-error',
                    label_override: 'label-error'
                };

                setFieldError($row, errorRoleMap[field], messageText);
                hasMappedError = true;
            });

            if (!hasMappedError && !$formError.is(':visible')) {
                showFormError(message('somethingWentWrong'));
            }
        }

        function saveComposition() {
            hideFormError();

            if (!validateRows()) {
                return;
            }

            var payload = {
                items: collectRows()
            };

            setSaveLoading(true);

            $.ajax({
                url: config.updateUrl,
                type: 'PATCH',
                dataType: 'json',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                data: payload
            }).done(function(response) {
                if (!response || response.success !== true) {
                    showFormError(response && response.msg ? response.msg : message('somethingWentWrong'));

                    return;
                }

                fetchComposition().done(function(freshComposition) {
                    applyCompositionToCard(freshComposition);

                    var modalInstance = bootstrap.Modal.getInstance(document.getElementById('kt_modal_edit_composition'));
                    if (modalInstance) {
                        modalInstance.hide();
                    }

                    if (freshComposition.warning_total_not_100 && typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'warning',
                            title: interpolate(message('compositionTotalWarning'), {
                                ':total': formatPercent(freshComposition.total_percent)
                            }),
                            confirmButtonColor: '#009EF7'
                        });
                    } else if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: message('compositionSaved'),
                            confirmButtonColor: '#009EF7'
                        });
                    }
                }).fail(function(error) {
                    showFormError(resolveErrorMessage(error));
                });
            }).fail(function(error) {
                if (error.status === 422 && error.responseJSON && error.responseJSON.errors) {
                    applyValidationErrors(error.responseJSON.errors);
                } else {
                    showFormError(resolveErrorMessage(error));
                }
            }).always(function() {
                setSaveLoading(false);
                validateRows();
            });
        }

        function bindEvents() {
            $(document).on('click', '[data-bs-target="#kt_modal_edit_composition"]', function(event) {
                event.preventDefault();
            });

            $modal.on('show.bs.modal', function() {
                loadModalData();
            });

            $modal.on('click', '[data-action="add-row"]', function() {
                appendComponentRow();
                validateRows();
            });

            $rowsContainer.on('change', '[data-role="catalog-select"]', function() {
                var $row = $(this).closest('[data-role="composition-row"]');
                toggleOtherLabelInput($row);
                validateRows();
            });

            $rowsContainer.on('input', '[data-role="percent-input"], [data-role="label-override"]', function() {
                validateRows();
            });

            $rowsContainer.on('click', '[data-action="remove-row"]', function() {
                $(this).closest('[data-role="composition-row"]').remove();
                validateRows();
            });

            $saveButton.on('click', function() {
                saveComposition();
            });
        }

        $(document).ready(function() {
            applyCompositionToCard(state.composition);
            bindEvents();
        });
    })(jQuery);

    (function($) {
        'use strict';
        var pantoneConfig = @json($pantoneFrontendConfig ?? null);
        var initialPantone = @json($pantoneView ?? []);
        var fallbackError = @json(__('messages.something_went_wrong'));
        var selectFromListError = @json(__('projectx::lang.pantone_select_from_list_error'));
        if (!pantoneConfig || !pantoneConfig.fetchUrl || !pantoneConfig.updateUrl || !pantoneConfig.catalogUrl) {
            return;
        }
        var pantoneCatalog = [];
        var pantoneSelected = [];
        var $pantoneModal = $('#kt_modal_edit_pantone_txc');
        var $pantoneSearch = $pantoneModal.find('[data-role="pantone-search"]');
        var $pantoneDropdown = $pantoneModal.find('[data-role="pantone-dropdown"]');
        var $pantoneList = $pantoneModal.find('[data-role="pantone-selected-list"]');
        var $pantoneFormError = $pantoneModal.find('[data-role="pantone-form-error"]');
        var $cardList = $('[data-role="pantone-list"]');

        function pantoneResolveErrorMessage(error) {
            if (!error) {
                return fallbackError;
            }

            if (typeof error === 'string') {
                return error;
            }

            if (error.message) {
                return error.message;
            }

            if (error.responseJSON) {
                if (error.responseJSON.msg) {
                    return error.responseJSON.msg;
                }

                if (error.responseJSON.message) {
                    return error.responseJSON.message;
                }

                if (error.status === 422 && error.responseJSON.errors && typeof error.responseJSON.errors === 'object') {
                    var errs = error.responseJSON.errors;
                    var firstKey = Object.keys(errs)[0];
                    if (firstKey && Array.isArray(errs[firstKey]) && errs[firstKey].length) {
                        return errs[firstKey][0];
                    }
                }
            }

            return fallbackError;
        }

        function normalizeSearchToken(value) {
            return String(value || '').toLowerCase().replace(/[\s-]/g, '');
        }

        function normalizeHexToken(value) {
            return String(value || '').toLowerCase().replace(/[^0-9a-f]/g, '');
        }

        function pantoneLoadCatalog() {
            if (pantoneCatalog.length > 0) {
                return $.Deferred().resolve(pantoneCatalog).promise();
            }

            return $.ajax({
                url: pantoneConfig.catalogUrl,
                method: 'GET',
                dataType: 'json'
            }).then(function(res) {
                if (!res || res.success !== true || !Array.isArray(res.catalog)) {
                    throw new Error(res && res.msg ? res.msg : fallbackError);
                }

                pantoneCatalog = res.catalog;
                return pantoneCatalog;
            });
        }

        function pantoneFetchSelected() {
            return $.ajax({
                url: pantoneConfig.fetchUrl,
                method: 'GET',
                dataType: 'json'
            }).then(function(res) {
                if (!res || res.success !== true || !Array.isArray(res.items)) {
                    throw new Error(res && res.msg ? res.msg : fallbackError);
                }

                initialPantone = res.items;
                pantoneSelected = initialPantone.map(function(p) {
                    return {
                        code: p.code,
                        hex: p.hex,
                        name: p.name
                    };
                });

                return pantoneSelected;
            });
        }

        function pantoneMatchQuery(item, q) {
            var query = String(q || '').trim().toLowerCase();
            if (query.length < 1) {
                return false;
            }

            var code = String(item.code || '');
            var name = String(item.name || '');
            var hex = String(item.hex || '');

            var normalizedQuery = normalizeSearchToken(query);
            var normalizedCode = normalizeSearchToken(code);
            var normalizedHex = normalizeHexToken(hex);
            var normalizedHexQuery = normalizeHexToken(query);

            return code.toLowerCase().indexOf(query) >= 0 ||
                name.toLowerCase().indexOf(query) >= 0 ||
                hex.toLowerCase().indexOf(query) >= 0 ||
                (normalizedQuery !== '' && normalizedCode.indexOf(normalizedQuery) >= 0) ||
                (normalizedHexQuery !== '' && normalizedHex.indexOf(normalizedHexQuery) >= 0);
        }

        function pantoneFindExactMatch(query) {
            var rawQuery = String(query || '').trim();
            if (rawQuery === '') {
                return null;
            }

            var lowerQuery = rawQuery.toLowerCase();
            var normalizedQuery = normalizeSearchToken(rawQuery);
            var normalizedHexQuery = normalizeHexToken(rawQuery);
            var found = null;

            pantoneCatalog.some(function(item) {
                var code = String(item.code || '');
                var name = String(item.name || '');
                var hex = String(item.hex || '');
                var isMatch = false;

                if (code.toLowerCase() === lowerQuery || name.toLowerCase() === lowerQuery) {
                    isMatch = true;
                } else if (normalizedQuery !== '' &&
                    (normalizeSearchToken(code) === normalizedQuery || normalizeSearchToken(name) === normalizedQuery)) {
                    isMatch = true;
                } else if (normalizedHexQuery !== '' && normalizeHexToken(hex) === normalizedHexQuery) {
                    isMatch = true;
                }

                if (isMatch) {
                    found = item;
                }

                return isMatch;
            });

            return found;
        }

        function pantoneAddSelection(item) {
            if (!item || !item.code) {
                return false;
            }

            var code = String(item.code);
            if (pantoneSelected.some(function(selectedItem) { return selectedItem.code === code; })) {
                return false;
            }

            pantoneSelected.push({
                code: code,
                hex: item.hex || '',
                name: item.name || code
            });
            pantoneRenderSelected();

            return true;
        }

        function pantoneResolvePendingSelection() {
            var pendingValue = String($pantoneSearch.val() || '').trim();
            if (pendingValue === '') {
                return true;
            }

            var matchedItem = pantoneFindExactMatch(pendingValue);
            if (!matchedItem) {
                pantoneShowError(selectFromListError);
                return false;
            }

            pantoneAddSelection(matchedItem);
            $pantoneSearch.val('');
            pantoneHideDropdown();

            return true;
        }

        function pantonePickFromDropdown($option) {
            if (!$option || !$option.length) {
                return;
            }

            var code = $option.data('code');
            var hex = $option.data('hex');
            var name = $option.data('name');

            if (!code) {
                return;
            }

            // Autofill selected value in the input and append it to selected items.
            $pantoneSearch.val(code);
            pantoneHideError();
            pantoneAddSelection({ code: code, hex: hex || '', name: name || code });
            $pantoneSearch.focus().select();
            pantoneHideDropdown();
        }

        function pantoneRenderSelected() {
            var html = '';
            pantoneSelected.forEach(function(item, idx) {
                html += '<div class="d-flex align-items-center rounded p-4 border border-gray-300 border-dashed" data-role="pantone-selected-item" data-code="' + (item.code || '').replace(/"/g, '&quot;') + '">';
                html += '<span class="rounded me-3 flex-shrink-0" style="width:24px;height:24px;background-color:' + (item.hex || '#ccc') + ';"></span>';
                html += '<div class="flex-grow-1"><span class="fw-bold text-gray-800">' + (item.name || item.code) + '</span><span class="text-muted fw-semibold d-block fs-7">' + (item.code || '') + '</span></div>';
                html += '<button type="button" class="btn btn-sm btn-icon btn-light-danger" data-action="pantone-remove" data-index="' + idx + '"><i class="ki-duotone ki-trash fs-5"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span></i></button>';
                html += '</div>';
            });
            $pantoneList.html(html || '<div class="text-muted fs-7">' + @json(__('projectx::lang.no_pantone_added')) + '</div>');
        }

        function pantoneRenderDropdown(filtered) {
            var limit = 50;
            var html = '';
            (filtered || []).slice(0, limit).forEach(function(item) {
                var already = pantoneSelected.some(function(s) { return s.code === item.code; });
                if (already) return;
                html += '<a href="#" class="dropdown-item d-flex align-items-center py-3" data-action="pantone-pick" data-code="' + (item.code || '').replace(/"/g, '&quot;') + '" data-hex="' + (item.hex || '').replace(/"/g, '&quot;') + '" data-name="' + (item.name || '').replace(/"/g, '&quot;').replace(/'/g, '&#39;') + '">';
                html += '<span class="rounded me-3 flex-shrink-0" style="width:20px;height:20px;background-color:' + (item.hex || '#ccc') + ';"></span>';
                html += '<span class="fw-semibold">' + (item.name || item.code) + '</span>';
                html += '<span class="text-muted ms-2 fs-7">' + (item.code || '') + ' ' + (item.hex || '') + '</span>';
                html += '</a>';
            });
            $pantoneDropdown.html(html || '<div class="dropdown-item text-muted">' + @json(__('projectx::lang.no_matches')) + '</div>');
            $pantoneDropdown.removeClass('d-none').addClass('show');
        }

        function pantoneHideDropdown() {
            $pantoneDropdown.addClass('d-none').removeClass('show');
        }

        function pantoneApplyToCard(items) {
            if (!$cardList.length) return;
            var noPantone = @json(__('projectx::lang.no_pantone_added'));
            var html = '';
            (items || []).forEach(function(p) {
                html += '<div class="d-flex align-items-center rounded p-5 mb-7" style="background-color: ' + (p.hex || '#fff') + ';">';
                html += '<i class="ki-duotone ki-abstract-26 fs-1 me-5"><span class="path1"></span><span class="path2"></span></i>';
                html += '<div class="flex-grow-1 me-2"><a href="#" class="fw-bold text-hover-primary fs-6">' + (p.name || p.code) + '</a><span class="text-muted fw-semibold d-block">' + (p.code || '') + '</span></div>';
                html += '<span class="fw-bold py-1">' + (p.hex || '') + '</span></div>';
            });
            $cardList.html(html || '<div class="text-muted fw-semibold py-5">' + noPantone + '</div>');
        }

        function pantoneShowError(msg) {
            $pantoneFormError.removeClass('d-none').text(msg || '');
        }
        function pantoneHideError() {
            $pantoneFormError.addClass('d-none').text('');
        }

        $pantoneModal.on('show.bs.modal', function() {
            pantoneHideError();
            $pantoneSearch.val('');
            pantoneHideDropdown();

            $.when(pantoneLoadCatalog(), pantoneFetchSelected()).done(function() {
                pantoneRenderSelected();
            }).fail(function(error) {
                pantoneShowError(pantoneResolveErrorMessage(error));
            }).always(function() {
                $pantoneSearch.val('');
                pantoneHideDropdown();
            });
        });

        $pantoneSearch.on('focus input', function() {
            var q = $(this).val().trim();
            if (q.length < 1) {
                pantoneHideDropdown();
                return;
            }
            var filtered = pantoneCatalog.filter(function(item) { return pantoneMatchQuery(item, q); });
            pantoneRenderDropdown(filtered);
        });
        $pantoneSearch.on('blur', function() {
            setTimeout(pantoneHideDropdown, 200);
        });

        $pantoneSearch.on('keydown', function(e) {
            if (e.key !== 'Enter') {
                return;
            }

            var $firstOption = $pantoneDropdown.find('[data-action="pantone-pick"]').first();
            if ($firstOption.length) {
                e.preventDefault();
                pantonePickFromDropdown($firstOption);
            }
        });

        $(document).on('click', '[data-bs-target="#kt_modal_edit_pantone_txc"]', function(e) { e.preventDefault(); });

        $pantoneDropdown.on('mousedown', '[data-action="pantone-pick"]', function(e) {
            e.preventDefault();
            pantonePickFromDropdown($(this));
        });

        $pantoneDropdown.on('click', '[data-action="pantone-pick"]', function(e) {
            e.preventDefault();
        });

        $pantoneList.on('click', '[data-action="pantone-remove"]', function(e) {
            e.preventDefault();
            var idx = parseInt($(this).data('index'), 10);
            if (!isNaN(idx) && idx >= 0 && idx < pantoneSelected.length) {
                pantoneSelected.splice(idx, 1);
                pantoneRenderSelected();
            }
        });

        $pantoneModal.find('[data-action="save-pantone"]').on('click', function() {
            var $btn = $(this);
            var token = $pantoneModal.find('input[name="_token"]').val();
            pantoneHideError();
            if (!pantoneResolvePendingSelection()) {
                return;
            }

            $btn.prop('disabled', true).addClass('loading');
            $btn.find('.indicator-label').addClass('d-none');
            $btn.find('.indicator-progress').removeClass('d-none');

            $.ajax({
                url: pantoneConfig.updateUrl,
                method: 'PATCH',
                dataType: 'json',
                contentType: 'application/json; charset=UTF-8',
                processData: false,
                headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
                data: JSON.stringify({ items: pantoneSelected.map(function(p) { return p.code; }) })
            }).done(function(response) {
                if (response && response.success && Array.isArray(response.items)) {
                    initialPantone = response.items;
                    pantoneSelected = response.items.map(function(p) {
                        return {
                            code: p.code,
                            hex: p.hex,
                            name: p.name
                        };
                    });
                    pantoneApplyToCard(response.items);
                    var modalInstance = bootstrap.Modal.getInstance(document.getElementById('kt_modal_edit_pantone_txc'));
                    if (modalInstance) modalInstance.hide();
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({ icon: 'success', title: @json(__('projectx::lang.pantone_saved')), confirmButtonColor: '#009EF7' });
                    }
                } else {
                    pantoneShowError(response && response.msg ? response.msg : fallbackError);
                }
            }).fail(function(xhr) {
                var msg = pantoneResolveErrorMessage(xhr);
                pantoneShowError(msg);
            }).always(function() {
                $btn.prop('disabled', false).removeClass('loading');
                $btn.find('.indicator-label').removeClass('d-none');
                $btn.find('.indicator-progress').addClass('d-none');
            });
        });
    })(jQuery);
</script>
@endsection
