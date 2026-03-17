@extends('layouts.app')
@section('title', __('lang_v1.calendar'))

@section('css')
    <style>
        #kt_calendar_workspace .fc .fc-toolbar {
            display: none;
        }

        #kt_calendar_workspace .fc .fc-scrollgrid,
        #kt_calendar_workspace .fc .fc-scrollgrid td,
        #kt_calendar_workspace .fc .fc-scrollgrid th {
            border-color: var(--bs-gray-300);
        }

        #kt_calendar_workspace .fc .fc-col-header-cell-cushion {
            padding: 0.85rem 0.75rem;
            color: var(--bs-gray-700);
            font-weight: 600;
            text-decoration: none;
        }

        #kt_calendar_workspace .fc .fc-daygrid-day-number,
        #kt_calendar_workspace .fc .fc-timegrid-slot-label-cushion,
        #kt_calendar_workspace .fc .fc-timegrid-axis-cushion {
            color: var(--bs-gray-900);
            font-weight: 600;
            text-decoration: none;
        }

        #kt_calendar_workspace .fc .fc-daygrid-day-frame {
            min-height: 138px;
        }

        #kt_calendar_workspace .fc .fc-day-today {
            background-color: rgba(27, 132, 255, 0.06);
        }

        #kt_calendar_workspace .fc .fc-daygrid-event,
        #kt_calendar_workspace .fc .fc-timegrid-event {
            border: 0;
            background: transparent;
            box-shadow: none;
        }

        #kt_calendar_workspace .fc .fc-event-main {
            padding: 0;
        }

        #kt_calendar_workspace .fc .fc-more-link {
            color: var(--bs-primary);
            font-weight: 600;
            text-decoration: none;
            padding-inline: 0.25rem;
        }

        #kt_calendar_workspace .fc .fc-daygrid-body-natural .fc-daygrid-day-events {
            margin-bottom: 0.35rem;
        }

        @media (max-width: 991.98px) {
            #kt_calendar_workspace .fc .fc-daygrid-day-frame {
                min-height: 112px;
            }
        }
    </style>
@endsection

@section('content')
    <div class="d-flex flex-column gap-7" id="kt_calendar_workspace">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-4">
            <div>
                <h1 class="fs-2hx fw-bold text-gray-900 mb-1">@lang('lang_v1.calendar')</h1>
                <div class="fs-6 text-muted" id="kt_calendar_current_period"></div>
            </div>
            <button class="btn btn-primary" id="kt_calendar_open_type_picker" type="button" @disabled(empty($create_types))>
                <i class="ki-duotone ki-plus fs-2">
                    <span class="path1"></span>
                    <span class="path2"></span>
                    <span class="path3"></span>
                </i>
                @lang('lang_v1.add_calendar_item')
            </button>
        </div>

        <div class="row g-5 g-xl-10">
            <div class="col-12 col-xl-4 col-xxl-3">
                <div class="card card-flush h-100">
                    <div class="card-header pt-7">
                        <div class="card-title">
                            <h3 class="fw-bold text-gray-900 m-0">@lang('report.filters')</h3>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <div class="d-flex flex-column gap-7">
                            @if (!empty($users))
                                <div>
                                    {!! Form::label('user_id', __('role.user') . ':', ['class' => 'form-label fw-semibold text-gray-700 fs-6 mb-2']) !!}
                                    {!! Form::select('user_id', $users, auth()->user()->id, [
                                        'class' => 'form-select form-select-solid select2',
                                        'placeholder' => __('messages.please_select'),
                                        'id' => 'user_id',
                                        'data-control' => 'select2',
                                        'data-placeholder' => __('messages.please_select'),
                                    ]) !!}
                                </div>
                            @endif

                            <div>
                                {!! Form::label('location_id', __('sale.location') . ':', ['class' => 'form-label fw-semibold text-gray-700 fs-6 mb-2']) !!}
                                {!! Form::select('location_id', $all_locations, null, [
                                    'class' => 'form-select form-select-solid select2',
                                    'placeholder' => __('messages.please_select'),
                                    'id' => 'location_id',
                                    'data-control' => 'select2',
                                    'data-placeholder' => __('messages.please_select'),
                                ]) !!}
                            </div>

                            <div>
                                <div class="form-label fw-semibold text-gray-700 fs-6 mb-4">@lang('lang_v1.event_types')</div>
                                <div class="d-flex flex-column gap-3">
                                    @foreach ($event_types as $key => $value)
                                        <label class="form-check form-check-custom form-check-solid align-items-start border border-gray-300 border-dashed rounded p-4">
                                            {!! Form::checkbox('events[]', $key, true, [
                                                'class' => 'form-check-input event_check mt-1',
                                            ]) !!}
                                            <span class="ms-4 d-flex flex-column gap-2 w-100">
                                                <span class="d-flex align-items-center gap-2">
                                                    <span class="badge badge-sm {{ $value['badge_class'] ?? 'badge-light-primary' }}">
                                                        {{ $value['label'] }}
                                                    </span>
                                                    <span class="bullet bullet-dot" style="background-color: {{ $value['color'] }}"></span>
                                                </span>
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-8 col-xxl-9">
                <div class="card card-flush">
                    <div class="card-header pt-7 flex-wrap gap-4">
                        <div class="card-title">
                            <div class="d-flex flex-column">
                                <span class="fs-2 fw-bold text-gray-900" id="kt_calendar_toolbar_title">@lang('lang_v1.calendar')</span>
                                <span class="fs-7 text-muted">@lang('lang_v1.calendar_workspace_hint')</span>
                            </div>
                        </div>
                        <div class="card-toolbar flex-wrap gap-3">
                            <div class="btn-group" role="group" aria-label="calendar navigation">
                                <button class="btn btn-sm btn-light-primary" data-calendar-nav="prev" type="button">
                                    <i class="ki-duotone ki-left fs-3 m-0">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </button>
                                <button class="btn btn-sm btn-light-primary" data-calendar-nav="today" type="button">{{ __('home.today') }}</button>
                                <button class="btn btn-sm btn-light-primary" data-calendar-nav="next" type="button">
                                    <i class="ki-duotone ki-right fs-3 m-0">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </button>
                            </div>
                            <div class="btn-group" role="group" aria-label="calendar views">
                                <button class="btn btn-sm btn-light-primary active" data-calendar-view="dayGridMonth" type="button">{{ __('lang_v1.month') }}</button>
                                <button class="btn btn-sm btn-light-primary" data-calendar-view="timeGridWeek" type="button">{{ __('lang_v1.week') }}</button>
                                <button class="btn btn-sm btn-light-primary" data-calendar-view="timeGridDay" type="button">{{ __('lang_v1.day') }}</button>
                                <button class="btn btn-sm btn-light-primary" data-calendar-view="listMonth" type="button">{{ __('lang_v1.list') }}</button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <div id="kt_calendar_app"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="kt_modal_calendar_type_picker" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered mw-700px">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div>
                        <h2 class="fw-bold mb-1">@lang('lang_v1.choose_calendar_type')</h2>
                        <div class="fs-7 text-muted" id="kt_calendar_type_picker_date"></div>
                    </div>
                    <button class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal" type="button">
                        <i class="ki-duotone ki-cross fs-1">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                    </button>
                </div>
                <div class="modal-body pt-7">
                    <div class="row g-5">
                        @forelse ($create_types as $type => $config)
                            <div class="col-12 col-md-6">
                                <button class="btn btn-light-primary d-flex align-items-start text-start border border-gray-300 border-dashed rounded w-100 h-100 px-5 py-4"
                                    data-calendar-create-type="{{ $type }}" type="button">
                                    <span class="me-4">
                                        <i class="ki-duotone ki-{{ $config['icon'] ?? 'calendar' }} fs-2x text-primary">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                            <span class="path3"></span>
                                            <span class="path4"></span>
                                        </i>
                                    </span>
                                    <span class="d-flex flex-column align-items-start gap-2">
                                        <span class="fw-bold fs-5 text-gray-900">{{ $config['label'] }}</span>
                                        <span class="badge badge-sm {{ $config['badge_class'] ?? 'badge-light-primary' }}">{{ $config['label'] }}</span>
                                    </span>
                                </button>
                            </div>
                        @empty
                            <div class="col-12">
                                <div class="notice d-flex bg-light-warning rounded border-warning border border-dashed p-6">
                                    <div class="d-flex flex-stack flex-grow-1">
                                        <div class="fw-semibold">
                                            <div class="fs-6 text-gray-700">@lang('lang_v1.no_calendar_create_flows')</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="kt_modal_calendar_schedule" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered mw-800px">
            <div class="modal-content">
                <form id="kt_calendar_schedule_form">
                    <div class="modal-header">
                        <div>
                            <h2 class="fw-bold mb-1" id="kt_calendar_schedule_title">@lang('lang_v1.add_schedule')</h2>
                            <div class="fs-7 text-muted" id="kt_calendar_schedule_context"></div>
                        </div>
                        <button class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal" type="button">
                            <i class="ki-duotone ki-cross fs-1">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                        </button>
                    </div>
                    <div class="modal-body py-10 px-lg-17">
                        <input id="kt_calendar_schedule_id" name="schedule_id" type="hidden">
                        <input id="kt_calendar_schedule_color" name="color" type="hidden" value="{{ $event_types['schedule']['color'] ?? '#1B84FF' }}">

                        <div class="row g-7">
                            <div class="col-12">
                                <label class="form-label fw-semibold required">@lang('essentials::lang.title')</label>
                                <input class="form-control form-control-solid" id="kt_calendar_schedule_name" name="title" required type="text">
                            </div>

                            @if (!empty($users))
                                <div class="col-12 col-md-6">
                                    <label class="form-label fw-semibold">{{ __('role.user') }}</label>
                                    <select class="form-select form-select-solid" id="kt_calendar_schedule_user_id" name="user_id">
                                        @foreach ($users as $userId => $userLabel)
                                            <option value="{{ $userId }}">{{ $userLabel }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif

                            <div class="col-12 {{ !empty($users) ? 'col-md-6' : '' }}">
                                <label class="form-label fw-semibold">{{ __('sale.location') }}</label>
                                <select class="form-select form-select-solid" id="kt_calendar_schedule_location_id" name="location_id">
                                    <option value="">{{ __('lang_v1.all') }}</option>
                                    @foreach ($all_locations as $locationId => $locationLabel)
                                        <option value="{{ $locationId }}">{{ $locationLabel }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-12">
                                <label class="form-check form-check-custom form-check-solid form-switch">
                                    <input class="form-check-input" id="kt_calendar_schedule_all_day" name="all_day" type="checkbox" value="1">
                                    <span class="form-check-label fw-semibold text-gray-700 ms-3">@lang('lang_v1.all_day')</span>
                                </label>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold required">{{ __('essentials::lang.start_date') }}</label>
                                <input class="form-control form-control-solid" id="kt_calendar_schedule_start" name="start" required type="text">
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold">{{ __('essentials::lang.end_date') }}</label>
                                <input class="form-control form-control-solid" id="kt_calendar_schedule_end" name="end" type="text">
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold">{{ __('lang_v1.description') }}</label>
                                <textarea class="form-control form-control-solid" id="kt_calendar_schedule_description" name="description" rows="3"></textarea>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold">{{ __('brand.note') }}</label>
                                <textarea class="form-control form-control-solid" id="kt_calendar_schedule_notes" name="notes" rows="3"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer flex-wrap gap-3">
                        <button class="btn btn-light-danger me-auto d-none" id="kt_calendar_schedule_delete" type="button">
                            <i class="ki-duotone ki-trash fs-2">
                                <span class="path1"></span>
                                <span class="path2"></span>
                                <span class="path3"></span>
                                <span class="path4"></span>
                            </i>
                            @lang('messages.delete')
                        </button>
                        <button class="btn btn-light" data-bs-dismiss="modal" type="button">@lang('messages.close')</button>
                        <button class="btn btn-primary" id="kt_calendar_schedule_submit" type="submit">
                            <span class="indicator-label">@lang('messages.save')</span>
                            <span class="indicator-progress">{{ __('product.please_wait') }}...
                                <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="task_modal" tabindex="-1" role="dialog" aria-hidden="true"></div>
    <div class="modal fade" id="add_holiday_modal" tabindex="-1" role="dialog" aria-hidden="true"></div>
    <div class="modal fade" id="add_leave_modal" tabindex="-1" role="dialog" aria-hidden="true"></div>
    <div id="calendar_dynamic_modal_root"></div>
@endsection

@section('javascript')
    <script type="text/javascript">
        window.CalendarPage = (function() {
            var calendar = null;
            var calendarEl = document.getElementById('kt_calendar_app');
            var fullCalendarLib = window.KTFullCalendar || window.FullCalendar;
            var schedulePickers = {};
            var selectedRange = null;
            var scheduleMode = 'create';
            var currentAuthUserId = @json((int) auth()->user()->id);
            var canManageAllSchedules = @json((bool) $can_manage_all_schedules);
            var createTypes = @json($create_types);
            var routes = {
                feed: @json(route('calendar')),
                createFlow: @json(route('calendar.create_flow')),
                scheduleStore: @json(route('calendar.schedules.store')),
                scheduleUpdateBase: @json(url('/calendar/schedules')),
            };
            var calendarCopy = {
                saveScheduleError: @json(__('lang_v1.unable_to_save_schedule')),
                deleteScheduleError: @json(__('lang_v1.unable_to_delete_schedule')),
                openCreateFlowError: @json(__('lang_v1.unable_to_open_calendar_create_flow')),
            };

            function initSelects() {
                $('#user_id, #location_id').each(function() {
                    if ($(this).length && !$(this).hasClass('select2-hidden-accessible')) {
                        $(this).select2({
                            width: '100%'
                        });
                    }
                });
            }

            function getCalendarFilterData() {
                var filters = {};
                var events = [];

                if ($('select#location_id').length) {
                    filters.location_id = $('select#location_id').val();
                }

                if ($('select#user_id').length) {
                    filters.user_id = $('select#user_id').val();
                }

                $.each($("input[name='events[]']:checked"), function() {
                    events.push($(this).val());
                });

                filters.events = events;

                return filters;
            }

            function escapeHtml(value) {
                return $('<div>').text(value || '').html();
            }

            function toRgba(hex, opacity) {
                var normalized = (hex || '').replace('#', '');
                if (normalized.length !== 6) {
                    return 'rgba(27,132,255,' + opacity + ')';
                }

                var bigint = parseInt(normalized, 16);
                var r = (bigint >> 16) & 255;
                var g = (bigint >> 8) & 255;
                var b = bigint & 255;

                return 'rgba(' + r + ',' + g + ',' + b + ',' + opacity + ')';
            }

            function buildCurrentRange(start, end, allDay) {
                return {
                    start: moment(start),
                    end: moment(end || start),
                    allDay: !!allDay
                };
            }

            function getDefaultCreateRange() {
                return buildCurrentRange(moment(), moment().add(1, 'hour'), false);
            }

            function openTypePicker(range) {
                if (!Object.keys(createTypes || {}).length) {
                    return;
                }

                selectedRange = range || getDefaultCreateRange();

                var rangeLabel = selectedRange.allDay
                    ? selectedRange.start.format('ddd, D MMM YYYY')
                    : selectedRange.start.format('ddd, D MMM YYYY HH:mm') + ' - ' + selectedRange.end.format('ddd, D MMM YYYY HH:mm');

                $('#kt_calendar_type_picker_date').text(rangeLabel);
                $('#kt_modal_calendar_type_picker').modal('show');
            }

            function syncToolbar(info) {
                $('#kt_calendar_current_period').text(info.view.title);
                $('#kt_calendar_toolbar_title').text(info.view.title);

                $('[data-calendar-view]').removeClass('active');
                $('[data-calendar-view="' + info.view.type + '"]').addClass('active');
            }

            function renderCalendarEventContent(info) {
                var event = info.event;
                var props = event.extendedProps || {};
                var badgeLabel = escapeHtml(props.type_label || event.title);
                var subtitle = props.subtitle ? '<span class="text-muted text-truncate">' + escapeHtml(props.subtitle) + '</span>' : '';
                var timeText = info.timeText ? '<span class="text-gray-700 fw-semibold text-truncate">' + escapeHtml(info.timeText) + '</span>' : subtitle;
                var bgTint = toRgba(event.backgroundColor, 0.12);

                return {
                    html: '' +
                        '<div data-calendar-event-card="true" class="d-flex align-items-start gap-3 rounded w-100 px-2 py-2 overflow-hidden" style="background-color:' + bgTint + '; border-left:3px solid ' + escapeHtml(event.backgroundColor) + ';">' +
                        '<span class="badge badge-sm ' + escapeHtml(props.badge_class || 'badge-light-primary') + '">' + badgeLabel + '</span>' +
                        '<span class="d-flex flex-column overflow-hidden">' +
                        '<span class="fw-bold text-gray-900 text-truncate">' + escapeHtml(event.title) + '</span>' +
                        timeText +
                        '</span>' +
                        '</div>'
                };
            }

            function initCalendar() {
                if (!calendarEl || !fullCalendarLib || typeof fullCalendarLib.Calendar !== 'function') {
                    return;
                }

                calendar = new fullCalendarLib.Calendar(calendarEl, {
                    locale: @json(session()->get('user.language', config('app.locale'))),
                    direction: @json(in_array(session()->get('user.language', config('app.locale')), config('constants.langs_rtl')) ? 'rtl' : 'ltr'),
                    themeSystem: 'bootstrap5',
                    initialView: 'dayGridMonth',
                    headerToolbar: false,
                    buttonText: {
                        today: @json(__('home.today')),
                        month: @json(__('lang_v1.month')),
                        week: @json(__('lang_v1.week')),
                        day: @json(__('lang_v1.day')),
                        list: @json(__('lang_v1.list')),
                    },
                    navLinks: true,
                    selectable: Object.keys(createTypes || {}).length > 0,
                    selectMirror: true,
                    dayMaxEvents: 3,
                    moreLinkClick: 'popover',
                    height: 'auto',
                    events: function(fetchInfo, successCallback, failureCallback) {
                        $.ajax({
                            url: routes.feed,
                            type: 'get',
                            dataType: 'json',
                            data: $.extend({}, getCalendarFilterData(), {
                                start: fetchInfo.startStr,
                                end: fetchInfo.endStr
                            }),
                            success: successCallback,
                            error: failureCallback
                        });
                    },
                    dateClick: function(info) {
                        openTypePicker(buildCurrentRange(info.date, info.date, true));
                    },
                    select: function(info) {
                        var rangeEnd = info.allDay ? moment(info.end).subtract(1, 'day') : info.end;
                        openTypePicker(buildCurrentRange(info.start, rangeEnd, info.allDay));
                        calendar.unselect();
                    },
                    eventClick: function(info) {
                        if (info.event.extendedProps.event_type === 'schedule') {
                            info.jsEvent.preventDefault();
                            openScheduleModal('edit', info.event);
                            return;
                        }

                        if (info.event.url) {
                            info.jsEvent.preventDefault();
                            window.location.href = info.event.url;
                        }
                    },
                    eventContent: renderCalendarEventContent,
                    eventDidMount: function(info) {
                        var subtitle = info.event.extendedProps && info.event.extendedProps.subtitle
                            ? ' - ' + info.event.extendedProps.subtitle
                            : '';
                        info.el.setAttribute('title', info.event.title + subtitle);
                    },
                    datesSet: syncToolbar
                });

                calendar.render();
            }

            function refetchEvents() {
                if (calendar) {
                    calendar.refetchEvents();
                }
            }

            function initSchedulePickers() {
                var allDay = $('#kt_calendar_schedule_all_day').is(':checked');
                var startInput = document.getElementById('kt_calendar_schedule_start');
                var endInput = document.getElementById('kt_calendar_schedule_end');
                var config = allDay ? {
                    altInput: true,
                    altFormat: 'd M, Y',
                    dateFormat: 'Y-m-d',
                    allowInput: false
                } : {
                    enableTime: true,
                    time_24hr: true,
                    altInput: true,
                    altFormat: 'd M, Y H:i',
                    dateFormat: 'Y-m-d H:i',
                    allowInput: false
                };

                ['start', 'end'].forEach(function(key) {
                    if (schedulePickers[key] && typeof schedulePickers[key].destroy === 'function') {
                        schedulePickers[key].destroy();
                    }
                });

                if (typeof window.flatpickr === 'function') {
                    schedulePickers.start = window.flatpickr(startInput, config);
                    schedulePickers.end = window.flatpickr(endInput, config);
                } else if (window.jQuery && window.jQuery.fn && typeof window.jQuery.fn.flatpickr === 'function') {
                    schedulePickers.start = window.jQuery(startInput).flatpickr(config);
                    schedulePickers.end = window.jQuery(endInput).flatpickr(config);
                }
            }

            function setScheduleValues(range) {
                var allDay = !!range.allDay;
                $('#kt_calendar_schedule_all_day').prop('checked', allDay);
                initSchedulePickers();

                var startValue = allDay ? range.start.format('YYYY-MM-DD') : range.start.format('YYYY-MM-DD HH:mm');
                var endValue = allDay ? range.end.format('YYYY-MM-DD') : range.end.format('YYYY-MM-DD HH:mm');

                $('#kt_calendar_schedule_start').val(startValue);
                $('#kt_calendar_schedule_end').val(endValue);

                if (schedulePickers.start && typeof schedulePickers.start.setDate === 'function') {
                    schedulePickers.start.setDate(startValue, true, allDay ? 'Y-m-d' : 'Y-m-d H:i');
                }
                if (schedulePickers.end && typeof schedulePickers.end.setDate === 'function') {
                    schedulePickers.end.setDate(endValue, true, allDay ? 'Y-m-d' : 'Y-m-d H:i');
                }
            }

            function resetScheduleForm() {
                $('#kt_calendar_schedule_form')[0].reset();
                $('#kt_calendar_schedule_id').val('');
                $('#kt_calendar_schedule_color').val(@json($event_types['schedule']['color'] ?? '#1B84FF'));
                $('#kt_calendar_schedule_title').text(@json(__('lang_v1.add_schedule')));
                $('#kt_calendar_schedule_context').text('');
                $('#kt_calendar_schedule_delete').addClass('d-none');
                scheduleMode = 'create';

                if ($('#kt_calendar_schedule_user_id').length) {
                    var selectedUserId = canManageAllSchedules && $('#user_id').val() ? $('#user_id').val() : currentAuthUserId;
                    $('#kt_calendar_schedule_user_id').val(String(selectedUserId)).trigger('change');
                }

                $('#kt_calendar_schedule_location_id').val($('#location_id').val() || '').trigger('change');
            }

            function openScheduleModal(mode, event) {
                var range = selectedRange || getDefaultCreateRange();
                resetScheduleForm();

                if (mode === 'edit' && event) {
                    scheduleMode = 'edit';
                    $('#kt_calendar_schedule_title').text(@json(__('lang_v1.edit_schedule')));
                    $('#kt_calendar_schedule_context').text(event.title);
                    $('#kt_calendar_schedule_delete').removeClass('d-none');
                    $('#kt_calendar_schedule_id').val(event.id);
                    $('#kt_calendar_schedule_name').val(event.title || '');
                    $('#kt_calendar_schedule_description').val(event.extendedProps.description || '');
                    $('#kt_calendar_schedule_notes').val(event.extendedProps.notes || '');
                    $('#kt_calendar_schedule_color').val(event.backgroundColor || @json($event_types['schedule']['color'] ?? '#1B84FF'));

                    if ($('#kt_calendar_schedule_user_id').length && event.extendedProps.user_id) {
                        $('#kt_calendar_schedule_user_id').val(String(event.extendedProps.user_id)).trigger('change');
                    }
                    if (event.extendedProps.location_id) {
                        $('#kt_calendar_schedule_location_id').val(String(event.extendedProps.location_id)).trigger('change');
                    }

                    range = buildCurrentRange(event.start, event.end || event.start, event.allDay);
                }

                setScheduleValues(range);
                $('#kt_modal_calendar_type_picker').modal('hide');
                $('#kt_modal_calendar_schedule').modal('show');
            }

            function submitScheduleForm(event) {
                event.preventDefault();

                var scheduleId = $('#kt_calendar_schedule_id').val();
                var requestUrl = scheduleMode === 'edit'
                    ? routes.scheduleUpdateBase + '/' + scheduleId
                    : routes.scheduleStore;
                var requestMethod = scheduleMode === 'edit' ? 'PUT' : 'POST';
                var payload = $('#kt_calendar_schedule_form').serializeArray();

                payload.push({
                    name: 'all_day',
                    value: $('#kt_calendar_schedule_all_day').is(':checked') ? 1 : 0
                });

                $('#kt_calendar_schedule_submit').attr('data-kt-indicator', 'on');

                $.ajax({
                    method: requestMethod,
                    url: requestUrl,
                    dataType: 'json',
                    data: $.param(payload),
                    success: function(result) {
                        if (result.success) {
                            $('#kt_modal_calendar_schedule').modal('hide');
                            toastr.success(result.msg);
                            refetchEvents();
                        } else {
                            toastr.error(result.msg || calendarCopy.saveScheduleError);
                        }
                    },
                    error: function(xhr) {
                        toastr.error((xhr.responseJSON && xhr.responseJSON.message) || calendarCopy.saveScheduleError);
                    },
                    complete: function() {
                        $('#kt_calendar_schedule_submit').removeAttr('data-kt-indicator');
                    }
                });
            }

            function deleteSchedule() {
                var scheduleId = $('#kt_calendar_schedule_id').val();
                if (!scheduleId) {
                    return;
                }

                swal({
                    title: LANG.sure,
                    icon: 'warning',
                    buttons: true,
                    dangerMode: true,
                }).then(function(willDelete) {
                    if (!willDelete) {
                        return;
                    }

                    $.ajax({
                        method: 'DELETE',
                        url: routes.scheduleUpdateBase + '/' + scheduleId,
                        dataType: 'json',
                        success: function(result) {
                            if (result.success) {
                                $('#kt_modal_calendar_schedule').modal('hide');
                                toastr.success(result.msg);
                                refetchEvents();
                            } else {
                                toastr.error(result.msg || calendarCopy.deleteScheduleError);
                            }
                        },
                        error: function() {
                            toastr.error(calendarCopy.deleteScheduleError);
                        }
                    });
                });
            }

            function getTodoDateTimeFormat() {
                return moment_date_format + ' ' + moment_time_format;
            }

            function destroyLegacyTodoDatePicker($input) {
                if (! $input || ! $input.length) {
                    return;
                }

                if ($input.data('DateTimePicker') && typeof $input.data('DateTimePicker').destroy === 'function') {
                    $input.data('DateTimePicker').destroy();
                }

                if ($input.data('daterangepicker')) {
                    $input.data('daterangepicker').remove();
                    $input.removeData('daterangepicker');
                }
            }

            function bindTodoCalendarDateInput($input, initialValue, minMoment) {
                if (! $input || ! $input.length || ! $.fn.daterangepicker || typeof moment === 'undefined') {
                    return;
                }

                destroyLegacyTodoDatePicker($input);

                var format = getTodoDateTimeFormat();
                var settings = $.extend(true, {}, window.dateRangeSettings || {}, {
                    parentEl: '#task_modal .modal-content',
                    singleDatePicker: true,
                    timePicker: true,
                    timePicker24Hour: true,
                    autoUpdateInput: true,
                    autoApply: true,
                    showDropdowns: true,
                    opens: 'center',
                });

                settings.locale = settings.locale || {};
                settings.locale.format = format;
                settings.startDate = initialValue.clone();
                settings.endDate = initialValue.clone();
                settings.minDate = minMoment ? minMoment.clone() : false;

                $input.attr('readonly', true);
                $input.daterangepicker(settings);
                $input.val(initialValue.format(format));

                $input.off('.calendarTodoPicker')
                    .on('apply.daterangepicker.calendarTodoPicker', function(ev, picker) {
                        $(this).val(picker.startDate.format(format));
                    })
                    .on('focus.calendarTodoPicker click.calendarTodoPicker', function() {
                        var picker = $(this).data('daterangepicker');
                        if (picker) {
                            picker.show();
                        }
                    });
            }

            function initTodoCalendarModal(range) {
                var selectedDate = range.start.clone();
                var endDate = range.end.clone();
                var $modal = $('#task_modal');
                var $startInput = $modal.find('form#task_form input[name="date"]');
                var $endInput = $modal.find('form#task_form input[name="end_date"]');
                var format = getTodoDateTimeFormat();

                bindTodoCalendarDateInput($startInput, selectedDate);
                bindTodoCalendarDateInput($endInput, endDate, selectedDate);

                $startInput.off('apply.daterangepicker.calendarTodoSync').on('apply.daterangepicker.calendarTodoSync', function(ev, picker) {
                    var startMoment = picker.startDate.clone();
                    var endPicker = $endInput.data('daterangepicker');

                    if (! endPicker) {
                        return;
                    }

                    endPicker.minDate = startMoment.clone();
                    if (endPicker.startDate.isBefore(startMoment)) {
                        endPicker.setStartDate(startMoment.clone());
                        endPicker.setEndDate(startMoment.clone());
                        $endInput.val(startMoment.format(format));
                    }
                });
            }

            function loadModalUrl(target, url, onLoaded) {
                $.ajax({
                    url: url,
                    dataType: 'html',
                    success: function(result) {
                        var $target = $(target);
                        $target.html(result);

                        if (typeof onLoaded === 'function') {
                            $target.one('shown.bs.modal', function() {
                                onLoaded($target);
                            });
                        }

                        $target.modal('show');
                    }
                });
            }

            function withTypePickerClosed(callback) {
                var $typePicker = $('#kt_modal_calendar_type_picker');

                if (! $typePicker.hasClass('show')) {
                    callback();
                    return;
                }

                $typePicker.one('hidden.bs.modal', function() {
                    callback();
                });
                $typePicker.modal('hide');
            }

            function initHolidayModal(range) {
                var startDate = range.start.format(moment_date_format);
                var endDate = range.end.format(moment_date_format);

                $('#add_holiday_modal .select2').select2({
                    width: '100%',
                    dropdownParent: $('#add_holiday_modal')
                });
                $('form#add_holiday_form #start_date, form#add_holiday_form #end_date').datepicker({
                    autoclose: true,
                    format: datepicker_date_format
                });
                $('form#add_holiday_form #start_date').val(startDate);
                $('form#add_holiday_form #end_date').val(endDate);
            }

            function initLeaveModal(range) {
                var startDate = range.start.format(moment_date_format);
                var endDate = range.end.format(moment_date_format);

                $('#add_leave_modal .select2').select2({
                    width: '100%',
                    dropdownParent: $('#add_leave_modal')
                });
                $('form#add_leave_form #start_date, form#add_leave_form #end_date').datepicker({
                    autoclose: true,
                    format: datepicker_date_format
                });
                $('form#add_leave_form #start_date').val(startDate);
                $('form#add_leave_form #end_date').val(endDate);
            }

            function initReminderModal(range) {
                var $modal = $('#calendar_dynamic_modal_root').find('.reminder');
                var defaultTime = range.allDay ? moment() : range.start;

                $modal.find('form#reminder_form .datepicker').datepicker({
                    autoclose: true,
                    format: datepicker_date_format
                });
                $modal.find('form#reminder_form input#time').datetimepicker({
                    format: moment_time_format,
                    ignoreReadonly: true
                });
                $modal.find('form#reminder_form input#end_time').datetimepicker({
                    format: moment_time_format,
                    ignoreReadonly: true
                });
                $modal.find('form#reminder_form input[name="date"]').val(range.start.format(moment_date_format));
                $modal.find('form#reminder_form input[name="time"]').val(defaultTime.format(moment_time_format));
                $modal.find('form#reminder_form input[name="end_time"]').val(defaultTime.clone().add(1, 'hour').format(moment_time_format));
            }

            function getLocationTables(locationId) {
                $.ajax({
                    method: 'GET',
                    url: '/modules/data/get-pos-details',
                    data: {
                        location_id: locationId
                    },
                    dataType: 'html',
                    success: function(result) {
                        $('div#restaurant_module_span').html(result);
                    }
                });
            }

            function resetBookingForm() {
                $('select#booking_location_id').val('').change();
                $('select#correspondent').val('').change();
                $('#booking_note, #start_time, #end_time').val('');
            }

            function initBookingModal(range) {
                var $modal = $('#calendar_dynamic_modal_root').find('#add_booking_modal');
                var startMoment = range.allDay ? range.start.clone().hour(moment().hour()).minute(0) : range.start.clone();
                var endMoment = range.allDay ? startMoment.clone().add(1, 'hour') : range.end.clone();

                if ($modal.find('select#booking_location_id').val()) {
                    getLocationTables($modal.find('select#booking_location_id').val());
                }

                $modal.find('select').each(function() {
                    $(this).select2({
                        dropdownParent: $modal,
                        width: '100%'
                    });
                });

                $modal.find('form#add_booking_form #start_time').datetimepicker({
                    format: moment_date_format + ' ' + moment_time_format,
                    minDate: moment(),
                    ignoreReadonly: true
                });
                $modal.find('form#add_booking_form #end_time').datetimepicker({
                    format: moment_date_format + ' ' + moment_time_format,
                    minDate: moment(),
                    ignoreReadonly: true
                });

                if ($modal.find('form#add_booking_form #start_time').data('DateTimePicker')) {
                    $modal.find('form#add_booking_form #start_time').data('DateTimePicker').date(startMoment);
                }
                if ($modal.find('form#add_booking_form #end_time').data('DateTimePicker')) {
                    $modal.find('form#add_booking_form #end_time').data('DateTimePicker').date(endMoment);
                }
            }

            function handleCreateFlow(type) {
                $.ajax({
                    url: routes.createFlow,
                    type: 'GET',
                    dataType: 'json',
                    data: {
                        type: type
                    },
                    success: function(response) {
                        var range = selectedRange || getDefaultCreateRange();

                        if (response.mode === 'schedule') {
                            withTypePickerClosed(function() {
                                openScheduleModal('create');
                            });
                            return;
                        }

                        if (response.mode === 'modal_url' && response.target && response.url) {
                            withTypePickerClosed(function() {
                                loadModalUrl(response.target, response.url, function() {
                                    if (type === 'todo') {
                                        initTodoCalendarModal(range);
                                    } else if (type === 'holiday') {
                                        initHolidayModal(range);
                                    } else if (type === 'leaves') {
                                        initLeaveModal(range);
                                    }
                                });
                            });
                            return;
                        }

                        if (response.mode === 'modal_html' && response.html) {
                            withTypePickerClosed(function() {
                                $('#calendar_dynamic_modal_root').html(response.html);

                                if (type === 'reminder') {
                                    var $reminderModal = $('#calendar_dynamic_modal_root').find('.reminder');
                                    initReminderModal(range);
                                    $reminderModal.modal('show');
                                } else if (type === 'booking') {
                                    var $bookingModal = $('#calendar_dynamic_modal_root').find('#add_booking_modal');
                                    initBookingModal(range);
                                    $bookingModal.modal('show');
                                }
                            });
                        }
                    },
                    error: function() {
                        toastr.error(calendarCopy.openCreateFlowError);
                    }
                });
            }

            function bindStaticActions() {
                $('#kt_calendar_open_type_picker').on('click', function() {
                    openTypePicker(getDefaultCreateRange());
                });

                $(document).on('click', '[data-calendar-create-type]', function() {
                    handleCreateFlow($(this).data('calendar-create-type'));
                });

                $('[data-calendar-nav]').on('click', function() {
                    if (!calendar) {
                        return;
                    }

                    var action = $(this).data('calendar-nav');
                    if (typeof calendar[action] === 'function') {
                        calendar[action]();
                    }
                });

                $('[data-calendar-view]').on('click', function() {
                    if (calendar) {
                        calendar.changeView($(this).data('calendar-view'));
                    }
                });

                $('#kt_calendar_schedule_form').on('submit', submitScheduleForm);
                $('#kt_calendar_schedule_all_day').on('change', function() {
                    var startValue = $('#kt_calendar_schedule_start').val();
                    var endValue = $('#kt_calendar_schedule_end').val();

                    initSchedulePickers();

                    if (startValue) {
                        $('#kt_calendar_schedule_start').val(startValue);
                    }
                    if (endValue) {
                        $('#kt_calendar_schedule_end').val(endValue);
                    }
                });
                $('#kt_calendar_schedule_delete').on('click', deleteSchedule);
            }

            function bindLegacyHandlers() {
                $(document).on('submit', 'form#add_holiday_form', function(e) {
                    e.preventDefault();
                    var $form = $(this);
                    $form.find('button[type="submit"]').attr('disabled', true);

                    $.ajax({
                        method: $form.attr('method'),
                        url: $form.attr('action'),
                        dataType: 'json',
                        data: $form.serialize(),
                        success: function(result) {
                            if (result.success) {
                                $('#add_holiday_modal').modal('hide');
                                toastr.success(result.msg);
                                refetchEvents();
                            } else {
                                toastr.error(result.msg);
                            }
                        },
                        complete: function() {
                            $form.find('button[type="submit"]').attr('disabled', false);
                        }
                    });
                });

                $(document).on('submit', 'form#add_leave_form', function(e) {
                    e.preventDefault();
                    var $form = $(this);
                    $form.find('button[type="submit"]').attr('disabled', true);

                    $.ajax({
                        method: $form.attr('method'),
                        url: $form.attr('action'),
                        dataType: 'json',
                        data: $form.serialize(),
                        success: function(result) {
                            if (result.success) {
                                $('#add_leave_modal').modal('hide');
                                toastr.success(result.msg);
                                refetchEvents();
                            } else {
                                toastr.error(result.msg);
                            }
                        },
                        complete: function() {
                            $form.find('button[type="submit"]').attr('disabled', false);
                        }
                    });
                });

                $(document).on('submit', 'form#reminder_form', function(e) {
                    e.preventDefault();
                    var $form = $(this);

                    $.ajax({
                        method: 'POST',
                        url: $form.attr('action'),
                        data: $form.serialize(),
                        dataType: 'json',
                        success: function(result) {
                            if (result.success) {
                                $('#calendar_dynamic_modal_root').find('.reminder').modal('hide');
                                toastr.success(result.msg);
                                refetchEvents();
                            } else {
                                toastr.error(result.msg);
                            }
                        }
                    });
                });

                $(document).on('change', 'select#booking_location_id', function() {
                    getLocationTables($(this).val());
                });

                $(document).on('submit', 'form#add_booking_form', function(e) {
                    e.preventDefault();
                    var $form = $(this);
                    $form.find('button[type="submit"]').attr('disabled', true);

                    $.ajax({
                        method: 'POST',
                        url: $form.attr('action'),
                        dataType: 'json',
                        data: $form.serialize(),
                        success: function(result) {
                            if (result.success) {
                                $('#calendar_dynamic_modal_root').find('#add_booking_modal').modal('hide');
                                toastr.success(result.msg);
                                refetchEvents();
                            } else {
                                toastr.error(result.msg);
                            }
                        },
                        complete: function() {
                            $form.find('button[type="submit"]').attr('disabled', false);
                        }
                    });
                });

                $(document).on('hidden.bs.modal', '#calendar_dynamic_modal_root .modal', function() {
                    if ($(this).attr('id') === 'add_booking_modal') {
                        resetBookingForm();
                    }
                    $('#calendar_dynamic_modal_root').html('');
                });
            }

            $(document).ready(function() {
                initSelects();
                bindStaticActions();
                bindLegacyHandlers();
                initCalendar();
            });

            $(document).on('change', '#user_id, #location_id, .event_check', function() {
                refetchEvents();
            });

            return {
                refetchEvents: refetchEvents,
                openTypePicker: openTypePicker
            };
        })();
    </script>
    @if (isset($create_types['todo']))
        @includeIf('essentials::todo.todo_javascript')
    @endif
@endsection
