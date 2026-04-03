<?php

namespace Modules\StorageManager\Services;

use App\Events\StockAdjustmentCreatedOrModified;
use App\Transaction;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class StockAdjustmentBridgeService
{
    public function __construct(
        protected ProductUtil $productUtil,
        protected TransactionUtil $transactionUtil
    ) {
    }

    public function createDecreaseAdjustment(
        int $businessId,
        int $locationId,
        array $lines,
        int $userId,
        array $options = []
    ): Transaction {
        $normalizedLines = collect($lines)
            ->map(function (array $line) {
                $quantity = round((float) ($line['quantity'] ?? 0), 4);
                if ($quantity <= 0) {
                    return null;
                }

                return [
                    'product_id' => (int) $line['product_id'],
                    'variation_id' => ! empty($line['variation_id']) ? (int) $line['variation_id'] : null,
                    'quantity' => $quantity,
                    'unit_price' => round((float) ($line['unit_price'] ?? $line['unit_cost'] ?? 0), 4),
                    'lot_no_line_id' => ! empty($line['lot_no_line_id']) ? (int) $line['lot_no_line_id'] : null,
                ];
            })
            ->filter()
            ->values();

        if ($normalizedLines->isEmpty()) {
            throw new RuntimeException('At least one positive adjustment line is required.');
        }

        return DB::transaction(function () use ($businessId, $locationId, $normalizedLines, $userId, $options) {
            $refCount = $this->productUtil->setAndGetReferenceCount('stock_adjustment');
            $transactionDate = $options['transaction_date'] ?? now();
            $notes = trim((string) ($options['notes'] ?? ''));
            $adjustmentType = (string) ($options['adjustment_type'] ?? 'abnormal');

            $transaction = Transaction::create([
                'business_id' => $businessId,
                'location_id' => $locationId,
                'type' => 'stock_adjustment',
                'status' => 'received',
                'adjustment_type' => in_array($adjustmentType, ['normal', 'abnormal'], true) ? $adjustmentType : 'abnormal',
                'transaction_date' => Carbon::parse($transactionDate),
                'ref_no' => (string) ($options['ref_no'] ?? $this->productUtil->generateReferenceNumber('stock_adjustment', $refCount)),
                'additional_notes' => $notes !== '' ? $notes : null,
                'total_amount_recovered' => round((float) ($options['total_amount_recovered'] ?? 0), 4),
                'final_total' => round((float) $normalizedLines->sum(fn (array $line) => $line['quantity'] * $line['unit_price']), 4),
                'created_by' => $userId,
            ]);

            foreach ($normalizedLines as $line) {
                $this->productUtil->decreaseProductQuantity(
                    (int) $line['product_id'],
                    $line['variation_id'] ? (int) $line['variation_id'] : null,
                    $locationId,
                    (float) $line['quantity']
                );
            }

            $transaction->stock_adjustment_lines()->createMany(
                $normalizedLines->map(function (array $line) {
                    $payload = [
                        'product_id' => $line['product_id'],
                        'variation_id' => $line['variation_id'],
                        'quantity' => $line['quantity'],
                        'unit_price' => $line['unit_price'],
                    ];

                    if (! empty($line['lot_no_line_id'])) {
                        $payload['lot_no_line_id'] = $line['lot_no_line_id'];
                    }

                    return $payload;
                })->all()
            );

            $business = [
                'id' => $businessId,
                'accounting_method' => $options['accounting_method'] ?? session('business.accounting_method'),
                'location_id' => $locationId,
            ];
            $this->transactionUtil->mapPurchaseSell($business, $transaction->stock_adjustment_lines, 'stock_adjustment');

            event(new StockAdjustmentCreatedOrModified($transaction, 'added'));
            $this->transactionUtil->activityLog($transaction, 'added', null, [], false);

            return $transaction->fresh('stock_adjustment_lines');
        });
    }
}
