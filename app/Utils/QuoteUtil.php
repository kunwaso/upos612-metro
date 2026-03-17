<?php

namespace App\Utils;

use App\Business;
use App\Contact;
use App\Product;
use App\ProductQuote;
use App\ProductQuoteLine;
use App\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class QuoteUtil
{
    protected ProductCostingUtil $costingUtil;

    public function __construct(ProductCostingUtil $costingUtil)
    {
        $this->costingUtil = $costingUtil;
    }

    public function createSingleProductQuote(int $business_id, int $product_id, array $payload, ?int $created_by = null): ProductQuote
    {
        $product = Product::where('business_id', $business_id)
            ->with([
                'unit:id,short_name',
                'category:id,name',
            ])
            ->findOrFail($product_id);
        $contact = $this->resolveCustomerContact($business_id, (int) $payload['contact_id']);

        $linePayload = $this->costingUtil->buildLinePayload($product, $payload);

        return $this->persistQuote($business_id, $contact, $payload, [$linePayload], $created_by);
    }

    public function createMultiProductQuote(int $business_id, array $payload, ?int $created_by = null): ProductQuote
    {
        $contact = $this->resolveCustomerContact($business_id, (int) $payload['contact_id']);
        $linesInput = $payload['lines'] ?? [];

        if (empty($linesInput)) {
            throw new \InvalidArgumentException(__('product.quote_lines_required'));
        }

        $productIds = collect($linesInput)
            ->pluck('product_id')
            ->map(function ($productId) {
                return (int) $productId;
            })
            ->filter()
            ->unique()
            ->values()
            ->all();

        $products = Product::where('business_id', $business_id)
            ->whereIn('id', $productIds)
            ->with([
                'unit:id,short_name',
                'category:id,name',
            ])
            ->get()
            ->keyBy('id');

        $linePayloads = [];

        foreach ($linesInput as $index => $lineInput) {
            $productId = (int) ($lineInput['product_id'] ?? 0);
            if ($productId <= 0 || ! isset($products[$productId])) {
                throw new \InvalidArgumentException(__('product.quote_product_invalid'));
            }

            $linePayload = $this->costingUtil->buildLinePayload($products[$productId], $lineInput);
            $linePayload['sort_order'] = $index;
            $linePayloads[] = $linePayload;
        }

        $this->costingUtil->assertSharedCurrencyAndIncoterm($linePayloads);

        return $this->persistQuote($business_id, $contact, $payload, $linePayloads, $created_by);
    }

    public function getLatestQuoteForProduct(int $business_id, int $product_id): ?ProductQuote
    {
        return ProductQuote::forBusiness($business_id)
            ->whereHas('lines', function ($query) use ($product_id) {
                $query->where('product_id', $product_id);
            })
            ->with($this->quoteRelations())
            ->orderByDesc('id')
            ->first();
    }

    public function getQuoteByIdForBusiness(int $business_id, int $quote_id): ProductQuote
    {
        return ProductQuote::forBusiness($business_id)
            ->with($this->quoteRelations())
            ->findOrFail($quote_id);
    }

    public function getContextForChat(int $business_id, int $quote_id): string
    {
        $quote = ProductQuote::forBusiness($business_id)
            ->with([
                'contact:id,name,supplier_business_name,email,contact_id',
                'location:id,name',
                'transaction:id,invoice_no,status,type,final_total,transaction_date',
                'lines.product:id,name,sku',
            ])
            ->findOrFail($quote_id);

        $quoteDate = optional($quote->quote_date ?: $quote->created_at)->format('Y-m-d');
        $expiresAt = optional($quote->expires_at)->format('Y-m-d');
        $customerName = trim((string) (
            $quote->customer_name
            ?: optional($quote->contact)->supplier_business_name
            ?: optional($quote->contact)->name
            ?: '-'
        ));
        $customerEmail = trim((string) (
            $quote->customer_email
            ?: optional($quote->contact)->email
            ?: '-'
        ));

        $lineRows = [];
        foreach ($quote->lines as $index => $line) {
            $snapshot = (array) ($line->product_snapshot ?? []);
            $costingInput = (array) ($line->costing_input ?? []);
            $costingBreakdown = (array) ($line->costing_breakdown ?? []);

            $itemName = trim((string) (
                $snapshot['name']
                ?? optional($line->product)->name
                ?? '-'
            ));
            $itemCode = trim((string) (
                $snapshot['sku']
                ?? optional($line->product)->sku
                ?? ''
            ));

            $quantity = (float) ($costingInput['qty'] ?? 0);
            $purchaseUom = trim((string) (
                $costingInput['purchase_uom']
                ?? ($snapshot['unit'] ?? optional(optional($line->product)->unit)->short_name)
                ?? ''
            ));
            $unitCost = (float) ($costingBreakdown['unit_cost'] ?? 0);
            $totalCost = (float) ($costingBreakdown['total_cost'] ?? 0);

            $lineRows[] = sprintf(
                '%d. %s%s | type: product | qty: %s%s | unit_cost: %s | total: %s',
                $index + 1,
                $itemName,
                $itemCode !== '' ? ' [' . $itemCode . ']' : '',
                $this->formatContextNumber($quantity),
                $purchaseUom !== '' ? ' ' . $purchaseUom : '',
                $this->formatContextNumber($unitCost),
                $this->formatContextNumber($totalCost)
            );
        }

        $lines = [
            'Quote context snapshot:',
            'quote_id: ' . (int) $quote->id,
            'quote_number: ' . trim((string) ($quote->quote_number ?: $quote->uuid ?: '-')),
            'status: ' . trim((string) ($quote->derived_state ?: '-')),
            'quote_date: ' . ($quoteDate ?: '-'),
            'expires_at: ' . ($expiresAt ?: '-'),
            'customer: ' . $customerName,
            'customer_email: ' . $customerEmail,
            'location: ' . trim((string) (optional($quote->location)->name ?: '-')),
            'currency: ' . trim((string) ($quote->currency ?: '-')),
            'incoterm: ' . trim((string) ($quote->incoterm ?: '-')),
            'grand_total: ' . $this->formatContextNumber((float) ($quote->grand_total ?? 0)),
            'line_count: ' . (int) ($quote->line_count ?: count($lineRows)),
            'linked_transaction_id: ' . (int) ($quote->transaction_id ?? 0),
            'linked_invoice_no: ' . trim((string) (optional($quote->transaction)->invoice_no ?: '-')),
            'lines:',
        ];

        if (empty($lineRows)) {
            $lines[] = '- (no quote lines)';
        } else {
            foreach ($lineRows as $lineRow) {
                $lines[] = '- ' . $lineRow;
            }
        }

        return implode("\n", $lines);
    }

    public function updateQuote(int $business_id, ProductQuote $quote, array $payload, bool $allowAdminOverride = false): ProductQuote
    {
        if ((int) $quote->business_id !== $business_id) {
            $quote = ProductQuote::forBusiness($business_id)->findOrFail((int) $quote->id);
        }

        if (! $allowAdminOverride) {
            $this->assertQuoteEditable($quote);
        }

        $contact = $this->resolveCustomerContact($business_id, (int) $payload['contact_id']);
        $linePayloads = $this->buildLinePayloadsForUpdate($business_id, $payload);

        return DB::transaction(function () use ($quote, $contact, $payload, $linePayloads) {
            $grandTotal = $this->calculateGrandTotal($linePayloads);
            $firstLine = $linePayloads[0];

            $customerName = trim((string) ($payload['customer_name'] ?? ''));
            $customerEmail = trim((string) ($payload['customer_email'] ?? ''));

            if ($customerName === '') {
                $customerName = $this->buildContactName($contact);
            }
            if ($customerEmail === '') {
                $customerEmail = (string) ($contact->email ?? '');
            }

            $quote->fill([
                'contact_id' => $contact->id,
                'location_id' => (int) $payload['location_id'],
                'quote_date' => ! empty($payload['quote_date']) ? Carbon::parse($payload['quote_date'])->startOfDay() : null,
                'expires_at' => ! empty($payload['expires_at']) ? Carbon::parse($payload['expires_at'])->startOfDay() : $quote->expires_at,
                'currency' => $firstLine['costing_input']['currency'] ?? null,
                'incoterm' => $firstLine['costing_input']['incoterm'] ?? null,
                'customer_email' => $customerEmail !== '' ? $customerEmail : null,
                'customer_name' => $customerName !== '' ? $customerName : null,
                'remark' => trim((string) ($payload['remark'] ?? '')) !== '' ? trim((string) $payload['remark']) : null,
                'shipment_port' => trim((string) ($payload['shipment_port'] ?? '')) !== '' ? trim((string) $payload['shipment_port']) : null,
                'grand_total' => round($grandTotal, 4),
                'line_count' => count($linePayloads),
            ]);
            $quote->save();

            $quote->lines()->delete();

            $linesToCreate = [];
            foreach ($linePayloads as $index => $linePayload) {
                $productSnapshot = $linePayload['product_snapshot'] ?? [];
                $productId = (int) ($productSnapshot['product_id'] ?? 0);

                if ($productId <= 0) {
                    throw new \InvalidArgumentException(__('product.quote_lines_required'));
                }

                $linesToCreate[] = [
                    'product_id' => $productId,
                    'sort_order' => (int) ($linePayload['sort_order'] ?? $index),
                    'product_snapshot' => $productSnapshot,
                    'costing_input' => $linePayload['costing_input'],
                    'costing_breakdown' => $linePayload['costing_breakdown'],
                ];
            }
            $quote->lines()->createMany($linesToCreate);

            return $quote->fresh($this->quoteRelations());
        });
    }

    public function deleteQuote(int $business_id, ProductQuote $quote, bool $allowAdminOverride = false): void
    {
        if ((int) $quote->business_id !== $business_id) {
            $quote = ProductQuote::forBusiness($business_id)->findOrFail((int) $quote->id);
        }

        if (! $allowAdminOverride) {
            $this->assertQuoteEditable($quote);
        }

        $quote->delete();
    }

    public function revertQuoteToDraft(int $business_id, ProductQuote $quote): ProductQuote
    {
        if ((int) $quote->business_id !== $business_id) {
            $quote = ProductQuote::forBusiness($business_id)->findOrFail((int) $quote->id);
        }

        if (empty($quote->transaction_id)) {
            throw new \InvalidArgumentException(__('product.quote_revert_requires_converted'));
        }

        $expiresDays = max(1, (int) config('product.quote_defaults.expiry_days', 14));

        $quote->transaction_id = null;
        $quote->confirmed_at = null;
        $quote->confirmation_signature = null;
        $quote->sent_at = null;
        $quote->expires_at = now()->addDays($expiresDays);
        $quote->save();

        return $quote->fresh($this->quoteRelations());
    }

    public function clearQuoteConfirmation(int $business_id, ProductQuote $quote): ProductQuote
    {
        if ((int) $quote->business_id !== $business_id) {
            $quote = ProductQuote::forBusiness($business_id)->findOrFail((int) $quote->id);
        }

        if (! empty($quote->transaction_id)) {
            throw new \InvalidArgumentException(__('product.quote_signature_clear_not_allowed_for_converted'));
        }

        if (empty($quote->confirmed_at) && empty($quote->confirmation_signature)) {
            throw new \InvalidArgumentException(__('product.quote_signature_clear_requires_confirmation'));
        }

        $quote->confirmation_signature = null;
        $quote->confirmed_at = null;
        $quote->save();

        return $quote->fresh($this->quoteRelations());
    }

    public function updatePublicLinkPassword(int $business_id, ProductQuote $quote, ?string $password): ProductQuote
    {
        if ((int) $quote->business_id !== $business_id) {
            $quote = ProductQuote::forBusiness($business_id)->findOrFail((int) $quote->id);
        }

        $normalizedPassword = is_null($password) ? null : trim((string) $password);
        if ($normalizedPassword === null || $normalizedPassword === '') {
            $quote->public_link_password = null;
        } else {
            $quote->public_link_password = Hash::make($normalizedPassword);
        }

        $quote->save();

        return $quote->fresh($this->quoteRelations());
    }

    public function getConfirmedQuoteForSellPrefill(int $business_id, int $quote_id): ProductQuote
    {
        $quote = ProductQuote::forBusiness($business_id)
            ->with([
                'contact:id,name,supplier_business_name',
                'lines.product:id,name,sku',
            ])
            ->findOrFail($quote_id);

        if ($quote->expires_at && $quote->expires_at->isPast()) {
            throw new \InvalidArgumentException(__('product.quote_invalid_or_expired'));
        }

        if (! $quote->isConfirmed()) {
            throw new \InvalidArgumentException(__('product.quote_must_be_confirmed_for_sale'));
        }

        if (empty($quote->contact_id)) {
            throw new \InvalidArgumentException(__('product.quote_release_contact_required'));
        }

        if (empty($quote->location_id)) {
            throw new \InvalidArgumentException(__('product.quote_release_location_required'));
        }

        if (! empty($quote->transaction_id)) {
            throw new \InvalidArgumentException(__('product.quote_already_converted'));
        }

        if ($quote->lines->isEmpty()) {
            throw new \InvalidArgumentException(__('product.quote_lines_required'));
        }

        return $quote;
    }

    public function linkQuoteToTransaction(int $business_id, int $quote_id, int $transaction_id): ProductQuote
    {
        $quote = ProductQuote::forBusiness($business_id)
            ->lockForUpdate()
            ->findOrFail($quote_id);

        if ($quote->expires_at && $quote->expires_at->isPast()) {
            throw new \InvalidArgumentException(__('product.quote_invalid_or_expired'));
        }

        if (! $quote->isConfirmed()) {
            throw new \InvalidArgumentException(__('product.quote_must_be_confirmed_for_sale'));
        }

        $transaction = Transaction::where('business_id', $business_id)
            ->where('type', 'sell')
            ->findOrFail($transaction_id);

        if (! empty($quote->transaction_id) && (int) $quote->transaction_id !== (int) $transaction->id) {
            throw new \InvalidArgumentException(__('product.quote_already_converted'));
        }

        if (empty($quote->transaction_id)) {
            $quote->transaction_id = (int) $transaction->id;
            $quote->save();
        }

        return $quote;
    }

    public function getQuotePrefix(int $business_id): string
    {
        $business = Business::find($business_id, ['id', 'ref_no_prefixes']);
        $prefixes = (array) ($business->ref_no_prefixes ?? []);
        $prefix = trim((string) ($prefixes['product_quote'] ?? config('product.quote_defaults.prefix', 'RFQ')));

        return $prefix !== '' ? $prefix : 'RFQ';
    }

    public function generateQuoteNumber(int $business_id, int $quote_id, ?Carbon $date = null): string
    {
        $date = $date ?: Carbon::now();
        $prefix = $this->getQuotePrefix($business_id);

        return sprintf('%s-%s-%s-%06d', $prefix, $date->format('Y'), $date->format('md'), $quote_id);
    }

    protected function persistQuote(int $business_id, Contact $contact, array $payload, array $linePayloads, ?int $created_by = null): ProductQuote
    {
        return DB::transaction(function () use ($business_id, $contact, $payload, $linePayloads, $created_by) {
            $grandTotal = $this->calculateGrandTotal($linePayloads);

            $customerName = trim((string) ($payload['customer_name'] ?? ''));
            $customerEmail = trim((string) ($payload['customer_email'] ?? ''));

            if ($customerName === '') {
                $customerName = $this->buildContactName($contact);
            }
            if ($customerEmail === '') {
                $customerEmail = (string) ($contact->email ?? '');
            }

            $firstLine = $linePayloads[0];
            $expiresDays = (int) config('product.quote_defaults.expiry_days', 14);
            if ($expiresDays < 1) {
                $expiresDays = 14;
            }
            $expiresAt = ! empty($payload['expires_at'])
                ? Carbon::parse($payload['expires_at'])->startOfDay()
                : now()->addDays($expiresDays);
            $quoteDate = ! empty($payload['quote_date'])
                ? Carbon::parse($payload['quote_date'])->startOfDay()
                : null;
            $remark = trim((string) ($payload['remark'] ?? ''));
            $shipmentPort = trim((string) ($payload['shipment_port'] ?? ''));

            $quote = ProductQuote::create([
                'business_id' => $business_id,
                'uuid' => ProductQuote::generateUuid(),
                'public_token' => ProductQuote::generateUniquePublicToken(),
                'contact_id' => $contact->id,
                'location_id' => (int) $payload['location_id'],
                'quote_date' => $quoteDate,
                'expires_at' => $expiresAt,
                'currency' => $firstLine['costing_input']['currency'] ?? null,
                'incoterm' => $firstLine['costing_input']['incoterm'] ?? null,
                'customer_email' => $customerEmail !== '' ? $customerEmail : null,
                'customer_name' => $customerName !== '' ? $customerName : null,
                'remark' => $remark !== '' ? $remark : null,
                'shipment_port' => $shipmentPort !== '' ? $shipmentPort : null,
                'grand_total' => round($grandTotal, 4),
                'line_count' => count($linePayloads),
                'created_by' => $created_by ?: auth()->id(),
            ]);

            $quote->quote_number = $this->generateQuoteNumber($business_id, (int) $quote->id, Carbon::parse($quote->created_at));
            $quote->save();

            $linesToCreate = [];
            foreach ($linePayloads as $index => $linePayload) {
                $productSnapshot = $linePayload['product_snapshot'] ?? [];
                $productId = (int) ($productSnapshot['product_id'] ?? 0);

                if ($productId <= 0) {
                    throw new \InvalidArgumentException(__('product.quote_lines_required'));
                }

                $linesToCreate[] = [
                    'product_id' => $productId,
                    'sort_order' => (int) ($linePayload['sort_order'] ?? $index),
                    'product_snapshot' => $productSnapshot,
                    'costing_input' => $linePayload['costing_input'],
                    'costing_breakdown' => $linePayload['costing_breakdown'],
                ];
            }
            $quote->lines()->createMany($linesToCreate);

            return $quote->fresh($this->quoteRelations());
        });
    }

    protected function buildLinePayloadsForUpdate(int $business_id, array $payload): array
    {
        $linesInput = $payload['lines'] ?? [];

        if (empty($linesInput)) {
            throw new \InvalidArgumentException(__('product.quote_lines_required'));
        }

        $productIds = collect($linesInput)
            ->pluck('product_id')
            ->map(function ($productId) {
                return (int) $productId;
            })
            ->filter()
            ->unique()
            ->values()
            ->all();

        $products = Product::where('business_id', $business_id)
            ->whereIn('id', $productIds)
            ->with([
                'unit:id,short_name',
                'category:id,name',
            ])
            ->get()
            ->keyBy('id');

        $linePayloads = [];
        foreach ($linesInput as $index => $lineInput) {
            $productId = (int) ($lineInput['product_id'] ?? 0);

            if (! isset($products[$productId])) {
                throw new \InvalidArgumentException(__('product.quote_product_invalid'));
            }

            $linePayload = $this->costingUtil->buildLinePayload($products[$productId], $lineInput);
            $linePayload['sort_order'] = $index;
            $linePayloads[] = $linePayload;
        }

        $this->costingUtil->assertSharedCurrencyAndIncoterm($linePayloads);

        return $linePayloads;
    }

    protected function calculateGrandTotal(array $linePayloads): float
    {
        $grandTotal = 0;
        foreach ($linePayloads as $linePayload) {
            $grandTotal += (float) ($linePayload['costing_breakdown']['total_cost'] ?? 0);
        }

        return $grandTotal;
    }

    protected function resolveCustomerContact(int $business_id, int $contact_id): Contact
    {
        return Contact::where('business_id', $business_id)
            ->whereIn('type', ['customer', 'both'])
            ->findOrFail($contact_id);
    }

    protected function buildContactName(Contact $contact): string
    {
        $supplierBusinessName = trim((string) ($contact->supplier_business_name ?? ''));
        $name = trim((string) ($contact->name ?? ''));

        if ($supplierBusinessName !== '' && $name !== '' && strcasecmp($supplierBusinessName, $name) !== 0) {
            return $supplierBusinessName . ' - ' . $name;
        }

        return $supplierBusinessName !== '' ? $supplierBusinessName : $name;
    }

    protected function quoteRelations(): array
    {
        return [
            'contact:id,name,supplier_business_name,email,contact_id',
            'location:id,name,landmark,city,state,country,zip_code,mobile,alternate_number,email',
            'lines.product:id,name,sku,unit_id,category_id',
            'lines.product.unit:id,short_name',
            'lines.product.category:id,name',
            'transaction:id,invoice_no,status,type',
            'creator:id,surname,first_name,last_name,email,username',
        ];
    }

    protected function assertQuoteEditable(ProductQuote $quote): void
    {
        if (! $quote->isEditable()) {
            throw new \InvalidArgumentException(__('product.quote_not_editable'));
        }
    }

    protected function formatContextNumber(float $value, int $precision = 4): string
    {
        $formatted = number_format($value, $precision, '.', '');

        return rtrim(rtrim($formatted, '0'), '.');
    }
}
