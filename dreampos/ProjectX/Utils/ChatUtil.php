<?php

namespace Modules\ProjectX\Utils;

use App\Transaction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Modules\ProjectX\Entities\ChatConversation;
use Modules\ProjectX\Entities\ChatCredential;
use Modules\ProjectX\Entities\ChatMemory;
use Modules\ProjectX\Entities\ChatMessage;
use Modules\ProjectX\Entities\ChatMessageFeedback;
use Modules\ProjectX\Entities\ChatSetting;
use Modules\ProjectX\Entities\Fabric;
use Modules\ProjectX\Entities\Quote;

class ChatUtil
{
    protected ChatAuditUtil $chatAuditUtil;

    protected FabricManagerUtil $fabricManagerUtil;

    protected ChatMessageRendererUtil $chatMessageRendererUtil;

    protected QuoteUtil $quoteUtil;

    protected TrimManagerUtil $trimManagerUtil;

    public function __construct(
        ChatAuditUtil $chatAuditUtil,
        FabricManagerUtil $fabricManagerUtil,
        ChatMessageRendererUtil $chatMessageRendererUtil,
        QuoteUtil $quoteUtil,
        TrimManagerUtil $trimManagerUtil
    )
    {
        $this->chatAuditUtil = $chatAuditUtil;
        $this->fabricManagerUtil = $fabricManagerUtil;
        $this->chatMessageRendererUtil = $chatMessageRendererUtil;
        $this->quoteUtil = $quoteUtil;
        $this->trimManagerUtil = $trimManagerUtil;
    }

    public function getProviderCatalog(): array
    {
        return (array) config('projectx.chat.providers', []);
    }

    public function getProviderModels(string $provider): array
    {
        return (array) data_get($this->getProviderCatalog(), strtolower($provider) . '.models', []);
    }

    public function isProviderSupported(string $provider): bool
    {
        $providers = $this->getProviderCatalog();

        return isset($providers[strtolower($provider)]);
    }

    public function isModelValidForProvider(string $provider, string $model): bool
    {
        $modelMap = $this->getProviderModels($provider);

        return isset($modelMap[$model]);
    }

    public function isChatEnabled(int $business_id): bool
    {
        if (! (bool) config('projectx.chat.enabled', true)) {
            return false;
        }

        return (bool) $this->getOrCreateBusinessSettings($business_id)->enabled;
    }

    public function getOrCreateBusinessSettings(int $business_id): ChatSetting
    {
        $defaults = [
            'enabled' => true,
            'fabric_insight_enabled' => true,
            'default_provider' => config('projectx.chat.default_provider', 'openai'),
            'default_model' => config('projectx.chat.default_model', 'gpt-4o-mini'),
            'system_prompt' => null,
            'model_allowlist' => null,
            'retention_days' => (int) config('projectx.chat.retention_days', 90),
            'pii_policy' => config('projectx.chat.pii_policy', 'warn'),
            'moderation_enabled' => (bool) config('projectx.chat.moderation_enabled', false),
            'moderation_terms' => implode("\n", (array) config('projectx.chat.moderation_terms', [])),
            'idle_timeout_minutes' => (int) config('projectx.chat.idle_timeout_minutes', 30),
            'suggested_replies' => (array) config('projectx.chat.suggested_replies', []),
            'share_ttl_hours' => (int) config('projectx.chat.share_ttl_hours', 168),
        ];

        return ChatSetting::firstOrCreate(
            ['business_id' => $business_id],
            $defaults
        );
    }

    public function updateBusinessSettings(int $business_id, array $data): ChatSetting
    {
        $settings = $this->getOrCreateBusinessSettings($business_id);

        if (array_key_exists('model_allowlist', $data) && is_string($data['model_allowlist'])) {
            $decoded = json_decode($data['model_allowlist'], true);
            $data['model_allowlist'] = is_array($decoded) ? $decoded : null;
        }

        if (array_key_exists('suggested_replies', $data) && is_string($data['suggested_replies'])) {
            $data['suggested_replies'] = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $data['suggested_replies']))));
        }

        if (array_key_exists('moderation_terms', $data) && is_array($data['moderation_terms'])) {
            $data['moderation_terms'] = implode("\n", $data['moderation_terms']);
        }

        $settings->fill($data);
        $settings->save();

        return $settings->fresh();
    }

    public function resolveCredentialForChat(int $user_id, int $business_id, string $provider): ?ChatCredential
    {
        $provider = strtolower($provider);

        $userCredential = ChatCredential::forBusiness($business_id)
            ->where('provider', $provider)
            ->where('user_id', $user_id)
            ->where('is_active', true)
            ->latest('id')
            ->first();
        if ($userCredential) {
            return $userCredential;
        }

        return ChatCredential::forBusiness($business_id)
            ->where('provider', $provider)
            ->whereNull('user_id')
            ->where('is_active', true)
            ->latest('id')
            ->first();
    }

    public function saveCredential(int $business_id, ?int $user_id, string $scope, string $provider, string $apiKey): ChatCredential
    {
        $provider = strtolower($provider);

        return DB::transaction(function () use ($business_id, $user_id, $scope, $provider, $apiKey) {
            $query = ChatCredential::forBusiness($business_id)
                ->where('provider', $provider)
                ->where('is_active', true);

            if ($scope === 'user') {
                $query->where('user_id', $user_id);
            } else {
                $query->whereNull('user_id');
            }

            $query->update([
                'is_active' => false,
                'rotated_at' => now(),
                'updated_at' => now(),
            ]);

            return ChatCredential::create([
                'business_id' => $business_id,
                'user_id' => $scope === 'user' ? $user_id : null,
                'provider' => $provider,
                'encrypted_api_key' => $this->encryptApiKey($apiKey),
                'is_active' => true,
            ]);
        });
    }

    public function encryptApiKey(string $plainApiKey): string
    {
        return Crypt::encryptString($plainApiKey);
    }

    public function decryptApiKey(string $encryptedApiKey): string
    {
        try {
            return Crypt::decryptString($encryptedApiKey);
        } catch (\Throwable $exception) {
            throw new \RuntimeException(__('projectx::lang.chat_provider_invalid_key_cipher'));
        }
    }

    public function listConversationsForUser(int $business_id, int $user_id, bool $include_archived = false, ?int $fabric_id = null)
    {
        $query = ChatConversation::forBusiness($business_id)
            ->forUser($user_id)
            ->forFabric($fabric_id)
            ->orderByDesc('updated_at');

        if (! $include_archived) {
            $query->where('is_archived', false);
        }

        return $query->get();
    }

    public function getConversationByIdForUser(int $business_id, int $user_id, string $conversation_id): ChatConversation
    {
        return ChatConversation::forBusiness($business_id)
            ->forUser($user_id)
            ->findOrFail($conversation_id);
    }

    public function getMessageByIdForUser(int $business_id, int $user_id, int $messageId): ChatMessage
    {
        return ChatMessage::forBusiness($business_id)
            ->where('id', $messageId)
            ->whereHas('conversation', function ($query) use ($business_id, $user_id) {
                $query->forBusiness($business_id)->forUser($user_id);
            })
            ->firstOrFail();
    }

    public function getLatestAssistantMessageId(int $business_id, string $conversationId): ?int
    {
        $id = ChatMessage::forBusiness($business_id)
            ->where('conversation_id', $conversationId)
            ->where('role', ChatMessage::ROLE_ASSISTANT)
            ->max('id');

        return $id !== null ? (int) $id : null;
    }

    public function getPreviousUserMessage(int $business_id, ChatMessage $assistantMessage): ?ChatMessage
    {
        return ChatMessage::forBusiness($business_id)
            ->where('conversation_id', $assistantMessage->conversation_id)
            ->where('role', ChatMessage::ROLE_USER)
            ->where('id', '<', $assistantMessage->id)
            ->orderByDesc('id')
            ->first();
    }

    public function getOrCreateConversation(int $business_id, int $user_id, ?int $fabric_id = null): ChatConversation
    {
        $conversation = ChatConversation::forBusiness($business_id)
            ->forUser($user_id)
            ->where('is_archived', false)
            ->forFabric($fabric_id)
            ->latest('updated_at')
            ->first();

        if ($conversation) {
            return $conversation;
        }

        return $this->createConversation($business_id, $user_id, null, $fabric_id);
    }

    public function createConversation(int $business_id, int $user_id, ?string $title = null, ?int $fabric_id = null): ChatConversation
    {
        return ChatConversation::create([
            'business_id' => $business_id,
            'user_id' => $user_id,
            'fabric_id' => $fabric_id,
            'title' => $title ?: 'New Chat',
            'is_archived' => false,
            'is_favorite' => false,
        ]);
    }

    public function appendMessage(
        ChatConversation $conversation,
        string $role,
        string $content,
        ?string $provider = null,
        ?string $model = null,
        ?int $user_id = null,
        ?int $fabric_id = null,
        bool $fabric_insight = false
    ): ChatMessage {
        $message = ChatMessage::create([
            'conversation_id' => $conversation->id,
            'business_id' => (int) $conversation->business_id,
            'user_id' => $user_id,
            'fabric_id' => $fabric_id,
            'fabric_insight' => $fabric_insight,
            'role' => $role,
            'content' => $content,
            'provider' => $provider,
            'model' => $model,
        ]);

        $updates = [
            'last_message_preview' => $this->buildLastMessagePreview($content),
            'last_message_at' => $message->created_at,
            'last_model' => $model ?: $conversation->last_model,
        ];

        if ($role === ChatMessage::ROLE_USER) {
            $userMessageCount = ChatMessage::forBusiness((int) $conversation->business_id)
                ->where('conversation_id', $conversation->id)
                ->where('role', ChatMessage::ROLE_USER)
                ->count();

            if ($userMessageCount <= 1 || empty($conversation->title) || $conversation->title === 'New Chat') {
                if ($this->countWords($content) >= 3) {
                    $updates['title'] = $this->buildConversationTitleFromText($content);
                } else {
                    $updates['title'] = 'New Chat';
                }
            }
        } elseif ($role === ChatMessage::ROLE_ASSISTANT && $this->isLowContextConversationTitle($conversation->title)) {
            $updates['title'] = $this->buildConversationTitleFromText($content);
        }

        $conversation->fill($updates);
        $conversation->save();

        return $message;
    }

    public function replaceAssistantMessageContent(
        ChatMessage $assistantMessage,
        string $newContent,
        ?string $provider = null,
        ?string $model = null,
        ?int $fabric_id = null,
        bool $fabric_insight = false
    ): ChatMessage {
        if ($assistantMessage->role !== ChatMessage::ROLE_ASSISTANT) {
            throw new \InvalidArgumentException('Only assistant messages can be replaced.');
        }

        $assistantMessage->fill([
            'content' => $newContent,
            'provider' => $provider ?: $assistantMessage->provider,
            'model' => $model ?: $assistantMessage->model,
            'fabric_id' => $fabric_id === null ? $assistantMessage->fabric_id : $fabric_id,
            'fabric_insight' => $fabric_insight,
        ]);
        $assistantMessage->save();

        $conversation = ChatConversation::forBusiness((int) $assistantMessage->business_id)
            ->findOrFail((string) $assistantMessage->conversation_id);

        $conversation->fill([
            'last_message_preview' => $this->buildLastMessagePreview($newContent),
            'last_message_at' => now(),
            'last_model' => $assistantMessage->model,
            'updated_at' => now(),
        ]);
        $conversation->save();

        ChatMessageFeedback::forBusiness((int) $assistantMessage->business_id)
            ->where('message_id', (int) $assistantMessage->id)
            ->delete();

        return $assistantMessage->fresh();
    }

    public function saveMessageFeedback(
        int $business_id,
        int $user_id,
        ChatMessage $message,
        string $feedback,
        ?string $note = null
    ): ChatMessageFeedback {
        return ChatMessageFeedback::updateOrCreate(
            [
                'message_id' => (int) $message->id,
                'user_id' => $user_id,
            ],
            [
                'business_id' => $business_id,
                'conversation_id' => (string) $message->conversation_id,
                'feedback' => $feedback,
                'note' => $note ? trim($note) : null,
            ]
        );
    }

    public function deleteConversationForUser(int $business_id, int $user_id, string $conversation_id): bool
    {
        $conversation = $this->getConversationByIdForUser($business_id, $user_id, $conversation_id);

        return (bool) $conversation->delete();
    }

    public function applyFabricUpdatesFromAssistantMessage(
        int $business_id,
        int $user_id,
        int $fabric_id,
        int $message_id,
        array $updates,
        ?int $actor_user_id = null
    ): Fabric {
        $allowedFields = $this->getChatFabricWritableFields();
        $invalidFields = array_values(array_diff(array_keys($updates), $allowedFields));
        if (! empty($invalidFields)) {
            throw new \InvalidArgumentException(__('projectx::lang.chat_apply_invalid_update_fields'));
        }

        $assistantMessage = ChatMessage::forBusiness($business_id)
            ->where('id', $message_id)
            ->where('role', ChatMessage::ROLE_ASSISTANT)
            ->where('fabric_insight', true)
            ->where('fabric_id', $fabric_id)
            ->whereHas('conversation', function ($query) use ($business_id, $user_id, $fabric_id) {
                $query->forBusiness($business_id)
                    ->forUser($user_id)
                    ->where('fabric_id', $fabric_id);
            })
            ->firstOrFail();

        if ((bool) config('projectx.chat.enforce_fabric_update_match', true)) {
            if (! $this->doesFabricUpdatesMatchAssistantMessage($updates, (string) $assistantMessage->content)) {
                throw new \InvalidArgumentException(__('projectx::lang.chat_apply_payload_mismatch'));
            }
        }

        return $this->fabricManagerUtil->updateFabricFromChat(
            $business_id,
            $fabric_id,
            $updates,
            $actor_user_id
        );
    }

    public function doesFabricUpdatesMatchAssistantMessage(array $requestedUpdates, string $assistantMessageContent): bool
    {
        $assistantUpdates = $this->extractFabricUpdatesFromAssistantMessage($assistantMessageContent);
        if ($assistantUpdates === null) {
            return false;
        }

        $normalizedRequested = $this->normalizeFabricUpdatesForMatching($requestedUpdates);
        if (empty($normalizedRequested)) {
            return false;
        }

        $requestedKeys = array_keys($normalizedRequested);
        $assistantKeys = array_keys($assistantUpdates);
        sort($requestedKeys);
        sort($assistantKeys);
        if ($requestedKeys !== $assistantKeys) {
            return false;
        }

        foreach ($requestedKeys as $key) {
            if (! array_key_exists($key, $assistantUpdates)) {
                return false;
            }

            if ($normalizedRequested[$key] !== $assistantUpdates[$key]) {
                return false;
            }
        }

        return true;
    }

    public function serializeConversation(ChatConversation $conversation): array
    {
        return [
            'id' => $conversation->id,
            'fabric_id' => $conversation->fabric_id ? (int) $conversation->fabric_id : null,
            'title' => $conversation->title ?: 'New Chat',
            'is_favorite' => (bool) $conversation->is_favorite,
            'is_archived' => (bool) $conversation->is_archived,
            'last_message_preview' => $conversation->last_message_preview,
            'last_message_at' => optional($conversation->last_message_at)->toIso8601String(),
            'updated_at' => optional($conversation->updated_at)->toIso8601String(),
            'last_model' => $conversation->last_model,
        ];
    }

    public function serializeMessage(ChatMessage $message, ?string $feedbackValue = null, ?int $latestAssistantMessageId = null): array
    {
        $content = (string) $message->content;
        $contentHtml = $this->chatMessageRendererUtil->renderForRole($content, (string) $message->role);

        $canRegenerate = $message->role === ChatMessage::ROLE_ASSISTANT
            && $latestAssistantMessageId !== null
            && (int) $message->id === (int) $latestAssistantMessageId;

        return [
            'id' => (int) $message->id,
            'conversation_id' => $message->conversation_id,
            'fabric_id' => $message->fabric_id ? (int) $message->fabric_id : null,
            'fabric_insight' => (bool) $message->fabric_insight,
            'role' => $message->role,
            'content' => $content,
            'content_html' => $contentHtml,
            'provider' => $message->provider,
            'model' => $message->model,
            'feedback_value' => $feedbackValue,
            'can_regenerate' => $canRegenerate,
            'created_at' => optional($message->created_at)->toIso8601String(),
        ];
    }

    public function getFeedbackMapForUser(int $business_id, int $user_id, array $messageIds): array
    {
        $cleanIds = array_values(array_unique(array_filter(array_map('intval', $messageIds))));
        if (empty($cleanIds)) {
            return [];
        }

        return ChatMessageFeedback::forBusiness($business_id)
            ->where('user_id', $user_id)
            ->whereIn('message_id', $cleanIds)
            ->pluck('feedback', 'message_id')
            ->map(function ($value) {
                return (string) $value;
            })
            ->all();
    }

    public function buildModelOptions(int $business_id, int $user_id): array
    {
        $providers = $this->getProviderCatalog();
        $enabledProviders = [];
        foreach (array_keys($providers) as $provider) {
            if ($this->resolveCredentialForChat($user_id, $business_id, $provider)) {
                $enabledProviders[] = $provider;
            }
        }

        if (empty($enabledProviders)) {
            $enabledProviders = array_keys($providers);
        }

        $settings = $this->getOrCreateBusinessSettings($business_id);

        $modelOptions = [];
        foreach ($enabledProviders as $provider) {
            foreach ((array) data_get($providers, $provider . '.models', []) as $modelId => $label) {
                if (! $this->isModelAllowedForBusiness($business_id, $provider, $modelId)) {
                    continue;
                }

                $modelOptions[] = [
                    'provider' => $provider,
                    'model_id' => $modelId,
                    'label' => $label . ' (' . ucfirst($provider) . ')',
                ];
            }
        }

        $defaultProvider = $settings->default_provider ?: config('projectx.chat.default_provider', 'openai');
        if (! in_array($defaultProvider, $enabledProviders, true)) {
            $defaultProvider = $enabledProviders[0] ?? 'openai';
        }

        $defaultModel = $settings->default_model ?: config('projectx.chat.default_model', 'gpt-4o-mini');
        if (! $this->isModelValidForProvider($defaultProvider, $defaultModel) || ! $this->isModelAllowedForBusiness($business_id, $defaultProvider, $defaultModel)) {
            $modelList = $this->getProviderModels($defaultProvider);
            $first = array_keys($modelList);
            $defaultModel = $first[0] ?? $defaultModel;
        }

        return [
            'enabled_providers' => $enabledProviders,
            'model_options' => $modelOptions,
            'default_provider' => $defaultProvider,
            'default_model' => $defaultModel,
        ];
    }

    public function buildClientConfig(int $business_id, int $user_id): array
    {
        $settings = $this->getOrCreateBusinessSettings($business_id);
        $modelOptions = $this->buildModelOptions($business_id, $user_id);
        $canEditChat = auth()->check() && auth()->user()->can('projectx.chat.edit');
        $canApplyFabricUpdates = auth()->check()
            && (auth()->user()->can('projectx.fabric.create') || auth()->user()->can('product.create'));
        $fabricUpdateFieldTypes = $this->getChatFabricWritableFieldTypes();

        return [
            'enabled' => $this->isChatEnabled($business_id),
            'permissions' => [
                'can_edit' => $canEditChat,
                'can_apply_fabric_updates' => $canApplyFabricUpdates,
            ],
            'features' => [
                'workflow_profile' => (string) config('projectx.chat.workflow_profile', 'dezai_assumed_v1'),
                'general_first_mode' => (bool) config('projectx.chat.general_first_mode', true),
                'fabric_insight_enabled' => (bool) $settings->fabric_insight_enabled,
                'idle_timeout_minutes' => (int) $settings->idle_timeout_minutes,
                'share_ttl_hours' => (int) $settings->share_ttl_hours,
                'suggested_replies' => $this->getSuggestedReplies($settings),
                'pii_policy' => (string) $settings->pii_policy,
                'moderation_enabled' => (bool) $settings->moderation_enabled,
                'fabric_update_fields' => [
                    'allowed' => array_keys($fabricUpdateFieldTypes),
                    'types' => $fabricUpdateFieldTypes,
                ],
            ],
            'routes' => [
                'index_url' => route('projectx.chat.index'),
                'config_url' => route('projectx.chat.config'),
                'list_url' => route('projectx.chat.conversations.index'),
                'create_url' => route('projectx.chat.conversations.store'),
                'destroy_url_template' => route('projectx.chat.conversations.destroy', ['id' => '__ID__']),
                'conversation_url_template' => route('projectx.chat.conversations.show', ['id' => '__ID__']),
                'send_url_template' => route('projectx.chat.conversations.send', ['id' => '__ID__']),
                'stream_url_template' => route('projectx.chat.conversations.stream', ['id' => '__ID__']),
                'share_url_template' => route('projectx.chat.conversations.share', ['id' => '__ID__']),
                'export_url_template' => route('projectx.chat.conversations.export', ['id' => '__ID__']),
                'apply_fabric_updates_url_template' => route('projectx.chat.fabric_updates.apply', ['fabric_id' => '__FABRIC_ID__', 'message' => '__MESSAGE_ID__']),
                'feedback_url_template' => route('projectx.chat.messages.feedback.store', ['message' => '__MESSAGE_ID__']),
                'regenerate_url_template' => route('projectx.chat.messages.regenerate', ['message' => '__MESSAGE_ID__']),
                'settings_url' => route('projectx.chat.settings'),
                'save_credential_url' => route('projectx.chat.settings.credential.store'),
                'save_business_settings_url' => route('projectx.chat.settings.business.update'),
            ],
            'default_provider' => $modelOptions['default_provider'],
            'default_model' => $modelOptions['default_model'],
            'enabled_providers' => $modelOptions['enabled_providers'],
            'model_options' => $modelOptions['model_options'],
            'i18n' => [
                'no_conversations' => __('projectx::lang.chat_no_conversations'),
                'copied' => __('projectx::lang.chat_copied'),
                'copy_failed' => __('projectx::lang.chat_copy_failed'),
                'request_failed' => __('projectx::lang.something_went_wrong'),
                'quota_exceeded' => __('projectx::lang.chat_provider_quota_exceeded'),
                'stream_not_supported' => __('projectx::lang.chat_stream_not_supported'),
                'stream_request_failed' => __('projectx::lang.chat_stream_request_failed'),
                'idle_message' => __('projectx::lang.chat_idle_message'),
                'warning' => __('projectx::lang.warning'),
                'error' => __('projectx::lang.error_generic'),
                'new_chat' => __('projectx::lang.new_chat'),
                'delete_conversation' => __('projectx::lang.chat_delete_conversation'),
                'delete_confirm' => __('projectx::lang.chat_delete_confirm'),
                'delete_success' => __('projectx::lang.chat_delete_success'),
                'start_new_chat' => __('projectx::lang.chat_start_new_chat'),
                'apply_fabric_changes' => __('projectx::lang.chat_apply_fabric_changes'),
                'apply_confirm' => __('projectx::lang.chat_apply_confirm'),
                'apply_success' => __('projectx::lang.chat_apply_success'),
                'apply_success_detail' => __('projectx::lang.chat_apply_success_detail'),
                'apply_error' => __('projectx::lang.chat_apply_error'),
            ],
        ];
    }

    public function resolveFabricContext(int $business_id, array $payload, ChatSetting $settings): array
    {
        $warnings = [];
        $context = null;
        $resolvedFabricId = null;
        $contextGeneratedAt = null;

        if (! (bool) ($payload['fabric_insight'] ?? false)) {
            return [
                'context' => null,
                'warnings' => [],
                'fabric_id' => null,
                'context_generated_at' => null,
            ];
        }

        $fabricId = (int) ($payload['fabric_id'] ?? 0);
        if ($fabricId <= 0) {
            return [
                'context' => null,
                'warnings' => [],
                'fabric_id' => null,
                'context_generated_at' => null,
            ];
        }

        if (! (bool) $settings->fabric_insight_enabled) {
            $warnings[] = __('projectx::lang.chat_warning_fabric_insight_disabled');

            return [
                'context' => null,
                'warnings' => $warnings,
                'fabric_id' => null,
                'context_generated_at' => null,
            ];
        }

        $user = auth()->user();
        if (! $user || (! $user->can('projectx.fabric.view') && ! $user->can('product.view'))) {
            $warnings[] = __('projectx::lang.chat_warning_fabric_access_denied');

            return [
                'context' => null,
                'warnings' => $warnings,
                'fabric_id' => null,
                'context_generated_at' => null,
            ];
        }

        try {
            $context = $this->fabricManagerUtil->getFabricContextForChat($business_id, $fabricId);
            $resolvedFabricId = $fabricId;
            $contextGeneratedAt = now()->toIso8601String();
        } catch (\Throwable $exception) {
            $warnings[] = __('projectx::lang.chat_warning_fabric_context_unavailable');
            $context = null;
        }

        return [
            'context' => $context,
            'warnings' => $warnings,
            'fabric_id' => $resolvedFabricId,
            'context_generated_at' => $contextGeneratedAt,
        ];
    }

    public function resolveQuoteContext(int $business_id, array $payload, ChatSetting $settings): array
    {
        $warnings = [];
        $quoteId = (int) ($payload['quote_id'] ?? 0);

        if ($quoteId <= 0) {
            return [
                'context' => null,
                'warnings' => [],
                'quote_id' => null,
            ];
        }

        $user = auth()->user();
        if (! $user || ! $user->can('projectx.quote.view')) {
            $warnings[] = __('projectx::lang.chat_warning_quote_access_denied');

            return [
                'context' => null,
                'warnings' => $warnings,
                'quote_id' => null,
            ];
        }

        try {
            $context = $this->quoteUtil->getContextForChat($business_id, $quoteId);

            return [
                'context' => $context,
                'warnings' => [],
                'quote_id' => $quoteId,
            ];
        } catch (\Throwable $exception) {
            $warnings[] = __('projectx::lang.chat_warning_quote_context_unavailable');

            return [
                'context' => null,
                'warnings' => $warnings,
                'quote_id' => null,
            ];
        }
    }

    public function enrichPayloadContextIds(int $business_id, array $payload): array
    {
        $enrichedPayload = $payload;

        try {
            $explicitQuoteId = (int) ($payload['quote_id'] ?? 0);
            $explicitTransactionId = (int) ($payload['transaction_id'] ?? 0);
            $quoteId = $explicitQuoteId > 0 ? $explicitQuoteId : null;
            $transactionId = $explicitTransactionId > 0 ? $explicitTransactionId : null;

            if ($quoteId !== null && $transactionId === null) {
                $quote = Quote::forBusiness($business_id)
                    ->select('id', 'transaction_id')
                    ->find($quoteId);

                $quoteTransactionId = (int) ($quote->transaction_id ?? 0);
                if ($quoteTransactionId > 0) {
                    $transactionId = $quoteTransactionId;
                }
            }

            if ($quoteId === null) {
                $fabricId = (int) ($payload['fabric_id'] ?? 0);
                if ($fabricId > 0) {
                    $latestQuote = $this->findLatestQuoteForFabricContext($business_id, $fabricId);
                    if ($latestQuote) {
                        $quoteId = (int) $latestQuote->id;
                        $latestQuoteTransactionId = (int) ($latestQuote->transaction_id ?? 0);
                        if ($transactionId === null && $latestQuoteTransactionId > 0) {
                            $transactionId = $latestQuoteTransactionId;
                        }
                    }
                }
            }

            if ($explicitQuoteId <= 0 && $quoteId !== null) {
                $enrichedPayload['quote_id'] = $quoteId;
            }

            if ($explicitTransactionId <= 0 && $transactionId !== null) {
                $enrichedPayload['transaction_id'] = $transactionId;
            }
        } catch (\Throwable $exception) {
            return $payload;
        }

        return $enrichedPayload;
    }

    public function resolveTrimContext(int $business_id, array $payload, ChatSetting $settings): array
    {
        $warnings = [];
        $trimId = (int) ($payload['trim_id'] ?? 0);

        if ($trimId <= 0) {
            return [
                'context' => null,
                'warnings' => [],
                'trim_id' => null,
            ];
        }

        $user = auth()->user();
        if (! $user || ! $user->can('projectx.trim.view')) {
            $warnings[] = __('projectx::lang.chat_warning_trim_access_denied');

            return [
                'context' => null,
                'warnings' => $warnings,
                'trim_id' => null,
            ];
        }

        try {
            $context = $this->trimManagerUtil->getContextForChat($business_id, $trimId);

            return [
                'context' => $context,
                'warnings' => [],
                'trim_id' => $trimId,
            ];
        } catch (\Throwable $exception) {
            $warnings[] = __('projectx::lang.chat_warning_trim_context_unavailable');

            return [
                'context' => null,
                'warnings' => $warnings,
                'trim_id' => null,
            ];
        }
    }

    public function getSalesOrderContextForChat(int $business_id, int $transaction_id): string
    {
        $transaction = Transaction::where('business_id', $business_id)
            ->whereIn('type', ['sell', 'sales_order'])
            ->with([
                'contact:id,name,supplier_business_name,email,contact_id',
                'location:id,name',
                'sell_lines:id,transaction_id,product_id,variation_id,quantity,unit_price,unit_price_inc_tax,item_tax,line_discount_type,line_discount_amount',
                'sell_lines.product:id,name',
                'sell_lines.variations:id,name,sub_sku',
            ])
            ->findOrFail($transaction_id);

        $transactionDate = null;
        try {
            if (! empty($transaction->transaction_date)) {
                $transactionDate = Carbon::parse($transaction->transaction_date)->format('Y-m-d H:i:s');
            }
        } catch (\Throwable $exception) {
            $transactionDate = null;
        }

        $lineRows = [];
        $totalQuantity = 0.0;
        foreach ($transaction->sell_lines as $index => $line) {
            $quantity = (float) ($line->quantity ?? 0);
            $unitPriceIncTax = (float) ($line->unit_price_inc_tax ?? 0);
            $lineTotal = $quantity * $unitPriceIncTax;
            $totalQuantity += $quantity;

            $productName = trim((string) (optional($line->product)->name ?? '-'));
            $variationName = trim((string) (optional($line->variations)->name ?? ''));
            if ($variationName !== '') {
                $productName .= ' (' . $variationName . ')';
            }

            $sku = trim((string) (optional($line->variations)->sub_sku ?? ''));
            $lineRows[] = sprintf(
                '%d. %s%s | qty: %s | unit_price_inc_tax: %s | line_total: %s',
                $index + 1,
                $productName,
                $sku !== '' ? ' [' . $sku . ']' : '',
                $this->formatContextNumber($quantity),
                $this->formatContextNumber($unitPriceIncTax),
                $this->formatContextNumber($lineTotal)
            );
        }

        $status = trim((string) ($transaction->status ?? ''));
        $subStatus = trim((string) ($transaction->sub_status ?? ''));
        if ($subStatus !== '') {
            $status .= ' (' . $subStatus . ')';
        }

        $customerName = trim((string) (
            optional($transaction->contact)->supplier_business_name
            ?: optional($transaction->contact)->name
            ?: '-'
        ));

        $lines = [
            'Sales order context snapshot:',
            'transaction_id: ' . (int) $transaction->id,
            'invoice_no: ' . trim((string) ($transaction->invoice_no ?: '-')),
            'type: ' . trim((string) ($transaction->type ?: '-')),
            'status: ' . ($status !== '' ? $status : '-'),
            'transaction_date: ' . ($transactionDate ?: '-'),
            'customer: ' . $customerName,
            'location: ' . trim((string) (optional($transaction->location)->name ?: '-')),
            'final_total: ' . $this->formatContextNumber((float) ($transaction->final_total ?? 0)),
            'total_quantity: ' . $this->formatContextNumber($totalQuantity),
            'line_count: ' . count($lineRows),
            'lines:',
        ];

        if (empty($lineRows)) {
            $lines[] = '- (no sell lines)';
        } else {
            foreach ($lineRows as $lineRow) {
                $lines[] = '- ' . $lineRow;
            }
        }

        return implode("\n", $lines);
    }

    public function resolveSalesOrderContext(int $business_id, array $payload, ChatSetting $settings): array
    {
        $warnings = [];
        $transactionId = (int) ($payload['transaction_id'] ?? 0);

        if ($transactionId <= 0) {
            return [
                'context' => null,
                'warnings' => [],
                'transaction_id' => null,
            ];
        }

        $user = auth()->user();
        if (! $user || (! $user->can('sell.view') && ! $user->can('direct_sell.view'))) {
            $warnings[] = __('projectx::lang.chat_warning_sales_order_access_denied');

            return [
                'context' => null,
                'warnings' => $warnings,
                'transaction_id' => null,
            ];
        }

        try {
            $context = $this->getSalesOrderContextForChat($business_id, $transactionId);

            return [
                'context' => $context,
                'warnings' => [],
                'transaction_id' => $transactionId,
            ];
        } catch (\Throwable $exception) {
            $warnings[] = __('projectx::lang.chat_warning_sales_order_context_unavailable');

            return [
                'context' => null,
                'warnings' => $warnings,
                'transaction_id' => null,
            ];
        }
    }

    public function buildSystemInstructionMessages(int $business_id, ?string $systemPrompt = null, bool $hasFabricContext = false, ?int $user_id = null): array
    {
        $workflowProfile = strtolower(trim((string) config('projectx.chat.workflow_profile', 'dezai_assumed_v1')));
        if ($workflowProfile === 'legacy') {
            return $this->buildLegacySystemInstructionMessages($business_id, $systemPrompt, $hasFabricContext, $user_id);
        }

        return $this->buildAssumedDezaiV1SystemInstructionMessages($business_id, $systemPrompt, $hasFabricContext, $user_id);
    }

    protected function buildLegacySystemInstructionMessages(int $business_id, ?string $systemPrompt = null, bool $hasFabricContext = false, ?int $user_id = null): array
    {
        $messages = [];

        if (! empty($systemPrompt)) {
            $messages[] = [
                'role' => 'system',
                'content' => $systemPrompt,
            ];
        }

        if ((bool) config('projectx.chat.structured_reasoning_enabled', true)) {
            $messages[] = [
                'role' => 'system',
                'content' => $this->getStructuredReasoningInstruction(),
            ];
        }

        if ((bool) config('projectx.chat.evaluation_checks_enabled', true)) {
            $messages[] = [
                'role' => 'system',
                'content' => $this->getEvaluationChecksInstruction(),
            ];
        }

        if ((bool) config('projectx.chat.tools_for_reasoning_enabled', true)) {
            $messages[] = [
                'role' => 'system',
                'content' => $this->getToolsForReasoningInstruction(),
            ];
        }

        if ((bool) config('projectx.chat.project_reference_enabled', true)) {
            $projectReferenceInstruction = trim($this->getProjectReferenceInstruction());
            if ($projectReferenceInstruction !== '') {
                $messages[] = [
                    'role' => 'system',
                    'content' => $projectReferenceInstruction,
                ];
            }
        }

        if ((bool) config('projectx.chat.memory_enabled', true)) {
            $persistentFactsInstruction = $this->getPersistentFactsInstruction($business_id, $user_id);
            if ($persistentFactsInstruction !== null && trim($persistentFactsInstruction) !== '') {
                $messages[] = [
                    'role' => 'system',
                    'content' => $persistentFactsInstruction,
                ];
            }
        }

        $messages[] = [
            'role' => 'system',
            'content' => $this->getResponseFormattingInstruction($hasFabricContext),
        ];

        return $messages;
    }

    protected function buildAssumedDezaiV1SystemInstructionMessages(int $business_id, ?string $systemPrompt = null, bool $hasFabricContext = false, ?int $user_id = null): array
    {
        $messages = [];

        if (! empty($systemPrompt)) {
            $messages[] = [
                'role' => 'system',
                'content' => $systemPrompt,
            ];
        }

        $messages[] = [
            'role' => 'system',
            'content' => $this->getPrimaryRoleInstruction(),
        ];

        if ((bool) config('projectx.chat.general_first_mode', true)) {
            $messages[] = [
                'role' => 'system',
                'content' => $this->getOpeningBehaviorInstruction(),
            ];
        }

        if ((bool) config('projectx.chat.memory_enabled', true)) {
            $persistentFactsInstruction = $this->getPersistentFactsInstruction($business_id, $user_id);
            if ($persistentFactsInstruction !== null && trim($persistentFactsInstruction) !== '') {
                $messages[] = [
                    'role' => 'system',
                    'content' => $persistentFactsInstruction,
                ];
            }
        }

        if ($hasFabricContext) {
            $messages[] = [
                'role' => 'system',
                'content' => $this->getFabricScopedBehaviorInstruction(),
            ];
            $messages[] = [
                'role' => 'system',
                'content' => $this->getFabricSourceOfTruthInstruction(),
            ];
        }

        $messages[] = [
            'role' => 'system',
            'content' => $this->getResponseFormattingInstruction($hasFabricContext),
        ];

        return $messages;
    }

    public function buildProviderMessages(
        ChatConversation $conversation,
        ?string $systemPrompt = null,
        ?string $fabricContext = null,
        int $historyLimit = 30,
        ?int $beforeMessageId = null,
        ?string $currentUserPrompt = null,
        ?int $fabricId = null,
        ?string $contextGeneratedAt = null,
        ?int $user_id = null,
        ?string $quoteContext = null,
        ?int $quoteId = null,
        ?string $trimContext = null,
        ?int $trimId = null,
        ?string $salesOrderContext = null,
        ?int $transactionId = null
    ): array
    {
        $hasFabricContext = $fabricContext !== null && trim($fabricContext) !== '';
        $messages = $this->buildSystemInstructionMessages(
            (int) $conversation->business_id,
            $systemPrompt,
            $hasFabricContext,
            $user_id
        );

        $historyQuery = ChatMessage::forBusiness((int) $conversation->business_id)
            ->where('conversation_id', $conversation->id)
            ->orderByDesc('created_at');

        if ($beforeMessageId !== null) {
            $historyQuery->where('id', '<', $beforeMessageId);
        }

        $historyFetchLimit = $hasFabricContext ? max($historyLimit, 30) : $historyLimit;
        $history = $historyQuery
            ->limit($historyFetchLimit)
            ->get();

        $normalizedHistory = [];
        foreach ($history as $historyMessage) {
            $role = $historyMessage->role;
            if ($role === ChatMessage::ROLE_ERROR) {
                $role = ChatMessage::ROLE_ASSISTANT;
            }

            if (! in_array($role, [ChatMessage::ROLE_USER, ChatMessage::ROLE_ASSISTANT, ChatMessage::ROLE_SYSTEM], true)) {
                continue;
            }

            $normalizedHistory[] = [
                'role' => $role,
                'content' => (string) $historyMessage->content,
            ];
        }

        if ($hasFabricContext) {
            $assistantCount = 0;
            $limitedHistory = [];
            foreach ($normalizedHistory as $historyMessage) {
                if (count($limitedHistory) >= 10) {
                    break;
                }

                if ($historyMessage['role'] === ChatMessage::ROLE_ASSISTANT) {
                    if ($assistantCount >= 3) {
                        continue;
                    }

                    $assistantCount++;
                }

                $limitedHistory[] = $historyMessage;
            }

            $normalizedHistory = array_reverse($limitedHistory);
        } else {
            $normalizedHistory = array_reverse($normalizedHistory);
        }

        foreach ($normalizedHistory as $historyMessage) {
            $messages[] = $historyMessage;
        }

        if ($hasFabricContext) {
            $contextLines = [
                $this->getFabricSourceOfTruthInstruction(),
                '',
                'Live Fabric Context (source of truth)',
            ];

            if ($fabricId !== null && $fabricId > 0) {
                $contextLines[] = 'fabric_id: ' . $fabricId;
            }

            if (! empty($contextGeneratedAt)) {
                $contextLines[] = 'context_generated_at: ' . $contextGeneratedAt;
            }

            $contextLines[] = '';
            $contextLines[] = (string) $fabricContext;

            $messages[] = [
                'role' => 'system',
                'content' => implode("\n", $contextLines),
            ];
        }

        if ($quoteContext !== null && trim($quoteContext) !== '') {
            $quoteContextLines = [
                'Live Quote Context (source of truth)',
                'Use this quote context as the source of truth for quote details, prices, and quantities.',
            ];
            if ($quoteId !== null && $quoteId > 0) {
                $quoteContextLines[] = 'quote_id: ' . $quoteId;
            }
            $quoteContextLines[] = '';
            $quoteContextLines[] = (string) $quoteContext;

            $messages[] = [
                'role' => 'system',
                'content' => implode("\n", $quoteContextLines),
            ];
        }

        if ($trimContext !== null && trim($trimContext) !== '') {
            $trimContextLines = [
                'Live Trim Context (source of truth)',
                'Use this trim context as the source of truth for trim specifications, costs, and status.',
            ];
            if ($trimId !== null && $trimId > 0) {
                $trimContextLines[] = 'trim_id: ' . $trimId;
            }
            $trimContextLines[] = '';
            $trimContextLines[] = (string) $trimContext;

            $messages[] = [
                'role' => 'system',
                'content' => implode("\n", $trimContextLines),
            ];
        }

        if ($salesOrderContext !== null && trim($salesOrderContext) !== '') {
            $salesOrderContextLines = [
                'Live Sales Order Context (source of truth)',
                'Use this sales order context as the source of truth for invoice, quantities, and totals.',
            ];
            if ($transactionId !== null && $transactionId > 0) {
                $salesOrderContextLines[] = 'transaction_id: ' . $transactionId;
            }
            $salesOrderContextLines[] = '';
            $salesOrderContextLines[] = (string) $salesOrderContext;

            $messages[] = [
                'role' => 'system',
                'content' => implode("\n", $salesOrderContextLines),
            ];
        }

        if ($currentUserPrompt !== null && trim($currentUserPrompt) !== '') {
            $messages[] = [
                'role' => ChatMessage::ROLE_USER,
                'content' => (string) $currentUserPrompt,
            ];
        }

        return $messages;
    }

    public function getStructuredReasoningInstruction(): string
    {
        return implode("\n", [
            'How to think and solve coding (six steps):',
            '1. Understand the message - Goal, scope, constraints; explicit vs implicit.',
            '2. Use context - Workspace rules, open/recent files, @-mentions, repo state; do not scan blindly.',
            '3. Decide what to do - Answer / find / plan / implement; choose actions (search -> read -> edit).',
            '4. Narrow where to look - Area (core vs module, layer); entry points from AGENTS.md/ai/; search/grep for exact files/symbols.',
            '5. Form task + objects + order - One-line task, list of objects, order of work, constraints; ask or search if something is missing.',
            '6. Execute - Read relevant code, make minimal changes, check rules and patterns, then iterate if needed.',
        ]);
    }

    public function getEvaluationChecksInstruction(): string
    {
        return implode("\n", [
            'Before you respond: five checks',
            '- Goal: Does this address what the user asked (fix, feature, explanation, etc.)?',
            '- Rules: Does it fit AGENTS.md, .cursor rules, and project conventions?',
            '- Evidence: Does it match what you just read (files, search results) with no contradictions?',
            '- Completeness: Are you missing an obvious follow-up read or edit?',
            '- Correctness: Any red flags such as syntax errors, wrong method, or wrong file?',
            'If any check fails, do one more read or edit before responding.',
        ]);
    }

    public function getToolsForReasoningInstruction(): string
    {
        return implode("\n", [
            'Tools you can use when reasoning (use tools instead of guessing):',
            'Search and discovery:',
            '- Semantic search: Find code by meaning.',
            '- Grep: Exact text or regex search in files.',
            '- Glob / file find: Find files by path pattern.',
            'Read:',
            '- Read file: Open full context before editing.',
            'Edit and write:',
            '- Search-replace: Minimal targeted edits.',
            '- Write: Create or replace a file when needed.',
            '- Delete: Remove files only when required.',
            'Run and verify:',
            '- Run terminal command: tests, artisan, composer, migrations.',
            '- Read lints: check diagnostics after edits.',
            'Plan and organize:',
            '- Todo list and plan: track multi-step work.',
            'External:',
            '- Web search for docs/errors when local code is insufficient.',
            '- Ask user for clarification when scope is unclear.',
            'Use tools in sequence: search -> read -> edit -> run tests -> read lints. Do not answer from guesswork when a tool can provide evidence.',
        ]);
    }

    public function getPrimaryRoleInstruction(): string
    {
        $instruction = (string) __('projectx::lang.chat_primary_role_instruction');
        if ($instruction === 'projectx::lang.chat_primary_role_instruction') {
            return 'You are ProjectX AI Assistant. Be a practical, accurate general assistant first. Answer naturally for everyday questions.';
        }

        return $instruction;
    }

    public function getOpeningBehaviorInstruction(): string
    {
        $instruction = (string) __('projectx::lang.chat_opening_behavior_instruction');
        if ($instruction === 'projectx::lang.chat_opening_behavior_instruction') {
            return 'For simple greetings like "hi" or "hello", reply with a short friendly greeting and optionally offer help in one sentence. Avoid long capability lists unless asked.';
        }

        return $instruction;
    }

    public function getFabricScopedBehaviorInstruction(): string
    {
        $instruction = (string) __('projectx::lang.chat_fabric_scoped_behavior_instruction');
        if ($instruction === 'projectx::lang.chat_fabric_scoped_behavior_instruction') {
            return 'When Live Fabric Context is present, treat it as the source of truth for fabric-specific answers and recommendations. Outside fabric-specific questions, remain a normal general assistant.';
        }

        return $instruction;
    }

    public function getProjectReferenceInstruction(): string
    {
        if (! (bool) config('projectx.chat.project_reference_enabled', true)) {
            return '';
        }

        $configuredPath = trim((string) config('projectx.chat.project_reference_path', 'Modules/ProjectX/Resources/context/chat-project-reference.md'));
        if ($configuredPath !== '') {
            $absolutePath = $configuredPath;
            if (preg_match('/^(?:[A-Za-z]:\\\\|\/)/', $configuredPath) !== 1) {
                $absolutePath = base_path($configuredPath);
            }

            if (is_file($absolutePath)) {
                $content = trim((string) @file_get_contents($absolutePath));
                if ($content !== '') {
                    return $content;
                }
            }
        }

        return $this->getDefaultProjectReferenceInstruction();
    }

    public function getPersistentFactsInstruction(int $business_id, ?int $user_id = null): ?string
    {
        if (! (bool) config('projectx.chat.memory_enabled', true)) {
            return null;
        }

        try {
            $facts = $this->listMemoryFactsForBusiness($business_id, $user_id);
        } catch (\Throwable $exception) {
            return null;
        }

        if ($facts->isEmpty()) {
            return null;
        }

        $lines = [
            'Persistent facts:',
            'Use these business-defined facts as stable context for this response.',
        ];

        foreach ($facts as $fact) {
            $memoryKey = trim((string) $fact->memory_key);
            $memoryValue = trim((string) $fact->memory_value);
            if ($memoryKey === '' || $memoryValue === '') {
                continue;
            }

            $lines[] = '- ' . $memoryKey . ': ' . $memoryValue;
        }

        return count($lines) > 2 ? implode("\n", $lines) : null;
    }

    public function getFabricSourceOfTruthInstruction(): string
    {
        return implode("\n", [
            __('projectx::lang.chat_system_fabric_context_single_source'),
            __('projectx::lang.chat_system_conflict_use_live_data'),
        ]);
    }

    public function getResponseFormattingInstruction(bool $hasFabricContext): string
    {
        $instructions = [
            'Return clean Markdown with concise headings, bullet lists, and comparison tables when useful.',
            'Prefer readable structure over dense paragraphs.',
            'If information is uncertain, state it clearly.',
            'Never claim that data was updated in the database.',
        ];

        if ($hasFabricContext) {
            $instructions[] = __('projectx::lang.chat_format_fabric_sections');
            $instructions[] = __('projectx::lang.chat_format_fabric_update_json');
            $instructions[] = __('projectx::lang.chat_format_fabric_update_allowed_keys', [
                'keys' => implode(', ', $this->getChatFabricWritableFields()),
            ]);
        }

        return implode("\n", $instructions);
    }

    protected function formatContextNumber(float $value, int $precision = 4): string
    {
        $formatted = number_format($value, $precision, '.', '');

        return rtrim(rtrim($formatted, '0'), '.');
    }

    protected function extractFabricUpdatesFromAssistantMessage(string $content): ?array
    {
        $text = trim($content);
        if ($text === '') {
            return null;
        }

        $candidates = [];
        if (preg_match_all('/```json\s*([\s\S]*?)```/i', $text, $fencedMatches) > 0 && isset($fencedMatches[1])) {
            foreach ((array) $fencedMatches[1] as $candidate) {
                $candidate = trim((string) $candidate);
                if ($candidate === '' || stripos($candidate, '"fabric_updates"') === false) {
                    continue;
                }

                $candidates[] = $candidate;
            }
        }

        if (preg_match('/\{\s*"fabric_updates"\s*:\s*\{[\s\S]*?\}\s*\}/i', $text, $directMatch) === 1) {
            $candidates[] = trim((string) ($directMatch[0] ?? ''));
        }

        foreach ($candidates as $candidate) {
            $parsed = json_decode((string) $candidate, true);
            if (! is_array($parsed)) {
                continue;
            }

            $updates = $this->normalizeFabricUpdatesForMatching((array) ($parsed['fabric_updates'] ?? []));
            if (! empty($updates)) {
                return $updates;
            }
        }

        return null;
    }

    protected function normalizeFabricUpdatesForMatching(array $updates): array
    {
        $fieldTypes = $this->getChatFabricWritableFieldTypes();
        $allowedFields = array_keys($fieldTypes);
        $providedFields = array_keys($updates);

        $invalidFields = array_values(array_diff($providedFields, $allowedFields));
        if (! empty($invalidFields)) {
            return [];
        }

        $normalized = [];
        foreach ($allowedFields as $field) {
            if (! array_key_exists($field, $updates)) {
                continue;
            }

            $type = (string) ($fieldTypes[$field] ?? 'string');
            $value = $this->normalizeFabricUpdateValueForType($updates[$field], $type);
            if ($value === '__invalid__') {
                return [];
            }

            $normalized[$field] = $value;
        }

        return $normalized;
    }

    /**
     * @param  mixed  $value
     * @return mixed
     */
    protected function normalizeFabricUpdateValueForType($value, string $type)
    {
        if ($value === '' || $value === null) {
            return null;
        }

        if ($type === 'decimal') {
            if (! is_numeric($value)) {
                return '__invalid__';
            }

            return rtrim(rtrim(number_format((float) $value, 8, '.', ''), '0'), '.');
        }

        if ($type === 'integer') {
            if (! is_numeric($value)) {
                return '__invalid__';
            }

            $number = (float) $value;
            if ((float) ((int) $number) !== $number) {
                return '__invalid__';
            }

            return (int) $number;
        }

        if ($type === 'boolean') {
            $normalized = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($normalized === null) {
                return '__invalid__';
            }

            return (bool) $normalized;
        }

        if ($type === 'date') {
            try {
                return Carbon::parse((string) $value)->format('Y-m-d');
            } catch (\Throwable $exception) {
                return '__invalid__';
            }
        }

        return trim((string) $value);
    }

    /**
     * @return array<string, string>
     */
    protected function getChatFabricWritableFieldTypes(): array
    {
        $fieldTypes = (array) config('projectx.chat.fabric_updates.writable_field_types', []);
        $allowedTypes = ['string', 'decimal', 'integer', 'boolean', 'date'];
        $normalized = [];

        foreach ($fieldTypes as $field => $type) {
            $field = trim((string) $field);
            $type = strtolower(trim((string) $type));

            if ($field === '' || ! in_array($type, $allowedTypes, true)) {
                continue;
            }

            $normalized[$field] = $type;
        }

        return $normalized;
    }

    /**
     * @return array<int, string>
     */
    protected function getChatFabricWritableFields(): array
    {
        return array_keys($this->getChatFabricWritableFieldTypes());
    }

    public function applyPiiPolicy(string $prompt, ChatSetting $settings): array
    {
        $warnings = [];
        $blocked = false;
        $policy = (string) ($settings->pii_policy ?: 'warn');

        $hasPii = preg_match('/\b\d{3}-\d{2}-\d{4}\b/', $prompt) === 1
            || preg_match('/\b(?:\d[ -]*?){13,16}\b/', $prompt) === 1;

        if (! $hasPii || $policy === 'off') {
            return ['blocked' => false, 'warnings' => []];
        }

        if ($policy === 'block') {
            $blocked = true;
        } else {
            $warnings[] = __('projectx::lang.chat_warning_sensitive_data_detected');
        }

        return [
            'blocked' => $blocked,
            'warnings' => $warnings,
        ];
    }

    public function moderateAssistantText(string $assistantText, ChatSetting $settings): array
    {
        if (! (bool) $settings->moderation_enabled) {
            return [
                'text' => $assistantText,
                'moderated' => false,
            ];
        }

        $terms = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) $settings->moderation_terms))));
        if (empty($terms)) {
            return [
                'text' => $assistantText,
                'moderated' => false,
            ];
        }

        $moderated = false;
        $finalText = $assistantText;
        foreach ($terms as $term) {
            if ($term === '') {
                continue;
            }

            $pattern = '/' . preg_quote($term, '/') . '/i';
            if (preg_match($pattern, $finalText) === 1) {
                $moderated = true;
                $finalText = preg_replace($pattern, '[redacted]', $finalText) ?: $finalText;
            }
        }

        return [
            'text' => $finalText,
            'moderated' => $moderated,
        ];
    }

    public function isModelAllowedForBusiness(int $business_id, string $provider, string $model): bool
    {
        if (! $this->isModelValidForProvider($provider, $model)) {
            return false;
        }

        $allowlist = $this->getOrCreateBusinessSettings($business_id)->model_allowlist;
        if (! is_array($allowlist) || empty($allowlist)) {
            return true;
        }

        if (isset($allowlist[$provider]) && is_array($allowlist[$provider])) {
            return in_array($model, $allowlist[$provider], true);
        }

        if (array_values($allowlist) === $allowlist) {
            return in_array($model, $allowlist, true);
        }

        return true;
    }

    public function getCredentialStatuses(int $business_id, int $user_id): array
    {
        $statuses = [];
        foreach ($this->getProviderCatalog() as $provider => $providerConfig) {
            $userCredential = ChatCredential::forBusiness($business_id)
                ->where('provider', $provider)
                ->where('user_id', $user_id)
                ->where('is_active', true)
                ->latest('id')
                ->first();

            $businessCredential = ChatCredential::forBusiness($business_id)
                ->where('provider', $provider)
                ->whereNull('user_id')
                ->where('is_active', true)
                ->latest('id')
                ->first();

            $effective = $this->resolveCredentialForChat($user_id, $business_id, $provider);
            $effectiveSource = 'none';
            if ($effective) {
                $effectiveSource = $effective->user_id ? 'user' : 'business';
            }

            $statuses[] = [
                'provider' => $provider,
                'label' => (string) data_get($providerConfig, 'label', ucfirst($provider)),
                'has_user_key' => $userCredential !== null,
                'has_business_key' => $businessCredential !== null,
                'effective_source' => $effectiveSource,
            ];
        }

        return $statuses;
    }

    public function listMemoryFactsForBusiness(int $business_id, ?int $user_id = null)
    {
        $query = ChatMemory::forBusiness($business_id);

        if ($user_id === null) {
            $query->whereNull('user_id');
        } else {
            $query->where(function ($memoryQuery) use ($user_id) {
                $memoryQuery->whereNull('user_id')
                    ->orWhere('user_id', $user_id);
            });
        }

        return $query
            ->orderBy('memory_key')
            ->orderBy('id')
            ->get();
    }

    public function getMemoryFactByIdForBusiness(int $business_id, int $memoryId): ChatMemory
    {
        return ChatMemory::forBusiness($business_id)
            ->where('id', $memoryId)
            ->firstOrFail();
    }

    public function createMemoryFact(int $business_id, int $user_id, array $payload): ChatMemory
    {
        return ChatMemory::create([
            'business_id' => $business_id,
            'user_id' => $user_id,
            'memory_key' => trim((string) ($payload['memory_key'] ?? '')),
            'memory_value' => trim((string) ($payload['memory_value'] ?? '')),
            'created_by' => $user_id,
            'updated_by' => $user_id,
        ]);
    }

    public function updateMemoryFact(int $business_id, int $memoryId, int $user_id, array $payload): ChatMemory
    {
        $memoryFact = $this->getMemoryFactByIdForBusiness($business_id, $memoryId);

        $memoryFact->fill([
            'memory_key' => trim((string) ($payload['memory_key'] ?? '')),
            'memory_value' => trim((string) ($payload['memory_value'] ?? '')),
            'updated_by' => $user_id,
        ]);
        $memoryFact->save();

        return $memoryFact->fresh();
    }

    public function deleteMemoryFact(int $business_id, int $memoryId): bool
    {
        $memoryFact = $this->getMemoryFactByIdForBusiness($business_id, $memoryId);

        return (bool) $memoryFact->delete();
    }

    public function createShareUrl(ChatConversation $conversation, int $ttlHours): string
    {
        $hours = max(1, $ttlHours);

        return URL::temporarySignedRoute(
            'projectx.chat.shared.show',
            now()->addHours($hours),
            ['conversation' => $conversation->id]
        );
    }

    public function exportConversationAsMarkdown(ChatConversation $conversation): string
    {
        $lines = [];
        $lines[] = '# ' . ($conversation->title ?: 'Chat Conversation');
        $lines[] = '';
        $lines[] = 'Conversation ID: ' . $conversation->id;
        $lines[] = 'Updated At: ' . optional($conversation->updated_at)->toDateTimeString();
        $lines[] = '';

        $messages = ChatMessage::forBusiness((int) $conversation->business_id)
            ->where('conversation_id', $conversation->id)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        foreach ($messages as $message) {
            $role = ucfirst((string) $message->role);
            $lines[] = '## ' . $role;
            $lines[] = '';
            $lines[] = trim((string) $message->content);
            $lines[] = '';
        }

        return implode("\n", $lines);
    }

    public function getSuggestedReplies(ChatSetting $settings): array
    {
        $replies = $settings->suggested_replies;
        if (! is_array($replies) || empty($replies)) {
            $replies = (array) config('projectx.chat.suggested_replies', []);
        }

        return array_slice(array_values(array_filter(array_map('trim', $replies))), 0, 3);
    }

    public function getEffectiveRetentionDays(int $business_id): int
    {
        $settings = $this->getOrCreateBusinessSettings($business_id);
        $days = (int) ($settings->retention_days ?: config('projectx.chat.retention_days', 90));

        return max(1, $days);
    }

    public function audit(
        int $business_id,
        ?int $user_id,
        string $action,
        ?string $conversation_id = null,
        ?string $provider = null,
        ?string $model = null,
        array $metadata = []
    ): void {
        $this->chatAuditUtil->log(
            $business_id,
            $user_id,
            $action,
            $conversation_id,
            $provider,
            $model,
            $metadata
        );
    }

    public function formatIsoForUi(?Carbon $date): ?string
    {
        return $date ? $date->toIso8601String() : null;
    }

    protected function getDefaultProjectReferenceInstruction(): string
    {
        return implode("\n", [
            'Project reference (UPOS + ProjectX):',
            '- Stack: Laravel 9, PHP 8+, MySQL, Blade templates.',
            '- Multi-tenant rule: every tenant query must scope by business_id.',
            '- Business context source: request()->session()->get(\'user.business_id\').',
            '- Permissions: check auth()->user()->can(...) before create/update/delete actions.',
            '- Validation: use Form Request classes for user input.',
            '- Architecture: controllers orchestrate; business logic belongs in Util classes.',
            '- Core app uses Trezo Tailwind, but ProjectX module uses Metronic 8.3.3 (Bootstrap 5).',
            '- ProjectX assets must use asset(\'modules/projectx/...\') paths.',
            '- Important ProjectX locations: Modules/ProjectX/Http/Controllers, Modules/ProjectX/Utils, Modules/ProjectX/Resources/views, Modules/ProjectX/Routes/web.php.',
            '- Response style: concise, evidence-based, and explicit about uncertainty.',
        ]);
    }

    protected function buildConversationTitleFromText(string $text): string
    {
        $normalized = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $text)) ?? '');
        if ($normalized === '') {
            return 'New Chat';
        }

        $words = preg_split('/\s+/u', $normalized, -1, PREG_SPLIT_NO_EMPTY);
        if (! is_array($words) || empty($words)) {
            return 'New Chat';
        }

        $title = implode(' ', array_slice($words, 0, 6));
        $title = trim((string) preg_replace('/[[:punct:]\s]+$/u', '', $title));

        return mb_substr($title !== '' ? $title : 'New Chat', 0, 80);
    }

    protected function buildLastMessagePreview(string $text, int $maxWords = 6): string
    {
        $normalized = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $text)) ?? '');
        if ($normalized === '') {
            return '';
        }

        $words = preg_split('/\s+/u', $normalized, -1, PREG_SPLIT_NO_EMPTY);
        if (! is_array($words) || empty($words)) {
            return '';
        }

        $limit = max(1, $maxWords);
        $preview = implode(' ', array_slice($words, 0, $limit));
        $preview = trim((string) preg_replace('/[[:punct:]\s]+$/u', '', $preview));

        if ($preview === '') {
            return '';
        }

        if (count($words) > $limit) {
            $preview .= '...';
        }

        return mb_substr($preview, 0, 120);
    }

    protected function countWords(string $text): int
    {
        $normalized = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $text)) ?? '');
        if ($normalized === '') {
            return 0;
        }

        $words = preg_split('/\s+/u', $normalized, -1, PREG_SPLIT_NO_EMPTY);

        return is_array($words) ? count($words) : 0;
    }

    protected function findLatestQuoteForFabricContext(int $business_id, int $fabric_id): ?Quote
    {
        return Quote::forBusiness($business_id)
            ->select('projectx_quotes.id', 'projectx_quotes.transaction_id', 'projectx_quotes.confirmed_at', 'projectx_quotes.quote_date')
            ->whereHas('lines', function ($query) use ($fabric_id) {
                $query->where('fabric_id', $fabric_id);
            })
            ->orderByRaw('CASE WHEN projectx_quotes.transaction_id IS NULL THEN 1 ELSE 0 END')
            ->orderByDesc('projectx_quotes.confirmed_at')
            ->orderByDesc('projectx_quotes.quote_date')
            ->orderByDesc('projectx_quotes.id')
            ->first();
    }

    protected function isLowContextConversationTitle(?string $title): bool
    {
        $normalized = trim((string) $title);
        if ($normalized === '' || mb_strtolower($normalized) === 'new chat') {
            return true;
        }

        return $this->countWords($normalized) <= 2;
    }
}
