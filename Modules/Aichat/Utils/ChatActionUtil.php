<?php

namespace Modules\Aichat\Utils;

use App\Contact;
use App\Product;
use App\ProductQuote;
use App\Transaction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Aichat\Entities\ChatConversation;
use Modules\Aichat\Entities\ChatPendingAction;

class ChatActionUtil
{
    protected ChatUtil $chatUtil;

    public function __construct(ChatUtil $chatUtil)
    {
        $this->chatUtil = $chatUtil;
    }

    public function getActionCatalog(array $capabilities = []): array
    {
        $modules = $this->moduleActionMap();
        $catalog = [];

        foreach ($modules as $module => $actions) {
            $actionItems = [];
            foreach ($actions as $action => $meta) {
                $capabilityPath = (string) ($meta['capability_path'] ?? '');
                $enabled = $capabilityPath === '' ? true : (bool) data_get($capabilities, $capabilityPath, false);

                if ($module === 'contacts') {
                    $enabled = (bool) data_get($capabilities, 'contacts.customer.' . $action, false)
                        || (bool) data_get($capabilities, 'contacts.supplier.' . $action, false);
                }

                if ($module === 'reports') {
                    $enabled = $action === 'export'
                        ? (bool) data_get($capabilities, 'reports.export', false)
                        : (bool) data_get($capabilities, 'reports.view', false);
                }

                if ($module === 'settings') {
                    $enabled = (bool) data_get($capabilities, 'settings.access', false)
                        || (bool) data_get($capabilities, 'settings.chat_settings', false);
                }

                $actionItems[] = [
                    'action' => $action,
                    'enabled' => $enabled,
                    'requires_confirmation' => (bool) ($meta['mutation'] ?? false),
                ];
            }

            $catalog[] = [
                'module' => $module,
                'enabled' => $this->isActionModuleEnabled($module),
                'actions' => $actionItems,
            ];
        }

        return $catalog;
    }

    public function prepareAction(
        int $business_id,
        int $user_id,
        string $conversation_id,
        array $data,
        string $channel = 'web'
    ): ChatPendingAction {
        if (! $this->isActionsEnabled()) {
            throw new \RuntimeException(__('aichat::lang.chat_action_disabled'));
        }

        $conversation = $this->requireConversationForUser($business_id, $user_id, $conversation_id);

        $module = strtolower(trim((string) ($data['module'] ?? '')));
        $action = strtolower(trim((string) ($data['action'] ?? '')));
        $payload = (array) ($data['payload'] ?? []);
        $channel = strtolower(trim($channel)) ?: 'web';

        $this->assertSupportedAction($module, $action);
        $this->assertModuleEnabled($module);

        $capabilities = $this->chatUtil->resolveChatCapabilities($business_id, $user_id);
        $normalizedPayload = $this->normalizePayloadForModule($module, $action, $payload);

        $this->assertActionAllowed($business_id, $user_id, $module, $action, $normalizedPayload, $capabilities);

        $pendingAction = ChatPendingAction::create([
            'business_id' => $business_id,
            'conversation_id' => (string) $conversation->id,
            'user_id' => $user_id,
            'channel' => in_array($channel, ['web', 'telegram'], true) ? $channel : 'web',
            'module' => $module,
            'action' => $action,
            'status' => ChatPendingAction::STATUS_PENDING,
            'target_type' => $this->resolveTargetType($module),
            'target_id' => $this->resolveTargetIdFromPayload($module, $normalizedPayload),
            'payload' => $normalizedPayload,
            'preview_text' => $this->buildPreviewText($module, $action, $normalizedPayload),
            'expires_at' => $this->resolveExpiryTimestamp(),
        ]);

        $this->chatUtil->audit($business_id, $user_id, 'chat_action_prepared', (string) $conversation->id, null, null, [
            'action_id' => (int) $pendingAction->id,
            'module' => $module,
            'action' => $action,
            'channel' => $pendingAction->channel,
            'expires_at' => optional($pendingAction->expires_at)->toIso8601String(),
        ]);

        return $pendingAction->fresh();
    }

    public function confirmAction(
        int $business_id,
        int $user_id,
        string $conversation_id,
        int $action_id,
        string $channel = 'web',
        ?string $confirmNote = null
    ): ChatPendingAction {
        if (! $this->isActionsEnabled()) {
            throw new \RuntimeException(__('aichat::lang.chat_action_disabled'));
        }

        $this->requireConversationForUser($business_id, $user_id, $conversation_id);

        return DB::transaction(function () use ($business_id, $user_id, $conversation_id, $action_id, $channel, $confirmNote) {
            return $this->confirmActionInTransaction(
                $business_id,
                $user_id,
                $conversation_id,
                $action_id,
                $channel,
                $confirmNote
            );
        });
    }

    public function cancelAction(
        int $business_id,
        int $user_id,
        string $conversation_id,
        int $action_id,
        ?string $reason = null
    ): ChatPendingAction {
        if (! $this->isActionsEnabled()) {
            throw new \RuntimeException(__('aichat::lang.chat_action_disabled'));
        }

        $this->requireConversationForUser($business_id, $user_id, $conversation_id);

        return DB::transaction(function () use ($business_id, $user_id, $conversation_id, $action_id, $reason) {
            return $this->cancelActionInTransaction($business_id, $user_id, $conversation_id, $action_id, $reason);
        });
    }

    public function listPendingActions(int $business_id, int $user_id, string $conversation_id): array
    {
        $this->requireConversationForUser($business_id, $user_id, $conversation_id);

        $this->expireStaleOwnedActions($business_id, $user_id, $conversation_id);

        return ChatPendingAction::forBusiness($business_id)
            ->forUser($user_id)
            ->forConversation($conversation_id)
            ->whereIn('status', [ChatPendingAction::STATUS_PENDING, ChatPendingAction::STATUS_CONFIRMED])
            ->orderByDesc('id')
            ->get()
            ->map(function (ChatPendingAction $action) {
                return $this->serializePendingAction($action);
            })
            ->values()
            ->all();
    }

    public function getPendingActionByIdForUser(int $business_id, int $user_id, string $conversation_id, int $action_id): ChatPendingAction
    {
        return ChatPendingAction::forBusiness($business_id)
            ->forUser($user_id)
            ->forConversation($conversation_id)
            ->where('id', $action_id)
            ->firstOrFail();
    }

    public function getLatestPendingActionForUser(int $business_id, int $user_id, string $conversation_id): ?ChatPendingAction
    {
        return ChatPendingAction::forBusiness($business_id)
            ->forUser($user_id)
            ->forConversation($conversation_id)
            ->whereIn('status', [ChatPendingAction::STATUS_PENDING, ChatPendingAction::STATUS_CONFIRMED])
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->orderByDesc('id')
            ->first();
    }

    public function serializePendingAction(ChatPendingAction $action): array
    {
        return [
            'id' => (int) $action->id,
            'module' => (string) $action->module,
            'action' => (string) $action->action,
            'status' => (string) $action->status,
            'channel' => (string) $action->channel,
            'target_type' => $action->target_type,
            'target_id' => $action->target_id,
            'payload' => (array) ($action->payload ?? []),
            'preview_text' => (string) ($action->preview_text ?? ''),
            'result_payload' => (array) ($action->result_payload ?? []),
            'error_message' => $action->error_message,
            'confirmed_at' => optional($action->confirmed_at)->toDateTimeString(),
            'executed_at' => optional($action->executed_at)->toDateTimeString(),
            'expires_at' => optional($action->expires_at)->toDateTimeString(),
            'created_at' => optional($action->created_at)->toDateTimeString(),
            'updated_at' => optional($action->updated_at)->toDateTimeString(),
        ];
    }

    protected function moduleActionMap(): array
    {
        return [
            'products' => [
                'create' => ['capability_path' => 'products.create', 'mutation' => true],
                'update' => ['capability_path' => 'products.update', 'mutation' => true],
                'delete' => ['capability_path' => 'products.delete', 'mutation' => true],
            ],
            'contacts' => [
                'create' => ['capability_path' => '', 'mutation' => true],
                'update' => ['capability_path' => '', 'mutation' => true],
                'delete' => ['capability_path' => '', 'mutation' => true],
            ],
            'settings' => [
                'update' => ['capability_path' => 'settings.access', 'mutation' => true],
            ],
            'sales' => [
                'create' => ['capability_path' => 'sales.create', 'mutation' => true],
                'update' => ['capability_path' => 'sales.update', 'mutation' => true],
                'delete' => ['capability_path' => 'sales.delete', 'mutation' => true],
            ],
            'quotes' => [
                'create' => ['capability_path' => 'quotes.create', 'mutation' => true],
                'update' => ['capability_path' => 'quotes.update', 'mutation' => true],
                'delete' => ['capability_path' => 'quotes.delete', 'mutation' => true],
            ],
            'purchases' => [
                'create' => ['capability_path' => 'purchases.create', 'mutation' => true],
                'update' => ['capability_path' => 'purchases.update', 'mutation' => true],
                'delete' => ['capability_path' => 'purchases.delete', 'mutation' => true],
            ],
            'reports' => [
                'view' => ['capability_path' => 'reports.view', 'mutation' => false],
                'run' => ['capability_path' => 'reports.view', 'mutation' => false],
                'export' => ['capability_path' => 'reports.export', 'mutation' => false],
            ],
        ];
    }

    protected function confirmActionInTransaction(
        int $business_id,
        int $user_id,
        string $conversation_id,
        int $action_id,
        string $channel,
        ?string $confirmNote
    ): ChatPendingAction {
        $pendingAction = $this->lockOwnedAction($business_id, $user_id, $conversation_id, $action_id);

        if ($pendingAction->status === ChatPendingAction::STATUS_EXECUTED) {
            return $pendingAction;
        }

        if ($pendingAction->isExpired()) {
            $pendingAction->status = ChatPendingAction::STATUS_EXPIRED;
            $pendingAction->save();
            $this->chatUtil->audit($business_id, $user_id, 'chat_action_expired', $conversation_id, null, null, [
                'action_id' => (int) $pendingAction->id,
                'module' => (string) $pendingAction->module,
                'action' => (string) $pendingAction->action,
            ]);

            throw new \RuntimeException(__('aichat::lang.chat_action_expired'));
        }

        if ($pendingAction->status === ChatPendingAction::STATUS_CANCELLED) {
            throw new \RuntimeException(__('aichat::lang.chat_action_already_cancelled'));
        }

        if ($pendingAction->status === ChatPendingAction::STATUS_FAILED) {
            throw new \RuntimeException(__('aichat::lang.chat_action_already_failed'));
        }

        if (! in_array($pendingAction->status, [ChatPendingAction::STATUS_PENDING, ChatPendingAction::STATUS_CONFIRMED], true)) {
            throw new \RuntimeException(__('aichat::lang.chat_action_invalid_status'));
        }

        $module = strtolower((string) $pendingAction->module);
        $action = strtolower((string) $pendingAction->action);
        $payload = (array) ($pendingAction->payload ?? []);

        $this->assertSupportedAction($module, $action);
        $this->assertModuleEnabled($module);

        $capabilities = $this->chatUtil->resolveChatCapabilities($business_id, $user_id);
        $this->assertActionAllowed($business_id, $user_id, $module, $action, $payload, $capabilities);

        if ($pendingAction->status === ChatPendingAction::STATUS_PENDING) {
            $pendingAction->status = ChatPendingAction::STATUS_CONFIRMED;
            $pendingAction->confirmed_at = now();
            $pendingAction->save();

            $this->chatUtil->audit($business_id, $user_id, 'chat_action_confirmed', $conversation_id, null, null, [
                'action_id' => (int) $pendingAction->id,
                'module' => $module,
                'action' => $action,
                'channel' => strtolower(trim($channel)) ?: 'web',
                'confirm_note' => trim((string) $confirmNote) ?: null,
            ]);
        }

        try {
            $resultPayload = $this->executeAction($business_id, $user_id, $module, $action, $payload);
        } catch (\Throwable $exception) {
            $pendingAction->status = ChatPendingAction::STATUS_FAILED;
            $pendingAction->error_message = (string) ($exception->getMessage() ?: __('aichat::lang.chat_action_failed'));
            $pendingAction->save();

            $this->chatUtil->audit($business_id, $user_id, 'chat_action_failed', $conversation_id, null, null, [
                'action_id' => (int) $pendingAction->id,
                'module' => $module,
                'action' => $action,
                'error' => $pendingAction->error_message,
            ]);

            throw $exception;
        }

        $pendingAction->status = ChatPendingAction::STATUS_EXECUTED;
        $pendingAction->executed_at = now();
        $pendingAction->result_payload = $resultPayload;
        $pendingAction->error_message = null;
        $pendingAction->save();

        $this->chatUtil->audit($business_id, $user_id, 'chat_action_executed', $conversation_id, null, null, [
            'action_id' => (int) $pendingAction->id,
            'module' => $module,
            'action' => $action,
            'result' => $resultPayload,
        ]);

        return $pendingAction->fresh();
    }

    protected function cancelActionInTransaction(
        int $business_id,
        int $user_id,
        string $conversation_id,
        int $action_id,
        ?string $reason
    ): ChatPendingAction {
        $pendingAction = $this->lockOwnedAction($business_id, $user_id, $conversation_id, $action_id);

        if ($pendingAction->status === ChatPendingAction::STATUS_EXECUTED) {
            throw new \RuntimeException(__('aichat::lang.chat_action_already_executed'));
        }

        if ($pendingAction->status === ChatPendingAction::STATUS_CANCELLED) {
            return $pendingAction;
        }

        if ($pendingAction->isExpired()) {
            $pendingAction->status = ChatPendingAction::STATUS_EXPIRED;
            $pendingAction->save();

            $this->chatUtil->audit($business_id, $user_id, 'chat_action_expired', $conversation_id, null, null, [
                'action_id' => (int) $pendingAction->id,
                'module' => (string) $pendingAction->module,
                'action' => (string) $pendingAction->action,
            ]);

            throw new \RuntimeException(__('aichat::lang.chat_action_expired'));
        }

        $pendingAction->status = ChatPendingAction::STATUS_CANCELLED;
        $pendingAction->save();

        $this->chatUtil->audit($business_id, $user_id, 'chat_action_cancelled', $conversation_id, null, null, [
            'action_id' => (int) $pendingAction->id,
            'module' => (string) $pendingAction->module,
            'action' => (string) $pendingAction->action,
            'reason' => trim((string) $reason) ?: null,
        ]);

        return $pendingAction->fresh();
    }

    protected function assertSupportedAction(string $module, string $action): void
    {
        $map = $this->moduleActionMap();
        if (! isset($map[$module]) || ! isset($map[$module][$action])) {
            throw new \InvalidArgumentException(__('aichat::lang.chat_action_unsupported'));
        }
    }

    protected function isActionsEnabled(): bool
    {
        return (bool) config('aichat.actions.enabled', false);
    }

    protected function assertModuleEnabled(string $module): void
    {
        if (! $this->isActionModuleEnabled($module)) {
            throw new \RuntimeException(__('aichat::lang.chat_action_module_disabled'));
        }
    }

    protected function isActionModuleEnabled(string $module): bool
    {
        return (bool) config('aichat.actions.modules.' . $module, true);
    }

    protected function requireConversationForUser(int $business_id, int $user_id, string $conversation_id): ChatConversation
    {
        return ChatConversation::forBusiness($business_id)
            ->forUser($user_id)
            ->where('id', $conversation_id)
            ->firstOrFail();
    }

    protected function lockOwnedAction(int $business_id, int $user_id, string $conversation_id, int $action_id): ChatPendingAction
    {
        return ChatPendingAction::forBusiness($business_id)
            ->forUser($user_id)
            ->forConversation($conversation_id)
            ->where('id', $action_id)
            ->lockForUpdate()
            ->firstOrFail();
    }

    protected function expireStaleOwnedActions(int $business_id, int $user_id, string $conversation_id): void
    {
        $staleActions = ChatPendingAction::forBusiness($business_id)
            ->forUser($user_id)
            ->forConversation($conversation_id)
            ->whereIn('status', [ChatPendingAction::STATUS_PENDING, ChatPendingAction::STATUS_CONFIRMED])
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->get();

        foreach ($staleActions as $staleAction) {
            $staleAction->status = ChatPendingAction::STATUS_EXPIRED;
            $staleAction->save();
        }
    }

    protected function resolveExpiryTimestamp(): Carbon
    {
        $ttlMinutes = (int) config('aichat.actions.confirmation_ttl_minutes', 10);

        return now()->addMinutes(max(1, $ttlMinutes));
    }

    protected function normalizePayloadForModule(string $module, string $action, array $payload): array
    {
        if ($module === 'contacts' && $action === 'create') {
            $type = strtolower(trim((string) ($payload['type'] ?? 'customer')));
            if (! in_array($type, ['customer', 'supplier', 'both'], true)) {
                $type = 'customer';
            }
            $payload['type'] = $type;
        }

        if (($module === 'sales' || $module === 'purchases') && $action === 'create') {
            $payload['status'] = strtolower(trim((string) ($payload['status'] ?? ($module === 'sales' ? 'draft' : 'received'))));
            $payload['payment_status'] = strtolower(trim((string) ($payload['payment_status'] ?? 'due')));
        }

        return $payload;
    }

    protected function assertActionAllowed(
        int $business_id,
        int $user_id,
        string $module,
        string $action,
        array $payload,
        array $capabilities
    ): void {
        if ($module === 'contacts') {
            $this->assertContactActionAllowed($business_id, $action, $payload, $capabilities);

            return;
        }

        if ($module === 'settings') {
            $canAccess = (bool) data_get($capabilities, 'settings.access', false)
                || (bool) data_get($capabilities, 'settings.chat_settings', false);
            if (! $canAccess) {
                throw new \RuntimeException(__('aichat::lang.chat_action_forbidden'));
            }

            return;
        }

        if ($module === 'reports') {
            $canRun = $action === 'export'
                ? (bool) data_get($capabilities, 'reports.export', false)
                : (bool) data_get($capabilities, 'reports.view', false);
            if (! $canRun) {
                throw new \RuntimeException(__('aichat::lang.chat_action_forbidden'));
            }

            return;
        }

        $capabilityPath = (string) data_get($this->moduleActionMap(), $module . '.' . $action . '.capability_path', '');
        if ($capabilityPath !== '' && ! (bool) data_get($capabilities, $capabilityPath, false)) {
            throw new \RuntimeException(__('aichat::lang.chat_action_forbidden'));
        }

        if ($module === 'quotes' && in_array($action, ['update', 'delete'], true)) {
            $this->assertQuoteOwnershipIfNeeded($business_id, $user_id, $payload, $capabilities);
        }
    }

    protected function assertContactActionAllowed(
        int $business_id,
        string $action,
        array $payload,
        array $capabilities
    ): void {
        $contact = null;
        if (in_array($action, ['update', 'delete'], true)) {
            $contact = $this->resolveContactTarget($business_id, $payload);
        }

        $contactTypeKey = $this->resolveContactCapabilityType($payload, $contact);
        $typeCapabilities = (array) data_get($capabilities, 'contacts.' . $contactTypeKey, []);

        if ($action === 'create') {
            $createType = strtolower(trim((string) ($payload['type'] ?? 'customer')));
            if ($createType === 'both') {
                $canCreateBoth = (bool) data_get($capabilities, 'contacts.customer.create', false)
                    && (bool) data_get($capabilities, 'contacts.supplier.create', false);
                if (! $canCreateBoth) {
                    throw new \RuntimeException(__('aichat::lang.chat_action_forbidden'));
                }

                return;
            }

            $canCreate = (bool) ($typeCapabilities['create'] ?? false);
            if (! $canCreate) {
                throw new \RuntimeException(__('aichat::lang.chat_action_forbidden'));
            }

            return;
        }

        $contactType = strtolower(trim((string) ($contact ? $contact->type : '')));
        $canMutation = $contactType === 'both'
            ? (bool) data_get($capabilities, 'contacts.customer.' . $action, false)
                || (bool) data_get($capabilities, 'contacts.supplier.' . $action, false)
            : (bool) ($typeCapabilities[$action] ?? false);
        if (! $canMutation) {
            throw new \RuntimeException(__('aichat::lang.chat_action_forbidden'));
        }
    }

    protected function assertQuoteOwnershipIfNeeded(int $business_id, int $user_id, array $payload, array $capabilities): void
    {
        $quoteId = (int) ($payload['id'] ?? $payload['quote_id'] ?? 0);
        if ($quoteId <= 0) {
            throw new \InvalidArgumentException(__('aichat::lang.chat_action_invalid_payload'));
        }

        $quote = ProductQuote::forBusiness($business_id)->where('id', $quoteId)->firstOrFail();
        $canAdminOverride = (bool) data_get($capabilities, 'quotes.admin_override', false);
        if ($canAdminOverride) {
            return;
        }

        $createdBy = (int) ($quote->created_by ?? 0);
        if ($createdBy > 0 && $createdBy !== $user_id) {
            throw new \RuntimeException(__('aichat::lang.chat_action_forbidden_own_scope'));
        }
    }

    protected function executeAction(int $business_id, int $user_id, string $module, string $action, array $payload): array
    {
        if ($module === 'products') {
            return $this->executeProductAction($business_id, $user_id, $action, $payload);
        }

        if ($module === 'contacts') {
            return $this->executeContactAction($business_id, $user_id, $action, $payload);
        }

        if ($module === 'settings') {
            return $this->executeSettingsAction($business_id, $action, $payload);
        }

        if ($module === 'sales') {
            return $this->executeSalesAction($business_id, $user_id, $action, $payload);
        }

        if ($module === 'purchases') {
            return $this->executePurchaseAction($business_id, $user_id, $action, $payload);
        }

        if ($module === 'quotes') {
            return $this->executeQuoteAction($business_id, $user_id, $action, $payload);
        }

        if ($module === 'reports') {
            return $this->executeReportAction($business_id, $user_id, $action, $payload);
        }

        throw new \InvalidArgumentException(__('aichat::lang.chat_action_unsupported'));
    }

    protected function executeProductAction(int $business_id, int $user_id, string $action, array $payload): array
    {
        if ($action === 'create') {
            foreach (['name', 'unit_id'] as $requiredKey) {
                if (trim((string) ($payload[$requiredKey] ?? '')) === '') {
                    throw new \InvalidArgumentException(__('aichat::lang.chat_action_invalid_payload'));
                }
            }

            $product = Product::create([
                'business_id' => $business_id,
                'name' => trim((string) $payload['name']),
                'unit_id' => (int) $payload['unit_id'],
                'type' => (string) ($payload['type'] ?? 'single'),
                'tax_type' => (string) ($payload['tax_type'] ?? 'exclusive'),
                'enable_stock' => ! empty($payload['enable_stock']) ? 1 : 0,
                'alert_quantity' => (float) ($payload['alert_quantity'] ?? 0),
                'sku' => (string) ($payload['sku'] ?? ('AICHAT-' . Str::upper(Str::random(8)))),
                'barcode_type' => (string) ($payload['barcode_type'] ?? 'C128'),
                'created_by' => $user_id,
                'brand_id' => isset($payload['brand_id']) ? (int) $payload['brand_id'] : null,
                'category_id' => isset($payload['category_id']) ? (int) $payload['category_id'] : null,
                'sub_category_id' => isset($payload['sub_category_id']) ? (int) $payload['sub_category_id'] : null,
                'tax' => isset($payload['tax']) ? (int) $payload['tax'] : null,
            ]);

            return [
                'entity' => 'product',
                'entity_id' => (int) $product->id,
                'message' => __('aichat::lang.chat_action_product_created'),
            ];
        }

        $productId = (int) ($payload['id'] ?? $payload['product_id'] ?? 0);
        if ($productId <= 0) {
            throw new \InvalidArgumentException(__('aichat::lang.chat_action_invalid_payload'));
        }

        $product = Product::where('business_id', $business_id)->where('id', $productId)->firstOrFail();

        if ($action === 'update') {
            $allowedFields = [
                'name', 'sku', 'type', 'unit_id', 'brand_id', 'category_id', 'sub_category_id',
                'tax', 'tax_type', 'enable_stock', 'alert_quantity', 'is_inactive', 'not_for_selling',
            ];
            $updatePayload = $this->filterAllowedFields($payload, $allowedFields);
            if (empty($updatePayload)) {
                throw new \InvalidArgumentException(__('aichat::lang.chat_action_invalid_payload'));
            }

            $product->fill($updatePayload);
            $product->save();

            return [
                'entity' => 'product',
                'entity_id' => (int) $product->id,
                'message' => __('aichat::lang.chat_action_product_updated'),
            ];
        }

        if ($action === 'delete') {
            $product->delete();

            return [
                'entity' => 'product',
                'entity_id' => $productId,
                'message' => __('aichat::lang.chat_action_product_deleted'),
            ];
        }

        throw new \InvalidArgumentException(__('aichat::lang.chat_action_unsupported'));
    }

    protected function executeContactAction(int $business_id, int $user_id, string $action, array $payload): array
    {
        if ($action === 'create') {
            foreach (['name', 'mobile'] as $requiredKey) {
                if (trim((string) ($payload[$requiredKey] ?? '')) === '') {
                    throw new \InvalidArgumentException(__('aichat::lang.chat_action_invalid_payload'));
                }
            }

            $type = strtolower(trim((string) ($payload['type'] ?? 'customer')));
            if (! in_array($type, ['customer', 'supplier', 'both'], true)) {
                $type = 'customer';
            }

            $contact = Contact::create([
                'business_id' => $business_id,
                'type' => $type,
                'name' => trim((string) $payload['name']),
                'mobile' => trim((string) $payload['mobile']),
                'supplier_business_name' => trim((string) ($payload['supplier_business_name'] ?? '')) ?: null,
                'city' => trim((string) ($payload['city'] ?? '')) ?: null,
                'state' => trim((string) ($payload['state'] ?? '')) ?: null,
                'country' => trim((string) ($payload['country'] ?? '')) ?: null,
                'landline' => trim((string) ($payload['landline'] ?? '')) ?: null,
                'alternate_number' => trim((string) ($payload['alternate_number'] ?? '')) ?: null,
                'created_by' => $user_id,
                'contact_status' => (string) ($payload['contact_status'] ?? 'active'),
            ]);

            return [
                'entity' => 'contact',
                'entity_id' => (int) $contact->id,
                'message' => __('aichat::lang.chat_action_contact_created'),
            ];
        }

        $contact = $this->resolveContactTarget($business_id, $payload);

        if ($action === 'update') {
            $allowedFields = [
                'type', 'name', 'mobile', 'supplier_business_name', 'tax_number', 'city',
                'state', 'country', 'landmark', 'landline', 'alternate_number',
                'email', 'contact_id', 'contact_status',
            ];
            $updatePayload = $this->filterAllowedFields($payload, $allowedFields);
            if (empty($updatePayload)) {
                throw new \InvalidArgumentException(__('aichat::lang.chat_action_invalid_payload'));
            }

            $contact->fill($updatePayload);
            $contact->save();

            return [
                'entity' => 'contact',
                'entity_id' => (int) $contact->id,
                'message' => __('aichat::lang.chat_action_contact_updated'),
            ];
        }

        if ($action === 'delete') {
            $transactionCount = Transaction::where('business_id', $business_id)
                ->where('contact_id', (int) $contact->id)
                ->count();
            if ($transactionCount > 0 || ! empty($contact->is_default)) {
                throw new \RuntimeException(__('aichat::lang.chat_action_contact_delete_blocked'));
            }

            $contact->delete();

            return [
                'entity' => 'contact',
                'entity_id' => (int) $contact->id,
                'message' => __('aichat::lang.chat_action_contact_deleted'),
            ];
        }

        throw new \InvalidArgumentException(__('aichat::lang.chat_action_unsupported'));
    }

    protected function executeSettingsAction(int $business_id, string $action, array $payload): array
    {
        if ($action !== 'update') {
            throw new \InvalidArgumentException(__('aichat::lang.chat_action_unsupported'));
        }

        $allowedFields = [
            'enabled',
            'default_provider',
            'default_model',
            'system_prompt',
            'retention_days',
            'pii_policy',
            'moderation_enabled',
            'moderation_terms',
            'idle_timeout_minutes',
            'share_ttl_hours',
            'suggested_replies',
        ];
        $updatePayload = $this->filterAllowedFields($payload, $allowedFields);
        if (empty($updatePayload)) {
            throw new \InvalidArgumentException(__('aichat::lang.chat_action_invalid_payload'));
        }

        $settings = $this->chatUtil->updateBusinessSettings($business_id, $updatePayload);

        return [
            'entity' => 'settings',
            'entity_id' => (int) $settings->id,
            'message' => __('aichat::lang.chat_action_settings_updated'),
        ];
    }

    protected function executeSalesAction(int $business_id, int $user_id, string $action, array $payload): array
    {
        return $this->executeTransactionAction($business_id, $user_id, $action, $payload, 'sales');
    }

    protected function executePurchaseAction(int $business_id, int $user_id, string $action, array $payload): array
    {
        return $this->executeTransactionAction($business_id, $user_id, $action, $payload, 'purchases');
    }

    protected function executeTransactionAction(int $business_id, int $user_id, string $action, array $payload, string $module): array
    {
        $isSales = $module === 'sales';
        $type = $isSales ? 'sell' : 'purchase';

        if ($action === 'create') {
            $contactId = (int) ($payload['contact_id'] ?? 0);
            if ($contactId <= 0) {
                throw new \InvalidArgumentException(__('aichat::lang.chat_action_invalid_payload'));
            }

            $transaction = Transaction::create([
                'business_id' => $business_id,
                'type' => (string) ($payload['type'] ?? $type),
                'status' => (string) ($payload['status'] ?? ($isSales ? 'draft' : 'received')),
                'payment_status' => (string) ($payload['payment_status'] ?? 'due'),
                'contact_id' => $contactId,
                'invoice_no' => trim((string) ($payload['invoice_no'] ?? '')) ?: null,
                'ref_no' => trim((string) ($payload['ref_no'] ?? '')) ?: null,
                'transaction_date' => (string) ($payload['transaction_date'] ?? now()->format('Y-m-d H:i:s')),
                'total_before_tax' => (float) ($payload['total_before_tax'] ?? $payload['final_total'] ?? 0),
                'tax_id' => isset($payload['tax_id']) ? (int) $payload['tax_id'] : null,
                'tax_amount' => (float) ($payload['tax_amount'] ?? 0),
                'discount_type' => isset($payload['discount_type']) ? (string) $payload['discount_type'] : null,
                'discount_amount' => (float) ($payload['discount_amount'] ?? 0),
                'shipping_details' => trim((string) ($payload['shipping_details'] ?? '')) ?: null,
                'shipping_charges' => (float) ($payload['shipping_charges'] ?? 0),
                'additional_notes' => trim((string) ($payload['additional_notes'] ?? '')) ?: null,
                'staff_note' => trim((string) ($payload['staff_note'] ?? '')) ?: null,
                'final_total' => (float) ($payload['final_total'] ?? 0),
                'created_by' => $user_id,
            ]);

            return [
                'entity' => 'transaction',
                'entity_id' => (int) $transaction->id,
                'message' => $isSales ? __('aichat::lang.chat_action_sale_created') : __('aichat::lang.chat_action_purchase_created'),
            ];
        }

        $transactionId = (int) ($payload['id'] ?? $payload['transaction_id'] ?? 0);
        if ($transactionId <= 0) {
            throw new \InvalidArgumentException(__('aichat::lang.chat_action_invalid_payload'));
        }

        $transaction = Transaction::where('business_id', $business_id)->where('id', $transactionId)->firstOrFail();
        if ($isSales && ! in_array((string) $transaction->type, ['sell', 'sales_order'], true)) {
            throw new \RuntimeException(__('aichat::lang.chat_action_not_found'));
        }
        if (! $isSales && ! in_array((string) $transaction->type, ['purchase', 'purchase_order'], true)) {
            throw new \RuntimeException(__('aichat::lang.chat_action_not_found'));
        }

        if ($action === 'update') {
            $allowedFields = [
                'status', 'payment_status', 'contact_id', 'invoice_no', 'ref_no', 'transaction_date',
                'total_before_tax', 'tax_id', 'tax_amount', 'discount_type', 'discount_amount',
                'shipping_details', 'shipping_charges', 'additional_notes', 'staff_note', 'final_total',
            ];
            $updatePayload = $this->filterAllowedFields($payload, $allowedFields);
            if (empty($updatePayload)) {
                throw new \InvalidArgumentException(__('aichat::lang.chat_action_invalid_payload'));
            }

            $transaction->fill($updatePayload);
            $transaction->save();

            return [
                'entity' => 'transaction',
                'entity_id' => (int) $transaction->id,
                'message' => $isSales ? __('aichat::lang.chat_action_sale_updated') : __('aichat::lang.chat_action_purchase_updated'),
            ];
        }

        if ($action === 'delete') {
            $transaction->delete();

            return [
                'entity' => 'transaction',
                'entity_id' => (int) $transaction->id,
                'message' => $isSales ? __('aichat::lang.chat_action_sale_deleted') : __('aichat::lang.chat_action_purchase_deleted'),
            ];
        }

        throw new \InvalidArgumentException(__('aichat::lang.chat_action_unsupported'));
    }

    protected function executeQuoteAction(int $business_id, int $user_id, string $action, array $payload): array
    {
        if ($action === 'create') {
            $locationId = (int) ($payload['location_id'] ?? 0);
            if ($locationId <= 0) {
                throw new \InvalidArgumentException(__('aichat::lang.chat_action_invalid_payload'));
            }

            $quote = ProductQuote::create([
                'business_id' => $business_id,
                'uuid' => ProductQuote::generateUuid(),
                'public_token' => ProductQuote::generateUniquePublicToken(),
                'contact_id' => isset($payload['contact_id']) ? (int) $payload['contact_id'] : null,
                'location_id' => $locationId,
                'expires_at' => (string) ($payload['expires_at'] ?? now()->addDays(7)->format('Y-m-d H:i:s')),
                'currency' => trim((string) ($payload['currency'] ?? '')) ?: null,
                'incoterm' => trim((string) ($payload['incoterm'] ?? '')) ?: null,
                'customer_email' => trim((string) ($payload['customer_email'] ?? '')) ?: null,
                'customer_name' => trim((string) ($payload['customer_name'] ?? '')) ?: null,
                'grand_total' => (float) ($payload['grand_total'] ?? 0),
                'line_count' => (int) ($payload['line_count'] ?? 0),
                'created_by' => $user_id,
            ]);

            return [
                'entity' => 'quote',
                'entity_id' => (int) $quote->id,
                'message' => __('aichat::lang.chat_action_quote_created'),
            ];
        }

        $quoteId = (int) ($payload['id'] ?? $payload['quote_id'] ?? 0);
        if ($quoteId <= 0) {
            throw new \InvalidArgumentException(__('aichat::lang.chat_action_invalid_payload'));
        }

        $quote = ProductQuote::forBusiness($business_id)->where('id', $quoteId)->firstOrFail();

        if ($action === 'update') {
            $allowedFields = [
                'contact_id', 'location_id', 'expires_at', 'currency', 'incoterm',
                'customer_email', 'customer_name', 'sent_at', 'transaction_id',
                'grand_total', 'line_count',
            ];
            $updatePayload = $this->filterAllowedFields($payload, $allowedFields);
            if (empty($updatePayload)) {
                throw new \InvalidArgumentException(__('aichat::lang.chat_action_invalid_payload'));
            }

            $quote->fill($updatePayload);
            $quote->save();

            return [
                'entity' => 'quote',
                'entity_id' => (int) $quote->id,
                'message' => __('aichat::lang.chat_action_quote_updated'),
            ];
        }

        if ($action === 'delete') {
            $quote->delete();

            return [
                'entity' => 'quote',
                'entity_id' => (int) $quote->id,
                'message' => __('aichat::lang.chat_action_quote_deleted'),
            ];
        }

        throw new \InvalidArgumentException(__('aichat::lang.chat_action_unsupported'));
    }

    protected function executeReportAction(int $business_id, int $user_id, string $action, array $payload): array
    {
        if (! in_array($action, ['view', 'run', 'export'], true)) {
            throw new \InvalidArgumentException(__('aichat::lang.chat_action_unsupported'));
        }

        $capabilities = $this->chatUtil->resolveChatCapabilities($business_id, $user_id);

        $result = [
            'report_type' => (string) ($payload['report_type'] ?? 'summary'),
        ];

        if ((bool) data_get($capabilities, 'products.view', false)) {
            $result['products_count'] = Product::where('business_id', $business_id)->count();
        }

        $canViewCustomerContacts = (bool) data_get($capabilities, 'contacts.customer.view', false);
        $canViewSupplierContacts = (bool) data_get($capabilities, 'contacts.supplier.view', false);
        $canViewOwnCustomerContacts = (bool) data_get($capabilities, 'contacts.customer.view_own', false);
        $canViewOwnSupplierContacts = (bool) data_get($capabilities, 'contacts.supplier.view_own', false);

        if ($canViewCustomerContacts || $canViewSupplierContacts || $canViewOwnCustomerContacts || $canViewOwnSupplierContacts) {
            $contactsQuery = Contact::where('business_id', $business_id)
                ->where('type', '!=', 'lead');

            if (! $canViewCustomerContacts && ! $canViewSupplierContacts) {
                $contactsQuery->leftJoin('user_contact_access as ucas', 'contacts.id', '=', 'ucas.contact_id')
                    ->where(function ($subQuery) use ($user_id) {
                        $subQuery->where('contacts.created_by', $user_id)
                            ->orWhere('ucas.user_id', $user_id);
                    })
                    ->distinct();
            }

            $result['contacts_count'] = $contactsQuery->count('contacts.id');
        }

        if ((bool) data_get($capabilities, 'sales.view', false) || (bool) data_get($capabilities, 'sales.view_own', false)) {
            $salesQuery = Transaction::where('business_id', $business_id)->where('type', 'sell');
            if (! (bool) data_get($capabilities, 'sales.view', false) && (bool) data_get($capabilities, 'sales.view_own', false)) {
                $salesQuery->where('created_by', $user_id);
            }
            $result['sales_count'] = $salesQuery->count();
        }

        if ((bool) data_get($capabilities, 'purchases.view', false) || (bool) data_get($capabilities, 'purchases.view_own', false)) {
            $purchaseQuery = Transaction::where('business_id', $business_id)->where('type', 'purchase');
            if (! (bool) data_get($capabilities, 'purchases.view', false) && (bool) data_get($capabilities, 'purchases.view_own', false)) {
                $purchaseQuery->where('created_by', $user_id);
            }
            $result['purchases_count'] = $purchaseQuery->count();
        }

        if ((bool) data_get($capabilities, 'quotes.view', false)) {
            $result['quotes_count'] = ProductQuote::forBusiness($business_id)->count();
        }

        if ($action === 'export') {
            $result['export_format'] = (string) ($payload['format'] ?? 'json');
            $result['exported_at'] = now()->toDateTimeString();
        }

        return [
            'entity' => 'report',
            'entity_id' => null,
            'message' => __('aichat::lang.chat_action_report_generated'),
            'data' => $result,
        ];
    }

    protected function filterAllowedFields(array $payload, array $allowedFields): array
    {
        $result = [];
        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $payload)) {
                $result[$field] = $payload[$field];
            }
        }

        return $result;
    }

    protected function buildPreviewText(string $module, string $action, array $payload): string
    {
        $targetId = $this->resolveTargetIdFromPayload($module, $payload);
        $suffix = $targetId !== null ? ' #' . $targetId : '';

        if ($module === 'contacts') {
            $contactType = strtolower(trim((string) ($payload['type'] ?? 'customer')));
            if (! in_array($contactType, ['customer', 'supplier', 'both'], true)) {
                $contactType = 'customer';
            }

            return ucfirst($action) . ' ' . $contactType . ' contact' . $suffix;
        }

        return ucfirst($action) . ' ' . rtrim($module, 's') . $suffix;
    }

    protected function resolveTargetIdFromPayload(string $module, array $payload): ?string
    {
        $ids = [
            (string) ($payload['id'] ?? ''),
            (string) ($payload['product_id'] ?? ''),
            (string) ($payload['contact_id'] ?? ''),
            (string) ($payload['transaction_id'] ?? ''),
            (string) ($payload['quote_id'] ?? ''),
        ];

        foreach ($ids as $id) {
            $id = trim($id);
            if ($id !== '') {
                return $id;
            }
        }

        return null;
    }

    protected function resolveTargetType(string $module): string
    {
        if ($module === 'sales' || $module === 'purchases') {
            return 'transaction';
        }

        if ($module === 'settings') {
            return 'chat_setting';
        }

        if ($module === 'reports') {
            return 'report';
        }

        return rtrim($module, 's');
    }

    protected function resolveContactTarget(int $business_id, array $payload): Contact
    {
        $contactId = (int) ($payload['id'] ?? $payload['contact_id'] ?? 0);
        if ($contactId <= 0) {
            throw new \InvalidArgumentException(__('aichat::lang.chat_action_invalid_payload'));
        }

        return Contact::where('business_id', $business_id)
            ->where('id', $contactId)
            ->firstOrFail();
    }

    protected function resolveContactCapabilityType(array $payload, ?Contact $contact = null): string
    {
        $type = strtolower(trim((string) ($payload['type'] ?? '')));
        if ($type === '') {
            $type = strtolower(trim((string) ($contact ? $contact->type : 'customer')));
        }

        if ($type === 'both') {
            return 'customer';
        }

        return in_array($type, ['customer', 'supplier'], true) ? $type : 'customer';
    }

}
