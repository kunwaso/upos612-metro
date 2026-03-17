<?php

namespace Modules\Projectauto\Utils;

use App\Business;
use App\BusinessLocation;
use App\Contact;
use App\Product;
use App\Transaction;
use App\User;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Projectauto\Entities\ProjectautoAuditLog;
use Modules\Projectauto\Entities\ProjectautoPendingTask;
use Modules\Projectauto\Entities\ProjectautoRule;
use Modules\Projectauto\Exceptions\IdempotencyConflictException;
use Modules\Projectauto\Exceptions\TaskExecutionException;

class ProjectautoUtil
{
    protected TransactionUtil $transactionUtil;

    protected ProductUtil $productUtil;

    protected ProjectautoPayloadValidator $payloadValidator;

    public function __construct(
        TransactionUtil $transactionUtil,
        ProductUtil $productUtil,
        ProjectautoPayloadValidator $payloadValidator
    ) {
        $this->transactionUtil = $transactionUtil;
        $this->productUtil = $productUtil;
        $this->payloadValidator = $payloadValidator;
    }

    public function getTaskForBusinessOrFail(int $businessId, int $taskId): ProjectautoPendingTask
    {
        return ProjectautoPendingTask::forBusiness($businessId)->findOrFail($taskId);
    }

    public function createTaskFromApiRequest(
        int $businessId,
        string $type,
        array $payload,
        ?string $notes,
        ?string $idempotencyKey,
        ?int $actorId
    ): ProjectautoPendingTask {
        $payload = $this->payloadValidator->validateTaskPayload($type, $payload);

        return $this->createPendingTask($businessId, $type, $payload, $actorId, [
            'notes' => $notes,
            'idempotency_key' => $idempotencyKey,
        ]);
    }

    public function createPendingTask(
        int $businessId,
        string $type,
        array $payload,
        ?int $actorId,
        array $options = []
    ): ProjectautoPendingTask {
        if (! in_array($type, ProjectautoPendingTask::taskTypes(), true)) {
            throw new \InvalidArgumentException("Unsupported task type: {$type}");
        }

        $idempotencyKey = $this->sanitizeIdempotencyKey($options['idempotency_key'] ?? null);
        if (! empty($idempotencyKey) && $this->hasExistingIdempotencyKey($businessId, $idempotencyKey)) {
            throw new IdempotencyConflictException(__('projectauto::lang.idempotency_conflict'));
        }

        $expiresAt = $options['expires_at'] ?? null;
        if (empty($expiresAt)) {
            $hours = (int) config('projectauto.task.default_expiry_hours', 72);
            $expiresAt = Carbon::now()->addHours(max(1, $hours));
        }

        $task = ProjectautoPendingTask::create([
            'business_id' => $businessId,
            'rule_id' => $options['rule_id'] ?? null,
            'task_type' => $type,
            'status' => ProjectautoPendingTask::STATUS_PENDING,
            'payload' => $payload,
            'notes' => $options['notes'] ?? null,
            'idempotency_key' => $idempotencyKey,
            'source_transaction_id' => $options['source_transaction_id'] ?? null,
            'created_by' => $actorId,
            'expires_at' => $expiresAt,
            'attempt_count' => 0,
        ]);

        $this->audit(
            $businessId,
            'task_created',
            [
                'pending_task_id' => $task->id,
                'rule_id' => $task->rule_id,
                'actor_id' => $actorId,
                'status_after' => $task->status,
                'meta' => [
                    'task_type' => $task->task_type,
                    'source_transaction_id' => $task->source_transaction_id,
                ],
            ]
        );

        return $task;
    }

    public function rejectTask(ProjectautoPendingTask $task, int $actorId, ?string $rejectionNotes): ProjectautoPendingTask
    {
        if ($task->status !== ProjectautoPendingTask::STATUS_PENDING) {
            throw new \RuntimeException(__('projectauto::lang.task_is_not_pending'));
        }

        $statusBefore = $task->status;
        $task->status = ProjectautoPendingTask::STATUS_REJECTED;
        $task->rejected_by = $actorId;
        $task->rejected_at = Carbon::now();
        $task->rejection_notes = $rejectionNotes;
        $task->save();

        $this->audit(
            (int) $task->business_id,
            'task_rejected',
            [
                'pending_task_id' => $task->id,
                'rule_id' => $task->rule_id,
                'actor_id' => $actorId,
                'status_before' => $statusBefore,
                'status_after' => $task->status,
                'notes' => $rejectionNotes,
            ]
        );

        return $task->fresh();
    }

    public function acceptTask(
        ProjectautoPendingTask $task,
        User $actor,
        ?array $overridePayload = null,
        ?string $notes = null
    ): ProjectautoPendingTask {
        if ((int) $task->business_id !== (int) $actor->business_id) {
            throw new \RuntimeException(__('projectauto::lang.cross_tenant_forbidden'));
        }

        if ($task->status !== ProjectautoPendingTask::STATUS_PENDING) {
            throw new \RuntimeException(__('projectauto::lang.task_is_not_pending'));
        }

        $payload = $overridePayload ?? (array) $task->payload;
        $payload = $this->payloadValidator->validateTaskPayload($task->task_type, $payload);

        if (! empty($notes)) {
            $task->notes = $notes;
        }
        $task->payload = $payload;
        $task->save();

        return $this->executeTask($task, $payload, $actor);
    }

    public function executeTask(ProjectautoPendingTask $task, array $payload, User $actor): ProjectautoPendingTask
    {
        $this->assertPermission($actor, ['projectauto.tasks.approve']);

        if (! empty($task->idempotency_key) && $this->hasApprovedTaskWithIdempotency(
            (int) $task->business_id,
            $task->idempotency_key,
            (int) $task->id
        )) {
            $this->markTaskFailed($task, (int) $actor->id, __('projectauto::lang.idempotency_conflict'));
            throw new IdempotencyConflictException(__('projectauto::lang.idempotency_conflict'));
        }

        try {
            DB::beginTransaction();

            /** @var ProjectautoPendingTask $taskForUpdate */
            $taskForUpdate = ProjectautoPendingTask::where('id', $task->id)->lockForUpdate()->firstOrFail();
            if ($taskForUpdate->status !== ProjectautoPendingTask::STATUS_PENDING) {
                throw new \RuntimeException(__('projectauto::lang.task_is_not_pending'));
            }

            $taskForUpdate->attempt_count = (int) $taskForUpdate->attempt_count + 1;
            $taskForUpdate->payload = $payload;
            $taskForUpdate->save();

            $result = $this->dispatchTaskExecution($taskForUpdate, $payload, $actor);

            $statusBefore = $taskForUpdate->status;
            $taskForUpdate->status = ProjectautoPendingTask::STATUS_APPROVED;
            $taskForUpdate->approved_by = (int) $actor->id;
            $taskForUpdate->approved_at = Carbon::now();
            $taskForUpdate->executed_at = Carbon::now();
            $taskForUpdate->last_error = null;
            $taskForUpdate->result_model_type = $result['model_type'] ?? null;
            $taskForUpdate->result_model_id = $result['model_id'] ?? null;
            $taskForUpdate->save();

            $this->audit(
                (int) $taskForUpdate->business_id,
                'task_approved',
                [
                    'pending_task_id' => $taskForUpdate->id,
                    'rule_id' => $taskForUpdate->rule_id,
                    'actor_id' => (int) $actor->id,
                    'status_before' => $statusBefore,
                    'status_after' => $taskForUpdate->status,
                    'meta' => [
                        'result_model_type' => $taskForUpdate->result_model_type,
                        'result_model_id' => $taskForUpdate->result_model_id,
                        'attempt_count' => $taskForUpdate->attempt_count,
                    ],
                ]
            );

            DB::commit();

            return $taskForUpdate->fresh();
        } catch (\Throwable $exception) {
            DB::rollBack();
            $this->markTaskFailed($task, (int) $actor->id, $exception->getMessage());

            throw new TaskExecutionException($exception->getMessage(), 0, $exception);
        }
    }

    public function createTasksFromTrigger(int $businessId, string $triggerType, array $context): array
    {
        $createdTasks = [];
        $transaction = $context['transaction'] ?? null;
        $sourceTransactionId = ! empty($transaction) ? (int) $transaction->id : null;

        $rules = ProjectautoRule::forBusiness($businessId)
            ->active()
            ->where('trigger_type', $triggerType)
            ->orderBy('priority', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        foreach ($rules as $rule) {
            if (! $this->ruleMatches($rule, $context)) {
                continue;
            }

            if (! empty($sourceTransactionId) && $this->hasExistingTaskForRuleSource(
                $businessId,
                (int) $rule->id,
                $rule->task_type,
                $sourceTransactionId
            )) {
                $this->audit($businessId, 'rule_skipped_recursion_guard', [
                    'rule_id' => (int) $rule->id,
                    'meta' => ['source_transaction_id' => $sourceTransactionId],
                ]);
                continue;
            }

            $payload = $this->renderPayloadTemplate((array) $rule->payload_template, $context);

            try {
                $task = $this->createPendingTask($businessId, $rule->task_type, $payload, null, [
                    'rule_id' => (int) $rule->id,
                    'source_transaction_id' => $sourceTransactionId,
                    'notes' => __('projectauto::lang.generated_by_rule', ['id' => $rule->id]),
                ]);

                $createdTasks[] = $task;

                $this->audit($businessId, 'rule_matched_task_created', [
                    'pending_task_id' => (int) $task->id,
                    'rule_id' => (int) $rule->id,
                    'status_after' => $task->status,
                ]);
            } catch (IdempotencyConflictException $exception) {
                $this->audit($businessId, 'rule_matched_idempotency_conflict', [
                    'rule_id' => (int) $rule->id,
                    'notes' => $exception->getMessage(),
                ]);
            }

            if ((bool) $rule->stop_on_match) {
                break;
            }
        }

        return $createdTasks;
    }

    public function escalateExpiredTasks(string $action = 'none', int $chunkSize = 100): int
    {
        $processed = 0;
        $action = strtolower(trim($action));

        ProjectautoPendingTask::query()
            ->where('status', ProjectautoPendingTask::STATUS_PENDING)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', Carbon::now())
            ->orderBy('id', 'asc')
            ->chunkById(max(1, $chunkSize), function ($tasks) use (&$processed, $action) {
                foreach ($tasks as $task) {
                    $statusBefore = $task->status;
                    $auditAction = 'task_escalated_no_action';
                    $notes = __('projectauto::lang.task_expired');

                    if ($action === 'reject') {
                        $task->status = ProjectautoPendingTask::STATUS_REJECTED;
                        $task->rejected_at = Carbon::now();
                        $task->rejection_notes = __('projectauto::lang.auto_rejected_after_expiry');
                        $auditAction = 'task_auto_rejected';
                    } elseif ($action === 'fail') {
                        $task->status = ProjectautoPendingTask::STATUS_FAILED;
                        $task->last_error = __('projectauto::lang.failed_after_expiry');
                        $task->executed_at = Carbon::now();
                        $auditAction = 'task_auto_failed';
                    }

                    $task->save();
                    $processed++;

                    $this->audit((int) $task->business_id, $auditAction, [
                        'pending_task_id' => (int) $task->id,
                        'rule_id' => $task->rule_id,
                        'status_before' => $statusBefore,
                        'status_after' => $task->status,
                        'notes' => $notes,
                    ]);
                }
            });

        return $processed;
    }

    protected function dispatchTaskExecution(ProjectautoPendingTask $task, array $payload, User $actor): array
    {
        if ($task->task_type === ProjectautoPendingTask::TASK_TYPE_ADJUST_STOCK) {
            return $this->executeAdjustStockTask((int) $task->business_id, $payload, $actor);
        }

        if ($task->task_type === ProjectautoPendingTask::TASK_TYPE_CREATE_INVOICE) {
            return $this->executeCreateInvoiceTask((int) $task->business_id, $payload, $actor);
        }

        if ($task->task_type === ProjectautoPendingTask::TASK_TYPE_ADD_PRODUCT) {
            return $this->executeAddProductTask((int) $task->business_id, $payload, $actor);
        }

        throw new \RuntimeException(__('projectauto::lang.unsupported_task_type'));
    }

    protected function executeAdjustStockTask(int $businessId, array $payload, User $actor): array
    {
        $this->assertPermission($actor, ['stock_adjustment.create']);

        $locationId = (int) $payload['location_id'];
        $this->assertLocationPermission($actor, $businessId, $locationId);

        $transactionData = [
            'business_id' => $businessId,
            'type' => 'stock_adjustment',
            'status' => 'received',
            'location_id' => $locationId,
            'created_by' => (int) $actor->id,
            'adjustment_type' => $payload['adjustment_type'] ?? 'normal',
            'additional_notes' => $payload['additional_notes'] ?? null,
            'transaction_date' => $this->normalizeDateTime($payload['transaction_date'] ?? null),
            'total_amount_recovered' => isset($payload['total_amount_recovered']) ? (float) $payload['total_amount_recovered'] : 0,
            'final_total' => 0,
            'payment_status' => 'paid',
        ];

        $refCount = $this->productUtil->setAndGetReferenceCount('stock_adjustment', $businessId);
        $transactionData['ref_no'] = ! empty($payload['ref_no'])
            ? (string) $payload['ref_no']
            : $this->productUtil->generateReferenceNumber('stock_adjustment', $refCount, $businessId);

        $lineData = [];
        foreach ((array) $payload['products'] as $product) {
            $quantity = (float) $product['quantity'];
            $unitPrice = (float) $product['unit_price'];

            $lineData[] = [
                'product_id' => (int) $product['product_id'],
                'variation_id' => (int) $product['variation_id'],
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'lot_no_line_id' => $product['lot_no_line_id'] ?? null,
            ];

            $this->productUtil->decreaseProductQuantity(
                (int) $product['product_id'],
                (int) $product['variation_id'],
                $locationId,
                $quantity
            );
        }

        $stockAdjustment = Transaction::create($transactionData);
        $stockAdjustment->stock_adjustment_lines()->createMany($lineData);

        $business = Business::findOrFail($businessId);
        $this->transactionUtil->mapPurchaseSell([
            'id' => $businessId,
            'accounting_method' => $business->accounting_method,
            'location_id' => $locationId,
        ], $stockAdjustment->stock_adjustment_lines, 'stock_adjustment');

        $this->transactionUtil->activityLog($stockAdjustment, 'added', null, [], false);

        return [
            'model_type' => Transaction::class,
            'model_id' => (int) $stockAdjustment->id,
        ];
    }

    protected function executeCreateInvoiceTask(int $businessId, array $payload, User $actor): array
    {
        $this->assertPermission($actor, ['sell.create', 'direct_sell.access']);

        $locationId = (int) $payload['location_id'];
        $this->assertLocationPermission($actor, $businessId, $locationId);

        $contact = Contact::where('business_id', $businessId)->findOrFail((int) $payload['contact_id']);

        $discount = [
            'discount_type' => $payload['discount_type'] ?? 'fixed',
            'discount_amount' => isset($payload['discount_amount']) ? (float) $payload['discount_amount'] : 0,
        ];

        $products = [];
        foreach ((array) $payload['products'] as $product) {
            $products[] = [
                'product_id' => (int) $product['product_id'],
                'variation_id' => (int) $product['variation_id'],
                'quantity' => (float) $product['quantity'],
                'unit_price_inc_tax' => (float) $product['unit_price_inc_tax'],
                'unit_price' => isset($product['unit_price']) ? (float) $product['unit_price'] : (float) $product['unit_price_inc_tax'],
                'item_tax' => isset($product['item_tax']) ? (float) $product['item_tax'] : 0,
                'tax_id' => $product['tax_id'] ?? null,
                'line_discount_type' => $product['line_discount_type'] ?? null,
                'line_discount_amount' => isset($product['line_discount_amount']) ? (float) $product['line_discount_amount'] : 0,
                'lot_no_line_id' => $product['lot_no_line_id'] ?? null,
            ];
        }

        $invoiceTotal = $this->productUtil->calculateInvoiceTotal($products, $payload['tax_id'] ?? null, $discount, false);
        if (empty($invoiceTotal)) {
            throw new \RuntimeException(__('projectauto::lang.invalid_invoice_payload'));
        }

        $input = [
            'location_id' => $locationId,
            'status' => $payload['status'] ?? 'final',
            'contact_id' => (int) $contact->id,
            'customer_group_id' => null,
            'transaction_date' => $this->normalizeDateTime($payload['transaction_date'] ?? null),
            'tax_rate_id' => $payload['tax_id'] ?? null,
            'discount_type' => $discount['discount_type'],
            'discount_amount' => $discount['discount_amount'],
            'final_total' => $invoiceTotal['final_total'],
            'is_direct_sale' => 1,
            'invoice_no' => $payload['invoice_no'] ?? null,
            'sale_note' => $payload['sale_note'] ?? null,
            'staff_note' => $payload['staff_note'] ?? null,
        ];

        $invoice = $this->transactionUtil->createSellTransaction($businessId, $input, $invoiceTotal, (int) $actor->id, false);
        $this->transactionUtil->createOrUpdateSellLines($invoice, $products, $locationId, false, null, [], false);

        if (($input['status'] ?? 'final') === 'final') {
            $business = Business::findOrFail($businessId);
            $this->transactionUtil->mapPurchaseSell([
                'id' => $businessId,
                'accounting_method' => $business->accounting_method,
                'location_id' => $locationId,
            ], $invoice->sell_lines, 'purchase');
        }

        $payments = [];
        foreach ((array) ($payload['payments'] ?? []) as $payment) {
            $payments[] = [
                'amount' => (float) $payment['amount'],
                'method' => (string) $payment['method'],
                'paid_on' => ! empty($payment['paid_on']) ? $this->normalizeDateTime($payment['paid_on']) : Carbon::now()->toDateTimeString(),
                'note' => $payment['note'] ?? null,
                'account_id' => $payment['account_id'] ?? null,
            ];
        }

        if (! empty($payments)) {
            $this->transactionUtil->createOrUpdatePaymentLines($invoice, $payments, $businessId, (int) $actor->id, false);
        }

        $this->transactionUtil->updatePaymentStatus((int) $invoice->id, (float) $invoice->final_total);
        $this->transactionUtil->activityLog($invoice, 'added', null, [], false);

        return [
            'model_type' => Transaction::class,
            'model_id' => (int) $invoice->id,
        ];
    }

    protected function executeAddProductTask(int $businessId, array $payload, User $actor): array
    {
        $this->assertPermission($actor, ['product.create']);

        $product = Product::create([
            'name' => (string) $payload['name'],
            'business_id' => $businessId,
            'created_by' => (int) $actor->id,
            'unit_id' => (int) $payload['unit_id'],
            'brand_id' => $payload['brand_id'] ?? null,
            'category_id' => $payload['category_id'] ?? null,
            'sub_category_id' => $payload['sub_category_id'] ?? null,
            'tax' => $payload['tax'] ?? null,
            'type' => $payload['type'] ?? 'single',
            'barcode_type' => $payload['barcode_type'] ?? 'C128',
            'sku' => ! empty(trim((string) ($payload['sku'] ?? ''))) ? trim((string) $payload['sku']) : ' ',
            'alert_quantity' => isset($payload['alert_quantity']) ? (float) $payload['alert_quantity'] : null,
            'tax_type' => $payload['tax_type'] ?? 'exclusive',
            'product_description' => $payload['product_description'] ?? null,
            'enable_stock' => ! empty($payload['enable_stock']) ? 1 : 0,
            'not_for_selling' => ! empty($payload['not_for_selling']) ? 1 : 0,
        ]);

        if ($product->sku === ' ') {
            $skuPrefix = (string) Business::where('id', $businessId)->value('sku_prefix');
            $product->sku = $skuPrefix.str_pad((string) $product->id, 4, '0', STR_PAD_LEFT);
            $product->save();
        }

        $productLocations = (array) ($payload['product_locations'] ?? []);
        if (! empty($productLocations)) {
            $allowedLocations = BusinessLocation::where('business_id', $businessId)
                ->whereIn('id', $productLocations)
                ->pluck('id')
                ->toArray();

            if (! empty($allowedLocations)) {
                $product->product_locations()->sync($allowedLocations);
            }
        }

        $this->productUtil->createSingleProductVariation(
            (int) $product->id,
            (string) $product->sku,
            (float) $payload['single_dpp'],
            (float) $payload['single_dpp_inc_tax'],
            isset($payload['profit_percent']) ? (float) $payload['profit_percent'] : 0,
            (float) $payload['single_dsp'],
            (float) $payload['single_dsp_inc_tax']
        );

        return [
            'model_type' => Product::class,
            'model_id' => (int) $product->id,
        ];
    }

    protected function assertPermission(User $actor, array $permissions): void
    {
        if ($actor->hasAnyPermission($permissions)) {
            return;
        }

        throw new \RuntimeException(__('projectauto::lang.unauthorized_action'));
    }

    protected function assertLocationPermission(User $actor, int $businessId, int $locationId): void
    {
        $location = BusinessLocation::where('business_id', $businessId)->findOrFail($locationId);
        if (empty($location)) {
            throw new \RuntimeException(__('projectauto::lang.invalid_location'));
        }

        $permittedLocations = $actor->permitted_locations($businessId);
        if ($permittedLocations !== 'all' && ! in_array($locationId, $permittedLocations, true)) {
            throw new \RuntimeException(__('projectauto::lang.location_not_permitted'));
        }
    }

    protected function hasExistingIdempotencyKey(int $businessId, string $key): bool
    {
        return ProjectautoPendingTask::forBusiness($businessId)
            ->where('idempotency_key', $key)
            ->exists();
    }

    protected function hasApprovedTaskWithIdempotency(int $businessId, string $key, int $excludeTaskId): bool
    {
        return ProjectautoPendingTask::forBusiness($businessId)
            ->where('idempotency_key', $key)
            ->where('status', ProjectautoPendingTask::STATUS_APPROVED)
            ->where('id', '!=', $excludeTaskId)
            ->exists();
    }

    protected function hasExistingTaskForRuleSource(int $businessId, int $ruleId, string $taskType, int $sourceTransactionId): bool
    {
        return ProjectautoPendingTask::forBusiness($businessId)
            ->where('rule_id', $ruleId)
            ->where('task_type', $taskType)
            ->where('source_transaction_id', $sourceTransactionId)
            ->whereIn('status', [
                ProjectautoPendingTask::STATUS_PENDING,
                ProjectautoPendingTask::STATUS_APPROVED,
            ])
            ->exists();
    }

    protected function sanitizeIdempotencyKey(?string $key): ?string
    {
        if ($key === null) {
            return null;
        }

        $key = trim($key);

        return $key !== '' ? $key : null;
    }

    protected function normalizeDateTime($value): string
    {
        if (empty($value)) {
            return Carbon::now()->toDateTimeString();
        }

        return Carbon::parse($value)->toDateTimeString();
    }

    protected function markTaskFailed(ProjectautoPendingTask $task, ?int $actorId, string $message): void
    {
        /** @var ProjectautoPendingTask|null $freshTask */
        $freshTask = ProjectautoPendingTask::find($task->id);
        if (empty($freshTask)) {
            return;
        }

        $statusBefore = $freshTask->status;
        if ($freshTask->status === ProjectautoPendingTask::STATUS_APPROVED || $freshTask->status === ProjectautoPendingTask::STATUS_REJECTED) {
            return;
        }

        $freshTask->status = ProjectautoPendingTask::STATUS_FAILED;
        $freshTask->last_error = $message;
        $freshTask->executed_at = Carbon::now();
        $freshTask->save();

        $this->audit(
            (int) $freshTask->business_id,
            'task_failed',
            [
                'pending_task_id' => (int) $freshTask->id,
                'rule_id' => $freshTask->rule_id,
                'actor_id' => $actorId,
                'status_before' => $statusBefore,
                'status_after' => $freshTask->status,
                'notes' => $message,
            ]
        );
    }

    protected function ruleMatches(ProjectautoRule $rule, array $context): bool
    {
        $conditions = (array) ($rule->conditions ?? []);
        if (empty($conditions)) {
            return true;
        }

        $flatContext = $this->flattenContext($context);

        if (isset($conditions['items']) && is_array($conditions['items'])) {
            foreach ($conditions['items'] as $condition) {
                if (! $this->matchesConditionItem((array) $condition, $flatContext)) {
                    return false;
                }
            }

            return true;
        }

        foreach ($conditions as $key => $expected) {
            $actual = $flatContext[$key] ?? null;

            if (is_array($expected)) {
                $expectedValues = array_map(static function ($item) {
                    return is_scalar($item) ? (string) $item : json_encode($item);
                }, $expected);

                if (! in_array((string) $actual, $expectedValues, true)) {
                    return false;
                }
            } else {
                if ((string) $actual !== (string) $expected) {
                    return false;
                }
            }
        }

        return true;
    }

    protected function matchesConditionItem(array $condition, array $flatContext): bool
    {
        $field = $condition['field'] ?? null;
        $operator = $condition['operator'] ?? 'equals';
        $value = $condition['value'] ?? null;
        $negate = (bool) ($condition['negate'] ?? false);
        $actual = $field !== null ? ($flatContext[$field] ?? null) : null;

        $matches = false;

        if ($operator === 'equals') {
            $matches = (string) $actual === (string) $value;
        } elseif ($operator === 'not_equals') {
            $matches = (string) $actual !== (string) $value;
        } elseif ($operator === 'greater_than') {
            $matches = is_numeric($actual) && is_numeric($value) && (float) $actual > (float) $value;
        } elseif ($operator === 'less_than') {
            $matches = is_numeric($actual) && is_numeric($value) && (float) $actual < (float) $value;
        } elseif ($operator === 'contains') {
            $matches = is_string($actual) && str_contains($actual, (string) $value);
        } elseif ($operator === 'in') {
            $matches = in_array((string) $actual, array_map('strval', (array) $value), true);
        } elseif ($operator === 'not_in') {
            $matches = ! in_array((string) $actual, array_map('strval', (array) $value), true);
        }

        return $negate ? ! $matches : $matches;
    }

    protected function flattenContext(array $context): array
    {
        $transaction = $context['transaction'] ?? null;

        $flat = [
            'old_status' => $context['old_status'] ?? null,
            'new_status' => ! empty($transaction) ? $transaction->status : null,
            'payment_status' => ! empty($transaction) ? $transaction->payment_status : null,
            'transaction_type' => ! empty($transaction) ? $transaction->type : null,
            'location_id' => ! empty($transaction) ? $transaction->location_id : null,
            'contact_id' => ! empty($transaction) ? $transaction->contact_id : null,
            'source_transaction_id' => ! empty($transaction) ? $transaction->id : null,
        ];

        if (! empty($transaction)) {
            $flat['transaction.status'] = $transaction->status;
            $flat['transaction.payment_status'] = $transaction->payment_status;
            $flat['transaction.type'] = $transaction->type;
            $flat['transaction.location_id'] = $transaction->location_id;
            $flat['transaction.contact_id'] = $transaction->contact_id;
            $flat['transaction.id'] = $transaction->id;
        }

        return $flat;
    }

    protected function renderPayloadTemplate(array $template, array $context): array
    {
        if (empty($template)) {
            return [];
        }

        $flatContext = $this->flattenContext($context);

        $render = function ($value) use (&$render, $flatContext, $context) {
            if (is_array($value)) {
                $rendered = [];
                foreach ($value as $k => $v) {
                    $rendered[$k] = $render($v);
                }

                return $rendered;
            }

            if (is_string($value) && preg_match('/^\{\{\s*(.+?)\s*\}\}$/', $value, $matches)) {
                $lookup = trim($matches[1]);
                if (array_key_exists($lookup, $flatContext)) {
                    return $flatContext[$lookup];
                }

                return data_get($context, $lookup, null);
            }

            return $value;
        };

        return $render($template);
    }

    protected function audit(int $businessId, string $action, array $data = []): void
    {
        ProjectautoAuditLog::create([
            'business_id' => $businessId,
            'pending_task_id' => $data['pending_task_id'] ?? null,
            'rule_id' => $data['rule_id'] ?? null,
            'actor_id' => $data['actor_id'] ?? null,
            'action' => $action,
            'status_before' => $data['status_before'] ?? null,
            'status_after' => $data['status_after'] ?? null,
            'notes' => $data['notes'] ?? null,
            'meta' => $data['meta'] ?? null,
        ]);
    }
}
