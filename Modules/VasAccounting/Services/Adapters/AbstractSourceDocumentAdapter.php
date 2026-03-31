<?php

namespace Modules\VasAccounting\Services\Adapters;

use App\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\VasAccounting\Contracts\SourceDocumentAdapterInterface;
use Modules\VasAccounting\Entities\VasAccount;
use Modules\VasAccounting\Entities\VasBusinessSetting;
use Modules\VasAccounting\Entities\VasTaxCode;
use Modules\VasAccounting\Services\NativeDocumentMetaBuilder;
use Modules\VasAccounting\Utils\VasAccountingUtil;

abstract class AbstractSourceDocumentAdapter implements SourceDocumentAdapterInterface
{
    public function __construct(
        protected VasAccountingUtil $vasUtil,
        protected ?NativeDocumentMetaBuilder $nativeDocumentMetaBuilder = null
    )
    {
    }

    protected function settings(int $businessId): VasBusinessSetting
    {
        return $this->vasUtil->getOrCreateBusinessSettings($businessId);
    }

    protected function postingMapAccount(VasBusinessSetting $settings, string $key): int
    {
        $accountId = (int) ((array) $settings->posting_map)[$key];
        if ($accountId <= 0) {
            throw new \RuntimeException("Missing required posting map account [{$key}].");
        }

        return $accountId;
    }

    protected function accountIdByCode(int $businessId, string $code): int
    {
        $accountId = (int) VasAccount::query()
            ->where('business_id', $businessId)
            ->where('account_code', $code)
            ->value('id');

        if ($accountId <= 0) {
            throw new \RuntimeException("Missing VAS account code [{$code}].");
        }

        return $accountId;
    }

    protected function taxCodeId(int $businessId, float $rate, string $direction): ?int
    {
        return VasTaxCode::query()
            ->where('business_id', $businessId)
            ->where('direction', $direction)
            ->where('rate', $rate)
            ->value('id');
    }

    protected function money(float|int|string|null $amount): float
    {
        return round((float) ($amount ?? 0), 4);
    }

    protected function line(int $accountId, string $description, float $debit = 0, float $credit = 0, array $extra = []): array
    {
        return array_merge([
            'account_id' => $accountId,
            'description' => $description,
            'debit' => round($debit, 4),
            'credit' => round($credit, 4),
        ], $extra);
    }

    protected function payload(array $base, array $lines): array
    {
        $base['lines'] = $lines;
        $base['posting_date'] = Carbon::parse($base['posting_date'])->toDateString();
        $base['document_date'] = Carbon::parse($base['document_date'])->toDateString();

        return $base;
    }

    protected function cogsForTransaction(Transaction $transaction): float
    {
        $cost = DB::table('transaction_sell_lines_purchase_lines as tslpl')
            ->join('transaction_sell_lines as tsl', 'tsl.id', '=', 'tslpl.sell_line_id')
            ->join('purchase_lines as pl', 'pl.id', '=', 'tslpl.purchase_line_id')
            ->where('tsl.transaction_id', $transaction->id)
            ->selectRaw('SUM((tslpl.quantity - tslpl.qty_returned) * pl.purchase_price) as total_cost')
            ->value('total_cost');

        return $this->money($cost);
    }

    protected function transactionTotalFromPurchaseLines(int $transactionId): float
    {
        $value = DB::table('purchase_lines')
            ->where('transaction_id', $transactionId)
            ->selectRaw('SUM(quantity * purchase_price) as total_value')
            ->value('total_value');

        return $this->money($value);
    }

    protected function metaBuilder(): NativeDocumentMetaBuilder
    {
        return $this->nativeDocumentMetaBuilder ?: app(NativeDocumentMetaBuilder::class);
    }
}
