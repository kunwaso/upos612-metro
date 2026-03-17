<?php

namespace App\Utils;

use App\BusinessLocation;
use App\CalendarSchedule;
use App\Contact;
use App\ProductQuote;
use App\Transaction;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Modules\Essentials\Http\Controllers\EssentialsHolidayController;
use Modules\Essentials\Http\Controllers\EssentialsLeaveController;
use Modules\Essentials\Http\Controllers\ToDoController;

class CalendarEventUtil
{
    protected ModuleUtil $moduleUtil;

    protected RestaurantUtil $restaurantUtil;

    protected array $baseEventTypeMeta = [
        'bookings' => [
            'label' => 'restaurant.bookings',
            'color' => '#007FFF',
            'badge_class' => 'badge-light-primary',
            'icon' => 'calendar-8',
            'create_mode' => 'modal_html',
        ],
        'todo' => [
            'label' => 'essentials::lang.todo',
            'color' => '#33006F',
            'badge_class' => 'badge-light-primary',
            'icon' => 'check-circle',
            'create_mode' => 'modal_url',
        ],
        'holiday' => [
            'label' => 'essentials::lang.holidays',
            'color' => '#568203',
            'badge_class' => 'badge-light-success',
            'icon' => 'abstract-26',
            'create_mode' => 'modal_url',
        ],
        'leaves' => [
            'label' => 'essentials::lang.leaves',
            'color' => '#BA0021',
            'badge_class' => 'badge-light-danger',
            'icon' => 'abstract-24',
            'create_mode' => 'modal_url',
        ],
        'reminder' => [
            'label' => 'essentials::lang.reminders',
            'color' => '#ff851b',
            'badge_class' => 'badge-light-warning',
            'icon' => 'notification-bing',
            'create_mode' => 'modal_html',
        ],
        'schedule' => [
            'label' => 'lang_v1.schedule',
            'color' => '#1B84FF',
            'badge_class' => 'badge-light-info',
            'icon' => 'calendar-2',
            'create_mode' => 'schedule',
        ],
        'quote_expiry' => [
            'label' => 'lang_v1.quote_expiry',
            'color' => '#F6B100',
            'badge_class' => 'badge-light-warning',
            'icon' => 'calendar-tick',
            'create_mode' => null,
        ],
        'sales_order_delivery' => [
            'label' => 'lang_v1.sales_order_delivery',
            'color' => '#FF6F1E',
            'badge_class' => 'badge-light-danger',
            'icon' => 'delivery-3',
            'create_mode' => null,
        ],
    ];

    public function __construct(ModuleUtil $moduleUtil, RestaurantUtil $restaurantUtil)
    {
        $this->moduleUtil = $moduleUtil;
        $this->restaurantUtil = $restaurantUtil;
    }

    public function getCalendarPageData(int $businessId, User $user): array
    {
        $canManageAllSchedules = $this->canManageAllSchedules($businessId, $user);

        return [
            'all_locations' => BusinessLocation::forDropdown($businessId)->toArray(),
            'users' => $canManageAllSchedules
                ? User::forDropdown($businessId, false)
                : [(int) $user->id => $user->user_full_name],
            'event_types' => $this->getVisibleEventTypes($businessId, $user),
            'create_types' => $this->getCreateTypes($businessId, $user),
            'can_manage_all_schedules' => $canManageAllSchedules,
        ];
    }

    public function getVisibleEventTypes(int $businessId, User $user): array
    {
        $eventTypes = [];

        if ($this->canViewBookings($user)) {
            $eventTypes['bookings'] = $this->buildEventTypeMeta('bookings');
        }

        if ($this->moduleUtil->isModuleInstalled('Essentials')) {
            $moduleEventTypes = $this->moduleUtil->getModuleData('eventTypes');
            foreach ($moduleEventTypes as $moduleEventType) {
                foreach ($moduleEventType as $key => $config) {
                    $eventTypes[$key] = array_merge($this->buildEventTypeMeta($key), $config);
                }
            }
        }

        $eventTypes['schedule'] = $this->buildEventTypeMeta('schedule');

        if ($this->canViewQuotes($user)) {
            $eventTypes['quote_expiry'] = $this->buildEventTypeMeta('quote_expiry');
        }

        if ($this->canViewSalesOrders($user)) {
            $eventTypes['sales_order_delivery'] = $this->buildEventTypeMeta('sales_order_delivery');
        }

        return $eventTypes;
    }

    public function getCreateTypes(int $businessId, User $user): array
    {
        $types = [];

        foreach (['schedule', 'todo', 'booking', 'holiday', 'leaves', 'reminder'] as $type) {
            if (! $this->canCreateType($type, $businessId, $user)) {
                continue;
            }

            $meta = $this->buildEventTypeMeta($type);
            $meta['key'] = $type;
            $types[$type] = $meta;
        }

        return $types;
    }

    public function canCreateType(string $type, int $businessId, User $user): bool
    {
        $hasEssentialsSubscription = $user->can('superadmin')
            || $this->moduleUtil->hasThePermissionInSubscription($businessId, 'essentials_module');

        return match ($type) {
            'schedule' => true,
            'todo' => $this->moduleUtil->isModuleInstalled('Essentials')
                && $hasEssentialsSubscription
                && $user->can('essentials.add_todos'),
            'holiday' => $this->moduleUtil->isModuleInstalled('Essentials')
                && $hasEssentialsSubscription
                && $this->canManageAllSchedules($businessId, $user),
            'leaves' => $this->moduleUtil->isModuleInstalled('Essentials')
                && $hasEssentialsSubscription
                && ($user->can('essentials.crud_all_leave') || $user->can('essentials.crud_own_leave')),
            'reminder' => $this->moduleUtil->isModuleInstalled('Essentials') && $hasEssentialsSubscription,
            'booking' => $this->canViewBookings($user),
            default => false,
        };
    }

    public function canManageAllSchedules(int $businessId, User $user): bool
    {
        return $user->can('superadmin') || $this->restaurantUtil->is_admin($user, $businessId);
    }

    public function buildFilters(Request $request, int $businessId, User $user): array
    {
        $canManageAllSchedules = $this->canManageAllSchedules($businessId, $user);
        $requestedUserId = $request->filled('user_id') ? (int) $request->input('user_id') : null;
        $selectedEvents = array_values(array_filter((array) $request->input('events', [])));
        $visibleEventTypes = $this->getVisibleEventTypes($businessId, $user);

        if (empty($selectedEvents)) {
            $selectedEvents = array_keys($visibleEventTypes);
        }

        return [
            'start_date' => (string) $request->input('start'),
            'end_date' => (string) $request->input('end'),
            'user_id' => $canManageAllSchedules && $requestedUserId > 0 ? $requestedUserId : (int) $user->id,
            'requested_user_id' => $requestedUserId,
            'location_id' => $request->filled('location_id') ? (int) $request->input('location_id') : null,
            'business_id' => $businessId,
            'events' => array_values(array_intersect($selectedEvents, array_keys($visibleEventTypes))),
            'color' => '#007FFF',
            'visible_event_types' => $visibleEventTypes,
            'can_manage_all_schedules' => $canManageAllSchedules,
        ];
    }

    public function getEvents(array $filters, User $user): array
    {
        $events = [];

        if (in_array('bookings', $filters['events'], true) && $this->canViewBookings($user)) {
            $events = array_merge($events, $this->restaurantUtil->getBookingsForCalendar($filters));
        }

        if ($this->moduleUtil->isModuleInstalled('Essentials')) {
            $moduleEvents = $this->moduleUtil->getModuleData('calendarEvents', $filters);
            foreach ($moduleEvents as $moduleEventGroup) {
                $events = array_merge($events, $moduleEventGroup);
            }
        }

        if (in_array('schedule', $filters['events'], true)) {
            $events = array_merge($events, $this->getScheduleEvents($filters));
        }

        if (in_array('quote_expiry', $filters['events'], true) && $this->canViewQuotes($user)) {
            $events = array_merge($events, $this->getQuoteExpiryEvents($filters));
        }

        if (in_array('sales_order_delivery', $filters['events'], true) && $this->canViewSalesOrders($user)) {
            $events = array_merge($events, $this->getSalesOrderDeliveryEvents($filters));
        }

        return array_values(array_map(function ($event) use ($filters) {
            return $this->normalizeEvent($event, $filters['visible_event_types']);
        }, $events));
    }

    public function prepareSchedulePayload(array $validated, int $businessId, User $user, bool $canManageAllSchedules): array
    {
        $allDay = (bool) ($validated['all_day'] ?? false);
        $start = Carbon::parse((string) $validated['start']);
        $endInput = ! empty($validated['end']) ? Carbon::parse((string) $validated['end']) : null;

        if ($allDay) {
            $start = $start->copy()->startOfDay();
            $end = ($endInput ?: $start)->copy()->endOfDay();
        } else {
            $end = ($endInput ?: $start)->copy();
        }

        $ownerId = $canManageAllSchedules && ! empty($validated['user_id'])
            ? (int) $validated['user_id']
            : (int) $user->id;

        return [
            'business_id' => $businessId,
            'user_id' => $ownerId,
            'created_by' => (int) $user->id,
            'location_id' => ! empty($validated['location_id']) ? (int) $validated['location_id'] : null,
            'title' => trim((string) $validated['title']),
            'description' => Arr::get($validated, 'description'),
            'notes' => Arr::get($validated, 'notes'),
            'start_at' => $start,
            'end_at' => $end,
            'all_day' => $allDay,
            'color' => $this->normalizeHexColor((string) ($validated['color'] ?? $this->buildEventTypeMeta('schedule')['color'])),
        ];
    }

    public function canEditSchedule(CalendarSchedule $schedule, int $businessId, User $user): bool
    {
        if ($schedule->business_id !== $businessId) {
            return false;
        }

        return $this->canManageAllSchedules($businessId, $user)
            || (int) $schedule->user_id === (int) $user->id
            || (int) $schedule->created_by === (int) $user->id;
    }

    public function buildCreateFlowResponse(string $type, int $businessId, User $user): ?array
    {
        if (! $this->canCreateType($type, $businessId, $user)) {
            return null;
        }

        return match ($type) {
            'schedule' => [
                'mode' => 'schedule',
                'type' => $type,
            ],
            'todo' => [
                'mode' => 'modal_url',
                'type' => $type,
                'target' => '#task_modal',
                'url' => action([ToDoController::class, 'create']) . '?from_calendar=true',
            ],
            'holiday' => [
                'mode' => 'modal_url',
                'type' => $type,
                'target' => '#add_holiday_modal',
                'url' => action([EssentialsHolidayController::class, 'create']),
            ],
            'leaves' => [
                'mode' => 'modal_url',
                'type' => $type,
                'target' => '#add_leave_modal',
                'url' => action([EssentialsLeaveController::class, 'create']),
            ],
            'reminder' => [
                'mode' => 'modal_html',
                'type' => $type,
                'html' => view('essentials::reminder.create')->render(),
            ],
            'booking' => [
                'mode' => 'modal_html',
                'type' => $type,
                'html' => view('restaurant.booking.create', [
                    'business_locations' => BusinessLocation::forDropdown($businessId),
                    'customers' => Contact::customersDropdown($businessId, false),
                    'correspondents' => User::forDropdown($businessId, false),
                ])->render(),
            ],
            default => null,
        };
    }

    protected function getScheduleEvents(array $filters): array
    {
        $query = CalendarSchedule::where('business_id', $filters['business_id'])
            ->with(['owner', 'creator', 'location'])
            ->where(function ($query) use ($filters) {
                $query->whereBetween('start_at', [$filters['start_date'], $filters['end_date']])
                    ->orWhereBetween('end_at', [$filters['start_date'], $filters['end_date']])
                    ->orWhere(function ($innerQuery) use ($filters) {
                        $innerQuery->where('start_at', '<=', $filters['start_date'])
                            ->where('end_at', '>=', $filters['end_date']);
                    });
            });

        if (! empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (! empty($filters['location_id'])) {
            $query->where(function ($locationQuery) use ($filters) {
                $locationQuery->where('location_id', $filters['location_id'])
                    ->orWhereNull('location_id');
            });
        }

        return $query->orderBy('start_at')->get()->map(function (CalendarSchedule $schedule) {
            $subtitle = trim((string) optional($schedule->location)->name);
            if ($subtitle === '') {
                $subtitle = trim((string) optional($schedule->owner)->user_full_name);
            }

            return [
                'id' => $schedule->id,
                'title' => $schedule->title,
                'start' => optional($schedule->start_at)->toIso8601String(),
                'end' => optional($schedule->end_at)->toIso8601String(),
                'allDay' => (bool) $schedule->all_day,
                'event_type' => 'schedule',
                'url' => null,
                'backgroundColor' => $schedule->color ?: $this->buildEventTypeMeta('schedule')['color'],
                'borderColor' => $schedule->color ?: $this->buildEventTypeMeta('schedule')['color'],
                'extendedProps' => [
                    'description' => $schedule->description,
                    'notes' => $schedule->notes,
                    'location_name' => optional($schedule->location)->name,
                    'owner_name' => optional($schedule->owner)->user_full_name,
                    'creator_name' => optional($schedule->creator)->user_full_name,
                    'location_id' => $schedule->location_id,
                    'user_id' => $schedule->user_id,
                    'schedule_id' => $schedule->id,
                    'subtitle' => $subtitle,
                ],
            ];
        })->all();
    }

    protected function getQuoteExpiryEvents(array $filters): array
    {
        $query = ProductQuote::forBusiness($filters['business_id'])
            ->whereNotNull('expires_at')
            ->whereBetween('expires_at', [$filters['start_date'], $filters['end_date']])
            ->with(['contact', 'location', 'creator']);

        if (! empty($filters['user_id'])) {
            $query->where('created_by', $filters['user_id']);
        }

        if (! empty($filters['location_id'])) {
            $query->where('location_id', $filters['location_id']);
        }

        return $query->orderBy('expires_at')->get()->map(function (ProductQuote $quote) {
            $title = trim((string) ($quote->quote_number ?: $quote->uuid ?: ('#' . $quote->id)));
            $subtitle = trim((string) ($quote->customer_name ?: optional($quote->contact)->supplier_business_name ?: optional($quote->contact)->name));

            return [
                'id' => $quote->id,
                'title' => $title,
                'start' => optional($quote->expires_at)->toDateString(),
                'end' => optional($quote->expires_at)->toDateString(),
                'allDay' => true,
                'event_type' => 'quote_expiry',
                'url' => route('product.quotes.show', ['id' => $quote->id]),
                'backgroundColor' => $this->buildEventTypeMeta('quote_expiry')['color'],
                'borderColor' => $this->buildEventTypeMeta('quote_expiry')['color'],
                'extendedProps' => [
                    'subtitle' => $subtitle,
                    'quote_id' => $quote->id,
                    'customer_name' => $subtitle,
                    'location_name' => optional($quote->location)->name,
                    'owner_name' => optional($quote->creator)->user_full_name,
                ],
            ];
        })->all();
    }

    protected function getSalesOrderDeliveryEvents(array $filters): array
    {
        $query = Transaction::where('transactions.business_id', $filters['business_id'])
            ->where('transactions.type', 'sell')
            ->whereNotNull('transactions.delivery_date')
            ->whereBetween('transactions.delivery_date', [$filters['start_date'], $filters['end_date']])
            ->join('product_quotes as pq', function ($join) use ($filters) {
                $join->on('pq.transaction_id', '=', 'transactions.id')
                    ->where('pq.business_id', '=', $filters['business_id']);
            })
            ->leftJoin('contacts as c', 'transactions.contact_id', '=', 'c.id')
            ->leftJoin('business_locations as bl', 'transactions.location_id', '=', 'bl.id')
            ->select([
                'transactions.id',
                'transactions.invoice_no',
                'transactions.delivery_date',
                'transactions.created_by',
                'transactions.location_id',
                'pq.quote_number',
                'c.name as customer_name',
                'bl.name as location_name',
            ]);

        if (! empty($filters['user_id'])) {
            $query->where('transactions.created_by', $filters['user_id']);
        }

        if (! empty($filters['location_id'])) {
            $query->where('transactions.location_id', $filters['location_id']);
        }

        return $query->orderBy('transactions.delivery_date')->get()->map(function ($order) {
            $title = trim((string) ($order->invoice_no ?: $order->quote_number ?: ('#' . $order->id)));

            return [
                'id' => $order->id,
                'title' => $title,
                'start' => Carbon::parse($order->delivery_date)->toDateString(),
                'end' => Carbon::parse($order->delivery_date)->toDateString(),
                'allDay' => true,
                'event_type' => 'sales_order_delivery',
                'url' => route('product.sales.orders.show', ['id' => $order->id]),
                'backgroundColor' => $this->buildEventTypeMeta('sales_order_delivery')['color'],
                'borderColor' => $this->buildEventTypeMeta('sales_order_delivery')['color'],
                'extendedProps' => [
                    'subtitle' => (string) ($order->customer_name ?: $order->location_name ?: ''),
                    'sales_order_id' => $order->id,
                    'customer_name' => (string) ($order->customer_name ?: ''),
                    'location_name' => (string) ($order->location_name ?: ''),
                ],
            ];
        })->all();
    }

    protected function normalizeEvent($event, array $visibleEventTypes): array
    {
        $payload = (array) $event;
        $eventType = (string) ($payload['event_type'] ?? 'schedule');
        $typeMeta = $visibleEventTypes[$eventType] ?? $this->buildEventTypeMeta($eventType);
        $extendedProps = array_merge((array) ($payload['extendedProps'] ?? []), [
            'event_type' => $eventType,
            'type_label' => $typeMeta['label'] ?? ucfirst(str_replace('_', ' ', $eventType)),
            'type_color' => $payload['backgroundColor'] ?? $typeMeta['color'] ?? '#1B84FF',
            'badge_class' => $typeMeta['badge_class'] ?? 'badge-light-primary',
            'title_html' => $payload['title_html'] ?? null,
            'subtitle' => $payload['subtitle'] ?? Arr::get($payload, 'extendedProps.subtitle'),
            'event_url' => $payload['event_url'] ?? null,
        ]);

        $id = (string) ($payload['id'] ?? $this->generateEventId($payload, $eventType));
        $allDay = (bool) ($payload['allDay'] ?? $payload['all_day'] ?? false);

        return [
            'id' => $id,
            'title' => (string) ($payload['title'] ?? ''),
            'start' => $this->normalizeDateValue($payload['start'] ?? null, $allDay),
            'end' => $this->normalizeDateValue($payload['end'] ?? ($payload['start'] ?? null), $allDay),
            'allDay' => $allDay,
            'event_type' => $eventType,
            'url' => $this->resolveEventUrl($eventType, $payload),
            'backgroundColor' => $payload['backgroundColor'] ?? $typeMeta['color'] ?? '#1B84FF',
            'borderColor' => $payload['borderColor'] ?? $payload['backgroundColor'] ?? $typeMeta['color'] ?? '#1B84FF',
            'extendedProps' => $extendedProps,
        ];
    }

    protected function buildEventTypeMeta(string $type): array
    {
        $meta = $this->baseEventTypeMeta[$type] ?? [
            'label' => Str::headline($type),
            'color' => '#1B84FF',
            'badge_class' => 'badge-light-primary',
            'icon' => 'calendar',
            'create_mode' => null,
        ];

        if (is_string($meta['label'] ?? null) && str_contains($meta['label'], '.')) {
            $meta['label'] = __($meta['label']);
        }

        return $meta;
    }

    protected function normalizeDateValue($value, bool $allDay): ?string
    {
        if (empty($value)) {
            return null;
        }

        try {
            $date = Carbon::parse((string) $value);

            return $allDay ? $date->toDateString() : $date->toIso8601String();
        } catch (\Throwable $exception) {
            return (string) $value;
        }
    }

    protected function generateEventId(array $payload, string $eventType): string
    {
        return $eventType . '-' . substr(md5(json_encode([
            $payload['title'] ?? '',
            $payload['start'] ?? '',
            $payload['end'] ?? '',
            $payload['url'] ?? '',
        ])), 0, 12);
    }

    protected function resolveEventUrl(string $eventType, array $payload): ?string
    {
        if ($eventType === 'reminder') {
            return $payload['event_url'] ?? $payload['url'] ?? null;
        }

        return $payload['url'] ?? $payload['event_url'] ?? null;
    }

    protected function canViewBookings(User $user): bool
    {
        return $user->can('crud_all_bookings') || $user->can('crud_own_bookings');
    }

    protected function canViewQuotes(User $user): bool
    {
        return $user->can('product_quote.view');
    }

    protected function canViewSalesOrders(User $user): bool
    {
        return $user->can('sell.view') || $user->can('direct_sell.view');
    }

    protected function normalizeHexColor(string $color): string
    {
        $normalized = trim($color);

        if ($normalized === '') {
            return $this->buildEventTypeMeta('schedule')['color'];
        }

        return Str::startsWith($normalized, '#') ? $normalized : ('#' . $normalized);
    }
}
