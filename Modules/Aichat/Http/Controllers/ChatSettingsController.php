<?php

namespace Modules\Aichat\Http\Controllers;

use App\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Aichat\Http\Requests\Chat\DeleteChatMemoryFactRequest;
use Modules\Aichat\Http\Requests\Chat\SaveChatCredentialRequest;
use Modules\Aichat\Http\Requests\Chat\StoreTelegramAllowedGroupRequest;
use Modules\Aichat\Http\Requests\Chat\StoreTelegramBotRequest;
use Modules\Aichat\Http\Requests\Chat\StoreChatMemoryFactRequest;
use Modules\Aichat\Http\Requests\Chat\UpdateUserChatProfileRequest;
use Modules\Aichat\Http\Requests\Chat\UpdateChatMemoryFactRequest;
use Modules\Aichat\Http\Requests\Chat\UpdateChatBusinessSettingsRequest;
use Modules\Aichat\Http\Requests\Chat\UpdateTelegramAllowedUsersRequest;
use Modules\Aichat\Entities\ChatMemory;
use Modules\Aichat\Utils\TelegramApiUtil;
use Modules\Aichat\Utils\ChatUtil;

class ChatSettingsController extends Controller
{
    protected ChatUtil $chatUtil;

    public function __construct(ChatUtil $chatUtil)
    {
        $this->chatUtil = $chatUtil;
    }

    public function index(Request $request)
    {
        if (! auth()->user()->can('aichat.chat.view')) {
            abort(403, __('aichat::lang.unauthorized_action'));
        }

        $business_id = (int) $request->session()->get('user.business_id');
        $user_id = (int) auth()->id();
        $credentialStatuses = $this->chatUtil->getCredentialStatuses($business_id, $user_id);
        $businessSettings = $this->chatUtil->getOrCreateBusinessSettings($business_id);
        $aiChatConfig = $this->chatUtil->buildClientConfig($business_id, $user_id);
        $persistentMemory = $this->chatUtil->getOrCreatePersistentMemory($business_id);
        $memoryFacts = $this->chatUtil->listMemoryFactsForBusiness($business_id, $user_id);
        $modelAllowlistJson = $this->chatUtil->formatModelAllowlistForTextarea($businessSettings);
        $suggestedRepliesText = $this->chatUtil->formatSuggestedRepliesForTextarea($businessSettings);
        $moderationTermsText = $this->chatUtil->formatModerationTermsForTextarea($businessSettings);
        $canManageAllMemories = auth()->user()->can('aichat.manage_all_memories');
        $userChatProfile = $this->chatUtil->getOrCreateUserChatProfile($business_id, $user_id);
        $telegramBot = $this->chatUtil->getTelegramBotForBusiness($business_id);
        $telegramWebhookUrl = $telegramBot ? $this->chatUtil->buildTelegramWebhookUrl((string) $telegramBot->webhook_key) : null;
        $telegramAllowedUsers = collect();
        $telegramAllowedUserIds = [];
        $telegramAllowedUserLinkCodes = [];
        $telegramAllowedGroups = [];
        $businessUsersForDropdown = [];
        $telegramLinkCode = null;

        if (auth()->user()->can('aichat.chat.settings')) {
            $telegramAllowedUsers = $this->chatUtil->getTelegramAllowedUsers($business_id);
            $telegramAllowedUserIds = $telegramAllowedUsers
                ->pluck('user_id')
                ->map(function ($id) {
                    return (int) $id;
                })
                ->values()
                ->all();
            $telegramAllowedGroups = $this->chatUtil->getTelegramAllowedGroups($business_id);
            $businessUsersForDropdown = User::query()
                ->where('business_id', $business_id)
                ->select('id', 'surname', 'first_name', 'last_name', 'username')
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->get();

            if ($telegramBot) {
                foreach ($telegramAllowedUsers as $allowedUser) {
                    try {
                        $telegramAllowedUserLinkCodes[(int) $allowedUser->user_id] = $this->chatUtil->getOrCreateTelegramLinkCode($business_id, (int) $allowedUser->user_id);
                    } catch (\Throwable $exception) {
                        // Keep the settings page usable even if one user's code cannot be generated.
                    }
                }
            }
        }

        if ($telegramBot && $this->chatUtil->isUserAllowedForTelegram($business_id, $user_id)) {
            try {
                if (array_key_exists($user_id, $telegramAllowedUserLinkCodes)) {
                    $telegramLinkCode = (string) $telegramAllowedUserLinkCodes[$user_id];
                } else {
                    $telegramLinkCode = $this->chatUtil->getOrCreateTelegramLinkCode($business_id, $user_id);
                }
            } catch (\Throwable $exception) {
                $telegramLinkCode = null;
            }
        }

        return view('aichat::chat.settings', compact(
            'credentialStatuses',
            'businessSettings',
            'aiChatConfig',
            'persistentMemory',
            'memoryFacts',
            'modelAllowlistJson',
            'suggestedRepliesText',
            'moderationTermsText',
            'canManageAllMemories',
            'userChatProfile',
            'telegramBot',
            'telegramWebhookUrl',
            'telegramAllowedUsers',
            'telegramAllowedUserIds',
            'telegramAllowedUserLinkCodes',
            'telegramAllowedGroups',
            'businessUsersForDropdown',
            'telegramLinkCode'
        ));
    }

    public function storeCredential(SaveChatCredentialRequest $request)
    {
        if (! auth()->user()->can('aichat.chat.view')) {
            return $this->respondUnauthorized(__('messages.unauthorized_action'));
        }

        $validated = $request->validated();
        $scope = (string) $validated['scope'];
        if ($scope === 'business' && ! auth()->user()->can('aichat.chat.settings')) {
            return $this->respondUnauthorized(__('messages.unauthorized_action'));
        }

        $business_id = (int) $request->session()->get('user.business_id');
        $user_id = (int) auth()->id();

        try {
            $credential = $this->chatUtil->saveCredential(
                $business_id,
                $user_id,
                $scope,
                (string) $validated['provider'],
                (string) $validated['api_key']
            );

            $this->chatUtil->audit(
                $business_id,
                $user_id,
                'credential_saved',
                null,
                (string) $validated['provider'],
                null,
                ['scope' => $scope, 'credential_id' => $credential->id]
            );

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => __('aichat::lang.chat_credential_saved'),
                ]);
            }

            return redirect()
                ->back()
                ->with('status', ['success' => true, 'msg' => __('aichat::lang.chat_credential_saved')]);
        } catch (\Throwable $exception) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $exception->getMessage(),
                ], 422);
            }

            return redirect()
                ->back()
                ->withInput()
                ->with('status', ['success' => false, 'msg' => $exception->getMessage()]);
        }
    }

    public function updateBusiness(UpdateChatBusinessSettingsRequest $request)
    {
        if (! auth()->user()->can('aichat.chat.settings')) {
            return $this->respondUnauthorized(__('messages.unauthorized_action'));
        }

        $business_id = (int) $request->session()->get('user.business_id');
        $user_id = (int) auth()->id();

        try {
            $settings = $this->chatUtil->updateBusinessSettings($business_id, $request->validated());

            $this->chatUtil->audit($business_id, $user_id, 'business_settings_updated', null, null, null, [
                'settings_id' => $settings->id,
            ]);

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => __('aichat::lang.chat_business_settings_saved'),
                ]);
            }

            return redirect()
                ->back()
                ->with('status', ['success' => true, 'msg' => __('aichat::lang.chat_business_settings_saved')]);
        } catch (\Throwable $exception) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $exception->getMessage(),
                ], 422);
            }

            return redirect()
                ->back()
                ->withInput()
                ->with('status', ['success' => false, 'msg' => $exception->getMessage()]);
        }
    }

    public function updateProfile(UpdateUserChatProfileRequest $request)
    {
        if (! auth()->user()->can('aichat.chat.settings')) {
            return $this->respondUnauthorized(__('messages.unauthorized_action'));
        }

        $business_id = (int) $request->session()->get('user.business_id');
        $user_id = (int) auth()->id();

        try {
            $profile = $this->chatUtil->getOrCreateUserChatProfile($business_id, $user_id);
            $profile->fill($request->validated());
            $profile->save();

            $this->chatUtil->audit($business_id, $user_id, 'user_chat_profile_updated', null, null, null, [
                'profile_id' => (int) $profile->id,
            ]);

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => __('aichat::lang.chat_profile_updated'),
                ]);
            }

            return redirect()
                ->back()
                ->with('status', ['success' => true, 'msg' => __('aichat::lang.chat_profile_updated')]);
        } catch (\Throwable $exception) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $exception->getMessage(),
                ], 422);
            }

            return redirect()
                ->back()
                ->withInput()
                ->with('status', ['success' => false, 'msg' => $exception->getMessage()]);
        }
    }

    public function storeMemory(StoreChatMemoryFactRequest $request)
    {
        if (! auth()->user()->can('aichat.chat.settings')) {
            return $this->respondUnauthorized(__('messages.unauthorized_action'));
        }

        $business_id = (int) $request->session()->get('user.business_id');
        $user_id = (int) auth()->id();

        try {
            $memoryFact = $this->chatUtil->createMemoryFact($business_id, $user_id, $request->validated());

            $this->chatUtil->audit($business_id, $user_id, 'memory_created', null, null, null, [
                'memory_id' => (int) $memoryFact->id,
                'memory_key' => (string) $memoryFact->memory_key,
            ]);

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => __('aichat::lang.chat_memory_saved'),
                ]);
            }

            return redirect()
                ->back()
                ->with('status', ['success' => true, 'msg' => __('aichat::lang.chat_memory_saved')]);
        } catch (\Throwable $exception) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $exception->getMessage(),
                ], 422);
            }

            return redirect()
                ->back()
                ->withInput()
                ->with('status', ['success' => false, 'msg' => $exception->getMessage()]);
        }
    }

    public function updateMemory(UpdateChatMemoryFactRequest $request, int $memory)
    {
        if (! auth()->user()->can('aichat.chat.settings')) {
            return $this->respondUnauthorized(__('messages.unauthorized_action'));
        }

        $business_id = (int) $request->session()->get('user.business_id');
        $user_id = (int) auth()->id();

        try {
            $memoryFact = $this->chatUtil->getMemoryFactByIdForBusiness($business_id, $memory);
            if (! $this->userCanUpdateMemoryFact($memoryFact, $user_id)) {
                return $this->respondUnauthorized(__('messages.unauthorized_action'));
            }

            $memoryFact = $this->chatUtil->updateMemoryFact($business_id, $memory, $user_id, $request->validated());

            $this->chatUtil->audit($business_id, $user_id, 'memory_updated', null, null, null, [
                'memory_id' => (int) $memoryFact->id,
                'memory_key' => (string) $memoryFact->memory_key,
            ]);

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => __('aichat::lang.chat_memory_updated'),
                ]);
            }

            return redirect()
                ->back()
                ->with('status', ['success' => true, 'msg' => __('aichat::lang.chat_memory_updated')]);
        } catch (\Throwable $exception) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $exception->getMessage(),
                ], 422);
            }

            return redirect()
                ->back()
                ->withInput()
                ->with('status', ['success' => false, 'msg' => $exception->getMessage()]);
        }
    }

    public function destroyMemory(DeleteChatMemoryFactRequest $request, int $memory)
    {
        if (! auth()->user()->can('aichat.chat.settings')) {
            return $this->respondUnauthorized(__('messages.unauthorized_action'));
        }

        $business_id = (int) $request->session()->get('user.business_id');
        $user_id = (int) auth()->id();

        try {
            $memoryFact = $this->chatUtil->getMemoryFactByIdForBusiness($business_id, $memory);
            if (! $this->userCanDeleteMemoryFact($memoryFact, $user_id)) {
                return $this->respondUnauthorized(__('messages.unauthorized_action'));
            }

            $memoryKey = (string) $memoryFact->memory_key;

            $this->chatUtil->deleteMemoryFact($business_id, $memory);

            $this->chatUtil->audit($business_id, $user_id, 'memory_deleted', null, null, null, [
                'memory_id' => $memory,
                'memory_key' => $memoryKey,
            ]);

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => __('aichat::lang.chat_memory_deleted'),
                ]);
            }

            return redirect()
                ->back()
                ->with('status', ['success' => true, 'msg' => __('aichat::lang.chat_memory_deleted')]);
        } catch (\Throwable $exception) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $exception->getMessage(),
                ], 422);
            }

            return redirect()
                ->back()
                ->withInput()
                ->with('status', ['success' => false, 'msg' => $exception->getMessage()]);
        }
    }

    public function storeTelegramBot(StoreTelegramBotRequest $request, TelegramApiUtil $telegramApi)
    {
        if (! auth()->user()->can('aichat.chat.settings')) {
            return $this->respondUnauthorized(__('messages.unauthorized_action'));
        }

        $business_id = (int) $request->session()->get('user.business_id');
        $user_id = (int) auth()->id();
        $botToken = trim((string) $request->validated()['bot_token']);
        $hadExistingBot = $this->chatUtil->getTelegramBotForBusiness($business_id) !== null;
        $savedBot = null;

        try {
            $telegramApi->getMe($botToken);

            $savedBot = $this->chatUtil->saveTelegramBot($business_id, $user_id, $botToken);
            $webhookUrl = $this->chatUtil->buildTelegramWebhookUrl((string) $savedBot->webhook_key);
            $telegramApi->setWebhook($botToken, $webhookUrl, (string) $savedBot->webhook_secret_token);

            $this->chatUtil->audit($business_id, $user_id, 'telegram_bot_saved', null, null, null, [
                'telegram_bot_id' => (int) $savedBot->id,
            ]);

            return redirect()
                ->back()
                ->with('status', ['success' => true, 'msg' => __('aichat::lang.telegram_bot_saved')]);
        } catch (\Throwable $exception) {
            if (! $hadExistingBot && $savedBot) {
                $this->chatUtil->deleteTelegramBot($business_id);
            }

            return redirect()
                ->back()
                ->withInput()
                ->with('status', ['success' => false, 'msg' => __('aichat::lang.telegram_bot_save_failed') . ' ' . $exception->getMessage()]);
        }
    }

    public function destroyTelegramBot(Request $request, TelegramApiUtil $telegramApi)
    {
        if (! auth()->user()->can('aichat.chat.settings')) {
            return $this->respondUnauthorized(__('messages.unauthorized_action'));
        }

        $business_id = (int) $request->session()->get('user.business_id');
        $user_id = (int) auth()->id();
        $bot = $this->chatUtil->getTelegramBotForBusiness($business_id);

        try {
            if ($bot) {
                try {
                    $telegramApi->deleteWebhook($this->chatUtil->getDecryptedBotToken($bot));
                } catch (\Throwable $exception) {
                    // Ignore webhook delete errors during disconnect.
                }
            }

            $this->chatUtil->deleteTelegramBot($business_id);

            $this->chatUtil->audit($business_id, $user_id, 'telegram_bot_deleted');

            return redirect()
                ->back()
                ->with('status', ['success' => true, 'msg' => __('aichat::lang.telegram_bot_deleted')]);
        } catch (\Throwable $exception) {
            return redirect()
                ->back()
                ->with('status', ['success' => false, 'msg' => __('aichat::lang.telegram_bot_delete_failed')]);
        }
    }

    public function updateTelegramAllowedUsers(UpdateTelegramAllowedUsersRequest $request)
    {
        if (! auth()->user()->can('aichat.chat.settings')) {
            return $this->respondUnauthorized(__('messages.unauthorized_action'));
        }

        $business_id = (int) $request->session()->get('user.business_id');
        $user_id = (int) auth()->id();
        $userIds = (array) ($request->validated()['user_ids'] ?? []);

        try {
            $this->chatUtil->syncTelegramAllowedUsers($business_id, $userIds);

            $this->chatUtil->audit($business_id, $user_id, 'telegram_allowed_users_updated', null, null, null, [
                'allowed_user_count' => count(array_unique(array_map('intval', $userIds))),
            ]);

            return redirect()
                ->back()
                ->with('status', ['success' => true, 'msg' => __('aichat::lang.telegram_allowed_users_saved')]);
        } catch (\Throwable $exception) {
            return redirect()
                ->back()
                ->withInput()
                ->with('status', ['success' => false, 'msg' => $exception->getMessage()]);
        }
    }

    public function regenerateTelegramLinkCode(Request $request, int $user_id)
    {
        if (! auth()->user()->can('aichat.chat.settings')) {
            return $this->respondUnauthorized(__('messages.unauthorized_action'));
        }

        $business_id = (int) $request->session()->get('user.business_id');
        $actorUserId = (int) auth()->id();

        try {
            $this->chatUtil->createTelegramLinkCode($business_id, $user_id);

            $this->chatUtil->audit($business_id, $actorUserId, 'telegram_link_code_regenerated', null, null, null, [
                'target_user_id' => $user_id,
            ]);

            return redirect()
                ->back()
                ->with('status', ['success' => true, 'msg' => __('aichat::lang.telegram_link_code_regenerated')]);
        } catch (\Throwable $exception) {
            return redirect()
                ->back()
                ->with('status', ['success' => false, 'msg' => $exception->getMessage()]);
        }
    }

    public function storeTelegramAllowedGroup(StoreTelegramAllowedGroupRequest $request)
    {
        if (! auth()->user()->can('aichat.chat.settings')) {
            return $this->respondUnauthorized(__('messages.unauthorized_action'));
        }

        $business_id = (int) $request->session()->get('user.business_id');
        $user_id = (int) auth()->id();
        $payload = $request->validated();

        $telegramChatId = (int) $payload['telegram_chat_id'];
        $title = isset($payload['title']) ? (string) $payload['title'] : null;

        try {
            $group = $this->chatUtil->addTelegramAllowedGroup($business_id, $telegramChatId, $title);

            $this->chatUtil->audit($business_id, $user_id, 'telegram_allowed_group_added', null, null, null, [
                'telegram_chat_id' => (int) $group->telegram_chat_id,
            ]);

            return redirect()
                ->back()
                ->with('status', ['success' => true, 'msg' => __('aichat::lang.telegram_allowed_group_added')]);
        } catch (\Throwable $exception) {
            return redirect()
                ->back()
                ->withInput()
                ->with('status', ['success' => false, 'msg' => $exception->getMessage()]);
        }
    }

    public function destroyTelegramAllowedGroup(Request $request, int $telegram_chat_id)
    {
        if (! auth()->user()->can('aichat.chat.settings')) {
            return $this->respondUnauthorized(__('messages.unauthorized_action'));
        }

        $business_id = (int) $request->session()->get('user.business_id');
        $user_id = (int) auth()->id();

        try {
            $this->chatUtil->removeTelegramAllowedGroup($business_id, $telegram_chat_id);

            $this->chatUtil->audit($business_id, $user_id, 'telegram_allowed_group_removed', null, null, null, [
                'telegram_chat_id' => $telegram_chat_id,
            ]);

            return redirect()
                ->back()
                ->with('status', ['success' => true, 'msg' => __('aichat::lang.telegram_allowed_group_removed')]);
        } catch (\Throwable $exception) {
            return redirect()
                ->back()
                ->with('status', ['success' => false, 'msg' => __('aichat::lang.telegram_allowed_group_remove_failed')]);
        }
    }

    protected function userCanUpdateMemoryFact(ChatMemory $memoryFact, int $user_id): bool
    {
        return (int) $memoryFact->user_id === $user_id;
    }

    protected function userCanDeleteMemoryFact(ChatMemory $memoryFact, int $user_id): bool
    {
        if ((int) $memoryFact->user_id === $user_id) {
            return true;
        }

        return $memoryFact->user_id === null
            && auth()->user()->can('aichat.manage_all_memories');
    }
}
