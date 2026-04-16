<?php

namespace Modules\VasAccounting\Services;

use Illuminate\Support\Collection;
use Modules\VasAccounting\Entities\VasAccountingPeriod;
use Modules\VasAccounting\Entities\VasAccount;
use Modules\VasAccounting\Entities\VasBusinessSetting;
use Modules\VasAccounting\Utils\VasAccountingUtil;

class FinancialStatementBuilderService
{
    public function __construct(protected VasAccountingUtil $vasUtil)
    {
    }

    public function build(int $businessId, array $filters = [], ?array $profile = null): array
    {
        $settings = $this->vasUtil->getOrCreateBusinessSettings($businessId);
        $profile = $profile ?: $this->vasUtil->complianceProfileForSettings($settings);
        $profileKey = (string) ($profile['key'] ?? config('vasaccounting.compliance_profiles.default', 'tt99_2025'));
        $statement = (string) ($filters['statement'] ?? 'balance_sheet');
        $format = (string) ($filters['format'] ?? 'html');

        $lineDefinitions = (array) data_get(config('vasaccounting.financial_statement_lines', []), $profileKey . '.' . $statement, []);
        if ($lineDefinitions === []) {
            $lineDefinitions = (array) data_get(config('vasaccounting.financial_statement_lines', []), 'tt99_2025.' . $statement, []);
        }

        $period = VasAccountingPeriod::query()
            ->where('business_id', $businessId)
            ->when(! empty($filters['period_id']), fn ($query) => $query->where('id', (int) $filters['period_id']))
            ->orderByDesc('end_date')
            ->firstOrFail();
        $comparativePeriod = VasAccountingPeriod::query()
            ->where('business_id', $businessId)
            ->when(! empty($filters['comparative_period_id']), fn ($query) => $query->where('id', (int) $filters['comparative_period_id']))
            ->when(empty($filters['comparative_period_id']), fn ($query) => $query->where('end_date', '<', $period->start_date))
            ->orderByDesc('end_date')
            ->first();

        $currentBalances = $this->loadBalances($businessId, (int) $period->id);
        $comparativeBalances = $comparativePeriod ? $this->loadBalances($businessId, (int) $comparativePeriod->id) : collect();

        $valuesCurrent = [];
        $valuesComparative = [];
        $items = [];
        foreach ($lineDefinitions as $line) {
            $lineCode = (string) ($line['line_code'] ?? '');
            if ($lineCode === '') {
                continue;
            }

            [$valueCurrent, $valueComparative, $type] = $this->resolveLineValues(
                $line,
                $currentBalances,
                $comparativeBalances,
                $valuesCurrent,
                $valuesComparative,
                $settings,
                $businessId
            );

            $valuesCurrent[$lineCode] = $valueCurrent;
            $valuesComparative[$lineCode] = $valueComparative;
            $items[] = [
                'line_code' => $lineCode,
                'label' => (string) ($line['label'] ?? $lineCode),
                'type' => $type,
                'current' => $valueCurrent,
                'comparative' => $valueComparative,
                'current_display' => $this->displayValue($valueCurrent, $type),
                'comparative_display' => $this->displayValue($valueComparative, $type),
            ];
        }

        return [
            'title' => (string) data_get(config('vasaccounting.financial_statement_types', []), $statement . '.label', 'Financial Statement'),
            'statement' => $statement,
            'format' => $format,
            'profile_key' => $profileKey,
            'profile_label' => (string) ($profile['label'] ?? $profileKey),
            'period' => [
                'id' => (int) $period->id,
                'name' => (string) $period->name,
                'start_date' => optional($period->start_date)->toDateString(),
                'end_date' => optional($period->end_date)->toDateString(),
            ],
            'comparative_period' => $comparativePeriod ? [
                'id' => (int) $comparativePeriod->id,
                'name' => (string) $comparativePeriod->name,
                'start_date' => optional($comparativePeriod->start_date)->toDateString(),
                'end_date' => optional($comparativePeriod->end_date)->toDateString(),
            ] : null,
            'line_items' => $items,
            'columns' => ['Line', 'Code', 'Current period', 'Comparative period'],
            'rows' => collect($items)->map(fn (array $item) => [
                $item['label'],
                $item['line_code'],
                $item['current_display'],
                $item['comparative_display'],
            ])->all(),
        ];
    }

    protected function loadBalances(int $businessId, int $periodId): Collection
    {
        return VasAccount::query()
            ->leftJoin('vas_ledger_balances as lb', function ($join) use ($periodId) {
                $join->on('lb.account_id', '=', 'vas_accounts.id')
                    ->where('lb.accounting_period_id', '=', $periodId);
            })
            ->where('vas_accounts.business_id', $businessId)
            ->selectRaw(
                'vas_accounts.account_code, COALESCE(lb.opening_debit, 0) as opening_debit, COALESCE(lb.opening_credit, 0) as opening_credit, COALESCE(lb.period_debit, 0) as period_debit, COALESCE(lb.period_credit, 0) as period_credit, COALESCE(lb.closing_debit, 0) as closing_debit, COALESCE(lb.closing_credit, 0) as closing_credit'
            )
            ->get();
    }

    protected function resolveLineValues(
        array $line,
        Collection $currentBalances,
        Collection $comparativeBalances,
        array $valuesCurrent,
        array $valuesComparative,
        VasBusinessSetting $settings,
        int $businessId
    ): array {
        if (isset($line['formula'])) {
            return [
                $this->evaluateFormula((string) $line['formula'], $valuesCurrent),
                $this->evaluateFormula((string) $line['formula'], $valuesComparative),
                'amount',
            ];
        }

        $type = (string) ($line['type'] ?? 'amount');
        if ($type === 'text') {
            $text = (string) ($line['default_text'] ?? '');

            return [$text, $text, 'text'];
        }

        if ($type === 'setting') {
            $path = (string) ($line['setting_path'] ?? '');
            $value = data_get($settings->toArray(), $path, '');

            return [(string) $value, (string) $value, 'text'];
        }

        if ($type === 'metric') {
            $metric = (string) ($line['metric'] ?? '');
            $value = $metric === 'active_accounts'
                ? VasAccount::query()->where('business_id', $businessId)->where('is_active', true)->count()
                : 0;

            return [$value, $value, 'amount'];
        }

        $source = (string) ($line['source'] ?? 'closing');
        $normal = (string) ($line['normal'] ?? 'debit_minus_credit');
        $prefixes = (array) ($line['account_prefixes'] ?? []);

        $calculate = function (Collection $balances) use ($prefixes, $source, $normal): float {
            $rows = $balances->filter(function ($row) use ($prefixes): bool {
                $accountCode = (string) ($row->account_code ?? '');
                foreach ($prefixes as $prefix) {
                    if ($prefix !== '' && str_starts_with($accountCode, (string) $prefix)) {
                        return true;
                    }
                }

                return false;
            });

            $debitField = $source === 'opening'
                ? 'opening_debit'
                : ($source === 'period' ? 'period_debit' : 'closing_debit');
            $creditField = $source === 'opening'
                ? 'opening_credit'
                : ($source === 'period' ? 'period_credit' : 'closing_credit');
            $debit = (float) $rows->sum($debitField);
            $credit = (float) $rows->sum($creditField);

            return round($normal === 'credit_minus_debit' ? ($credit - $debit) : ($debit - $credit), 2);
        };

        return [
            $calculate($currentBalances),
            $calculate($comparativeBalances),
            'amount',
        ];
    }

    protected function evaluateFormula(string $formula, array $valueMap): float
    {
        $tokens = preg_split('/\s+/', trim($formula)) ?: [];
        if ($tokens === []) {
            return 0.0;
        }

        $result = 0.0;
        $operator = '+';
        foreach ($tokens as $token) {
            if (in_array($token, ['+', '-'], true)) {
                $operator = $token;
                continue;
            }

            $value = (float) ($valueMap[$token] ?? 0);
            $result += $operator === '-' ? ($value * -1) : $value;
        }

        return round($result, 2);
    }

    protected function displayValue(mixed $value, string $type): string
    {
        if ($type === 'text') {
            return (string) $value;
        }

        if ($value === null || $value === '') {
            return '-';
        }

        return number_format((float) $value, 2);
    }
}

