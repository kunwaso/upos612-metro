<?php

namespace App\Utils;

use App\ProductQuote;
use Illuminate\Support\Carbon;

class QuoteDisplayPresenter
{
    protected ?NumberFormatUtil $numberFormatUtil;

    public function __construct(?NumberFormatUtil $numberFormatUtil = null)
    {
        $this->numberFormatUtil = $numberFormatUtil;
    }

    public function presentQuote(ProductQuote $quote): array
    {
        $business = $this->resolveBusinessFromQuote($quote);
        $currencyCode = trim((string) ($quote->currency ?? ''));
        $numberFormat = $this->getNumberFormatUtil();

        $lineRows = [];

        foreach ($quote->lines as $line) {
            $snapshot = (array) ($line->product_snapshot ?? []);
            $input = (array) ($line->costing_input ?? []);
            $breakdown = (array) ($line->costing_breakdown ?? []);

            $itemName = (string) (
                $snapshot['name']
                ?? optional($line->product)->name
                ?? ('#' . $line->product_id)
            );
            $itemCode = (string) (
                $snapshot['sku']
                ?? optional($line->product)->sku
                ?? ''
            );
            $categoryName = trim((string) (
                $snapshot['category']
                ?? optional(optional($line->product)->category)->name
                ?? ''
            ));
            $purchaseUom = trim((string) (
                $snapshot['unit']
                ?? optional(optional($line->product)->unit)->short_name
                ?? ($input['purchase_uom'] ?? '')
            ));

            $qty = (float) ($input['qty'] ?? 0);
            $unitCost = (float) ($breakdown['unit_cost'] ?? 0);
            $totalCost = (float) ($breakdown['total_cost'] ?? 0);

            $lineRows[] = [
                'isTrimLine' => false,
                'itemName' => $itemName,
                'itemCode' => $itemCode,
                'itemCodeLabel' => __('product.sku'),
                'categoryName' => $categoryName,
                'purchaseUom' => $purchaseUom,
                'quantity' => $qty,
                'quantityPublicDisplay' => $numberFormat->formatQuantityDisplay($qty, $business),
                'unitCost' => $unitCost,
                'unitCostPublicDisplay' => $numberFormat->formatCurrencyCodeDisplay($unitCost, $business, $currencyCode),
                'totalCost' => $totalCost,
                'totalCostPublicDisplay' => $numberFormat->formatCurrencyCodeDisplay($totalCost, $business, $currencyCode),
            ];
        }

        $stateMeta = $this->resolveStateMeta((string) ($quote->derived_state ?? ProductQuote::STATE_DRAFT));

        return [
            'quoteNumber' => (string) ($quote->quote_number ?: $quote->uuid),
            'customerName' => $this->resolveCustomerName($quote),
            'customerEmail' => $this->resolveCustomerEmail($quote),
            'locationName' => (string) (optional($quote->location)->name ?: '-'),
            'currencyCode' => $currencyCode,
            'quoteDateDisplay' => $this->formatDate($quote->quote_date ?? $quote->created_at),
            'validUntilDisplay' => $this->formatDate($quote->expires_at),
            'quoteLineCount' => (int) ($quote->line_count ?: count($lineRows)),
            'quoteGrandTotalValue' => (float) ($quote->grand_total ?? 0),
            'quoteGrandTotalPublicDisplay' => $numberFormat->formatCurrencyCodeDisplay((float) ($quote->grand_total ?? 0), $business, $currencyCode),
            'quoteDisplayLines' => $lineRows,
            'hasTrimLines' => false,
            'quoteStateBadgeClass' => $stateMeta['class'],
            'quoteStateLabel' => $stateMeta['label'],
            'remarkDisplay' => $this->buildRemarkDisplay($quote),
            'locationAddressDisplay' => $this->buildLocationAddressDisplay($quote),
            'isEditable' => $quote->isEditable(),
            'isConfirmedUnlinked' => $quote->isConfirmed() && empty($quote->transaction_id),
        ];
    }

    public function presentPublicQuote(ProductQuote $quote): array
    {
        $payload = $this->presentQuote($quote);

        return array_merge($payload, [
            'publicQuoteBusinessName' => (string) (optional($quote->business)->name ?: config('app.name')),
            'publicQuoteFooterNote' => __('product.quote_footer_note', [
                'currency' => (string) ($payload['currencyCode'] ?: '-'),
                'incoterm' => (string) ($quote->incoterm ?: '-'),
            ]),
            'publicQuoteConfirmedAtDisplay' => $this->formatDateTime($quote->confirmed_at),
        ]);
    }

    public function presentLatestQuoteSummary(?ProductQuote $quote, $quoteLine = null): ?array
    {
        if (! $quote || ! $quoteLine) {
            return null;
        }

        $business = $this->resolveBusinessFromQuote($quote);
        $numberFormat = $this->getNumberFormatUtil();

        $input = (array) ($quoteLine->costing_input ?? []);
        $breakdown = (array) ($quoteLine->costing_breakdown ?? []);
        $currencyCode = trim((string) ($quote->currency ?? ''));

        return [
            'quoteId' => (int) $quote->id,
            'quoteNumber' => (string) ($quote->quote_number ?: $quote->uuid),
            'createdAtDisplay' => $this->formatDateTime($quote->created_at),
            'validUntilDisplay' => $this->formatDate($quote->expires_at),
            'quantityDisplay' => $this->getNumberFormatUtil()->formatQuantityDisplay((float) ($input['qty'] ?? 0), $business),
            'unitCostDisplay' => $numberFormat->formatCurrencyAmountDisplay((float) ($breakdown['unit_cost'] ?? 0), $business),
            'totalCostDisplay' => $numberFormat->formatCurrencyCodeDisplay((float) ($breakdown['total_cost'] ?? 0), $business, $currencyCode),
            'recipientEmail' => (string) ($quote->customer_email ?: (optional($quote->contact)->email ?? '')),
            'canCreateSaleFromQuote' => ! empty($quote->confirmed_at) && empty($quote->transaction_id),
        ];
    }

    protected function resolveStateMeta(string $state): array
    {
        $map = [
            ProductQuote::STATE_CONVERTED => [
                'class' => 'badge-light-success',
                'label' => __('product.quote_state_converted'),
            ],
            ProductQuote::STATE_CONFIRMED => [
                'class' => 'badge-light-primary',
                'label' => __('product.quote_state_confirmed'),
            ],
            ProductQuote::STATE_SENT => [
                'class' => 'badge-light-warning',
                'label' => __('product.quote_state_sent'),
            ],
        ];

        return $map[$state] ?? [
            'class' => 'badge-light-secondary',
            'label' => __('product.quote_state_draft'),
        ];
    }

    protected function resolveCustomerName(ProductQuote $quote): string
    {
        return (string) ($quote->customer_name ?: (optional($quote->contact)->supplier_business_name ?? optional($quote->contact)->name ?? '-'));
    }

    protected function resolveCustomerEmail(ProductQuote $quote): string
    {
        return (string) ($quote->customer_email ?: (optional($quote->contact)->email ?? '-'));
    }

    protected function buildRemarkDisplay(ProductQuote $quote): string
    {
        $remarkDisplay = trim((string) ($quote->remark ?? ''));
        if ($remarkDisplay === '') {
            return '';
        }

        $validUntilFormatted = $this->formatDate($quote->expires_at);
        $shipmentPortValue = trim((string) ($quote->shipment_port ?? ''));
        if ($shipmentPortValue === '') {
            $shipmentPortValue = '-';
        }

        return str_replace(
            ['{{valid_until}}', '{{shipment_port}}'],
            [$validUntilFormatted, $shipmentPortValue],
            $remarkDisplay
        );
    }

    protected function buildLocationAddressDisplay(ProductQuote $quote): string
    {
        $location = $quote->location;
        if (! $location) {
            return '-';
        }

        $parts = array_filter([
            $location->landmark ?? null,
            $location->city ?? null,
            $location->state ?? null,
            $location->zip_code ?? null,
            $location->country ?? null,
        ]);

        return ! empty($parts) ? implode(', ', $parts) : '-';
    }

    protected function resolveBusinessFromQuote(ProductQuote $quote): ?object
    {
        if (is_object($quote->business)) {
            return $quote->business;
        }

        return null;
    }

    protected function formatDate($value): string
    {
        if (empty($value)) {
            return '-';
        }

        return Carbon::parse($value)->format('M d, Y');
    }

    protected function formatDateTime($value): string
    {
        if (empty($value)) {
            return '-';
        }

        return Carbon::parse($value)->format('M d, Y h:i A');
    }

    protected function getNumberFormatUtil(): NumberFormatUtil
    {
        if ($this->numberFormatUtil) {
            return $this->numberFormatUtil;
        }

        $this->numberFormatUtil = app(NumberFormatUtil::class);

        return $this->numberFormatUtil;
    }
}
