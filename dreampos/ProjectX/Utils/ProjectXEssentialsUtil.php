<?php

namespace Modules\ProjectX\Utils;

use App\Business;
use App\BusinessLocation;
use App\Media;
use App\User;
use App\Utils\ModuleUtil;
use App\Utils\Util;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Modules\Essentials\Entities\Document;
use Modules\Essentials\Entities\DocumentShare;
use Modules\Essentials\Entities\EssentialsMessage;
use Modules\Essentials\Entities\KnowledgeBase;
use Modules\Essentials\Entities\Reminder;
use Modules\Essentials\Entities\ToDo;

class ProjectXEssentialsUtil
{
    protected Util $commonUtil;

    protected ModuleUtil $moduleUtil;

    public function __construct(Util $commonUtil, ModuleUtil $moduleUtil)
    {
        $this->commonUtil = $commonUtil;
        $this->moduleUtil = $moduleUtil;
    }

    public function businessId(Request $request): int
    {
        return (int) $request->session()->get('user.business_id');
    }

    public function authUserId(): int
    {
        return (int) auth()->id();
    }

    public function isAdmin(int $business_id): bool
    {
        return (bool) $this->moduleUtil->is_admin(auth()->user(), $business_id);
    }

    public function ensureEssentialsAccess(int $business_id): void
    {
        if (! $this->hasEssentialsSubscription($business_id)) {
            abort(403, __('messages.unauthorized_action'));
        }
    }

    /**
     * @param  array<int, string>  $permissions
     */
    public function ensurePermission(int $business_id, array $permissions): void
    {
        $this->ensureEssentialsAccess($business_id);

        if (auth()->user()->can('superadmin')) {
            return;
        }

        foreach ($permissions as $permission) {
            if (auth()->user()->can($permission)) {
                return;
            }
        }

        abort(403, __('messages.unauthorized_action'));
    }

    public function ensureSettingsPermission(int $business_id): void
    {
        $this->ensureEssentialsAccess($business_id);

        if (auth()->user()->can('superadmin') || auth()->user()->can('edit_essentials_settings')) {
            return;
        }

        abort(403, __('messages.unauthorized_action'));
    }

    public function hasEssentialsSubscription(int $business_id): bool
    {
        if (! auth()->check()) {
            return false;
        }

        return auth()->user()->can('superadmin')
            || (bool) $this->moduleUtil->hasThePermissionInSubscription($business_id, 'essentials_module');
    }

    public function hasEssentialsPermission(int $business_id, string $permission): bool
    {
        if (! $this->hasEssentialsSubscription($business_id)) {
            return false;
        }

        return auth()->user()->can('superadmin') || auth()->user()->can($permission);
    }

    public function isEssentialsAvailableForBusiness(int $business_id): bool
    {
        if (! $this->moduleUtil->isModuleInstalled('Essentials')) {
            return false;
        }

        return $this->hasEssentialsSubscription($business_id)
            && class_exists(ToDo::class)
            && class_exists(Document::class)
            && class_exists(Reminder::class)
            && class_exists(EssentialsMessage::class)
            && class_exists(KnowledgeBase::class);
    }

    public function getUserRoleId(int $user_id): ?int
    {
        $role = User::where('id', $user_id)->first()?->roles()->first();

        return ! empty($role) ? (int) $role->id : null;
    }

    public function getTodoQueryForUser(int $business_id, int $auth_id): Builder
    {
        $query = ToDo::where('business_id', $business_id);

        if (! $this->isAdmin($business_id)) {
            $query->where(function (Builder $builder) use ($auth_id) {
                $builder->where('created_by', $auth_id)
                    ->orWhereHas('users', function (Builder $q) use ($auth_id) {
                        $q->where('user_id', $auth_id);
                    });
            });
        }

        return $query;
    }

    public function canMutateTodo(ToDo $todo, int $auth_id, int $business_id): bool
    {
        if ($this->isAdmin($business_id)) {
            return true;
        }

        if ((int) $todo->created_by === $auth_id) {
            return true;
        }

        return $todo->users()->where('user_id', $auth_id)->exists();
    }

    public function generateTodoReference(array $settings = []): string
    {
        $ref_count = $this->commonUtil->setAndGetReferenceCount('essentials_todos');
        $prefix = ! empty($settings['essentials_todos_prefix']) ? (string) $settings['essentials_todos_prefix'] : '';

        return $this->commonUtil->generateReferenceNumber('essentials_todos', $ref_count, null, $prefix);
    }

    public function normalizeTodoInput(array $input): array
    {
        $input['date'] = $this->normalizeDateTimeForStorage((string) $input['date']);
        $input['end_date'] = ! empty($input['end_date'])
            ? $this->normalizeDateTimeForStorage((string) $input['end_date'])
            : null;
        $input['status'] = ! empty($input['status']) ? (string) $input['status'] : 'new';

        return $input;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, array<string, mixed>>
     */
    public function reminderEventsForProjectX(array $data, string $show_route, string $index_route): array
    {
        $events = Reminder::getReminders($data);

        return array_map(function (array $event) use ($show_route, $index_route) {
            $event['url'] = route($show_route, ['reminder' => (int) $event['id']]);
            $event['event_url'] = route($index_route);

            return $event;
        }, $events);
    }

    public function normalizeReminderInput(array $input): array
    {
        return [
            'date' => $this->normalizeDateForStorage((string) $input['date']),
            'time' => $this->normalizeTimeForStorage((string) $input['time']),
            'end_time' => ! empty($input['end_time']) ? $this->normalizeTimeForStorage((string) $input['end_time']) : null,
            'name' => (string) $input['name'],
            'repeat' => (string) $input['repeat'],
        ];
    }

    public function formatReminderTime(string $time): string
    {
        return $this->commonUtil->format_time($time);
    }

    public function formatDate(string $date, bool $with_time = false): ?string
    {
        return $this->commonUtil->format_date($date, $with_time);
    }

    /**
     * @return array<int, mixed>|string
     */
    public function permittedLocations()
    {
        return auth()->user()->permitted_locations();
    }

    public function getMessagesQuery(int $business_id): Builder
    {
        $query = EssentialsMessage::where('business_id', $business_id)
            ->with(['sender'])
            ->orderBy('created_at', 'ASC');

        $permitted_locations = $this->permittedLocations();
        if ($permitted_locations !== 'all') {
            $query->where(function (Builder $builder) use ($permitted_locations) {
                $builder->whereIn('location_id', $permitted_locations)
                    ->orWhereNull('location_id');
            });
        }

        return $query;
    }

    public function getBusinessLocations(int $business_id): array
    {
        $locations = BusinessLocation::forDropdown($business_id);

        if ($locations instanceof \Illuminate\Support\Collection) {
            return $locations->all();
        }

        return is_array($locations) ? $locations : [];
    }

    public function shouldSendDatabaseNotification(?EssentialsMessage $last_message): bool
    {
        return empty($last_message) || $last_message->created_at->diffInMinutes(Carbon::now()) > 10;
    }

    public function getLastMessageForLocation(int $business_id, ?int $location_id): ?EssentialsMessage
    {
        return EssentialsMessage::where('business_id', $business_id)
            ->where(function (Builder $query) use ($location_id) {
                if (empty($location_id)) {
                    $query->whereNull('location_id');
                } else {
                    $query->where('location_id', $location_id)
                        ->orWhereNull('location_id');
                }
            })
            ->orderByDesc('created_at')
            ->first();
    }

    public function normalizeMessage(string $message): string
    {
        return nl2br($message);
    }

    public function knowledgeBaseTree(int $business_id, int $user_id)
    {
        return KnowledgeBase::where('business_id', $business_id)
            ->where('kb_type', 'knowledge_base')
            ->whereNull('parent_id')
            ->with(['children', 'children.children'])
            ->where(function (Builder $query) use ($user_id) {
                $query->whereHas('users', function (Builder $q) use ($user_id) {
                    $q->where('user_id', $user_id);
                })->orWhere('created_by', $user_id)
                    ->orWhere('share_with', 'public');
            })
            ->get();
    }

    public function findBusinessDocument(int $business_id, int $id): Document
    {
        return Document::where('business_id', $business_id)->findOrFail($id);
    }

    public function isDocumentSharedWithUser(int $document_id, int $user_id, ?int $role_id): bool
    {
        return DocumentShare::where('document_id', $document_id)
            ->where(function (Builder $query) use ($user_id, $role_id) {
                $query->where(function (Builder $q) use ($user_id) {
                    $q->where('value_type', 'user')
                        ->where('value', $user_id);
                });

                if (! empty($role_id)) {
                    $query->orWhere(function (Builder $q) use ($role_id) {
                        $q->where('value_type', 'role')
                            ->where('value', $role_id);
                    });
                }
            })
            ->exists();
    }

    public function deleteMediaDocument(Media $media): void
    {
        $media_path = public_path('uploads/media/' . $media->file_name);
        if (file_exists($media_path)) {
            @unlink($media_path);
        }
        $media->delete();
    }

    /**
     * @return array<string, mixed>
     */
    public function essentialsSettings(int $business_id): array
    {
        $business = Business::where('id', $business_id)->firstOrFail();
        $settings = ! empty($business->essentials_settings) ? json_decode((string) $business->essentials_settings, true) : [];

        return is_array($settings) ? $settings : [];
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function updateEssentialsSettings(int $business_id, array $input, Request $request): void
    {
        $business = Business::where('id', $business_id)->firstOrFail();
        $business->essentials_settings = json_encode($input);
        $business->save();

        $request->session()->put('business', $business);
    }

    protected function normalizeDateTimeForStorage(string $value): ?string
    {
        if ($value === '') {
            return null;
        }

        try {
            return $this->commonUtil->uf_date($value, true);
        } catch (\Throwable $exception) {
            return Carbon::parse($value)->format('Y-m-d H:i:s');
        }
    }

    protected function normalizeDateForStorage(string $value): ?string
    {
        if ($value === '') {
            return null;
        }

        try {
            return $this->commonUtil->uf_date($value);
        } catch (\Throwable $exception) {
            return Carbon::parse($value)->format('Y-m-d');
        }
    }

    protected function normalizeTimeForStorage(string $value): ?string
    {
        if ($value === '') {
            return null;
        }

        try {
            return $this->commonUtil->uf_time($value);
        } catch (\Throwable $exception) {
            return Carbon::parse($value)->format('H:i');
        }
    }
}
