<?php

namespace Modules\Aichat\Utils;

use App\BusinessLocation;
use App\Contact;
use App\Http\Requests\StoreProductBudgetQuoteRequest;
use App\Http\Requests\StoreProductQuoteRequest;
use App\Product;
use App\Utils\ProductCostingUtil;
use App\Utils\QuoteUtil;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Modules\Aichat\Entities\ChatConversation;
use Modules\Aichat\Entities\ChatMessage;
use Modules\Aichat\Entities\ProductQuoteDraft;

class ChatProductQuoteWizardUtil
{
    protected ChatUtil $chatUtil;

    protected AIChatUtil $aiChatUtil;

    protected ProductCostingUtil $productCostingUtil;

    protected QuoteUtil $quoteUtil;

    public function __construct(ChatUtil $chatUtil, AIChatUtil $aiChatUtil, ProductCostingUtil $productCostingUtil, QuoteUtil $quoteUtil)
    {
        $this->chatUtil = $chatUtil;
        $this->aiChatUtil = $aiChatUtil;
        $this->productCostingUtil = $productCostingUtil;
        $this->quoteUtil = $quoteUtil;
    }

    public function getOrCreateDraft(?string $conversationId, int $userId, int $businessId, ?string $draftId = null, ?int $telegramChatId = null): ProductQuoteDraft
    {
        $draft = null;
        $conversationId = $conversationId !== null && trim($conversationId) !== '' ? (string) $conversationId : null;
        $telegramChatId = $telegramChatId && $telegramChatId > 0 ? (int) $telegramChatId : null;

        if ($conversationId === null && $telegramChatId === null) {
            throw new \InvalidArgumentException('Draft channel context is required.');
        }

        $channelQuery = ProductQuoteDraft::forBusiness($businessId)
            ->forUser($userId)
            ->forChannel($conversationId, $telegramChatId);

        if ($draftId) {
            $draft = (clone $channelQuery)
                ->where('id', $draftId)
                ->first();
        }

        if (! $draft) {
            $draft = (clone $channelQuery)
                ->latest('updated_at')
                ->latest('created_at')
                ->first();
        }

        if ($draft && $draft->isExpired()) {
            $draft->status = ProductQuoteDraft::STATUS_EXPIRED;
            $draft->save();
            $draft = null;
        }

        if ($draft && $draft->status === ProductQuoteDraft::STATUS_CONSUMED) {
            $draft = null;
        }

        if ($draft) {
            return $draft;
        }

        $defaults = $this->defaultPayload($businessId);

        return ProductQuoteDraft::create([
            'business_id' => $businessId,
            'user_id' => $userId,
            'conversation_id' => $conversationId,
            'telegram_chat_id' => $telegramChatId,
            'flow' => $defaults['flow'],
            'status' => ProductQuoteDraft::STATUS_COLLECTING,
            'payload' => $defaults,
            'expires_at' => now()->addHours(max(1, (int) config('aichat.quote_wizard.draft_ttl_hours', 24))),
            'last_interaction_at' => now(),
        ]);
    }

    public function searchContacts(int $businessId, ?string $query = null, ?int $limit = null): array
    {
        $normalizedQuery = trim((string) $query);
        $limit = $this->normalizeLimit($limit, (int) config('aichat.quote_wizard.max_contact_results', 8));

        $contacts = Contact::where('contacts.business_id', $businessId)
            ->whereIn('contacts.type', ['customer', 'both'])
            ->active();

        if ($normalizedQuery !== '') {
            $contacts->where(function ($builder) use ($normalizedQuery) {
                $builder->where('contacts.name', 'like', '%' . $normalizedQuery . '%')
                    ->orWhere('contacts.supplier_business_name', 'like', '%' . $normalizedQuery . '%')
                    ->orWhere('contacts.contact_id', 'like', '%' . $normalizedQuery . '%');
            });
        }

        if (auth()->check() && ! auth()->user()->can('customer.view') && auth()->user()->can('customer.view_own')) {
            $contacts->onlyOwnContact();
        }

        return $contacts
            ->select(['contacts.id', 'contacts.name', 'contacts.supplier_business_name', 'contacts.contact_id'])
            ->orderBy('contacts.supplier_business_name')
            ->orderBy('contacts.name')
            ->limit($limit)
            ->get()
            ->map(function (Contact $contact) {
                return [
                    'id' => (int) $contact->id,
                    'name' => (string) $contact->name,
                    'supplier_business_name' => (string) ($contact->supplier_business_name ?? ''),
                    'contact_id' => (string) ($contact->contact_id ?? ''),
                    'label' => $this->contactDisplayName($contact),
                ];
            })
            ->values()
            ->all();
    }

    public function listLocations(int $businessId): array
    {
        $query = BusinessLocation::where('business_id', $businessId)->active();

        if (auth()->check()) {
            $permittedLocations = auth()->user()->permitted_locations();
            if ($permittedLocations !== 'all') {
                $query->whereIn('id', (array) $permittedLocations);
            }
        }

        return $query
            ->select(['id', 'name', 'location_id'])
            ->orderBy('name')
            ->get()
            ->map(function (BusinessLocation $location) {
                $label = (string) $location->name;
                if (! empty($location->location_id)) {
                    $label .= ' (' . $location->location_id . ')';
                }

                return [
                    'id' => (int) $location->id,
                    'name' => (string) $location->name,
                    'location_id' => (string) ($location->location_id ?? ''),
                    'label' => $label,
                ];
            })
            ->values()
            ->all();
    }

    public function searchProducts(int $businessId, ?string $query = null, ?int $limit = null): array
    {
        $normalizedQuery = trim((string) $query);
        $limit = $this->normalizeLimit($limit, (int) config('aichat.quote_wizard.max_product_results', 8));

        $products = Product::where('products.business_id', $businessId)
            ->where('products.is_inactive', 0)
            ->where('products.not_for_selling', 0)
            ->where('products.type', '!=', 'modifier');

        if ($normalizedQuery !== '') {
            $products->where(function ($builder) use ($normalizedQuery) {
                $builder->where('products.name', 'like', '%' . $normalizedQuery . '%')
                    ->orWhere('products.sku', 'like', '%' . $normalizedQuery . '%');
            });
        }

        return $products
            ->with(['unit:id,short_name', 'category:id,name'])
            ->select(['products.id', 'products.name', 'products.sku', 'products.unit_id', 'products.category_id'])
            ->orderBy('products.name')
            ->limit($limit)
            ->get()
            ->map(function (Product $product) {
                $label = trim((string) $product->name);
                if (! empty($product->sku)) {
                    $label .= ' [' . $product->sku . ']';
                }

                return [
                    'id' => (int) $product->id,
                    'name' => (string) $product->name,
                    'sku' => (string) ($product->sku ?? ''),
                    'unit' => (string) (optional($product->unit)->short_name ?? ''),
                    'category' => (string) (optional($product->category)->name ?? ''),
                    'label' => $label,
                ];
            })
            ->values()
            ->all();
    }

    public function getCostingDefaults(int $businessId): array
    {
        $options = $this->productCostingUtil->getDropdownOptions($businessId);
        $currencyOptions = (array) ($options['currency'] ?? []);
        $incotermOptions = array_values((array) ($options['incoterm'] ?? []));

        return [
            'default_currency' => $this->productCostingUtil->getDefaultCurrencyCode($businessId),
            'default_incoterm' => $incotermOptions[0] ?? '',
            'currency' => $currencyOptions,
            'incoterm' => $incotermOptions,
            'purchase_uom' => array_values((array) ($options['purchase_uom'] ?? [])),
        ];
    }

    public function processStep(ProductQuoteDraft $draft, ChatConversation $conversation, int $userId, int $businessId, array $input): array
    {
        $message = trim((string) ($input['message'] ?? ''));
        $provider = trim((string) ($input['provider'] ?? ''));
        $model = trim((string) ($input['model'] ?? ''));
        $channel = strtolower(trim((string) ($input['channel'] ?? 'web')));
        if (! in_array($channel, ['web', 'telegram'], true)) {
            $channel = 'web';
        }

        $payload = $this->normalizePayload((array) ($draft->payload ?? []), $businessId);

        $userMessage = null;
        if ($message !== '') {
            $userMessage = $this->chatUtil->appendMessage(
                $conversation,
                ChatMessage::ROLE_USER,
                $message,
                $provider !== '' ? $provider : null,
                $model !== '' ? $model : null,
                $userId
            );

            $lineRemovalIndex = $this->extractLineRemovalIndexFromMessage($message);
            if ($lineRemovalIndex !== null) {
                $payload = $this->removeLineByNumber($payload, $lineRemovalIndex);
            } else {
                $delta = $this->extractDeltaFromMessage($payload, $message, $businessId, $userId, $provider, $model);
                $payload = $this->mergeDeltaIntoPayload($payload, $delta, $message);
            }
        }

        $payload = $this->applySelectionsToPayload($payload, $businessId, $input);
        $payload = $this->applyImplicitDefaults($payload, $businessId);
        $payload = $this->resolvePayloadEntities($payload, $businessId);

        $derived = $this->deriveDraftState($payload, $businessId);
        $assistantText = $this->buildAssistantMessage($derived, $channel);

        $draft->fill([
            'flow' => (string) ($payload['flow'] ?? ProductQuoteDraft::FLOW_MULTI),
            'status' => (string) $derived['status'],
            'payload' => $payload,
            'expires_at' => now()->addHours(max(1, (int) config('aichat.quote_wizard.draft_ttl_hours', 24))),
            'last_interaction_at' => now(),
        ]);
        $draft->save();

        $assistantMessage = $this->chatUtil->appendMessage(
            $conversation,
            ChatMessage::ROLE_ASSISTANT,
            $assistantText,
            $provider !== '' ? $provider : null,
            $model !== '' ? $model : null,
            $userId
        );

        return [
            'draft' => $draft->fresh(),
            'user_message' => $userMessage,
            'assistant_message' => $assistantMessage,
            'state' => $derived,
        ];
    }

    public function serializeDraft(ProductQuoteDraft $draft): array
    {
        $payload = $this->normalizePayload((array) ($draft->payload ?? []), (int) $draft->business_id);
        $derived = $this->deriveDraftState($payload, (int) $draft->business_id);

        return [
            'id' => (string) $draft->id,
            'status' => (string) $derived['status'],
            'flow' => (string) ($payload['flow'] ?? ProductQuoteDraft::FLOW_MULTI),
            'summary' => (array) $derived['summary'],
            'missing_fields' => (array) $derived['missing_fields'],
            'pick_lists' => (array) $derived['pick_lists'],
            'result' => (array) ($payload['result'] ?? []),
        ];
    }

    public function getLatestActiveDraftForChannel(int $businessId, int $userId, ?string $conversationId = null, ?int $telegramChatId = null): ?ProductQuoteDraft
    {
        $draft = ProductQuoteDraft::forBusiness($businessId)
            ->forUser($userId)
            ->forChannel($conversationId, $telegramChatId)
            ->active()
            ->latest('updated_at')
            ->latest('created_at')
            ->first();

        if (! $draft) {
            return null;
        }

        if ($draft->isExpired()) {
            $draft->status = ProductQuoteDraft::STATUS_EXPIRED;
            $draft->save();

            return null;
        }

        return $draft;
    }

    public function expireDraft(ProductQuoteDraft $draft): ProductQuoteDraft
    {
        $draft->status = ProductQuoteDraft::STATUS_EXPIRED;
        $draft->expires_at = now();
        $draft->last_interaction_at = now();
        $draft->save();

        return $draft->fresh();
    }

    public function confirmDraft(ProductQuoteDraft $draft, int $businessId, int $userId): array
    {
        if ($draft->isExpired() || $draft->status !== ProductQuoteDraft::STATUS_READY) {
            throw new \RuntimeException(__('aichat::lang.quote_assistant_draft_not_ready'));
        }

        $confirmed = DB::transaction(function () use ($businessId, $userId, $draft) {
            $lockedDraft = ProductQuoteDraft::forBusiness($businessId)
                ->forUser($userId)
                ->where('id', (string) $draft->id)
                ->lockForUpdate()
                ->first();

            if (! $lockedDraft) {
                throw new \RuntimeException(__('aichat::lang.quote_assistant_draft_not_found'));
            }

            if ($lockedDraft->isExpired()) {
                $lockedDraft->status = ProductQuoteDraft::STATUS_EXPIRED;
                $lockedDraft->save();

                throw new \RuntimeException(__('aichat::lang.quote_assistant_draft_not_ready'));
            }

            if ($lockedDraft->status !== ProductQuoteDraft::STATUS_READY) {
                throw new \RuntimeException(__('aichat::lang.quote_assistant_draft_not_ready'));
            }

            $createContext = $this->buildCreateContext($lockedDraft, $businessId);
            if (($createContext['flow'] ?? ProductQuoteDraft::FLOW_MULTI) === ProductQuoteDraft::FLOW_SINGLE) {
                $quote = $this->quoteUtil->createSingleProductQuote(
                    $businessId,
                    (int) $createContext['product_id'],
                    (array) $createContext['payload'],
                    $userId
                );
            } else {
                $quote = $this->quoteUtil->createMultiProductQuote(
                    $businessId,
                    (array) $createContext['payload'],
                    $userId
                );
            }

            $payload = (array) ($lockedDraft->payload ?? []);
            $payload['result'] = [
                'quote_id' => (int) $quote->id,
                'public_url' => route('product.quotes.public', ['publicToken' => $quote->public_token]),
                'admin_url' => route('product.quotes.show', ['id' => $quote->id]),
            ];

            $lockedDraft->fill([
                'status' => ProductQuoteDraft::STATUS_CONSUMED,
                'consumed_at' => now(),
                'last_interaction_at' => now(),
                'payload' => $payload,
            ]);
            $lockedDraft->save();

            return [
                'quote' => $quote,
                'draft' => $lockedDraft->fresh(),
                'flow' => (string) ($createContext['flow'] ?? ProductQuoteDraft::FLOW_MULTI),
            ];
        });

        $quote = $confirmed['quote'];
        $publicUrl = route('product.quotes.public', ['publicToken' => $quote->public_token]);
        $adminUrl = route('product.quotes.show', ['id' => $quote->id]);

        return [
            'quote' => $quote,
            'draft' => $confirmed['draft'],
            'flow' => (string) $confirmed['flow'],
            'public_url' => $publicUrl,
            'admin_url' => $adminUrl,
        ];
    }

    public function buildCreateContext(ProductQuoteDraft $draft, int $businessId): array
    {
        $payload = $this->normalizePayload((array) ($draft->payload ?? []), $businessId);
        $derived = $this->deriveDraftState($payload, $businessId);

        if ($derived['status'] !== ProductQuoteDraft::STATUS_READY) {
            throw new \RuntimeException(__('aichat::lang.quote_assistant_draft_not_ready'));
        }

        if (($payload['flow'] ?? ProductQuoteDraft::FLOW_MULTI) === ProductQuoteDraft::FLOW_SINGLE) {
            $line = (array) ($payload['lines'][0] ?? []);
            $quotePayload = $this->buildSingleFlowPayload($payload, $line);
            $validator = Validator::make($quotePayload, StoreProductBudgetQuoteRequest::buildRules($businessId));
            StoreProductBudgetQuoteRequest::applyAdditionalValidation($validator, $quotePayload, $businessId);
            $validator->validate();

            return [
                'flow' => ProductQuoteDraft::FLOW_SINGLE,
                'product_id' => (int) ($line['product_id'] ?? 0),
                'payload' => $quotePayload,
            ];
        }

        $quotePayload = $this->buildMultiFlowPayload($payload);
        $validator = Validator::make($quotePayload, StoreProductQuoteRequest::buildRules($businessId));
        StoreProductQuoteRequest::applyAdditionalValidation($validator, $quotePayload, $businessId);
        $validator->validate();

        return [
            'flow' => ProductQuoteDraft::FLOW_MULTI,
            'payload' => $quotePayload,
        ];
    }

    protected function normalizePayload(array $payload, int $businessId): array
    {
        $normalized = array_replace_recursive($this->defaultPayload($businessId), $payload);
        $normalized['flow'] = in_array(($normalized['flow'] ?? ProductQuoteDraft::FLOW_MULTI), [ProductQuoteDraft::FLOW_MULTI, ProductQuoteDraft::FLOW_SINGLE], true)
            ? $normalized['flow']
            : ProductQuoteDraft::FLOW_MULTI;
        $normalized['lines'] = array_values(array_map(function ($line) {
            return $this->normalizeLine((array) $line);
        }, (array) ($normalized['lines'] ?? [])));

        return $normalized;
    }

    protected function normalizeLine(array $line): array
    {
        return [
            'uid' => (string) ($line['uid'] ?? Str::uuid()),
            'product_id' => ! empty($line['product_id']) ? (int) $line['product_id'] : null,
            'product_hint' => $this->nullableString($line['product_hint'] ?? null),
            'qty' => isset($line['qty']) && $line['qty'] !== '' ? (float) $line['qty'] : null,
            'currency' => $this->nullableString($line['currency'] ?? null),
            'incoterm' => $this->nullableString($line['incoterm'] ?? null),
            'purchase_uom' => $this->nullableString($line['purchase_uom'] ?? null),
            'base_mill_price' => isset($line['base_mill_price']) && $line['base_mill_price'] !== '' ? (float) $line['base_mill_price'] : null,
            'line_total_price' => isset($line['line_total_price']) && $line['line_total_price'] !== '' ? (float) $line['line_total_price'] : null,
            'price_mode' => $this->nullableString($line['price_mode'] ?? null),
            'test_cost' => isset($line['test_cost']) && $line['test_cost'] !== '' ? (float) $line['test_cost'] : null,
            'surcharge' => isset($line['surcharge']) && $line['surcharge'] !== '' ? (float) $line['surcharge'] : null,
            'finish_uplift_pct' => isset($line['finish_uplift_pct']) && $line['finish_uplift_pct'] !== '' ? (float) $line['finish_uplift_pct'] : null,
            'waste_pct' => isset($line['waste_pct']) && $line['waste_pct'] !== '' ? (float) $line['waste_pct'] : null,
        ];
    }

    protected function extractDeltaFromMessage(array $payload, string $message, int $businessId, int $userId, string $provider, string $model): array
    {
        $providerContext = $this->resolveModelContext($businessId, $userId, $provider, $model);
        $defaults = $this->getCostingDefaults($businessId);

        $response = $this->aiChatUtil->generateText(
            $providerContext['provider'],
            $providerContext['api_key'],
            $providerContext['model'],
            [
                [
                    'role' => 'system',
                    'content' => "You extract only structured quote-draft deltas from a user message.\nReturn JSON only.\nAllowed top-level keys: flow, customer_hint, location_hint, expires_in_days, shipment_port, remark, lines.\nflow must be multi or single.\nlines must be an array of objects. Each line object may include: line_index, product_hint, qty, currency, incoterm, purchase_uom, base_mill_price, line_total_price, price_mode, test_cost, surcharge, finish_uplift_pct, waste_pct.\nline_index is optional and 1-based when the user explicitly mentions a line number (for example: line 1, line 2).\nIf user says line total/total price, set price_mode=total and set line_total_price.\nIf user gives unit price, set price_mode=unit and set base_mill_price.\nUPOS creates quotes only when the user confirms in the app.\nDo not claim the flow cannot save, create, or modify records.\nDo not suggest manual dashboard entry as the primary path.\nDo not include guessed IDs.\nDo not wrap the JSON in markdown.",
                ],
                [
                    'role' => 'user',
                    'content' => json_encode([
                        'current_draft' => $this->payloadForExtractionContext($payload),
                        'defaults' => [
                            'default_currency' => $defaults['default_currency'] ?? 'USD',
                            'allowed_incoterms' => array_values((array) ($defaults['incoterm'] ?? [])),
                        ],
                        'message' => $message,
                    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                ],
            ]
        );

        $decoded = $this->decodeJsonObject($response);
        if (! is_array($decoded)) {
            return [];
        }

        return [
            'flow' => $this->nullableString($decoded['flow'] ?? null),
            'customer_hint' => $this->nullableString($decoded['customer_hint'] ?? null),
            'location_hint' => $this->nullableString($decoded['location_hint'] ?? null),
            'expires_in_days' => isset($decoded['expires_in_days']) && $decoded['expires_in_days'] !== '' ? (int) $decoded['expires_in_days'] : null,
            'shipment_port' => $this->nullableString($decoded['shipment_port'] ?? null),
            'remark' => $this->nullableString($decoded['remark'] ?? null),
            'lines' => array_values(array_filter(array_map(function ($line) {
                if (! is_array($line)) {
                    return null;
                }

                return [
                    'line_index' => isset($line['line_index']) && $line['line_index'] !== '' ? (int) $line['line_index'] : null,
                    'product_hint' => $this->nullableString($line['product_hint'] ?? null),
                    'qty' => isset($line['qty']) && $line['qty'] !== '' ? (float) $line['qty'] : null,
                    'currency' => $this->nullableString($line['currency'] ?? null),
                    'incoterm' => $this->nullableString($line['incoterm'] ?? null),
                    'purchase_uom' => $this->nullableString($line['purchase_uom'] ?? null),
                    'base_mill_price' => isset($line['base_mill_price']) && $line['base_mill_price'] !== '' ? (float) $line['base_mill_price'] : null,
                    'line_total_price' => isset($line['line_total_price']) && $line['line_total_price'] !== '' ? (float) $line['line_total_price'] : null,
                    'price_mode' => $this->nullableString($line['price_mode'] ?? null),
                    'test_cost' => isset($line['test_cost']) && $line['test_cost'] !== '' ? (float) $line['test_cost'] : null,
                    'surcharge' => isset($line['surcharge']) && $line['surcharge'] !== '' ? (float) $line['surcharge'] : null,
                    'finish_uplift_pct' => isset($line['finish_uplift_pct']) && $line['finish_uplift_pct'] !== '' ? (float) $line['finish_uplift_pct'] : null,
                    'waste_pct' => isset($line['waste_pct']) && $line['waste_pct'] !== '' ? (float) $line['waste_pct'] : null,
                ];
            }, (array) ($decoded['lines'] ?? [])))),
        ];
    }

    protected function payloadForExtractionContext(array $payload): array
    {
        return [
            'flow' => $payload['flow'] ?? ProductQuoteDraft::FLOW_MULTI,
            'customer_hint' => data_get($payload, 'contact.hint'),
            'contact_id' => data_get($payload, 'contact.contact_id'),
            'location_hint' => data_get($payload, 'location.hint'),
            'location_id' => data_get($payload, 'location.location_id'),
            'expires_at' => $payload['expires_at'] ?? null,
            'shipment_port' => $payload['shipment_port'] ?? null,
            'lines' => array_map(function ($line) {
                return [
                    'product_hint' => $line['product_hint'] ?? null,
                    'product_id' => $line['product_id'] ?? null,
                    'qty' => $line['qty'] ?? null,
                    'currency' => $line['currency'] ?? null,
                    'incoterm' => $line['incoterm'] ?? null,
                ];
            }, (array) ($payload['lines'] ?? [])),
        ];
    }

    protected function resolveModelContext(int $businessId, int $userId, string $provider, string $model): array
    {
        $settings = $this->chatUtil->getOrCreateBusinessSettings($businessId);
        $resolvedProvider = $provider !== ''
            ? strtolower($provider)
            : strtolower((string) ($settings->default_provider ?: config('aichat.chat.default_provider', 'openai')));
        $resolvedModel = $model !== ''
            ? $model
            : (string) ($settings->default_model ?: config('aichat.chat.default_model', 'gpt-4o-mini'));

        if (! $this->chatUtil->isModelAllowedForBusiness($businessId, $resolvedProvider, $resolvedModel)) {
            throw new \RuntimeException(__('aichat::lang.chat_validation_model_invalid'));
        }

        $credential = $this->chatUtil->resolveCredentialForChat($userId, $businessId, $resolvedProvider);
        if (! $credential) {
            throw new \RuntimeException(__('aichat::lang.quote_assistant_provider_required'));
        }

        return [
            'provider' => $resolvedProvider,
            'model' => $resolvedModel,
            'api_key' => $this->chatUtil->decryptApiKey((string) $credential->encrypted_api_key),
        ];
    }

    protected function mergeDeltaIntoPayload(array $payload, array $delta, string $message = ''): array
    {
        $flow = (string) ($delta['flow'] ?? '');
        if (in_array($flow, [ProductQuoteDraft::FLOW_MULTI, ProductQuoteDraft::FLOW_SINGLE], true)) {
            $payload['flow'] = $flow;
        }

        if (! empty($delta['customer_hint'])) {
            $payload['contact']['hint'] = $delta['customer_hint'];
            $payload['contact']['contact_id'] = null;
        }

        if (! empty($delta['location_hint'])) {
            $payload['location']['hint'] = $delta['location_hint'];
            $payload['location']['location_id'] = null;
        }

        if (! empty($delta['expires_in_days'])) {
            $payload['expires_at'] = now()->addDays(max(1, (int) $delta['expires_in_days']))->toDateString();
        }

        if (array_key_exists('shipment_port', $delta) && $delta['shipment_port'] !== null) {
            $payload['shipment_port'] = $delta['shipment_port'];
        }

        if (array_key_exists('remark', $delta) && $delta['remark'] !== null) {
            $payload['remark'] = $delta['remark'];
        }

        foreach ((array) ($delta['lines'] ?? []) as $lineDelta) {
            $targetIndex = $this->findMergeableLineIndex($payload['lines'], $lineDelta, $message);
            $existingLine = $targetIndex !== null ? (array) ($payload['lines'][$targetIndex] ?? []) : [];
            $lineDelta = $this->normalizeLineDeltaPricing((array) $lineDelta, $message, $existingLine);
            if ($targetIndex === null) {
                $payload['lines'][] = array_merge($this->normalizeLine([]), $lineDelta);
                continue;
            }

            $payload['lines'][$targetIndex] = array_merge($payload['lines'][$targetIndex], array_filter($lineDelta, function ($value) {
                return $value !== null && $value !== '';
            }));
        }

        if (($payload['flow'] ?? ProductQuoteDraft::FLOW_MULTI) === ProductQuoteDraft::FLOW_SINGLE && ! empty($payload['lines'])) {
            $payload['lines'] = [reset($payload['lines'])];
        }

        return $payload;
    }

    protected function normalizeLineDeltaPricing(array $lineDelta, string $message = '', array $existingLine = []): array
    {
        $normalized = $lineDelta;
        $qty = isset($lineDelta['qty']) && $lineDelta['qty'] !== ''
            ? (float) $lineDelta['qty']
            : (isset($existingLine['qty']) && $existingLine['qty'] !== '' ? (float) $existingLine['qty'] : null);
        $unitPrice = isset($lineDelta['base_mill_price']) && $lineDelta['base_mill_price'] !== '' ? (float) $lineDelta['base_mill_price'] : null;
        $lineTotalPrice = isset($lineDelta['line_total_price']) && $lineDelta['line_total_price'] !== '' ? (float) $lineDelta['line_total_price'] : null;
        $priceMode = strtolower(trim((string) ($lineDelta['price_mode'] ?? '')));

        if ($priceMode === '' && $this->messageSuggestsTotalPrice($message)) {
            $priceMode = 'total';
        }
        if ($lineTotalPrice !== null) {
            $priceMode = 'total';
        }

        if ($priceMode === 'total') {
            $totalPrice = $lineTotalPrice ?? $unitPrice;
            if ($totalPrice !== null && $qty !== null && $qty > 0) {
                $normalized['base_mill_price'] = round($totalPrice / $qty, 4);
            }
        }

        unset($normalized['line_total_price'], $normalized['price_mode'], $normalized['line_index']);

        return $normalized;
    }

    protected function messageSuggestsTotalPrice(string $message): bool
    {
        if ($message === '') {
            return false;
        }

        return (bool) preg_match('/\b(line\s*total|total\s*price|price\s*total|amount\s*total|grand\s*total|total\s*amount)\b/i', $message);
    }

    protected function findMergeableLineIndex(array $lines, array $lineDelta, string $message = ''): ?int
    {
        if (empty($lines)) {
            return null;
        }

        $lineNumber = isset($lineDelta['line_index']) && $lineDelta['line_index'] !== ''
            ? (int) $lineDelta['line_index']
            : (int) ($this->extractLineNumberFromMessage($message) ?? 0);
        if ($lineNumber > 0) {
            $targetIndex = $lineNumber - 1;
            if ($targetIndex < count($lines)) {
                return $targetIndex;
            }

            // Explicit out-of-range line number means append a new line.
            return null;
        }

        if ($this->messageSuggestsNewLine($message)) {
            return null;
        }

        $incompleteIndexes = [];
        foreach ($lines as $index => $line) {
            if (empty($line['product_id']) || empty($line['qty']) || empty($line['currency'])) {
                $incompleteIndexes[] = $index;
            }
        }

        if (count($incompleteIndexes) === 1) {
            return $incompleteIndexes[0];
        }

        $lineHint = strtolower(trim((string) ($lineDelta['product_hint'] ?? '')));
        if ($lineHint !== '') {
            foreach ($lines as $index => $line) {
                $existingHint = strtolower(trim((string) ($line['product_hint'] ?? '')));
                if ($existingHint === '') {
                    continue;
                }

                if (str_contains($existingHint, $lineHint) || str_contains($lineHint, $existingHint)) {
                    return $index;
                }
            }
        }

        if (! empty($incompleteIndexes)) {
            return $incompleteIndexes[0];
        }

        return ! empty($lineDelta['product_hint']) ? null : count($lines) - 1;
    }

    protected function extractLineNumberFromMessage(string $message): ?int
    {
        if ($message === '') {
            return null;
        }

        if (! preg_match('/\\bline\\s*(\\d+)\\b/i', $message, $matches)) {
            return null;
        }

        $lineNumber = (int) ($matches[1] ?? 0);

        return $lineNumber > 0 ? $lineNumber : null;
    }

    protected function extractLineRemovalIndexFromMessage(string $message): ?int
    {
        if ($message === '') {
            return null;
        }

        $matches = [];
        if (
            ! preg_match('/\b(?:remove|delete|drop)\s+line\s*(\d+)\b/i', $message, $matches)
            && ! preg_match('/\bline\s*(\d+)\s*(?:remove|delete|drop)\b/i', $message, $matches)
        ) {
            return null;
        }

        $lineNumber = (int) ($matches[1] ?? 0);

        return $lineNumber > 0 ? $lineNumber : null;
    }

    protected function removeLineByNumber(array $payload, int $lineNumber): array
    {
        if ($lineNumber <= 0) {
            return $payload;
        }

        $targetIndex = $lineNumber - 1;
        if (! isset($payload['lines'][$targetIndex])) {
            return $payload;
        }

        unset($payload['lines'][$targetIndex]);
        $payload['lines'] = array_values((array) ($payload['lines'] ?? []));

        return $payload;
    }

    protected function messageSuggestsNewLine(string $message): bool
    {
        if ($message === '') {
            return false;
        }

        return (bool) preg_match('/\\b(new\\s*line|add\\s*line|another\\s*line|next\\s*line)\\b/i', $message);
    }

    protected function applySelectionsToPayload(array $payload, int $businessId, array $input): array
    {
        $selectedRemoveLineUid = (string) ($input['selected_remove_line_uid'] ?? '');
        if ($selectedRemoveLineUid !== '') {
            $payload['lines'] = array_values(array_filter((array) ($payload['lines'] ?? []), function ($line) use ($selectedRemoveLineUid) {
                return (string) ($line['uid'] ?? '') !== $selectedRemoveLineUid;
            }));
        }

        $selectedContactId = (int) ($input['selected_contact_id'] ?? 0);
        if ($selectedContactId > 0) {
            $contact = Contact::where('business_id', $businessId)
                ->whereIn('type', ['customer', 'both'])
                ->findOrFail($selectedContactId);

            $payload['contact']['contact_id'] = (int) $contact->id;
            $payload['contact']['hint'] = $this->contactDisplayName($contact);
        }

        $selectedProductId = (int) ($input['selected_product_id'] ?? 0);
        if ($selectedProductId > 0) {
            $selectedLineUid = (string) ($input['selected_line_uid'] ?? '');
            $product = Product::where('business_id', $businessId)->findOrFail($selectedProductId);

            foreach ($payload['lines'] as $index => $line) {
                if ($selectedLineUid !== '' && (string) ($line['uid'] ?? '') !== $selectedLineUid) {
                    continue;
                }

                $payload['lines'][$index]['product_id'] = (int) $product->id;
                $payload['lines'][$index]['product_hint'] = trim((string) ($product->name . (! empty($product->sku) ? ' [' . $product->sku . ']' : '')));
                break;
            }
        }

        return $payload;
    }

    protected function applyImplicitDefaults(array $payload, int $businessId): array
    {
        $defaults = $this->getCostingDefaults($businessId);
        $defaultCurrency = (string) ($defaults['default_currency'] ?? 'USD');

        if (! data_get($payload, 'location.location_id')) {
            $locations = $this->listLocations($businessId);
            if (count($locations) === 1) {
                $payload['location']['location_id'] = (int) ($locations[0]['id'] ?? 0);
                $payload['location']['hint'] = (string) ($locations[0]['label'] ?? '');
            }
        }

        if (empty($payload['expires_at'])) {
            $payload['expires_at'] = now()->addDays(max(1, (int) config('product.quote_defaults.expiry_days', 14)))->toDateString();
        }

        $sharedCurrency = null;
        $sharedIncoterm = null;
        foreach ($payload['lines'] as $line) {
            if ($sharedCurrency === null && ! empty($line['currency'])) {
                $sharedCurrency = (string) $line['currency'];
            }
            if ($sharedIncoterm === null && array_key_exists('incoterm', $line) && $line['incoterm'] !== null && $line['incoterm'] !== '') {
                $sharedIncoterm = (string) $line['incoterm'];
            }
        }

        foreach ($payload['lines'] as $index => $line) {
            if (empty($line['currency'])) {
                $payload['lines'][$index]['currency'] = $sharedCurrency ?: $defaultCurrency;
            }

            if (trim((string) ($payload['shipment_port'] ?? '')) === '') {
                $payload['lines'][$index]['incoterm'] = $line['incoterm'] ?? '';
                continue;
            }

            if ($line['incoterm'] === null || $line['incoterm'] === '') {
                $payload['lines'][$index]['incoterm'] = $sharedIncoterm;
            }
        }

        return $payload;
    }

    protected function resolvePayloadEntities(array $payload, int $businessId): array
    {
        if (! data_get($payload, 'contact.contact_id') && ! empty(data_get($payload, 'contact.hint'))) {
            $contactCandidates = $this->searchContacts($businessId, (string) data_get($payload, 'contact.hint'));
            if (count($contactCandidates) === 1) {
                $payload['contact']['contact_id'] = (int) $contactCandidates[0]['id'];
            }
        }

        if (! data_get($payload, 'location.location_id') && ! empty(data_get($payload, 'location.hint'))) {
            $hint = strtolower(trim((string) data_get($payload, 'location.hint')));
            foreach ($this->listLocations($businessId) as $location) {
                $haystack = strtolower(trim((string) (($location['label'] ?? '') . ' ' . ($location['location_id'] ?? '') . ' ' . ($location['name'] ?? ''))));
                if ($hint !== '' && $haystack !== '' && str_contains($haystack, $hint)) {
                    $payload['location']['location_id'] = (int) ($location['id'] ?? 0);
                    $payload['location']['hint'] = (string) ($location['label'] ?? '');
                    break;
                }
            }
        }

        foreach ($payload['lines'] as $index => $line) {
            if (! empty($line['product_id']) || empty($line['product_hint'])) {
                continue;
            }

            $productCandidates = $this->searchProducts($businessId, (string) $line['product_hint']);
            if (count($productCandidates) === 1) {
                $payload['lines'][$index]['product_id'] = (int) $productCandidates[0]['id'];
                $payload['lines'][$index]['product_hint'] = (string) ($productCandidates[0]['label'] ?? $line['product_hint']);
            }
        }

        return $payload;
    }

    protected function deriveDraftState(array $payload, int $businessId): array
    {
        $missingFields = [];
        $pickLists = ['contacts' => [], 'products' => []];

        if (! data_get($payload, 'contact.contact_id')) {
            $missingFields[] = ['key' => 'contact_id', 'label' => __('aichat::lang.quote_assistant_customer_required')];
            if (! empty(data_get($payload, 'contact.hint'))) {
                $pickLists['contacts'] = $this->searchContacts($businessId, (string) data_get($payload, 'contact.hint'));
            }
        }

        if (! data_get($payload, 'location.location_id')) {
            $missingFields[] = ['key' => 'location_id', 'label' => __('aichat::lang.quote_assistant_location_required')];
        }

        $lines = array_values((array) ($payload['lines'] ?? []));
        if (empty($lines)) {
            $missingFields[] = ['key' => 'lines', 'label' => __('aichat::lang.quote_assistant_line_required')];
        }

        foreach ($lines as $index => $line) {
            $lineLabel = __('aichat::lang.quote_assistant_line_label', ['number' => $index + 1]);

            if (empty($line['product_id'])) {
                $missingFields[] = ['key' => 'lines.' . $index . '.product_id', 'label' => $lineLabel . ': ' . __('aichat::lang.quote_assistant_product_required')];
                if (! empty($line['product_hint'])) {
                    $pickLists['products'][] = [
                        'line_uid' => (string) ($line['uid'] ?? ''),
                        'label' => $lineLabel,
                        'options' => $this->searchProducts($businessId, (string) $line['product_hint']),
                    ];
                }
            }

            if (empty($line['qty'])) {
                $missingFields[] = ['key' => 'lines.' . $index . '.qty', 'label' => $lineLabel . ': ' . __('aichat::lang.quote_assistant_qty_required')];
            }

            if (empty($line['currency'])) {
                $missingFields[] = ['key' => 'lines.' . $index . '.currency', 'label' => $lineLabel . ': ' . __('aichat::lang.quote_assistant_currency_required')];
            }

            if (trim((string) ($payload['shipment_port'] ?? '')) !== '' && ($line['incoterm'] === null || $line['incoterm'] === '')) {
                $missingFields[] = ['key' => 'lines.' . $index . '.incoterm', 'label' => $lineLabel . ': ' . __('aichat::lang.quote_assistant_incoterm_required')];
            }
        }

        if (count($lines) > 1) {
            $firstCurrency = (string) ($lines[0]['currency'] ?? '');
            $firstIncoterm = (string) ($lines[0]['incoterm'] ?? '');

            foreach ($lines as $line) {
                $currency = (string) ($line['currency'] ?? '');
                $incoterm = (string) ($line['incoterm'] ?? '');
                if ($currency !== '' && $firstCurrency !== '' && ($currency !== $firstCurrency || $incoterm !== $firstIncoterm)) {
                    $missingFields[] = [
                        'key' => 'lines.shared_currency_incoterm',
                        'label' => __('product.quote_shared_currency_incoterm_required'),
                    ];
                    break;
                }
            }
        }

        $status = empty($missingFields) && empty($pickLists['contacts']) && empty($pickLists['products'])
            ? ProductQuoteDraft::STATUS_READY
            : ProductQuoteDraft::STATUS_COLLECTING;

        return [
            'status' => $status,
            'missing_fields' => array_values($missingFields),
            'pick_lists' => $pickLists,
            'summary' => $this->buildSummary($payload),
        ];
    }

    protected function buildSummary(array $payload): array
    {
        $summaryLines = collect((array) ($payload['lines'] ?? []))
            ->map(function ($line, $index) {
                $parts = [];
                $parts[] = ! empty($line['product_hint']) ? (string) $line['product_hint'] : __('aichat::lang.quote_assistant_product_required');
                if (! empty($line['qty'])) {
                    $parts[] = 'qty ' . rtrim(rtrim(number_format((float) $line['qty'], 4, '.', ''), '0'), '.');
                }
                if (! empty($line['currency'])) {
                    $parts[] = (string) $line['currency'];
                }
                if ($line['incoterm'] !== null && $line['incoterm'] !== '') {
                    $parts[] = (string) $line['incoterm'];
                }

                return [
                    'line_uid' => (string) ($line['uid'] ?? ''),
                    'label' => __('aichat::lang.quote_assistant_line_label', ['number' => $index + 1]),
                    'text' => implode(' | ', array_filter($parts)),
                ];
            })
            ->values()
            ->all();

        return [
            'customer' => (string) (data_get($payload, 'contact.hint') ?: '-'),
            'location' => (string) (data_get($payload, 'location.hint') ?: '-'),
            'expires_at' => (string) ($payload['expires_at'] ?? '-'),
            'line_count' => count($summaryLines),
            'lines' => $summaryLines,
        ];
    }

    protected function buildAssistantMessage(array $derived, string $channel = 'web'): string
    {
        if (! empty($derived['pick_lists']['contacts'])) {
            if ($channel === 'telegram') {
                return $this->buildTelegramContactPickMessage((array) $derived['pick_lists']['contacts']);
            }

            return __('aichat::lang.quote_assistant_contact_pick_prompt');
        }

        if (! empty($derived['pick_lists']['products'])) {
            if ($channel === 'telegram') {
                return $this->buildTelegramProductPickMessage((array) $derived['pick_lists']['products']);
            }

            return __('aichat::lang.quote_assistant_product_pick_prompt');
        }

        if (($derived['status'] ?? ProductQuoteDraft::STATUS_COLLECTING) === ProductQuoteDraft::STATUS_READY) {
            return $this->buildReadyAssistantMessage((array) ($derived['summary'] ?? []), $channel);
        }

        $missingKeys = collect((array) ($derived['missing_fields'] ?? []))->pluck('key')->all();
        if (in_array('contact_id', $missingKeys, true)) {
            return __('aichat::lang.quote_assistant_need_customer_prompt');
        }
        if (in_array('location_id', $missingKeys, true)) {
            return __('aichat::lang.quote_assistant_need_location_prompt');
        }
        if (in_array('lines', $missingKeys, true)) {
            return __('aichat::lang.quote_assistant_need_line_prompt');
        }
        foreach ($missingKeys as $key) {
            if (str_ends_with((string) $key, '.incoterm')) {
                return __('aichat::lang.quote_assistant_need_incoterm_prompt');
            }
        }

        return __('aichat::lang.quote_assistant_need_more_prompt');
    }

    protected function buildReadyAssistantMessage(array $summary, string $channel = 'web'): string
    {
        $lines = [
            __('aichat::lang.quote_assistant_ready_summary_title'),
            __('aichat::lang.quote_assistant_customer_label') . ': ' . (string) ($summary['customer'] ?? '-'),
            __('aichat::lang.quote_assistant_location_label') . ': ' . (string) ($summary['location'] ?? '-'),
            __('aichat::lang.quote_assistant_expires_label') . ': ' . (string) ($summary['expires_at'] ?? '-'),
        ];

        $summaryLines = (array) ($summary['lines'] ?? []);
        if (! empty($summaryLines)) {
            $lines[] = __('aichat::lang.quote_assistant_lines_label') . ':';
            foreach ($summaryLines as $line) {
                $label = trim((string) ($line['label'] ?? ''));
                $text = trim((string) ($line['text'] ?? ''));
                $lineText = trim($label . ($text !== '' ? ': ' . $text : ''));
                if ($lineText !== '') {
                    $lines[] = '- ' . $lineText;
                }
            }
        }

        $lines[] = '';
        $lines[] = $channel === 'telegram'
            ? __('aichat::lang.quote_assistant_ready_confirm_cta_telegram')
            : __('aichat::lang.quote_assistant_ready_confirm_cta_web');
        $lines[] = __('aichat::lang.quote_assistant_ready_after_create_note');

        return implode("\n", $lines);
    }

    protected function buildTelegramContactPickMessage(array $contacts): string
    {
        $lines = [__('aichat::lang.quote_assistant_telegram_pick_customer_prompt')];

        foreach ($contacts as $index => $contact) {
            $lines[] = ($index + 1) . '. ' . (string) ($contact['label'] ?? $contact['name'] ?? '');
        }

        $lines[] = __('aichat::lang.quote_assistant_telegram_pick_customer_hint');

        return implode("\n", $lines);
    }

    protected function buildTelegramProductPickMessage(array $groups): string
    {
        $lines = [__('aichat::lang.quote_assistant_telegram_pick_product_prompt')];

        foreach ($groups as $groupIndex => $group) {
            $lineNumber = $groupIndex + 1;
            $groupLabel = trim((string) ($group['label'] ?? __('aichat::lang.quote_assistant_line_label', ['number' => $lineNumber])));
            $lines[] = $groupLabel . ':';

            foreach ((array) ($group['options'] ?? []) as $optionIndex => $option) {
                $lines[] = '  ' . ($optionIndex + 1) . '. ' . (string) ($option['label'] ?? $option['name'] ?? '');
            }
        }

        $lines[] = __('aichat::lang.quote_assistant_telegram_pick_product_hint');

        return implode("\n", $lines);
    }

    protected function buildMultiFlowPayload(array $payload): array
    {
        return [
            'contact_id' => (int) data_get($payload, 'contact.contact_id'),
            'location_id' => (int) data_get($payload, 'location.location_id'),
            'quote_date' => (string) ($payload['quote_date'] ?? now()->toDateString()),
            'expires_at' => (string) ($payload['expires_at'] ?? now()->toDateString()),
            'shipment_port' => (string) ($payload['shipment_port'] ?? ''),
            'remark' => (string) ($payload['remark'] ?? ''),
            'lines' => array_values(array_map(function ($line) {
                return [
                    'line_type' => 'product',
                    'product_id' => (int) ($line['product_id'] ?? 0),
                    'qty' => (float) ($line['qty'] ?? 0),
                    'purchase_uom' => (string) ($line['purchase_uom'] ?? ''),
                    'base_mill_price' => $line['base_mill_price'] ?? null,
                    'test_cost' => $line['test_cost'] ?? null,
                    'surcharge' => $line['surcharge'] ?? null,
                    'finish_uplift_pct' => $line['finish_uplift_pct'] ?? null,
                    'waste_pct' => $line['waste_pct'] ?? null,
                    'currency' => (string) ($line['currency'] ?? ''),
                    'incoterm' => (string) ($line['incoterm'] ?? ''),
                ];
            }, (array) ($payload['lines'] ?? []))),
        ];
    }

    protected function buildSingleFlowPayload(array $payload, array $line): array
    {
        return [
            'contact_id' => (int) data_get($payload, 'contact.contact_id'),
            'location_id' => (int) data_get($payload, 'location.location_id'),
            'quote_date' => (string) ($payload['quote_date'] ?? now()->toDateString()),
            'expires_at' => (string) ($payload['expires_at'] ?? now()->toDateString()),
            'shipment_port' => (string) ($payload['shipment_port'] ?? ''),
            'remark' => (string) ($payload['remark'] ?? ''),
            'qty' => (float) ($line['qty'] ?? 0),
            'purchase_uom' => (string) ($line['purchase_uom'] ?? ''),
            'base_mill_price' => $line['base_mill_price'] ?? null,
            'test_cost' => $line['test_cost'] ?? null,
            'surcharge' => $line['surcharge'] ?? null,
            'finish_uplift_pct' => $line['finish_uplift_pct'] ?? null,
            'waste_pct' => $line['waste_pct'] ?? null,
            'currency' => (string) ($line['currency'] ?? ''),
            'incoterm' => (string) ($line['incoterm'] ?? ''),
        ];
    }

    protected function decodeJsonObject(string $raw): ?array
    {
        $decoded = json_decode(trim($raw), true);
        if (is_array($decoded)) {
            return $decoded;
        }

        if (preg_match('/\{.*\}/s', $raw, $matches)) {
            $decoded = json_decode($matches[0], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    protected function nullableString($value): ?string
    {
        $normalized = trim((string) $value);

        return $normalized === '' ? null : $normalized;
    }

    protected function defaultPayload(int $businessId): array
    {
        $defaultExpiryDays = max(1, (int) config('product.quote_defaults.expiry_days', 14));
        $defaults = $this->getCostingDefaults($businessId);

        return [
            'flow' => ProductQuoteDraft::FLOW_MULTI,
            'contact' => [
                'contact_id' => null,
                'hint' => null,
            ],
            'location' => [
                'location_id' => null,
                'hint' => null,
            ],
            'quote_date' => now()->toDateString(),
            'expires_at' => now()->addDays($defaultExpiryDays)->toDateString(),
            'shipment_port' => '',
            'remark' => null,
            'lines' => [],
            'defaults' => [
                'currency' => (string) ($defaults['default_currency'] ?? 'USD'),
                'incoterm' => (string) ($defaults['default_incoterm'] ?? ''),
            ],
            'result' => [],
        ];
    }

    protected function normalizeLimit(?int $limit, int $default): int
    {
        $value = (int) ($limit ?: $default);

        return max(1, min(25, $value));
    }

    protected function contactDisplayName(Contact $contact): string
    {
        $supplierBusinessName = trim((string) ($contact->supplier_business_name ?? ''));
        $name = trim((string) ($contact->name ?? ''));

        if ($supplierBusinessName !== '' && $name !== '' && strcasecmp($supplierBusinessName, $name) !== 0) {
            return $supplierBusinessName . ' - ' . $name;
        }

        return $supplierBusinessName !== '' ? $supplierBusinessName : $name;
    }
}
