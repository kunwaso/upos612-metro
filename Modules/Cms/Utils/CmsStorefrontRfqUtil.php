<?php

namespace Modules\Cms\Utils;

use App\Business;
use App\User;
use App\Utils\Util;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Modules\Cms\Entities\CmsQuoteRequest;

class CmsStorefrontRfqUtil
{
    public function __construct(protected Util $util)
    {
    }

    /**
     * Persist RFQ and create an Essentials To Do for staff (no admin email).
     *
     * @param  array<string, mixed>  $validated
     */
    public function createRfqAndTodo(
        int $businessId,
        int $productId,
        array $validated,
        string $productName
    ): CmsQuoteRequest {
        $rfq = CmsQuoteRequest::query()->create([
            'business_id' => $businessId,
            'product_id' => $productId,
            'email' => (string) $validated['email'],
            'phone' => (string) $validated['phone'],
            'company' => (string) ($validated['company'] ?? ''),
            'message' => (string) ($validated['message'] ?? ''),
        ]);

        $this->createEssentialsTodoForRfq($businessId, $productId, $productName, $validated);

        return $rfq;
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    protected function createEssentialsTodoForRfq(
        int $businessId,
        int $productId,
        string $productName,
        array $validated
    ): void {
        if (! class_exists(\Modules\Essentials\Entities\ToDo::class)) {
            Log::warning('CMS storefront RFQ: Essentials ToDo model not available; skipping todo.');

            return;
        }

        if (! Schema::hasTable('essentials_to_dos') || ! Schema::hasTable('essentials_todos_users')) {
            Log::warning('CMS storefront RFQ: Essentials todo tables missing; skipping todo.');

            return;
        }

        $assigneeIds = $this->resolveRfqTodoAssigneeIds($businessId);
        if ($assigneeIds === []) {
            Log::warning('CMS storefront RFQ: No user found to assign Essentials todo; skipping.');

            return;
        }

        $business = Business::query()->find($businessId);
        $essentialsSettings = [];
        if ($business !== null && ! empty($business->essentials_settings)) {
            $essentialsSettings = json_decode($business->essentials_settings, true) ?: [];
        }
        $prefix = $essentialsSettings['essentials_todos_prefix'] ?? '';
        $refCount = $this->util->setAndGetReferenceCount('essentials_todos', $businessId);
        $taskRef = $this->util->generateReferenceNumber('essentials_todos', $refCount, null, $prefix);

        $taskTitle = __('cms::lang.storefront_rfq_todo_task', ['product' => $productName]);
        $description = $this->buildTodoDescription($productId, $productName, $validated);

        $createdBy = $assigneeIds[0];

        $todo = \Modules\Essentials\Entities\ToDo::query()->create([
            'business_id' => $businessId,
            'task' => $taskTitle,
            'date' => Carbon::now()->format('Y-m-d'),
            'end_date' => null,
            'task_id' => $taskRef,
            'description' => $description,
            'status' => 'new',
            'estimated_hours' => null,
            'priority' => 'high',
            'created_by' => $createdBy,
        ]);

        $todo->users()->sync($assigneeIds);
    }

    /**
     * @return list<int>
     */
    protected function resolveRfqTodoAssigneeIds(int $businessId): array
    {
        $configured = config('cms.storefront_rfq_todo_user_id');
        if ($configured !== null && $configured !== '') {
            $uid = (int) $configured;
            if ($uid > 0 && User::query()->where('business_id', $businessId)->where('id', $uid)->exists()) {
                return [$uid];
            }
        }

        $business = Business::query()->find($businessId);
        if ($business !== null && ! empty($business->owner_id)) {
            $oid = (int) $business->owner_id;
            if ($oid > 0 && User::query()->where('business_id', $businessId)->where('id', $oid)->exists()) {
                return [$oid];
            }
        }

        $first = User::query()->where('business_id', $businessId)->orderBy('id')->value('id');
        if ($first !== null) {
            return [(int) $first];
        }

        return [];
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    protected function buildTodoDescription(int $productId, string $productName, array $validated): string
    {
        $lines = [
            __('cms::lang.storefront_rfq_todo_product_id', ['id' => $productId]),
            __('cms::lang.storefront_rfq_todo_product', ['name' => $productName]),
            __('cms::lang.storefront_rfq_todo_email', ['email' => (string) $validated['email']]),
            __('cms::lang.storefront_rfq_todo_phone', ['phone' => (string) $validated['phone']]),
            __('cms::lang.storefront_rfq_todo_company', ['company' => (string) ($validated['company'] ?? '')]),
        ];
        $message = trim((string) ($validated['message'] ?? ''));
        if ($message !== '') {
            $lines[] = __('cms::lang.storefront_rfq_todo_message', ['message' => $message]);
        }

        return implode("\n", $lines);
    }
}
