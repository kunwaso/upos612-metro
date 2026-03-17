<?php

namespace Modules\ProjectX\Utils;

use Illuminate\Support\Carbon;
use Modules\ProjectX\Entities\Quote;

class ProjectXQuoteDisplayPresenter
{
    protected ?ProjectXNumberFormatUtil $numberFormatUtil;

    public function __construct(?ProjectXNumberFormatUtil $numberFormatUtil = null)
    {
        $this->numberFormatUtil = $numberFormatUtil;
    }

    public function presentQuote(Quote $quote): array
    {
        $business = $this->resolveBusinessFromQuote($quote);
        $currencyPrecision = $this->getNumberFormatUtil()->getCurrencyPrecision($business);
        $quantityPrecision = $this->getNumberFormatUtil()->getQuantityPrecision($business);
        $currencyCode = trim((string) ($quote->currency ?? ''));

        $lineRows = [];
        $hasTrimLines = false;

        foreach ($quote->lines as $line) {
            $isTrimLine = ! empty($line->trim_id);
            $hasTrimLines = $hasTrimLines || $isTrimLine;

            $snapshot = (array) ($isTrimLine ? ($line->trim_snapshot ?? []) : ($line->fabric_snapshot ?? []));
            $input = (array) ($line->costing_input ?? []);
            $breakdown = (array) ($line->costing_breakdown ?? []);

            $itemName = (string) (
                $snapshot['name']
                ?? ($isTrimLine ? optional($line->trim)->name : optional($line->fabric)->name)
                ?? ('#' . ($isTrimLine ? $line->trim_id : $line->fabric_id))
            );
            $itemCode = (string) (
                $snapshot[$isTrimLine ? 'part_number' : 'fabric_sku']
                ?? ($isTrimLine ? optional($line->trim)->part_number : optional($line->fabric)->fabric_sku)
                ?? ''
            );

            $qty = (float) ($input['qty'] ?? 0);
            $unitCost = (float) ($breakdown['unit_cost'] ?? 0);
            $totalCost = (float) ($breakdown['total_cost'] ?? 0);

            $lineRows[] = [
                'isTrimLine' => $isTrimLine,
                'itemName' => $itemName,
                'itemCode' => $itemCode,
                'itemCodeLabel' => $isTrimLine ? __('projectx::lang.part_number') : __('projectx::lang.sku'),
                'purchaseUom' => (string) ($input['purchase_uom'] ?? '-'),
                'quantity' => $qty,
                'quantityPublicDisplay' => $this->formatTrimmedNumber($qty, $quantityPrecision),
                'unitCost' => $unitCost,
                'unitCostPublicDisplay' => $this->formatCurrencyCodeNumber($unitCost, $currencyPrecision, $currencyCode),
                'totalCost' => $totalCost,
                'totalCostPublicDisplay' => $this->formatCurrencyCodeNumber($totalCost, $currencyPrecision, $currencyCode),
            ];
        }

        $stateMeta = $this->resolveStateMeta((string) ($quote->derived_state ?? Quote::STATE_DRAFT));

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
            'quoteGrandTotalPublicDisplay' => $this->formatCurrencyCodeNumber((float) ($quote->grand_total ?? 0), $currencyPrecision, $currencyCode),
            'quoteDisplayLines' => $lineRows,
            'hasTrimLines' => $hasTrimLines,
            'quoteStateBadgeClass' => $stateMeta['class'],
            'quoteStateLabel' => $stateMeta['label'],
            'remarkDisplay' => $this->buildRemarkDisplay($quote),
            'locationAddressDisplay' => $this->buildLocationAddressDisplay($quote),
            'isEditable' => $quote->isEditable(),
            'isConfirmedUnlinked' => $quote->isConfirmed() && empty($quote->transaction_id),
        ];
    }

    public function presentPublicQuote(Quote $quote): array
    {
        $payload = $this->presentQuote($quote);

        return array_merge($payload, [
            'publicQuoteBusinessName' => (string) (optional($quote->business)->name ?: config('app.name')),
            'publicQuoteFooterNote' => __('projectx::lang.quote_footer_note', [
                'currency' => (string) ($payload['currencyCode'] ?: '-'),
                'incoterm' => (string) ($quote->incoterm ?: '-'),
            ]),
            'publicQuoteConfirmedAtDisplay' => $this->formatDateTime($quote->confirmed_at),
        ]);
    }

    public function presentLatestQuoteSummary(?Quote $quote, $quoteLine = null): ?array
    {
        if (! $quote || ! $quoteLine) {
            return null;
        }

        $business = $this->resolveBusinessFromQuote($quote);
        $currencyPrecision = $this->getNumberFormatUtil()->getCurrencyPrecision($business);
        $quantityPrecision = $this->getNumberFormatUtil()->getQuantityPrecision($business);

        $input = (array) ($quoteLine->costing_input ?? []);
        $breakdown = (array) ($quoteLine->costing_breakdown ?? []);
        $currencyCode = trim((string) ($quote->currency ?? ''));

        return [
            'quoteId' => (int) $quote->id,
            'quoteNumber' => (string) ($quote->quote_number ?: $quote->uuid),
            'createdAtDisplay' => $this->formatDateTime($quote->created_at),
            'validUntilDisplay' => $this->formatDate($quote->expires_at),
            'quantityDisplay' => $this->formatTrimmedNumber((float) ($input['qty'] ?? 0), $quantityPrecision),
            'unitCostDisplay' => $this->formatNumber((float) ($breakdown['unit_cost'] ?? 0), $currencyPrecision),
            'totalCostDisplay' => $this->formatCurrencyCodeNumber((float) ($breakdown['total_cost'] ?? 0), $currencyPrecision, $currencyCode),
            'recipientEmail' => (string) ($quote->customer_email ?: (optional($quote->contact)->email ?? '')),
            'canCreateSaleFromQuote' => ! empty($quote->confirmed_at) && empty($quote->transaction_id),
        ];
    }

    protected function resolveStateMeta(string $state): array
    {
        $map = [
            Quote::STATE_CONVERTED => [
                'class' => 'badge-light-success',
                'label' => __('projectx::lang.quote_state_converted'),
            ],
            Quote::STATE_CONFIRMED => [
                'class' => 'badge-light-primary',
                'label' => __('projectx::lang.quote_state_confirmed'),
            ],
            Quote::STATE_SENT => [
                'class' => 'badge-light-warning',
                'label' => __('projectx::lang.quote_state_sent'),
            ],
        ];

        return $map[$state] ?? [
            'class' => 'badge-light-secondary',
            'label' => __('projectx::lang.quote_state_draft'),
        ];
    }

    protected function resolveCustomerName(Quote $quote): string
    {
        return (string) ($quote->customer_name ?: (optional($quote->contact)->supplier_business_name ?? optional($quote->contact)->name ?? '-'));
    }

    protected function resolveCustomerEmail(Quote $quote): string
    {
        return (string) ($quote->customer_email ?: (optional($quote->contact)->email ?? '-'));
    }

    protected function buildRemarkDisplay(Quote $quote): string
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

    protected function buildLocationAddressDisplay(Quote $quote): string
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

    protected function resolveBusinessFromQuote(Quote $quote): ?object
    {
        if (is_object($quote->business)) {
            return $quote->business;
        }

        return null;
    }

    protected function formatCurrencyCodeNumber(float $value, int $precision, string $currencyCode): string
    {
        $formatted = $this->formatNumber($value, $precision);

        return trim($formatted . ' ' . $currencyCode);
    }

    protected function formatNumber(float $value, int $precision): string
    {
        return number_format($value, max(0, $precision), '.', '');
    }

    protected function formatTrimmedNumber(float $value, int $precision): string
    {
        $formatted = $this->formatNumber($value, $precision);

        return rtrim(rtrim($formatted, '0'), '.');
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

    protected function getNumberFormatUtil(): ProjectXNumberFormatUtil
    {
        if ($this->numberFormatUtil) {
            return $this->numberFormatUtil;
        }

        $this->numberFormatUtil = app(ProjectXNumberFormatUtil::class);

        return $this->numberFormatUtil;
    }
}
