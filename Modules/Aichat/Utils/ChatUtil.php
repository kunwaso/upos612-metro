<?php

namespace Modules\Aichat\Utils;

use App\Business;
use App\Contact;
use App\Product;
use App\ProductQuote;
use App\Transaction;
use App\User;
use Illuminate\Support\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Modules\Aichat\Entities\ChatConversation;
use Modules\Aichat\Entities\ChatCredential;
use Modules\Aichat\Entities\ChatMemory;
use Modules\Aichat\Entities\ChatMessage;
use Modules\Aichat\Entities\ChatMessageFeedback;
use Modules\Aichat\Entities\ChatSetting;
use Modules\Aichat\Entities\PersistentMemory;
use Modules\Aichat\Entities\TelegramAllowedGroup;
use Modules\Aichat\Entities\TelegramAllowedUser;
use Modules\Aichat\Entities\TelegramBot;
use Modules\Aichat\Entities\TelegramChat;
use Modules\Aichat\Entities\TelegramLinkCode;
use Modules\Aichat\Entities\UserChatProfile;

class ChatUtil
{
    protected ChatAuditUtil $chatAuditUtil;

    protected ChatMessageRendererUtil $chatMessageRendererUtil;

    protected ChatAuthorizationPolicy $chatAuthorizationPolicy;

    protected ChatModelSerializer $chatModelSerializer;

    protected ChatIntentDomainResolver $chatIntentDomainResolver;

    protected array $capabilityEnvelopeCache = [];

    public function __construct(
        ChatAuditUtil $chatAuditUtil,
        ChatMessageRendererUtil $chatMessageRendererUtil,
        ?ChatAuthorizationPolicy $chatAuthorizationPolicy = null,
        ?ChatModelSerializer $chatModelSerializer = null,
        ?ChatIntentDomainResolver $chatIntentDomainResolver = null
    )
    {
        $this->chatAuditUtil = $chatAuditUtil;
        $this->chatMessageRendererUtil = $chatMessageRendererUtil;
        $this->chatAuthorizationPolicy = $chatAuthorizationPolicy ?: app(ChatAuthorizationPolicy::class);
        $this->chatModelSerializer = $chatModelSerializer ?: app(ChatModelSerializer::class);
        $this->chatIntentDomainResolver = $chatIntentDomainResolver ?: app(ChatIntentDomainResolver::class);
    }

    public function getProviderCatalog(): array
    {
        return (array) config('aichat.chat.providers', []);
    }

    public function getProviderModels(string $provider): array
    {
        return (array) data_get($this->getProviderCatalog(), strtolower($provider) . '.models', []);
    }

    public function isProviderSupported(string $provider): bool
    {
        return isset($this->getProviderCatalog()[strtolower($provider)]);
    }

    public function isModelValidForProvider(string $provider, string $model): bool
    {
        return isset($this->getProviderModels($provider)[$model]);
    }

    public function isChatEnabled(int $business_id): bool
    {
        if (! (bool) config('aichat.chat.enabled', true)) {
            return false;
        }

        return (bool) $this->getOrCreateBusinessSettings($business_id)->enabled;
    }

    public function getOrCreateBusinessSettings(int $business_id): ChatSetting
    {
        $defaults = [
            'enabled' => true,
            'default_provider' => config('aichat.chat.default_provider', 'openai'),
            'default_model' => config('aichat.chat.default_model', 'gpt-4o-mini'),
            'system_prompt' => null,
            'model_allowlist' => null,
            'retention_days' => (int) config('aichat.chat.retention_days', 90),
            'pii_policy' => config('aichat.chat.pii_policy', 'warn'),
            'moderation_enabled' => (bool) config('aichat.chat.moderation_enabled', false),
            'moderation_terms' => implode("\n", (array) config('aichat.chat.moderation_terms', [])),
            'idle_timeout_minutes' => (int) config('aichat.chat.idle_timeout_minutes', 30),
            'suggested_replies' => (array) config('aichat.chat.suggested_replies', []),
            'share_ttl_hours' => (int) config('aichat.chat.share_ttl_hours', 168),
        ];

        return ChatSetting::firstOrCreate(['business_id' => $business_id], $defaults);
    }

    public function updateBusinessSettings(int $business_id, array $data): ChatSetting
    {
        $settings = $this->getOrCreateBusinessSettings($business_id);

        if (array_key_exists('model_allowlist', $data) && is_string($data['model_allowlist'])) {
            $value = trim($data['model_allowlist']);
            if ($value === '') {
                $data['model_allowlist'] = null;
            } else {
                $decoded = json_decode($value, true);
                if (! is_array($decoded)) {
                    throw new \InvalidArgumentException(__('aichat::lang.chat_validation_model_invalid'));
                }
                $data['model_allowlist'] = $decoded;
            }
        }

        if (array_key_exists('suggested_replies', $data) && is_string($data['suggested_replies'])) {
            $data['suggested_replies'] = $this->normalizeLines($data['suggested_replies']);
        }

        if (array_key_exists('moderation_terms', $data) && is_string($data['moderation_terms'])) {
            $data['moderation_terms'] = implode("\n", $this->normalizeLines($data['moderation_terms']));
        }

        $provider = (string) ($data['default_provider'] ?? $settings->default_provider ?? config('aichat.chat.default_provider', 'openai'));
        $model = (string) ($data['default_model'] ?? $settings->default_model ?? config('aichat.chat.default_model', 'gpt-4o-mini'));
        if ($provider !== '' && $model !== '' && ! $this->isModelValidForProvider($provider, $model)) {
            throw new \InvalidArgumentException(__('aichat::lang.chat_validation_model_invalid'));
        }

        $settings->fill($data);
        $settings->save();

        return $settings->fresh();
    }

    public function getOrCreateUserChatProfile(int $business_id, int $user_id): UserChatProfile
    {
        return UserChatProfile::firstOrCreate(
            ['business_id' => $business_id, 'user_id' => $user_id],
            [
                'display_name' => null,
                'timezone' => null,
                'concerns_topics' => null,
                'preferences' => null,
            ]
        );
    }

    public function buildUserProfileContext(int $business_id, int $user_id): string
    {
        $profile = UserChatProfile::forUser($business_id, $user_id)->first();
        if (! $profile) {
            return '';
        }

        $displayName = trim((string) ($profile->display_name ?? ''));
        $timezone = trim((string) ($profile->timezone ?? ''));
        $concernsTopics = trim((string) ($profile->concerns_topics ?? ''));
        $preferences = trim((string) ($profile->preferences ?? ''));

        $lines = [];
        if ($displayName !== '') {
            $lines[] = '- Display name: ' . $displayName;
        }
        if ($timezone !== '') {
            $lines[] = '- Timezone: ' . $timezone;
        }
        if ($concernsTopics !== '') {
            $lines[] = '- Concerns/topics: ' . $concernsTopics;
        }
        if ($preferences !== '') {
            $lines[] = '- Preferences: ' . $preferences;
        }

        return empty($lines) ? '' : implode("\n", $lines);
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

    public function getCredentialStatuses(int $business_id, int $user_id): array
    {
        $statuses = [];
        foreach ($this->getProviderCatalog() as $provider => $config) {
            $statuses[] = [
                'provider' => $provider,
                'label' => (string) ($config['label'] ?? ucfirst($provider)),
                'has_user_key' => ChatCredential::forBusiness($business_id)->where('provider', $provider)->where('user_id', $user_id)->where('is_active', true)->exists(),
                'has_business_key' => ChatCredential::forBusiness($business_id)->where('provider', $provider)->whereNull('user_id')->where('is_active', true)->exists(),
            ];
        }

        return $statuses;
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
            throw new \RuntimeException(__('aichat::lang.chat_provider_invalid_key_cipher'));
        }
    }

    public function encryptBotToken(string $plain): string
    {
        return Crypt::encryptString($plain);
    }

    public function decryptBotToken(string $encrypted): string
    {
        try {
            return Crypt::decryptString($encrypted);
        } catch (\Throwable $exception) {
            throw new \RuntimeException(__('aichat::lang.telegram_bot_token_invalid_cipher'));
        }
    }

    public function getDecryptedBotToken(TelegramBot $bot): string
    {
        return $this->decryptBotToken((string) $bot->encrypted_bot_token);
    }

    public function findTelegramBotByWebhookKey(string $webhookKey): ?TelegramBot
    {
        return TelegramBot::forWebhookKey($webhookKey)->first();
    }

    public function getTelegramBotForBusiness(int $business_id): ?TelegramBot
    {
        return TelegramBot::forBusiness($business_id)->first();
    }

    public function buildTelegramWebhookUrl(string $webhookKey): string
    {
        $webhookBaseUrl = trim((string) config('aichat.telegram.webhook_base_url', ''));
        if ($webhookBaseUrl !== '') {
            $path = route('aichat.telegram.webhook', ['webhookKey' => $webhookKey], false);

            return rtrim($webhookBaseUrl, '/') . '/' . ltrim($path, '/');
        }

        return route('aichat.telegram.webhook', ['webhookKey' => $webhookKey], true);
    }

    public function getTelegramWebhookUrlForBusiness(int $business_id): ?string
    {
        $bot = $this->getTelegramBotForBusiness($business_id);

        if (! $bot) {
            return null;
        }

        return $this->buildTelegramWebhookUrl((string) $bot->webhook_key);
    }

    public function saveTelegramBot(int $business_id, int $linked_user_id, string $botToken): TelegramBot
    {
        $normalizedToken = trim($botToken);
        if ($normalizedToken === '') {
            throw new \InvalidArgumentException(__('aichat::lang.telegram_bot_token_required'));
        }

        return DB::transaction(function () use ($business_id, $linked_user_id, $normalizedToken) {
            $bot = TelegramBot::forBusiness($business_id)->first();
            if (! $bot) {
                $bot = new TelegramBot();
                $bot->business_id = $business_id;
            }

            $bot->linked_user_id = $linked_user_id;
            $bot->webhook_key = $this->generateUniqueTelegramWebhookKey();
            $bot->webhook_secret_token = Str::random(48);
            $bot->encrypted_bot_token = $this->encryptBotToken($normalizedToken);
            $bot->save();

            return $bot->fresh();
        });
    }

    public function deleteTelegramBot(int $business_id): bool
    {
        $deleted = TelegramBot::forBusiness($business_id)->delete();

        return $deleted > 0;
    }

    public function getTelegramAllowedUserIds(int $business_id): array
    {
        return TelegramAllowedUser::forBusiness($business_id)
            ->pluck('user_id')
            ->map(function ($userId) {
                return (int) $userId;
            })
            ->values()
            ->all();
    }

    public function getTelegramAllowedUsers(int $business_id)
    {
        return TelegramAllowedUser::forBusiness($business_id)
            ->with(['user' => function ($query) {
                $query->select('id', 'surname', 'first_name', 'last_name', 'username');
            }])
            ->orderBy('id')
            ->get();
    }

    public function syncTelegramAllowedUsers(int $business_id, array $user_ids): void
    {
        $normalizedUserIds = collect($user_ids)
            ->map(function ($id) {
                return (int) $id;
            })
            ->filter(function ($id) {
                return $id > 0;
            })
            ->unique()
            ->values()
            ->all();

        if (! empty($normalizedUserIds)) {
            $validUserIds = User::query()
                ->where('business_id', $business_id)
                ->whereIn('id', $normalizedUserIds)
                ->pluck('id')
                ->map(function ($id) {
                    return (int) $id;
                })
                ->all();

            sort($validUserIds);
            $idsToValidate = $normalizedUserIds;
            sort($idsToValidate);

            if ($validUserIds !== $idsToValidate) {
                throw new \InvalidArgumentException(__('aichat::lang.telegram_allowed_users_invalid'));
            }
        }

        DB::transaction(function () use ($business_id, $normalizedUserIds) {
            TelegramAllowedUser::forBusiness($business_id)->delete();

            $timestamp = now();
            foreach ($normalizedUserIds as $userId) {
                TelegramAllowedUser::create([
                    'business_id' => $business_id,
                    'user_id' => $userId,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ]);
            }
        });
    }

    public function isUserAllowedForTelegram(int $business_id, int $user_id): bool
    {
        return TelegramAllowedUser::forBusiness($business_id)->where('user_id', $user_id)->exists();
    }

    public function createTelegramLinkCode(int $business_id, int $user_id): string
    {
        if (! $this->isUserAllowedForTelegram($business_id, $user_id)) {
            throw new \InvalidArgumentException(__('aichat::lang.telegram_link_user_not_allowed'));
        }

        TelegramLinkCode::forBusiness($business_id)->where('user_id', $user_id)->delete();

        $attempts = 0;
        while ($attempts < 10) {
            $attempts++;
            $code = $this->generateUniqueTelegramLinkCode();

            try {
                TelegramLinkCode::create([
                    'business_id' => $business_id,
                    'user_id' => $user_id,
                    'code' => $code,
                    'expires_at' => now()->addMinutes(10),
                ]);

                return $code;
            } catch (QueryException $exception) {
                $sqlState = (string) $exception->getCode();
                if ($sqlState !== '23000') {
                    throw $exception;
                }
            }
        }

        throw new \RuntimeException(__('aichat::lang.telegram_link_code_generation_failed'));
    }

    public function getActiveTelegramLinkCode(int $business_id, int $user_id): ?string
    {
        if (! $this->isUserAllowedForTelegram($business_id, $user_id)) {
            return null;
        }

        $linkCode = TelegramLinkCode::forBusiness($business_id)
            ->where('user_id', $user_id)
            ->notExpired()
            ->latest('id')
            ->first();

        return $linkCode ? (string) $linkCode->code : null;
    }

    public function getOrCreateTelegramLinkCode(int $business_id, int $user_id): string
    {
        $activeCode = $this->getActiveTelegramLinkCode($business_id, $user_id);
        if ($activeCode !== null && $activeCode !== '') {
            return $activeCode;
        }

        return $this->createTelegramLinkCode($business_id, $user_id);
    }

    public function consumeTelegramLinkCode(int $business_id, string $code, int $telegram_chat_id): ?TelegramChat
    {
        $normalizedCode = strtoupper(trim($code));
        if ($normalizedCode === '') {
            return null;
        }

        return DB::transaction(function () use ($business_id, $normalizedCode, $telegram_chat_id) {
            $linkCode = TelegramLinkCode::forBusiness($business_id)
                ->where('code', $normalizedCode)
                ->notExpired()
                ->lockForUpdate()
                ->first();

            if (! $linkCode) {
                return null;
            }

            $linkedUserId = (int) $linkCode->user_id;
            if (! $this->isUserAllowedForTelegram($business_id, $linkedUserId)) {
                $linkCode->delete();

                return null;
            }

            $telegramChat = TelegramChat::forBusiness($business_id)
                ->forTelegramChatId($telegram_chat_id)
                ->lockForUpdate()
                ->first();

            if ($telegramChat) {
                $conversation = ChatConversation::forBusiness($business_id)->find($telegramChat->conversation_id);
                if (! $conversation || (int) $telegramChat->user_id !== $linkedUserId) {
                    $conversation = $this->createConversation($business_id, $linkedUserId, 'Telegram');
                    $telegramChat->conversation_id = (string) $conversation->id;
                }

                $telegramChat->user_id = $linkedUserId;
                $telegramChat->save();
            } else {
                $conversation = $this->createConversation($business_id, $linkedUserId, 'Telegram');
                $telegramChat = TelegramChat::create([
                    'business_id' => $business_id,
                    'telegram_chat_id' => $telegram_chat_id,
                    'conversation_id' => (string) $conversation->id,
                    'user_id' => $linkedUserId,
                ]);
            }

            $linkCode->delete();

            return $telegramChat->fresh();
        });
    }

    public function getTelegramChatForBusiness(int $business_id, int $telegram_chat_id): ?TelegramChat
    {
        return TelegramChat::forBusiness($business_id)->forTelegramChatId($telegram_chat_id)->first();
    }

    public function getOrCreateTelegramConversation(int $business_id, int $telegram_chat_id, int $user_id): ChatConversation
    {
        return DB::transaction(function () use ($business_id, $telegram_chat_id, $user_id) {
            $telegramChat = TelegramChat::forBusiness($business_id)
                ->forTelegramChatId($telegram_chat_id)
                ->lockForUpdate()
                ->first();

            if ($telegramChat) {
                $conversation = ChatConversation::forBusiness($business_id)->find((string) $telegramChat->conversation_id);
                if ($conversation) {
                    if ((int) $telegramChat->user_id !== $user_id) {
                        $telegramChat->user_id = $user_id;
                        $telegramChat->save();
                    }

                    return $conversation;
                }

                $conversation = $this->createConversation($business_id, $user_id, 'Telegram');
                $telegramChat->conversation_id = (string) $conversation->id;
                $telegramChat->user_id = $user_id;
                $telegramChat->save();

                return $conversation;
            }

            $conversation = $this->createConversation($business_id, $user_id, 'Telegram');
            TelegramChat::create([
                'business_id' => $business_id,
                'telegram_chat_id' => $telegram_chat_id,
                'conversation_id' => (string) $conversation->id,
                'user_id' => $user_id,
            ]);

            return $conversation;
        });
    }

    public function getOrCreateTelegramGroupConversation(int $business_id, int $telegram_chat_id, int $owner_user_id): ChatConversation
    {
        return $this->getOrCreateTelegramConversation($business_id, $telegram_chat_id, $owner_user_id);
    }

    public function resetTelegramConversation(int $business_id, int $telegram_chat_id, int $user_id): ChatConversation
    {
        return DB::transaction(function () use ($business_id, $telegram_chat_id, $user_id) {
            $conversation = $this->createConversation($business_id, $user_id, 'Telegram');

            $telegramChat = TelegramChat::forBusiness($business_id)
                ->forTelegramChatId($telegram_chat_id)
                ->lockForUpdate()
                ->first();

            if ($telegramChat) {
                $telegramChat->conversation_id = (string) $conversation->id;
                $telegramChat->user_id = $user_id;
                $telegramChat->save();
            } else {
                TelegramChat::create([
                    'business_id' => $business_id,
                    'telegram_chat_id' => $telegram_chat_id,
                    'conversation_id' => (string) $conversation->id,
                    'user_id' => $user_id,
                ]);
            }

            return $conversation;
        });
    }

    public function getTelegramAllowedGroups(int $business_id): array
    {
        return TelegramAllowedGroup::forBusiness($business_id)
            ->orderBy('id')
            ->get(['telegram_chat_id', 'title'])
            ->map(function (TelegramAllowedGroup $group) {
                return [
                    'telegram_chat_id' => (int) $group->telegram_chat_id,
                    'title' => $group->title !== null ? (string) $group->title : null,
                ];
            })
            ->values()
            ->all();
    }

    public function isGroupAllowedForTelegram(int $business_id, int $telegram_chat_id): bool
    {
        return TelegramAllowedGroup::forBusiness($business_id)
            ->where('telegram_chat_id', $telegram_chat_id)
            ->exists();
    }

    public function addTelegramAllowedGroup(int $business_id, int $telegram_chat_id, ?string $title = null): TelegramAllowedGroup
    {
        return TelegramAllowedGroup::updateOrCreate(
            ['business_id' => $business_id, 'telegram_chat_id' => $telegram_chat_id],
            ['title' => $title !== null ? trim($title) : null]
        );
    }

    public function removeTelegramAllowedGroup(int $business_id, int $telegram_chat_id): bool
    {
        $deleted = TelegramAllowedGroup::forBusiness($business_id)->where('telegram_chat_id', $telegram_chat_id)->delete();

        return $deleted > 0;
    }

    public function splitTelegramMessage(string $text, int $limit = 4096): array
    {
        $normalized = trim($text);
        if ($normalized === '') {
            return [__('aichat::lang.chat_provider_empty_response')];
        }

        if (mb_strlen($normalized) <= $limit) {
            return [$normalized];
        }

        $chunks = [];
        $currentChunk = '';
        $lines = preg_split('/\R/u', $normalized) ?: [$normalized];

        foreach ($lines as $line) {
            $candidate = $currentChunk === '' ? $line : $currentChunk . "\n" . $line;
            if (mb_strlen($candidate) <= $limit) {
                $currentChunk = $candidate;
                continue;
            }

            if ($currentChunk !== '') {
                $chunks[] = $currentChunk;
                $currentChunk = '';
            }

            while (mb_strlen($line) > $limit) {
                $chunks[] = mb_substr($line, 0, $limit);
                $line = mb_substr($line, $limit);
            }

            $currentChunk = $line;
        }

        if ($currentChunk !== '') {
            $chunks[] = $currentChunk;
        }

        return ! empty($chunks) ? $chunks : [mb_substr($normalized, 0, $limit)];
    }

    public function normalizeTelegramOutboundText(string $text): string
    {
        $normalized = str_replace(["\r\n", "\r"], "\n", trim($text));
        if ($normalized === '') {
            return __('aichat::lang.chat_provider_empty_response');
        }

        $lines = preg_split('/\n/u', $normalized) ?: [$normalized];
        $cleanedLines = [];

        foreach ($lines as $line) {
            $cleanedLine = trim($line);
            if ($cleanedLine === '') {
                $cleanedLines[] = '';
                continue;
            }

            $cleanedLine = preg_replace('/^\s{0,3}#{1,6}\s+/u', '', $cleanedLine) ?? $cleanedLine;
            $cleanedLine = preg_replace('/^\s*>\s?/u', '', $cleanedLine) ?? $cleanedLine;
            $cleanedLine = preg_replace('/^\s*[\*\-\+•]\s+/u', '- ', $cleanedLine) ?? $cleanedLine;
            $cleanedLine = preg_replace('/\[(.*?)\]\((https?:\/\/[^\s)]+)\)/u', '$1 ($2)', $cleanedLine) ?? $cleanedLine;

            $cleanedLine = str_replace(['***', '___', '**', '__', '`'], '', $cleanedLine);
            $cleanedLine = preg_replace('/\*([^*\n]+)\*/u', '$1', $cleanedLine) ?? $cleanedLine;
            $cleanedLine = preg_replace('/_([^_\n]+)_/u', '$1', $cleanedLine) ?? $cleanedLine;
            $cleanedLine = preg_replace('/[ \t]{2,}/u', ' ', $cleanedLine) ?? $cleanedLine;

            $cleanedLines[] = trim($cleanedLine);
        }

        $cleaned = implode("\n", $cleanedLines);
        $cleaned = preg_replace("/\n{3,}/u", "\n\n", $cleaned) ?? $cleaned;
        $cleaned = trim($cleaned);

        return $cleaned !== '' ? $cleaned : __('aichat::lang.chat_provider_empty_response');
    }

    public function listConversationsForUser(int $business_id, int $user_id, bool $include_archived = false, ?int $fabric_id = null)
    {
        $query = ChatConversation::forBusiness($business_id)->forUser($user_id)->orderByDesc('updated_at');

        if (! $include_archived) {
            $query->where('is_archived', false);
        }

        return $query->get();
    }

    public function getConversationByIdForUser(int $business_id, int $user_id, string $conversation_id): ChatConversation
    {
        return ChatConversation::forBusiness($business_id)->forUser($user_id)->findOrFail($conversation_id);
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
        $id = ChatMessage::forBusiness($business_id)->where('conversation_id', $conversationId)->where('role', ChatMessage::ROLE_ASSISTANT)->max('id');

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
        $conversation = ChatConversation::forBusiness($business_id)->forUser($user_id)->where('is_archived', false)->latest('updated_at')->first();

        return $conversation ?: $this->createConversation($business_id, $user_id);
    }

    public function createConversation(int $business_id, int $user_id, ?string $title = null, ?int $fabric_id = null): ChatConversation
    {
        return ChatConversation::create([
            'business_id' => $business_id,
            'user_id' => $user_id,
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

            if ($userMessageCount <= 1 || $this->isLowContextConversationTitle((string) $conversation->title)) {
                $updates['title'] = $this->buildConversationTitleFromText($content);
            }
        } elseif ($role === ChatMessage::ROLE_ASSISTANT && $this->isLowContextConversationTitle((string) $conversation->title)) {
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

    public function saveMessageFeedback(int $business_id, int $user_id, ChatMessage $message, string $feedback, ?string $note = null): ChatMessageFeedback
    {
        return ChatMessageFeedback::updateOrCreate(
            ['message_id' => (int) $message->id, 'user_id' => $user_id],
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
        return (bool) $this->getConversationByIdForUser($business_id, $user_id, $conversation_id)->delete();
    }

    public function serializeConversation(ChatConversation $conversation): array
    {
        return [
            'id' => $conversation->id,
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
        return [
            'id' => (int) $message->id,
            'conversation_id' => $message->conversation_id,
            'role' => $message->role,
            'content' => (string) $message->content,
            'content_html' => $this->chatMessageRendererUtil->renderForRole((string) $message->content, (string) $message->role),
            'provider' => $message->provider,
            'model' => $message->model,
            'feedback_value' => $feedbackValue,
            'can_regenerate' => $message->role === ChatMessage::ROLE_ASSISTANT
                && $latestAssistantMessageId !== null
                && (int) $message->id === (int) $latestAssistantMessageId,
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

        $settings = $this->getOrCreateBusinessSettings($business_id);
        $defaultProvider = (string) ($settings->default_provider ?: config('aichat.chat.default_provider', 'openai'));
        if (! in_array($defaultProvider, $enabledProviders, true)) {
            $defaultProvider = $enabledProviders[0] ?? 'openai';
        }

        $defaultModel = (string) ($settings->default_model ?: config('aichat.chat.default_model', 'gpt-4o-mini'));
        if (! $this->isModelAllowedForBusiness($business_id, $defaultProvider, $defaultModel)) {
            $firstAllowed = collect($modelOptions)->first(function ($option) use ($defaultProvider) {
                return $option['provider'] === $defaultProvider;
            });
            $defaultModel = (string) ($firstAllowed['model_id'] ?? $defaultModel);
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
        $capabilityEnvelope = $this->resolveCapabilityEnvelope($business_id, $user_id, 'web');
        $capabilities = (array) data_get($capabilityEnvelope, 'domains', []);
        $capabilities['_meta'] = [
            'business_id' => $business_id,
            'user_id' => data_get($capabilityEnvelope, 'actor.user_id'),
            'channel' => (string) data_get($capabilityEnvelope, 'actor.channel', 'web'),
        ];

        return [
            'enabled' => $this->isChatEnabled($business_id),
            'capabilities' => $capabilities,
            'capability_envelope' => $capabilityEnvelope,
            'permissions' => [
                'can_edit' => auth()->check() && auth()->user()->can('aichat.chat.edit'),
                'can_manage_settings' => auth()->check() && auth()->user()->can('aichat.chat.settings'),
                'can_use_quote_wizard' => auth()->check() && auth()->user()->can('aichat.quote_wizard.use'),
            ],
            'features' => [
                'workflow_profile' => (string) config('aichat.chat.workflow_profile', 'root_org_context_v1'),
                'general_first_mode' => (bool) config('aichat.chat.general_first_mode', true),
                'memory_enabled' => (bool) config('aichat.chat.memory_enabled', true),
                'quote_wizard_enabled' => (bool) config('aichat.quote_wizard.enabled', true),
                'actions_enabled' => (bool) config('aichat.actions.enabled', false),
            ],
            'enabled_providers' => $modelOptions['enabled_providers'],
            'model_options' => $modelOptions['model_options'],
            'default_provider' => $modelOptions['default_provider'],
            'default_model' => $modelOptions['default_model'],
            'share_ttl_hours' => (int) $settings->share_ttl_hours,
            'routes' => [
                'list_url' => route('aichat.chat.conversations.index'),
                'create_url' => route('aichat.chat.conversations.store'),
                'destroy_url_template' => route('aichat.chat.conversations.destroy', ['id' => '__CONVERSATION_ID__']),
                'conversation_url_template' => route('aichat.chat.conversations.show', ['id' => '__CONVERSATION_ID__']),
                'send_url_template' => route('aichat.chat.conversations.send', ['id' => '__CONVERSATION_ID__']),
                'stream_url_template' => route('aichat.chat.conversations.stream', ['id' => '__CONVERSATION_ID__']),
                'share_url_template' => route('aichat.chat.conversations.share', ['id' => '__CONVERSATION_ID__']),
                'export_url_template' => route('aichat.chat.conversations.export', ['id' => '__CONVERSATION_ID__']),
                'feedback_url_template' => route('aichat.chat.messages.feedback.store', ['message' => '__MESSAGE_ID__']),
                'regenerate_url_template' => route('aichat.chat.messages.regenerate', ['message' => '__MESSAGE_ID__']),
                'config_url' => route('aichat.chat.config'),
                'settings_url' => route('aichat.chat.settings'),
                'quote_wizard_contacts_url_template' => route('aichat.chat.conversations.quote_wizard.contacts', ['id' => '__CONVERSATION_ID__']),
                'quote_wizard_locations_url_template' => route('aichat.chat.conversations.quote_wizard.locations', ['id' => '__CONVERSATION_ID__']),
                'quote_wizard_products_url_template' => route('aichat.chat.conversations.quote_wizard.products', ['id' => '__CONVERSATION_ID__']),
                'quote_wizard_costing_defaults_url_template' => route('aichat.chat.conversations.quote_wizard.costing_defaults', ['id' => '__CONVERSATION_ID__']),
                'quote_wizard_process_url_template' => route('aichat.chat.conversations.quote_wizard.process', ['id' => '__CONVERSATION_ID__']),
                'quote_wizard_confirm_url_template' => route('aichat.chat.conversations.quote_wizard.confirm', ['id' => '__CONVERSATION_ID__']),
                'actions_prepare_url_template' => route('aichat.chat.conversations.actions.prepare', ['id' => '__CONVERSATION_ID__']),
                'actions_confirm_url_template' => route('aichat.chat.conversations.actions.confirm', ['id' => '__CONVERSATION_ID__', 'actionId' => '__ACTION_ID__']),
                'actions_cancel_url_template' => route('aichat.chat.conversations.actions.cancel', ['id' => '__CONVERSATION_ID__', 'actionId' => '__ACTION_ID__']),
                'actions_pending_url_template' => route('aichat.chat.conversations.actions.pending', ['id' => '__CONVERSATION_ID__']),
            ],
            'i18n' => [
                'new_chat' => __('aichat::lang.new_chat'),
                'chat_no_conversations' => __('aichat::lang.chat_no_conversations'),
                'chat_delete_conversation' => __('aichat::lang.chat_delete_conversation'),
                'chat_delete_confirm' => __('aichat::lang.chat_delete_confirm'),
                'chat_delete_success' => __('aichat::lang.chat_delete_success'),
                'chat_conversation_not_found' => __('aichat::lang.chat_conversation_not_found'),
                'chat_provider_error' => __('aichat::lang.chat_provider_error'),
                'chat_missing_provider_key' => __('aichat::lang.chat_missing_provider_key'),
                'chat_warning_sensitive_data_detected' => __('aichat::lang.chat_warning_sensitive_data_detected'),
                'chat_blocked_sensitive_data' => __('aichat::lang.chat_blocked_sensitive_data'),
                'chat_regenerate_latest_only' => __('aichat::lang.chat_regenerate_latest_only'),
                'chat_feedback_saved' => __('aichat::lang.chat_feedback_saved'),
                'chat_copied' => __('aichat::lang.chat_copied'),
                'chat_share' => __('aichat::lang.chat_share'),
                'chat_export_markdown' => __('aichat::lang.chat_export_markdown'),
                'chat_export_pdf' => __('aichat::lang.chat_export_pdf'),
                'send_message' => __('aichat::lang.send_message'),
                'type_message' => __('aichat::lang.type_message'),
                'quote_assistant' => __('aichat::lang.quote_assistant'),
                'quote_assistant_ready' => __('aichat::lang.quote_assistant_ready'),
                'quote_assistant_confirm' => __('aichat::lang.quote_assistant_confirm'),
                'quote_assistant_open_public' => __('aichat::lang.quote_assistant_open_public'),
                'quote_assistant_open_admin' => __('aichat::lang.quote_assistant_open_admin'),
                'quote_assistant_missing' => __('aichat::lang.quote_assistant_missing'),
                'quote_assistant_fill_missing' => __('aichat::lang.quote_assistant_fill_missing'),
                'quote_assistant_fill_modal_title' => __('aichat::lang.quote_assistant_fill_modal_title'),
                'quote_assistant_fill_modal_help' => __('aichat::lang.quote_assistant_fill_modal_help'),
                'quote_assistant_fill_modal_placeholder' => __('aichat::lang.quote_assistant_fill_modal_placeholder'),
                'quote_assistant_fill_modal_submit' => __('aichat::lang.quote_assistant_fill_modal_submit'),
                'quote_assistant_fill_modal_cancel' => __('aichat::lang.quote_assistant_fill_modal_cancel'),
                'quote_assistant_fill_modal_error_required' => __('aichat::lang.quote_assistant_fill_modal_error_required'),
                'quote_assistant_fill_modal_unavailable' => __('aichat::lang.quote_assistant_fill_modal_unavailable'),
                'quote_assistant_remove_line' => __('aichat::lang.quote_assistant_remove_line'),
                'quote_assistant_pick_customer' => __('aichat::lang.quote_assistant_pick_customer'),
                'quote_assistant_pick_product' => __('aichat::lang.quote_assistant_pick_product'),
                'quote_assistant_current_summary' => __('aichat::lang.quote_assistant_current_summary'),
                'quote_assistant_customer_required' => __('aichat::lang.quote_assistant_customer_required'),
                'quote_assistant_location_required' => __('aichat::lang.quote_assistant_location_required'),
                'quote_assistant_expires_label' => __('aichat::lang.quote_assistant_expires_label'),
                'quote_assistant_summary_empty' => __('aichat::lang.quote_assistant_summary_empty'),
                'quote_assistant_mode_on' => __('aichat::lang.quote_assistant_mode_on'),
                'quote_assistant_mode_off' => __('aichat::lang.quote_assistant_mode_off'),
                'chat_action_prepared' => __('aichat::lang.chat_action_prepared'),
                'chat_action_executed' => __('aichat::lang.chat_action_executed'),
                'chat_action_cancelled' => __('aichat::lang.chat_action_cancelled'),
                'chat_action_not_found' => __('aichat::lang.chat_action_not_found'),
                'chat_action_forbidden' => __('aichat::lang.chat_action_forbidden'),
                'chat_action_invalid_payload' => __('aichat::lang.chat_action_invalid_payload'),
                'chat_action_pending_empty' => __('aichat::lang.chat_action_pending_empty'),
                'chat_action_pending_title' => __('aichat::lang.chat_action_pending_title'),
                'chat_action_pending_hint' => __('aichat::lang.chat_action_pending_hint'),
                'chat_action_pending_prepare_hint' => __('aichat::lang.chat_action_pending_prepare_hint'),
                'chat_action_pending_more' => __('aichat::lang.chat_action_pending_more'),
                'chat_action_toolbar_pending' => __('aichat::lang.chat_action_toolbar_pending'),
                'chat_action_toolbar_confirm_latest' => __('aichat::lang.chat_action_toolbar_confirm_latest'),
                'chat_action_toolbar_cancel_latest' => __('aichat::lang.chat_action_toolbar_cancel_latest'),
                'chat_action_queue_title' => __('aichat::lang.chat_action_queue_title'),
                'chat_action_queue_empty' => __('aichat::lang.chat_action_queue_empty'),
                'chat_action_queue_loading' => __('aichat::lang.chat_action_queue_loading'),
                'chat_action_confirm_button' => __('aichat::lang.chat_action_confirm_button'),
                'chat_action_cancel_button' => __('aichat::lang.chat_action_cancel_button'),
                'chat_action_executed_hint' => __('aichat::lang.chat_action_executed_hint'),
                'chat_action_complete_hint' => __('aichat::lang.chat_action_complete_hint'),
                'chat_action_label' => __('aichat::lang.chat_action_label'),
                'chat_action_command_help' => __('aichat::lang.chat_action_command_help'),
                'chat_action_prepare_usage' => __('aichat::lang.chat_action_prepare_usage'),
                'chat_action_confirm_usage' => __('aichat::lang.chat_action_confirm_usage'),
                'chat_action_cancel_usage' => __('aichat::lang.chat_action_cancel_usage'),
                'chat_action_natural_language_hint' => __('aichat::lang.chat_action_natural_language_hint'),
            ],
        ];
    }

    public function resolveCapabilityEnvelope(
        int $business_id,
        ?int $user_id = null,
        string $channel = 'web',
        ?User $actor = null,
        bool $refresh = false
    ): array {
        $normalizedChannel = trim($channel) !== '' ? strtolower(trim($channel)) : 'web';
        $cacheKey = $business_id . '|' . (string) ($user_id ?? 'guest') . '|' . $normalizedChannel;
        if (! $refresh && isset($this->capabilityEnvelopeCache[$cacheKey])) {
            return $this->capabilityEnvelopeCache[$cacheKey];
        }

        $resolvedActor = $this->chatAuthorizationPolicy->resolveActorForBusiness($business_id, $user_id, $actor);
        $envelope = app(ChatCapabilityResolver::class)->resolveForActor($resolvedActor, $business_id, $normalizedChannel);

        $this->capabilityEnvelopeCache[$cacheKey] = $envelope;

        return $envelope;
    }

    public function resolveChatCapabilities(int $business_id, ?int $user_id = null): array
    {
        $envelope = $this->resolveCapabilityEnvelope($business_id, $user_id, 'web');
        $domains = (array) data_get($envelope, 'domains', []);
        $domains['_meta'] = [
            'business_id' => $business_id,
            'user_id' => data_get($envelope, 'actor.user_id'),
            'channel' => (string) data_get($envelope, 'actor.channel', 'web'),
        ];
        $domains['_envelope'] = $envelope;

        return $domains;
    }

    public function isModelAllowedForBusiness(int $business_id, string $provider, string $model): bool
    {
        if (! $this->isProviderSupported($provider) || ! $this->isModelValidForProvider($provider, $model)) {
            return false;
        }

        $allowlist = (array) ($this->getOrCreateBusinessSettings($business_id)->model_allowlist ?? []);
        if (empty($allowlist)) {
            return true;
        }

        if (isset($allowlist[$provider]) && is_array($allowlist[$provider])) {
            return in_array($model, $allowlist[$provider], true);
        }

        return in_array($model, $allowlist, true) || in_array($provider . ':' . $model, $allowlist, true);
    }

    public function applyPiiPolicy(string $prompt, ChatSetting $settings): array
    {
        $policy = strtolower((string) ($settings->pii_policy ?: config('aichat.chat.pii_policy', 'warn')));
        if (! in_array($policy, ['off', 'warn', 'block'], true)) {
            $policy = 'warn';
        }

        $text = $prompt;
        $detected = false;

        $emailCount = 0;
        $text = preg_replace('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i', (string) config('aichat.chat.pii_replacement_map.email', '[redacted email]'), $text, -1, $emailCount);
        $detected = $detected || $emailCount > 0;

        $phoneCount = 0;
        $text = preg_replace('/(?<!\w)(?:\+?\d[\d\-\s().]{7,}\d)(?!\w)/', (string) config('aichat.chat.pii_replacement_map.phone', '[redacted phone]'), $text, -1, $phoneCount);
        $detected = $detected || $phoneCount > 0;

        $secretPattern = '/(?P<label>\b(?:password|passwd|pwd|secret|api[_ -]?key|access[_ -]?token|refresh[_ -]?token|auth[_ -]?token|authorization|bearer|session(?:[_ -]?id)?)\b\s*[:=]\s*)(?P<value>[^\s,;]+)/i';
        $text = preg_replace_callback($secretPattern, function ($matches) use (&$detected) {
            $detected = true;
            $label = strtolower((string) ($matches['label'] ?? ''));
            $replacement = str_contains($label, 'pass')
                ? (string) config('aichat.chat.pii_replacement_map.password', '[redacted password]')
                : (string) config('aichat.chat.pii_replacement_map.auth', '[redacted auth secret]');

            return (string) $matches['label'] . $replacement;
        }, $text);

        $jwtCount = 0;
        $text = preg_replace('/(?:Bearer\s+)?eyJ[A-Za-z0-9_\-]+\.[A-Za-z0-9._\-]+\.[A-Za-z0-9._\-]+/', (string) config('aichat.chat.pii_replacement_map.auth', '[redacted auth secret]'), $text, -1, $jwtCount);
        $detected = $detected || $jwtCount > 0;

        return [
            'blocked' => $detected && $policy === 'block',
            'warnings' => $detected && $policy === 'warn' ? [__('aichat::lang.chat_warning_sensitive_data_detected')] : [],
            'text' => $text,
        ];
    }

    public function moderateAssistantText(string $assistantText, ChatSetting $settings): array
    {
        if (! (bool) $settings->moderation_enabled) {
            return ['text' => $assistantText, 'moderated' => false];
        }

        $terms = $this->normalizeLines((string) $settings->moderation_terms);
        if (empty($terms)) {
            return ['text' => $assistantText, 'moderated' => false];
        }

        $moderated = false;
        $text = $assistantText;
        foreach ($terms as $term) {
            $updated = preg_replace('/' . preg_quote($term, '/') . '/i', '[redacted]', $text);
            if ($updated !== null && $updated !== $text) {
                $moderated = true;
                $text = $updated;
            }
        }

        return ['text' => $text, 'moderated' => $moderated];
    }

    public function resolveFabricContext(int $business_id, array $payload, ChatSetting $settings): array
    {
        return ['context' => '', 'fabric_id' => null, 'warnings' => []];
    }

    public function resolveQuoteContext(int $business_id, array $payload, ChatSetting $settings): array
    {
        return ['context' => '', 'quote_id' => null, 'warnings' => []];
    }

    public function resolveTrimContext(int $business_id, array $payload, ChatSetting $settings): array
    {
        return ['context' => '', 'trim_id' => null, 'warnings' => []];
    }

    public function resolveSalesOrderContext(int $business_id, array $payload, ChatSetting $settings): array
    {
        return ['context' => '', 'transaction_id' => null, 'warnings' => []];
    }

    public function enrichPayloadContextIds(int $business_id, array $payload): array
    {
        return $payload;
    }

    public function buildProviderMessages(
        ChatConversation $conversation,
        ?string $systemPrompt = null,
        ?string $fabricContext = null,
        int $historyLimit = 30,
        ?int $latestUserMessageId = null,
        ?string $currentPrompt = null,
        ?int $fabricId = null,
        ?string $contextGeneratedAt = null,
        ?int $user_id = null,
        ?string $quoteContext = null,
        ?int $quoteId = null,
        ?string $trimContext = null,
        ?int $trimId = null,
        ?string $salesOrderContext = null,
        ?int $transactionId = null,
        ?array $capabilityEnvelope = null,
        ?string $channel = null
    ): array {
        $business_id = (int) $conversation->business_id;
        $channel = trim((string) $channel) !== '' ? strtolower(trim((string) $channel)) : 'web';
        $capabilityEnvelope = $capabilityEnvelope ?: $this->resolveCapabilityEnvelope($business_id, $user_id, $channel);
        $capabilities = (array) data_get($capabilityEnvelope, 'domains', []);
        $sections = [
            'You are Aichat, an AI assistant for a single business account inside UPOS.',
            'Use only the organization data, persistent memory, and conversation history provided in this prompt.',
            'Do not invent data for other businesses. If information is unavailable in the provided business context, say so clearly.',
            'Do not claim any record was created, finalized, updated, or deleted unless this conversation includes an explicit app confirmation for that action.',
            'Never request, reveal, or restate passwords, auth tokens, session identifiers, email addresses, or phone numbers.',
            'For each request, decompose by domain and only answer using domains where capability is granted.',
            'If a domain capability is denied, explicitly refuse that part and continue with any allowed domains.',
            'Only claim facts grounded in authorized retrieved context or confirmed tool results.',
        ];

        $settings = $this->getOrCreateBusinessSettings($business_id);
        $reasoningRules = trim((string) ($settings->reasoning_rules ?? ''));
        if ($reasoningRules !== '') {
            $sections[] = 'Reasoning and response rules:' . "\n" . $reasoningRules;
        }

        if ($channel === 'telegram') {
            $sections[] = implode("\n", [
                'Telegram response format:',
                '- Return plain, readable text optimized for Telegram.',
                '- Do not use markdown markers such as **, __, or `.',
                '- Use numbered lists with "1." and child bullets with "-" when needed.',
            ]);
        }

        if (! empty($systemPrompt)) {
            $sections[] = 'Additional business instruction:' . "\n" . trim($systemPrompt);
        }

        $allowedOperations = $this->buildAllowedOperationsSection($capabilities);
        if ($allowedOperations !== '') {
            $sections[] = 'Allowed operations:' . "\n" . $allowedOperations;
        }

        $mutationWorkflow = $this->buildMutationWorkflowSection($capabilities);
        if ($mutationWorkflow !== '') {
            $sections[] = 'Action execution workflow:' . "\n" . $mutationWorkflow;
        }

        $domainBoundarySection = $this->buildDomainBoundarySection((string) ($currentPrompt ?? ''), $capabilities);
        if ($domainBoundarySection !== '') {
            $sections[] = 'Current request authorization boundary:' . "\n" . $domainBoundarySection;
        }

        $organizationContext = $this->buildOrganizationContext($business_id, $user_id, $capabilities);
        if ($organizationContext !== '') {
            $sections[] = 'Organization data context:' . "\n" . $organizationContext;
        }

        if ($user_id !== null) {
            $userProfileContext = $this->buildUserProfileContext($business_id, $user_id);
            $organizationMemoryContext = $this->buildOrganizationMemoryContext($business_id);
            $userMemoryContext = $this->buildUserMemoryContext($business_id, $user_id);

            if ($userProfileContext !== '') {
                $sections[] = 'User profile and context:' . "\n" . $userProfileContext;
            }
            if ($organizationMemoryContext !== '') {
                $sections[] = 'Organization memory:' . "\n" . $organizationMemoryContext;
            }
            if ($userMemoryContext !== '') {
                $sections[] = 'User memory:' . "\n" . $userMemoryContext;
            }

            if ($userProfileContext !== '' || $userMemoryContext !== '') {
                $sections[] = 'Use the user profile and user memory above to tailor tone and topics for this user.';
            }
        } else {
            $memoryContext = $this->buildMemoryContext($business_id, null);
            if ($memoryContext !== '') {
                $sections[] = 'Persistent business memory:' . "\n" . $memoryContext;
            }
        }

        $messages = [
            ['role' => 'system', 'content' => implode("\n\n", $sections)],
        ];

        $history = ChatMessage::forBusiness($business_id)
            ->where('conversation_id', $conversation->id)
            ->when($latestUserMessageId !== null, function ($query) use ($latestUserMessageId) {
                $query->where('id', '<=', $latestUserMessageId);
            })
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->filter(function (ChatMessage $message) {
                return in_array($message->role, [ChatMessage::ROLE_USER, ChatMessage::ROLE_ASSISTANT, ChatMessage::ROLE_SYSTEM], true);
            })
            ->values();

        if ($historyLimit > 0 && $history->count() > $historyLimit) {
            $history = $history->slice(-$historyLimit)->values();
        }

        foreach ($history as $message) {
            $messages[] = [
                'role' => $message->role,
                'content' => (string) $message->content,
            ];
        }

        if ($latestUserMessageId === null && $currentPrompt !== null && trim($currentPrompt) !== '') {
            $messages[] = ['role' => 'user', 'content' => trim($currentPrompt)];
        }

        return $messages;
    }

    public function getOrCreatePersistentMemory(int $business_id): PersistentMemory
    {
        $persistentMemory = PersistentMemory::forBusiness($business_id)->first();
        if ($persistentMemory) {
            return $persistentMemory;
        }

        $attempts = 0;
        while ($attempts < 5) {
            $attempts++;
            try {
                return PersistentMemory::create([
                    'business_id' => $business_id,
                    'slug' => $this->generateUniquePersistentMemorySlug(),
                    'display_name' => null,
                ]);
            } catch (QueryException $exception) {
                $existing = PersistentMemory::forBusiness($business_id)->first();
                if ($existing) {
                    return $existing;
                }
            }
        }

        return PersistentMemory::forBusiness($business_id)->firstOrFail();
    }

    public function updatePersistentMemoryDisplayName(int $business_id, ?string $display_name): PersistentMemory
    {
        $persistentMemory = $this->getOrCreatePersistentMemory($business_id);
        $normalizedDisplayName = trim((string) $display_name);

        $persistentMemory->display_name = $normalizedDisplayName !== '' ? $normalizedDisplayName : null;
        $persistentMemory->save();

        return $persistentMemory->fresh();
    }

    public function wipeBusinessMemory(int $business_id): int
    {
        return (int) ChatMemory::forBusiness($business_id)->delete();
    }

    public function paginatePersistentMemoriesForAdmin(int $perPage = 15)
    {
        $this->ensurePersistentMemoryContainersForAllBusinesses();
        $normalizedPerPage = max(1, min(50, $perPage));

        return PersistentMemory::query()
            ->with([
                'business:id,name',
                'memoryFacts' => function ($query) {
                    $query->select('id', 'business_id', 'user_id', 'memory_key', 'memory_value', 'updated_at');
                },
            ])
            ->withCount('memoryFacts')
            ->orderBy('business_id')
            ->paginate($normalizedPerPage);
    }

    public function listMemoryFactsForBusiness(int $business_id, int $user_id)
    {
        return ChatMemory::forBusiness($business_id)
            ->where(function ($query) use ($user_id) {
                $query->whereNull('user_id')->orWhere('user_id', $user_id);
            })
            ->orderByRaw('CASE WHEN user_id = ? THEN 0 WHEN user_id IS NULL THEN 1 ELSE 2 END', [$user_id])
            ->orderBy('memory_key')
            ->get();
    }

    public function getMemoryFactByIdForBusiness(int $business_id, int $memoryId): ChatMemory
    {
        return ChatMemory::forBusiness($business_id)->findOrFail($memoryId);
    }

    public function createMemoryFact(int $business_id, int $user_id, array $data): ChatMemory
    {
        return ChatMemory::create([
            'business_id' => $business_id,
            'user_id' => $user_id,
            'memory_key' => trim((string) $data['memory_key']),
            'memory_value' => trim((string) $data['memory_value']),
            'created_by' => $user_id,
            'updated_by' => $user_id,
        ]);
    }

    public function updateMemoryFact(int $business_id, int $memoryId, int $user_id, array $data): ChatMemory
    {
        $memory = $this->getMemoryFactByIdForBusiness($business_id, $memoryId);
        $memory->fill([
            'memory_key' => trim((string) $data['memory_key']),
            'memory_value' => trim((string) $data['memory_value']),
            'updated_by' => $user_id,
        ]);
        $memory->save();

        return $memory->fresh();
    }

    public function deleteMemoryFact(int $business_id, int $memoryId): bool
    {
        return (bool) $this->getMemoryFactByIdForBusiness($business_id, $memoryId)->delete();
    }

    public function createShareUrl(ChatConversation $conversation, int $ttlHours): string
    {
        return URL::temporarySignedRoute('aichat.chat.shared.show', now()->addHours(max(1, $ttlHours)), ['conversation' => $conversation->id]);
    }

    public function exportConversationAsMarkdown(ChatConversation $conversation): string
    {
        $messages = ChatMessage::forBusiness((int) $conversation->business_id)
            ->where('conversation_id', $conversation->id)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        $lines = ['# ' . ($conversation->title ?: 'New Chat'), ''];
        foreach ($messages as $message) {
            $lines[] = '## ' . ucfirst((string) $message->role);
            $lines[] = trim((string) $message->content);
            $lines[] = '';
        }

        return implode("\n", $lines);
    }

    public function getEffectiveRetentionDays(int $business_id): int
    {
        $retentionDays = (int) ($this->getOrCreateBusinessSettings($business_id)->retention_days ?: config('aichat.chat.retention_days', 90));

        return max(1, $retentionDays);
    }

    public function getSuggestedReplies(ChatSetting $settings): array
    {
        return array_values(array_filter((array) ($settings->suggested_replies ?: config('aichat.chat.suggested_replies', []))));
    }

    public function formatModelAllowlistForTextarea(ChatSetting $settings): string
    {
        return ! empty($settings->model_allowlist)
            ? json_encode($settings->model_allowlist, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            : '';
    }

    public function formatSuggestedRepliesForTextarea(ChatSetting $settings): string
    {
        return implode("\n", (array) ($settings->suggested_replies ?? []));
    }

    public function formatModerationTermsForTextarea(ChatSetting $settings): string
    {
        return (string) ($settings->moderation_terms ?? '');
    }

    public function audit(int $business_id, ?int $user_id, string $action, ?string $conversation_id = null, ?string $provider = null, ?string $model = null, array $metadata = [])
    {
        return $this->chatAuditUtil->log($business_id, $user_id, $action, $conversation_id, $provider, $model, $metadata);
    }

    protected function buildOrganizationContext(int $business_id, ?int $user_id = null, ?array $capabilities = null): string
    {
        $capabilities = $capabilities ?? $this->resolveChatCapabilities($business_id, $user_id);
        $business = Business::select('id', 'name')->find($business_id);

        $sections = [];
        if ($business) {
            $sections[] = 'Business: ' . $business->name;
        }

        $productsContext = $this->buildProductsContext(
            $business_id,
            (bool) data_get($capabilities, 'products.view', false),
            (bool) data_get($capabilities, 'products.view_cost', false)
        );
        if ($productsContext !== '') {
            $sections[] = 'Products context:' . "\n" . $productsContext;
        }

        $customerContext = $this->buildContactsContext($business_id, $user_id, 'customer', (array) data_get($capabilities, 'contacts.customer', []));
        if ($customerContext !== '') {
            $sections[] = 'Customer contacts:' . "\n" . $customerContext;
        }

        $supplierContext = $this->buildContactsContext($business_id, $user_id, 'supplier', (array) data_get($capabilities, 'contacts.supplier', []));
        if ($supplierContext !== '') {
            $sections[] = 'Supplier contacts:' . "\n" . $supplierContext;
        }

        $salesContext = $this->buildSalesContext($business_id, $user_id, $capabilities);
        if ($salesContext !== '') {
            $sections[] = 'Sales context:' . "\n" . $salesContext;
        }

        $purchaseContext = $this->buildPurchasesContext($business_id, $user_id, $capabilities);
        if ($purchaseContext !== '') {
            $sections[] = 'Purchases context:' . "\n" . $purchaseContext;
        }

        $quoteContext = $this->buildQuotesContext($business_id, $capabilities);
        if ($quoteContext !== '') {
            $sections[] = 'Quotes context:' . "\n" . $quoteContext;
        }

        $reportContext = $this->buildReportsContext($business_id, $user_id, $capabilities);
        if ($reportContext !== '') {
            $sections[] = 'Reports context:' . "\n" . $reportContext;
        }

        $settingsContext = $this->buildSettingsContext($capabilities);
        if ($settingsContext !== '') {
            $sections[] = 'Settings context:' . "\n" . $settingsContext;
        }

        return trim(implode("\n\n", $sections));
    }

    protected function buildAllowedOperationsSection(array $capabilities): string
    {
        $lines = [];

        $productOps = $this->formatAllowedOperationLine('Products', [
            'view' => (bool) data_get($capabilities, 'products.view', false),
            'create' => (bool) data_get($capabilities, 'products.create', false),
            'update' => (bool) data_get($capabilities, 'products.update', false),
            'delete' => (bool) data_get($capabilities, 'products.delete', false),
        ]);
        if ($productOps !== '') {
            $lines[] = $productOps;
        }

        $customerOps = $this->formatAllowedOperationLine('Customer contacts', [
            'view' => (bool) data_get($capabilities, 'contacts.customer.view', false),
            'view_own' => (bool) data_get($capabilities, 'contacts.customer.view_own', false),
            'create' => (bool) data_get($capabilities, 'contacts.customer.create', false),
            'update' => (bool) data_get($capabilities, 'contacts.customer.update', false),
            'delete' => (bool) data_get($capabilities, 'contacts.customer.delete', false),
        ]);
        if ($customerOps !== '') {
            $lines[] = $customerOps;
        }

        $supplierOps = $this->formatAllowedOperationLine('Supplier contacts', [
            'view' => (bool) data_get($capabilities, 'contacts.supplier.view', false),
            'view_own' => (bool) data_get($capabilities, 'contacts.supplier.view_own', false),
            'create' => (bool) data_get($capabilities, 'contacts.supplier.create', false),
            'update' => (bool) data_get($capabilities, 'contacts.supplier.update', false),
            'delete' => (bool) data_get($capabilities, 'contacts.supplier.delete', false),
        ]);
        if ($supplierOps !== '') {
            $lines[] = $supplierOps;
        }

        $salesOps = $this->formatAllowedOperationLine('Sales', [
            'view' => (bool) data_get($capabilities, 'sales.view', false),
            'view_own' => (bool) data_get($capabilities, 'sales.view_own', false),
            'create' => (bool) data_get($capabilities, 'sales.create', false),
            'update' => (bool) data_get($capabilities, 'sales.update', false),
            'delete' => (bool) data_get($capabilities, 'sales.delete', false),
        ]);
        if ($salesOps !== '') {
            $lines[] = $salesOps;
        }

        $purchaseOps = $this->formatAllowedOperationLine('Purchases', [
            'view' => (bool) data_get($capabilities, 'purchases.view', false),
            'view_own' => (bool) data_get($capabilities, 'purchases.view_own', false),
            'create' => (bool) data_get($capabilities, 'purchases.create', false),
            'update' => (bool) data_get($capabilities, 'purchases.update', false),
            'delete' => (bool) data_get($capabilities, 'purchases.delete', false),
        ]);
        if ($purchaseOps !== '') {
            $lines[] = $purchaseOps;
        }

        $quoteOps = $this->formatAllowedOperationLine('Quotes', [
            'view' => (bool) data_get($capabilities, 'quotes.view', false),
            'create' => (bool) data_get($capabilities, 'quotes.create', false),
            'update' => (bool) data_get($capabilities, 'quotes.update', false),
            'delete' => (bool) data_get($capabilities, 'quotes.delete', false),
            'send' => (bool) data_get($capabilities, 'quotes.send', false),
        ]);
        if ($quoteOps !== '') {
            $lines[] = $quoteOps;
        }

        $reportOps = $this->formatAllowedOperationLine('Reports', [
            'view' => (bool) data_get($capabilities, 'reports.view', false),
            'export' => (bool) data_get($capabilities, 'reports.export', false),
        ]);
        if ($reportOps !== '') {
            $lines[] = $reportOps;
        }

        $settingsOps = $this->formatAllowedOperationLine('Settings', [
            'access' => (bool) data_get($capabilities, 'settings.access', false),
            'chat_settings' => (bool) data_get($capabilities, 'settings.chat_settings', false),
            'manage_all_memories' => (bool) data_get($capabilities, 'settings.manage_all_memories', false),
        ]);
        if ($settingsOps !== '') {
            $lines[] = $settingsOps;
        }

        return implode("\n", $lines);
    }

    protected function formatAllowedOperationLine(string $label, array $operations): string
    {
        $allowed = array_keys(array_filter($operations));
        if (empty($allowed)) {
            return '';
        }

        return '- ' . $label . ': ' . implode(', ', $allowed);
    }

    protected function buildMutationWorkflowSection(array $capabilities): string
    {
        if (! (bool) config('aichat.actions.enabled', false)) {
            return '';
        }

        if (! (bool) data_get($capabilities, 'chat.edit', false)) {
            return '';
        }

        return implode("\n", [
            '- Mutations (create, update, delete) require action commands plus explicit confirm before database execution.',
            '- Suggest this draft format when user requests a mutation: /action prepare <module> <action> <json payload>',
            '- Tell the user to execute with: /action confirm <id> (or /action confirm for the latest pending action).',
            '- Tell the user they can cancel with: /action cancel <id> (or /action cancel for the latest pending action).',
            '- If required fields are missing, ask for those fields before suggesting the command.',
            '- Never claim mutation success until explicit app confirmation is present in conversation state.',
        ]);
    }

    protected function buildDomainBoundarySection(string $prompt, array $capabilities): string
    {
        $requestedDomains = $this->chatIntentDomainResolver->resolveRequestedDomains($prompt);
        if (empty($requestedDomains)) {
            return '';
        }

        $allowed = [];
        $denied = [];

        foreach ($requestedDomains as $domain) {
            $allowedOperations = $this->resolveAllowedDomainOperations($domain, $capabilities);
            if (empty($allowedOperations)) {
                $denied[] = $domain;
                continue;
            }

            $allowed[] = $domain . ' (' . implode(', ', $allowedOperations) . ')';
        }

        $lines = [];
        if (! empty($allowed)) {
            $lines[] = '- Requested domains with access: ' . implode('; ', $allowed) . '.';
        }
        if (! empty($denied)) {
            $lines[] = '- Requested domains without access: ' . implode(', ', $denied) . '. Refuse these parts clearly.';
        }

        return implode("\n", $lines);
    }

    protected function resolveAllowedDomainOperations(string $domain, array $capabilities): array
    {
        $domainCapabilities = (array) data_get($capabilities, $domain, []);
        if (empty($domainCapabilities)) {
            return [];
        }

        if (array_key_exists('view', $domainCapabilities)) {
            $allowed = array_keys(array_filter($domainCapabilities, function ($value) {
                return $value === true;
            }));

            return array_values($allowed);
        }

        if ($domain === 'contacts') {
            $allowed = [];
            foreach (['customer', 'supplier'] as $contactType) {
                $typeCapabilities = (array) data_get($domainCapabilities, $contactType, []);
                $ops = array_keys(array_filter($typeCapabilities, function ($value) {
                    return $value === true;
                }));
                if (! empty($ops)) {
                    $allowed[] = $contactType . ':' . implode('/', $ops);
                }
            }

            return $allowed;
        }

        return [];
    }

    protected function buildProductsContext(int $business_id, bool $canView, bool $canViewCost = false): string
    {
        if (! $canView) {
            return '';
        }

        $limit = (int) config('aichat.chat.organization_context.products_limit', 25);
        $productCount = Product::where('business_id', $business_id)->count();
        $products = Product::where('business_id', $business_id)
            ->with([
                'variations' => function ($query) {
                    $query->select('id', 'product_id', 'default_purchase_price', 'default_sell_price', 'sell_price_inc_tax')
                        ->orderBy('id');
                },
            ])
            ->select('id', 'name', 'sku', 'type')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();

        $lines = ['Products count: ' . $productCount];
        foreach ($products as $product) {
            $variation = $product->variations->first();
            $serialized = $this->chatModelSerializer->serialize('product', [
                'id' => (int) $product->id,
                'name' => trim((string) $product->name),
                'sku' => (string) ($product->sku ?: '-'),
                'type' => (string) ($product->type ?: '-'),
                'unit_price' => $variation && is_numeric($variation->default_sell_price)
                    ? number_format((float) $variation->default_sell_price, 2, '.', '')
                    : '-',
                'selling_price' => $variation && is_numeric($variation->sell_price_inc_tax)
                    ? number_format((float) $variation->sell_price_inc_tax, 2, '.', '')
                    : '-',
                'cost' => $variation && is_numeric($variation->default_purchase_price)
                    ? number_format((float) $variation->default_purchase_price, 2, '.', '')
                    : '-',
            ]);

            $line = '- Product #' . ($serialized['id'] ?? (int) $product->id)
                . ': ' . ($serialized['name'] ?? trim((string) $product->name))
                . ' | sku=' . ($serialized['sku'] ?? '-')
                . ' | type=' . ($serialized['type'] ?? '-')
                . ' | unit_price=' . ($serialized['unit_price'] ?? '-')
                . ' | selling_price=' . ($serialized['selling_price'] ?? '-');

            if ($canViewCost && array_key_exists('cost', $serialized)) {
                $line .= ' | cost=' . (string) $serialized['cost'];
            }

            $lines[] = $line;
        }

        return implode("\n", $lines);
    }

    protected function buildContactsContext(int $business_id, ?int $user_id, string $type, array $contactCapabilities): string
    {
        $canView = (bool) ($contactCapabilities['view'] ?? false);
        $canViewOwn = (bool) ($contactCapabilities['view_own'] ?? false);

        if (! $canView && ! $canViewOwn) {
            return '';
        }

        $limit = (int) config('aichat.chat.organization_context.contacts_limit', 25);
        $query = Contact::where('business_id', $business_id)
            ->whereIn('type', [$type, 'both'])
            ->where('type', '!=', 'lead');

        $this->chatAuthorizationPolicy->applyContactVisibilityScope($query, $user_id, $canView, $canViewOwn);

        $countQuery = clone $query;
        $contactCount = $countQuery->count('contacts.id');

        $contacts = $query
            ->select('contacts.id', 'contacts.name', 'contacts.supplier_business_name', 'contacts.type', 'contacts.contact_id')
            ->orderByDesc('contacts.id')
            ->limit($limit)
            ->get();

        $lines = [$type === 'customer' ? 'Customer contacts count: ' . $contactCount : 'Supplier contacts count: ' . $contactCount];
        foreach ($contacts as $contact) {
            $serialized = $this->chatModelSerializer->serialize('contact', [
                'id' => (int) $contact->id,
                'name' => (string) $contact->name,
                'supplier_business_name' => (string) ($contact->supplier_business_name ?? ''),
                'type' => (string) ($contact->type ?: '-'),
                'contact_id' => (string) ($contact->contact_id ?: '-'),
            ]);

            $displayName = trim((string) ($serialized['name'] ?? ''));
            $supplierBusinessName = trim((string) ($serialized['supplier_business_name'] ?? ''));
            if ($supplierBusinessName !== '') {
                $displayName .= ' (' . $supplierBusinessName . ')';
            }

            $lines[] = '- Contact #' . ((int) ($serialized['id'] ?? $contact->id))
                . ': ' . $displayName
                . ' | type=' . ((string) ($serialized['type'] ?? '-'))
                . ' | reference=' . ((string) ($serialized['contact_id'] ?? '-'));
        }

        return implode("\n", $lines);
    }

    protected function buildSalesContext(int $business_id, ?int $user_id, array $capabilities): string
    {
        $canView = (bool) data_get($capabilities, 'sales.view', false);
        $canViewOwn = (bool) data_get($capabilities, 'sales.view_own', false);

        if (! $canView && ! $canViewOwn) {
            return '';
        }

        $limit = (int) config('aichat.chat.organization_context.transactions_limit', 20);
        $query = Transaction::where('business_id', $business_id)
            ->where('type', 'sell');

        $this->chatAuthorizationPolicy->applyTransactionVisibilityScope($query, $user_id, $canView, $canViewOwn);

        $countQuery = clone $query;
        $transactionCount = $countQuery->count();

        $transactions = $query
            ->with(['contact' => function ($relation) {
                $relation->select('id', 'name', 'supplier_business_name');
            }])
            ->select('id', 'business_id', 'contact_id', 'type', 'status', 'invoice_no', 'transaction_date', 'final_total')
            ->orderByDesc('transaction_date')
            ->limit($limit)
            ->get();

        $lines = ['Sales transactions count: ' . $transactionCount];
        foreach ($transactions as $transaction) {
            $contactName = $transaction->contact ? trim((string) ($transaction->contact->supplier_business_name ?: $transaction->contact->name)) : '-';
            $serialized = $this->chatModelSerializer->serialize('sale_transaction', [
                'id' => (int) $transaction->id,
                'status' => (string) ($transaction->status ?: '-'),
                'invoice_no' => (string) ($transaction->invoice_no ?: '-'),
                'transaction_date' => $transaction->transaction_date ? Carbon::parse($transaction->transaction_date)->format('Y-m-d') : '-',
                'final_total' => is_numeric($transaction->final_total) ? number_format((float) $transaction->final_total, 2, '.', '') : '-',
                'contact_name' => $contactName,
            ]);

            $lines[] = '- Sale #' . ((int) ($serialized['id'] ?? $transaction->id))
                . ': status=' . ((string) ($serialized['status'] ?? '-'))
                . ' | invoice=' . ((string) ($serialized['invoice_no'] ?? '-'))
                . ' | date=' . ((string) ($serialized['transaction_date'] ?? '-'))
                . ' | total=' . ((string) ($serialized['final_total'] ?? '-'))
                . ' | contact=' . ((string) ($serialized['contact_name'] ?? '-'));
        }

        return implode("\n", $lines);
    }

    protected function buildPurchasesContext(int $business_id, ?int $user_id, array $capabilities): string
    {
        $canView = (bool) data_get($capabilities, 'purchases.view', false);
        $canViewOwn = (bool) data_get($capabilities, 'purchases.view_own', false);

        if (! $canView && ! $canViewOwn) {
            return '';
        }

        $limit = (int) config('aichat.chat.organization_context.transactions_limit', 20);
        $query = Transaction::where('business_id', $business_id)
            ->where('type', 'purchase');

        $this->chatAuthorizationPolicy->applyTransactionVisibilityScope($query, $user_id, $canView, $canViewOwn);

        $countQuery = clone $query;
        $transactionCount = $countQuery->count();

        $transactions = $query
            ->with(['contact' => function ($relation) {
                $relation->select('id', 'name', 'supplier_business_name');
            }])
            ->select('id', 'business_id', 'contact_id', 'type', 'status', 'invoice_no', 'transaction_date', 'final_total')
            ->orderByDesc('transaction_date')
            ->limit($limit)
            ->get();

        $lines = ['Purchase transactions count: ' . $transactionCount];
        foreach ($transactions as $transaction) {
            $contactName = $transaction->contact ? trim((string) ($transaction->contact->supplier_business_name ?: $transaction->contact->name)) : '-';
            $serialized = $this->chatModelSerializer->serialize('purchase_transaction', [
                'id' => (int) $transaction->id,
                'status' => (string) ($transaction->status ?: '-'),
                'invoice_no' => (string) ($transaction->invoice_no ?: '-'),
                'transaction_date' => $transaction->transaction_date ? Carbon::parse($transaction->transaction_date)->format('Y-m-d') : '-',
                'final_total' => is_numeric($transaction->final_total) ? number_format((float) $transaction->final_total, 2, '.', '') : '-',
                'contact_name' => $contactName,
            ]);

            $lines[] = '- Purchase #' . ((int) ($serialized['id'] ?? $transaction->id))
                . ': status=' . ((string) ($serialized['status'] ?? '-'))
                . ' | invoice=' . ((string) ($serialized['invoice_no'] ?? '-'))
                . ' | date=' . ((string) ($serialized['transaction_date'] ?? '-'))
                . ' | total=' . ((string) ($serialized['final_total'] ?? '-'))
                . ' | contact=' . ((string) ($serialized['contact_name'] ?? '-'));
        }

        return implode("\n", $lines);
    }

    protected function buildQuotesContext(int $business_id, array $capabilities): string
    {
        if (! (bool) data_get($capabilities, 'quotes.view', false)) {
            return '';
        }

        $limit = (int) config('aichat.chat.organization_context.transactions_limit', 20);
        $quoteCount = ProductQuote::forBusiness($business_id)->count();
        $quotes = ProductQuote::forBusiness($business_id)
            ->with([
                'contact:id,name,supplier_business_name',
                'location:id,name',
            ])
            ->select('id', 'business_id', 'contact_id', 'location_id', 'expires_at', 'sent_at', 'transaction_id', 'grand_total', 'line_count', 'created_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();

        $lines = ['Quotes count: ' . $quoteCount];
        foreach ($quotes as $quote) {
            $contactName = $quote->contact ? trim((string) ($quote->contact->supplier_business_name ?: $quote->contact->name)) : '-';
            $locationName = $quote->location ? trim((string) $quote->location->name) : '-';
            $serialized = $this->chatModelSerializer->serialize('quote', [
                'id' => (int) $quote->id,
                'contact_name' => $contactName,
                'location_name' => $locationName,
                'expires_at' => $quote->expires_at ? Carbon::parse($quote->expires_at)->format('Y-m-d') : '-',
                'grand_total' => is_numeric($quote->grand_total) ? number_format((float) $quote->grand_total, 2, '.', '') : '-',
                'line_count' => (int) $quote->line_count,
            ]);

            $lines[] = '- Quote #' . ((int) ($serialized['id'] ?? $quote->id))
                . ': contact=' . ((string) ($serialized['contact_name'] ?? '-'))
                . ' | location=' . ((string) ($serialized['location_name'] ?? '-'))
                . ' | expires=' . ((string) ($serialized['expires_at'] ?? '-'))
                . ' | total=' . ((string) ($serialized['grand_total'] ?? '-'))
                . ' | lines=' . ((int) ($serialized['line_count'] ?? 0));
        }

        return implode("\n", $lines);
    }

    protected function buildReportsContext(int $business_id, ?int $user_id, array $capabilities): string
    {
        if (! (bool) data_get($capabilities, 'reports.view', false)) {
            return '';
        }

        $lines = [];
        if ((bool) data_get($capabilities, 'products.view', false)) {
            $lines[] = '- Products available: ' . Product::where('business_id', $business_id)->count();
        }

        if ((bool) data_get($capabilities, 'contacts.customer.view', false)
            || (bool) data_get($capabilities, 'contacts.customer.view_own', false)
            || (bool) data_get($capabilities, 'contacts.supplier.view', false)
            || (bool) data_get($capabilities, 'contacts.supplier.view_own', false)) {
            $contactsQuery = Contact::where('business_id', $business_id)->where('type', '!=', 'lead');
            $canViewContacts = (bool) data_get($capabilities, 'contacts.customer.view', false)
                || (bool) data_get($capabilities, 'contacts.supplier.view', false);
            $canViewOwnContacts = (bool) data_get($capabilities, 'contacts.customer.view_own', false)
                || (bool) data_get($capabilities, 'contacts.supplier.view_own', false);

            $this->chatAuthorizationPolicy->applyContactVisibilityScope($contactsQuery, $user_id, $canViewContacts, $canViewOwnContacts);
            $lines[] = '- Contacts available: ' . $contactsQuery->count('contacts.id');
        }

        if ((bool) data_get($capabilities, 'sales.view', false) || (bool) data_get($capabilities, 'sales.view_own', false)) {
            $salesQuery = Transaction::where('business_id', $business_id)->where('type', 'sell');
            $this->chatAuthorizationPolicy->applyTransactionVisibilityScope(
                $salesQuery,
                $user_id,
                (bool) data_get($capabilities, 'sales.view', false),
                (bool) data_get($capabilities, 'sales.view_own', false)
            );
            $lines[] = '- Sales transactions available: ' . $salesQuery->count();
        }

        if ((bool) data_get($capabilities, 'purchases.view', false) || (bool) data_get($capabilities, 'purchases.view_own', false)) {
            $purchaseQuery = Transaction::where('business_id', $business_id)->where('type', 'purchase');
            $this->chatAuthorizationPolicy->applyTransactionVisibilityScope(
                $purchaseQuery,
                $user_id,
                (bool) data_get($capabilities, 'purchases.view', false),
                (bool) data_get($capabilities, 'purchases.view_own', false)
            );
            $lines[] = '- Purchase transactions available: ' . $purchaseQuery->count();
        }

        if ((bool) data_get($capabilities, 'quotes.view', false)) {
            $lines[] = '- Quotes available: ' . ProductQuote::forBusiness($business_id)->count();
        }

        return implode("\n", $lines);
    }

    protected function buildSettingsContext(array $capabilities): string
    {
        $lines = [];

        if ((bool) data_get($capabilities, 'settings.access', false)) {
            $lines[] = '- Business settings access: granted';
        }
        if ((bool) data_get($capabilities, 'settings.chat_settings', false)) {
            $lines[] = '- AI chat settings access: granted';
        }
        if ((bool) data_get($capabilities, 'settings.manage_all_memories', false)) {
            $lines[] = '- Memory administration access: granted';
        }

        return implode("\n", $lines);
    }

    protected function buildMemoryContext(int $business_id, ?int $user_id = null): string
    {
        if ($user_id === null) {
            return $this->buildOrganizationMemoryContext($business_id);
        }

        $organizationMemory = $this->buildOrganizationMemoryContext($business_id);
        $userMemory = $this->buildUserMemoryContext($business_id, $user_id);

        return collect([$organizationMemory, $userMemory])
            ->filter(function ($value) {
                return trim((string) $value) !== '';
            })
            ->implode("\n");
    }

    protected function buildOrganizationMemoryContext(int $business_id): string
    {
        return $this->buildScopedMemoryContext($business_id, null);
    }

    protected function buildUserMemoryContext(int $business_id, int $user_id): string
    {
        return $this->buildScopedMemoryContext($business_id, $user_id);
    }

    protected function buildScopedMemoryContext(int $business_id, ?int $user_id): string
    {
        $this->getOrCreatePersistentMemory($business_id);

        if (! (bool) config('aichat.chat.memory_enabled', true)) {
            return '';
        }

        $memoryFacts = ChatMemory::forBusiness($business_id)
            ->when($user_id === null, function ($query) {
                $query->whereNull('user_id');
            }, function ($query) use ($user_id) {
                $query->where('user_id', $user_id);
            })
            ->orderBy('memory_key')
            ->get(['memory_key', 'memory_value']);

        if ($memoryFacts->isEmpty()) {
            return '';
        }

        return $memoryFacts->map(function (ChatMemory $memory) {
            return '- ' . trim((string) $memory->memory_key) . ': ' . trim((string) $memory->memory_value);
        })->implode("\n");
    }

    protected function generateUniqueTelegramWebhookKey(): string
    {
        do {
            $webhookKey = Str::random(64);
            $exists = TelegramBot::where('webhook_key', $webhookKey)->exists();
        } while ($exists);

        return $webhookKey;
    }

    protected function generateUniqueTelegramLinkCode(): string
    {
        return Str::upper(Str::random(8));
    }

    protected function generateUniquePersistentMemorySlug(): string
    {
        do {
            $slug = Str::lower(Str::random(16));
            $exists = PersistentMemory::where('slug', $slug)->exists();
        } while ($exists);

        return $slug;
    }

    protected function ensurePersistentMemoryContainersForAllBusinesses(): void
    {
        Business::query()
            ->leftJoin('aichat_persistent_memory as pm', 'business.id', '=', 'pm.business_id')
            ->whereNull('pm.business_id')
            ->select('business.id')
            ->orderBy('business.id')
            ->chunk(200, function ($businesses) {
                foreach ($businesses as $business) {
                    $businessId = (int) $business->id;
                    if ($businessId <= 0) {
                        continue;
                    }

                    $this->getOrCreatePersistentMemory($businessId);
                }
            });
    }

    protected function buildLastMessagePreview(string $content): string
    {
        return Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags($content))), 160);
    }

    protected function countWords(string $text): int
    {
        return str_word_count(trim(strip_tags($text)));
    }

    protected function buildConversationTitleFromText(string $text): string
    {
        $plain = trim(preg_replace('/\s+/', ' ', strip_tags($text)));
        $title = Str::limit($plain, 70, '');
        $words = preg_split('/\s+/', $title, 9) ?: [];
        $title = implode(' ', array_slice($words, 0, 8));

        return $title !== '' ? $title : 'New Chat';
    }

    protected function isLowContextConversationTitle(string $title): bool
    {
        return in_array(strtolower(trim($title)), ['', 'new chat', 'untitled chat', 'chat'], true);
    }

    protected function normalizeLines(string $text): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $text ?: ''))));
    }
}
