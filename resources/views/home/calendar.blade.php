@extends('layouts.app')
@section('title', __('lang_v1.calendar'))

@section('content')
    <div class="d-flex flex-column gap-7">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h1 class="fs-2hx fw-bold text-gray-900 mb-0">@lang('lang_v1.calendar')</h1>
            </div>
        </div>

        <div class="row g-5 g-xl-10">
            <div class="col-12 col-xxl-3">
                <div class="card">
                    <div class="card-header border-0 pt-6">
                        <div class="card-title">
                            <h3 class="card-title fw-bold text-gray-900 mb-0">@lang('report.filters')</h3>
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
                                <div class="form-label fw-semibold text-gray-700 fs-6 mb-4">Event Types</div>
                                <div class="d-flex flex-column">
                                    @foreach ($event_types as $key => $value)
                                        <label class="form-check form-check-custom form-check-solid align-items-center mb-4">
                                            {!! Form::checkbox('events', $key, true, [
                                                'class' => 'form-check-input event_check',
                                            ]) !!}
                                            <span class="form-check-label fw-semibold ms-3" style="color: {{ $value['color'] }}">
                                                {{ $value['label'] }}
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            @if (Module::has('Essentials'))
                                <button class="btn btn-primary btn-flex justify-content-center btn-modal"
                                    data-href="{{ action([\Modules\Essentials\Http\Controllers\ToDoController::class, 'create']) }}?from_calendar=true"
                                    data-container="#task_modal" type="button">
                                    <i class="ki-duotone ki-plus fs-2">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                        <span class="path3"></span>
                                    </i>
                                    @lang('essentials::lang.add_to_do')
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xxl-9">
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title fw-bold">@lang('lang_v1.calendar')</h2>
                        <div class="card-toolbar">
                            @if (Module::has('Essentials'))
                                <button class="btn btn-flex btn-primary" id="kt_calendar_add_event" type="button">
                                    <i class="ki-duotone ki-plus fs-2">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                        <span class="path3"></span>
                                    </i>
                                    @lang('essentials::lang.add_to_do')
                                </button>
                            @endif
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="kt_calendar_app"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('javascript')
    <script type="text/javascript">
        window.CalendarPage = (function() {
            var calendar = null;
            var calendarEl = document.getElementById('kt_calendar_app');
            var fullCalendarLib = window.KTFullCalendar || window.FullCalendar;
            var canCreateTask = @json(Module::has('Essentials'));
            var taskCreateUrl = @json(Module::has('Essentials') ? action([\Modules\Essentials\Http\Controllers\ToDoController::class, 'create']) . '?from_calendar=true' : null);
            var calendarLocale = @json(session()->get('user.language', config('app.locale')));
            var calendarDirection =
                @if (in_array(session()->get('user.language', config('app.locale')), config('constants.langs_rtl')))
                    'rtl';
                @else
                    'ltr';
                @endif

            function getCalendarFilterData() {
                var filters = {};
                var events = [];

                if ($('select#location_id').length) {
                    filters.location_id = $('select#location_id').val();
                }

                if ($('select#user_id').length) {
                    filters.user_id = $('select#user_id').val();
                }

                $.each($("input[name='events']:checked"), function() {
                    events.push($(this).val());
                });

                filters.events = events;

                return filters;
            }

            function normalizeCalendarEvent(event) {
                var normalizedEvent = $.extend({}, event);
                var targetUrl = normalizedEvent.event_url || normalizedEvent.url || null;

                if (targetUrl) {
                    normalizedEvent.url = targetUrl;
                }

                return normalizedEvent;
            }

            function setTaskModalDate(dateValue) {
                var selectedDate = moment(dateValue);
                var formattedDate = selectedDate.format(moment_date_format + ' ' + moment_time_format);
                var $dateInput = $('form#task_form input[name="date"]');

                if (!$dateInput.length) {
                    return;
                }

                if ($dateInput.data('DateTimePicker')) {
                    $dateInput.data('DateTimePicker').date(selectedDate);
                } else {
                    $dateInput.val(formattedDate);
                }
            }

            function openTaskModal(dateValue) {
                if (!canCreateTask || !taskCreateUrl) {
                    return;
                }

                $.ajax({
                    url: taskCreateUrl,
                    dataType: 'html',
                    success: function(result) {
                        var $taskModal = $('#task_modal');
                        $taskModal.html(result);
                        $taskModal.one('shown.bs.modal', function() {
                            setTaskModalDate(dateValue || moment());
                        });
                        $taskModal.modal('show');
                    }
                });
            }

            function renderCalendarEventContent(info) {
                var titleHtml = info.event.extendedProps.title_html;

                if (!titleHtml) {
                    return;
                }

                var titleElement = info.el.querySelector('.fc-event-title');
                if (titleElement) {
                    titleElement.innerHTML = titleHtml;
                }

                var listTitleLink = info.el.querySelector('.fc-list-event-title a');
                if (listTitleLink) {
                    listTitleLink.innerHTML = titleHtml;
                }
            }

            function initCalendar() {
                if (!calendarEl || !fullCalendarLib || typeof fullCalendarLib.Calendar !== 'function') {
                    return;
                }

                calendar = new fullCalendarLib.Calendar(calendarEl, {
                    locale: calendarLocale,
                    direction: calendarDirection,
                    themeSystem: 'bootstrap5',
                    initialView: 'dayGridMonth',
                    headerToolbar: {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'dayGridMonth,timeGridWeek,timeGridDay,listMonth'
                    },
                    buttonText: {
                        today: 'Today',
                        month: 'Month',
                        week: 'Week',
                        day: 'Day',
                        list: 'List'
                    },
                    navLinks: true,
                    dayMaxEvents: true,
                    height: 'auto',
                    selectable: canCreateTask,
                    events: function(fetchInfo, successCallback, failureCallback) {
                        $.ajax({
                            url: '/calendar',
                            type: 'get',
                            dataType: 'json',
                            data: $.extend({}, getCalendarFilterData(), {
                                start: fetchInfo.startStr,
                                end: fetchInfo.endStr
                            }),
                            success: function(response) {
                                var normalizedEvents = $.map(response, function(event) {
                                    return normalizeCalendarEvent(event);
                                });
                                successCallback(normalizedEvents);
                            },
                            error: function(xhr) {
                                failureCallback(xhr);
                            }
                        });
                    },
                    dateClick: function(info) {
                        openTaskModal(info.date);
                    },
                    eventClick: function(info) {
                        var targetUrl = info.event.extendedProps.event_url || info.event.url;

                        if (targetUrl) {
                            info.jsEvent.preventDefault();
                            window.location.href = targetUrl;
                        }
                    },
                    eventDidMount: function(info) {
                        renderCalendarEventContent(info);
                    }
                });

                calendar.render();
            }

            function refetchEvents() {
                if (calendar) {
                    calendar.refetchEvents();
                }
            }

            $(document).ready(function() {
                initCalendar();

                $('#kt_calendar_add_event').on('click', function() {
                    openTaskModal(moment());
                });
            });

            $(document).on('change', '#user_id, #location_id, .event_check', function() {
                refetchEvents();
            });

            return {
                refetchEvents: refetchEvents,
                openTaskModal: openTaskModal
            };
        })();
    </script>
@endsection
