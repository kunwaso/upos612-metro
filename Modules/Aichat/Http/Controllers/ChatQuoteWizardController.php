<?php

namespace Modules\Aichat\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Aichat\Entities\ProductQuoteDraft;
use Modules\Aichat\Http\Requests\Chat\ConfirmProductQuoteDraftRequest;
use Modules\Aichat\Http\Requests\Chat\GetQuoteWizardCostingDefaultsRequest;
use Modules\Aichat\Http\Requests\Chat\ListQuoteWizardLocationsRequest;
use Modules\Aichat\Http\Requests\Chat\ProcessQuoteWizardStepRequest;
use Modules\Aichat\Http\Requests\Chat\SearchQuoteWizardContactsRequest;
use Modules\Aichat\Http\Requests\Chat\SearchQuoteWizardProductsRequest;
use Modules\Aichat\Utils\ChatProductQuoteWizardUtil;
use Modules\Aichat\Utils\ChatUtil;

class ChatQuoteWizardController extends Controller
{
    protected ChatUtil $chatUtil;

    protected ChatProductQuoteWizardUtil $quoteWizardUtil;

    public function __construct(ChatUtil $chatUtil, ChatProductQuoteWizardUtil $quoteWizardUtil)
    {
        $this->chatUtil = $chatUtil;
        $this->quoteWizardUtil = $quoteWizardUtil;
    }

    public function contacts(SearchQuoteWizardContactsRequest $request)
    {
        $business_id = (int) $request->session()->get('user.business_id');
        if ($response = $this->ensureWizardEnabled($business_id)) {
            return $response;
        }

        return response()->json([
            'success' => true,
            'data' => $this->quoteWizardUtil->searchContacts(
                $business_id,
                $request->validated()['q'] ?? null,
                isset($request->validated()['limit']) ? (int) $request->validated()['limit'] : null
            ),
        ]);
    }

    public function locations(ListQuoteWizardLocationsRequest $request)
    {
        $business_id = (int) $request->session()->get('user.business_id');
        if ($response = $this->ensureWizardEnabled($business_id)) {
            return $response;
        }

        return response()->json([
            'success' => true,
            'data' => $this->quoteWizardUtil->listLocations($business_id),
        ]);
    }

    public function products(SearchQuoteWizardProductsRequest $request)
    {
        $business_id = (int) $request->session()->get('user.business_id');
        if ($response = $this->ensureWizardEnabled($business_id)) {
            return $response;
        }

        return response()->json([
            'success' => true,
            'data' => $this->quoteWizardUtil->searchProducts(
                $business_id,
                $request->validated()['q'] ?? null,
                isset($request->validated()['limit']) ? (int) $request->validated()['limit'] : null
            ),
        ]);
    }

    public function costingDefaults(GetQuoteWizardCostingDefaultsRequest $request)
    {
        $business_id = (int) $request->session()->get('user.business_id');
        if ($response = $this->ensureWizardEnabled($business_id)) {
            return $response;
        }

        return response()->json([
            'success' => true,
            'data' => $this->quoteWizardUtil->getCostingDefaults($business_id),
        ]);
    }

    public function process(ProcessQuoteWizardStepRequest $request, string $id)
    {
        $business_id = (int) $request->session()->get('user.business_id');
        $user_id = (int) auth()->id();
        if ($response = $this->ensureWizardEnabled($business_id)) {
            return $response;
        }

        try {
            $conversation = $this->chatUtil->getConversationByIdForUser($business_id, $user_id, $id);
            $validated = $request->validated();
            $draft = $this->quoteWizardUtil->getOrCreateDraft(
                (string) $conversation->id,
                $user_id,
                $business_id,
                $validated['draft_id'] ?? null
            );

            $result = $this->quoteWizardUtil->processStep($draft, $conversation, $user_id, $business_id, $validated);

            $this->chatUtil->audit($business_id, $user_id, 'quote_wizard_step_processed', (string) $conversation->id, null, null, [
                'draft_id' => (string) $result['draft']->id,
                'status' => (string) ($result['state']['status'] ?? ProductQuoteDraft::STATUS_COLLECTING),
            ]);

            return response()->json([
                'success' => true,
                'message' => __('lang_v1.success'),
                'data' => [
                    'conversation' => $this->chatUtil->serializeConversation($conversation->fresh()),
                    'user_message' => $result['user_message'] ? $this->chatUtil->serializeMessage($result['user_message']) : null,
                    'assistant_message' => $this->chatUtil->serializeMessage($result['assistant_message']),
                    'draft' => $this->quoteWizardUtil->serializeDraft($result['draft']),
                ],
            ]);
        } catch (\Throwable $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage() ?: __('messages.something_went_wrong'),
            ], 422);
        }
    }

    public function confirm(ConfirmProductQuoteDraftRequest $request, string $id)
    {
        $business_id = (int) $request->session()->get('user.business_id');
        $user_id = (int) auth()->id();
        if ($response = $this->ensureWizardEnabled($business_id)) {
            return $response;
        }

        if (! auth()->user()->can('product_quote.create')) {
            return $this->respondUnauthorized(__('messages.unauthorized_action'));
        }

        try {
            $conversation = $this->chatUtil->getConversationByIdForUser($business_id, $user_id, $id);
            $draft = ProductQuoteDraft::forBusiness($business_id)
                ->forUser($user_id)
                ->forConversation((string) $conversation->id)
                ->where('id', (string) $request->validated()['draft_id'])
                ->firstOrFail();

            $confirmResult = $this->quoteWizardUtil->confirmDraft($draft, $business_id, $user_id);
            $quote = $confirmResult['quote'];
            $draft = $confirmResult['draft'];
            $publicUrl = (string) $confirmResult['public_url'];
            $adminUrl = (string) $confirmResult['admin_url'];
            $assistantText = __('aichat::lang.quote_assistant_success_prompt') . "\n" . $publicUrl . "\n" . $adminUrl;
            $assistantMessage = $this->chatUtil->appendMessage($conversation, 'assistant', $assistantText, null, null, $user_id);

            $this->chatUtil->audit($business_id, $user_id, 'quote_created_from_chat', (string) $conversation->id, null, null, [
                'draft_id' => (string) $draft->id,
                'quote_id' => (int) $quote->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => __('aichat::lang.quote_created_success'),
                'data' => [
                    'quote_id' => (int) $quote->id,
                    'public_url' => $publicUrl,
                    'admin_url' => $adminUrl,
                    'assistant_message' => $this->chatUtil->serializeMessage($assistantMessage),
                    'draft' => $this->quoteWizardUtil->serializeDraft($draft->fresh()),
                ],
            ]);
        } catch (\Throwable $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage() ?: __('aichat::lang.quote_assistant_validation_failed'),
            ], 422);
        }
    }

    protected function ensureWizardEnabled(int $business_id)
    {
        if (! $this->chatUtil->isChatEnabled($business_id)) {
            return response()->json(['success' => false, 'message' => __('aichat::lang.chat_disabled')], 403);
        }

        if (! (bool) config('aichat.quote_wizard.enabled', true)) {
            return response()->json(['success' => false, 'message' => __('aichat::lang.quote_assistant_feature_disabled')], 403);
        }

        return null;
    }
}
