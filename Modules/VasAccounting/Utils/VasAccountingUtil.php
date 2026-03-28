<?php

namespace Modules\VasAccounting\Utils;

use App\Business;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Modules\VasAccounting\Entities\VasAccount;
use Modules\VasAccounting\Entities\VasAccountingPeriod;
use Modules\VasAccounting\Entities\VasBusinessSetting;
use Modules\VasAccounting\Entities\VasDocumentSequence;
use Modules\VasAccounting\Entities\VasPostingFailure;
use Modules\VasAccounting\Entities\VasTaxCode;
use Modules\VasAccounting\Entities\VasVoucher;

class VasAccountingUtil
{
    public function businessIdFromRequest(Request $request): int
    {
        return (int) $request->session()->get('user.business_id');
    }

    public function getOrCreateBusinessSettings(int $businessId): VasBusinessSetting
    {
        $defaults = [
            'book_currency' => (string) config('vasaccounting.book_currency', 'VND'),
            'inventory_method' => (string) config('vasaccounting.inventory_method', 'weighted_average'),
            'is_enabled' => true,
            'posting_map' => $this->buildDefaultPostingMapByCodes($businessId),
            'compliance_settings' => [
                'standard' => 'Circular 99/2025/TT-BTC',
                'effective_date' => '2026-01-01',
            ],
            'inventory_settings' => [
                'method' => (string) config('vasaccounting.inventory_method', 'weighted_average'),
            ],
            'depreciation_settings' => [
                'method' => 'straight_line',
                'run_day_of_month' => 1,
            ],
            'tax_settings' => [
                'declaration_currency' => 'VND',
                'default_output_tax_code' => 'VAT10_OUT',
                'default_input_tax_code' => 'VAT10_IN',
            ],
            'einvoice_settings' => [
                'provider' => 'sandbox',
                'mode' => 'sandbox',
                'issue_on_post' => false,
            ],
            'report_preferences' => [
                'show_zero_balances' => false,
            ],
            'feature_flags' => $this->defaultFeatureFlags(),
            'approval_settings' => [
                'default_manual_voucher_status' => 'draft',
                'require_manual_voucher_approval' => false,
            ],
            'branch_settings' => [
                'dimension_key' => 'business_location_id',
                'use_business_locations_as_branches' => true,
            ],
            'integration_settings' => [
                'api_guard' => (string) config('vasaccounting.api_guard', 'auth:api'),
                'bank_statement_provider' => 'manual',
                'tax_export_provider' => 'local',
                'payroll_bridge_provider' => 'essentials',
            ],
            'budget_settings' => [
                'enforce_budget_control' => false,
                'default_budget_status' => 'draft',
            ],
        ];

        if (Schema::hasColumn('vas_business_settings', 'cutover_settings')) {
            $defaults['cutover_settings'] = $this->defaultCutoverSettings();
        }

        if (Schema::hasColumn('vas_business_settings', 'rollout_settings')) {
            $defaults['rollout_settings'] = $this->defaultRolloutSettings();
        }

        return VasBusinessSetting::firstOrCreate(
            ['business_id' => $businessId],
            $defaults
        );
    }

    public function buildDefaultPostingMapByCodes(int $businessId): array
    {
        $codes = (array) config('vasaccounting.default_posting_map_codes', []);
        if (empty($codes)) {
            return [];
        }

        $accounts = VasAccount::query()
            ->where('business_id', $businessId)
            ->whereIn('account_code', array_values($codes))
            ->pluck('id', 'account_code');

        $map = [];
        foreach ($codes as $key => $code) {
            $map[$key] = $accounts[$code] ?? null;
        }

        return $map;
    }

    public function mandatoryPostingMapKeys(): array
    {
        return (array) config('vasaccounting.mandatory_posting_map_keys', []);
    }

    public function isPostingMapComplete(VasBusinessSetting $settings): bool
    {
        $postingMap = (array) $settings->posting_map;

        foreach ($this->mandatoryPostingMapKeys() as $requiredKey) {
            if (empty($postingMap[$requiredKey])) {
                return false;
            }
        }

        return true;
    }

    public function bootstrapBusiness(int $businessId, int $userId): array
    {
        $accounts = $this->seedChartOfAccounts($businessId, $userId);
        $taxCodes = $this->seedTaxCodes($businessId);
        $sequences = $this->seedDocumentSequences($businessId);
        $period = $this->createFiscalYearPeriodIfMissing($businessId);
        $settings = $this->getOrCreateBusinessSettings($businessId);
        $settings->posting_map = $this->buildDefaultPostingMapByCodes($businessId);
        $settings->save();

        return compact('accounts', 'taxCodes', 'sequences', 'period', 'settings');
    }

    public function bootstrapStatus(int $businessId): array
    {
        $accountCount = VasAccount::query()
            ->where('business_id', $businessId)
            ->count();

        $systemAccountCount = VasAccount::query()
            ->where('business_id', $businessId)
            ->where('is_system', true)
            ->count();

        $taxCodeCount = VasTaxCode::query()
            ->where('business_id', $businessId)
            ->count();

        $sequenceCount = VasDocumentSequence::query()
            ->where('business_id', $businessId)
            ->count();

        $periodCount = VasAccountingPeriod::query()
            ->where('business_id', $businessId)
            ->count();

        $settingsExists = VasBusinessSetting::query()
            ->where('business_id', $businessId)
            ->exists();

        return [
            'account_count' => $accountCount,
            'system_account_count' => $systemAccountCount,
            'manual_account_count' => max(0, $accountCount - $systemAccountCount),
            'tax_code_count' => $taxCodeCount,
            'sequence_count' => $sequenceCount,
            'period_count' => $periodCount,
            'settings_exists' => $settingsExists,
            'needs_bootstrap' => ! $settingsExists
                || $systemAccountCount === 0
                || $taxCodeCount === 0
                || $sequenceCount === 0
                || $periodCount === 0,
        ];
    }

    public function ensureBusinessBootstrapped(int $businessId, int $userId): array
    {
        $status = $this->bootstrapStatus($businessId);

        if (! $status['needs_bootstrap']) {
            return [
                'bootstrapped' => false,
                'status' => $status,
            ];
        }

        $result = $this->bootstrapBusiness($businessId, $userId);

        return [
            'bootstrapped' => true,
            'result' => $result,
            'status' => $this->bootstrapStatus($businessId),
        ];
    }

    public function seedChartOfAccounts(int $businessId, int $userId): Collection
    {
        $seedRows = collect((array) config('vasaccounting.vn_chart', []))
            ->sort(function (array $left, array $right) {
                return [$left['level'] ?? 1, strlen((string) $left['code']), (string) $left['code']]
                    <=>
                    [$right['level'] ?? 1, strlen((string) $right['code']), (string) $right['code']];
            })
            ->values();
        $created = collect();
        $parentCodes = $seedRows->pluck('parent_code')->filter()->unique()->values();
        $createdBy = $userId > 0 ? $userId : null;

        foreach ($seedRows as $row) {
            $parentId = null;
            if (! empty($row['parent_code'])) {
                $parentId = VasAccount::query()
                    ->where('business_id', $businessId)
                    ->where('account_code', $row['parent_code'])
                    ->value('id');
            }

            $account = VasAccount::updateOrCreate(
                [
                    'business_id' => $businessId,
                    'account_code' => $row['code'],
                ],
                [
                    'account_name' => $row['name'],
                    'account_type' => $row['type'],
                    'account_category' => $row['category'] ?? null,
                    'normal_balance' => $row['normal_balance'] ?? 'debit',
                    'level' => $row['level'] ?? 1,
                    'parent_id' => $parentId,
                    'allows_manual_entries' => (bool) ($row['allows_manual_entries'] ?? ! $parentCodes->contains($row['code'])),
                    'is_control_account' => (bool) ($row['is_control_account'] ?? $parentCodes->contains($row['code'])),
                    'is_system' => true,
                    'is_active' => true,
                    'created_by' => $createdBy,
                    'meta' => [
                        'standard' => 'Circular 99/2025/TT-BTC',
                        'source_name' => $row['name'],
                        'parent_code' => $row['parent_code'] ?? null,
                        'seed_category' => $row['category'] ?? null,
                    ],
                ]
            );

            $created->push($account);
        }

        return $created;
    }

    public function seedTaxCodes(int $businessId): Collection
    {
        $created = collect();

        foreach ((array) config('vasaccounting.tax_codes', []) as $row) {
            $taxCode = VasTaxCode::updateOrCreate(
                [
                    'business_id' => $businessId,
                    'code' => $row['code'],
                ],
                [
                    'name' => $row['name'],
                    'direction' => $row['direction'],
                    'rate' => $row['rate'],
                    'is_system' => true,
                    'is_active' => true,
                ]
            );

            $created->push($taxCode);
        }

        return $created;
    }

    public function seedDocumentSequences(int $businessId): Collection
    {
        $created = collect();

        foreach ((array) config('vasaccounting.voucher_sequences', []) as $row) {
            $sequence = VasDocumentSequence::updateOrCreate(
                [
                    'business_id' => $businessId,
                    'sequence_key' => $row['sequence_key'],
                ],
                [
                    'prefix' => $row['prefix'],
                    'padding' => $row['padding'] ?? 5,
                    'next_number' => 1,
                    'reset_frequency' => 'yearly',
                    'is_active' => true,
                ]
            );

            $created->push($sequence);
        }

        return $created;
    }

    public function createFiscalYearPeriodIfMissing(int $businessId, ?Carbon $date = null): VasAccountingPeriod
    {
        $date = $date ?: Carbon::now(config('vasaccounting.default_timezone', 'Asia/Ho_Chi_Minh'));
        $business = Business::findOrFail($businessId);
        $fyStartMonth = max(1, min(12, (int) $business->fy_start_month));
        $year = (int) $date->year;
        $fiscalStart = Carbon::create($year, $fyStartMonth, 1, 0, 0, 0, $date->getTimezone());

        if ($date->lt($fiscalStart)) {
            $fiscalStart->subYear();
        }

        $fiscalEnd = (clone $fiscalStart)->addYear()->subDay();

        return VasAccountingPeriod::firstOrCreate(
            [
                'business_id' => $businessId,
                'start_date' => $fiscalStart->toDateString(),
                'end_date' => $fiscalEnd->toDateString(),
            ],
            [
                'name' => $fiscalStart->format('Y') . '-' . $fiscalEnd->format('Y') . ' Fiscal Year',
                'status' => 'open',
                'is_adjustment_period' => false,
            ]
        );
    }

    public function resolvePeriodForDate(int $businessId, Carbon $date): VasAccountingPeriod
    {
        $period = VasAccountingPeriod::query()
            ->where('business_id', $businessId)
            ->whereDate('start_date', '<=', $date->toDateString())
            ->whereDate('end_date', '>=', $date->toDateString())
            ->orderByDesc('start_date')
            ->first();

        if ($period) {
            return $period;
        }

        return $this->createFiscalYearPeriodIfMissing($businessId, $date);
    }

    public function documentStatuses(): array
    {
        return (array) config('vasaccounting.document_statuses', []);
    }

    public function periodStatuses(): array
    {
        return (array) config('vasaccounting.period_statuses', []);
    }

    public function defaultFeatureFlags(): array
    {
        return (array) config('vasaccounting.feature_flags', []);
    }

    public function enterpriseDomains(): array
    {
        return (array) config('vasaccounting.enterprise_domains', []);
    }

    public function defaultCutoverSettings(): array
    {
        $defaults = (array) config('vasaccounting.cutover_defaults', []);
        $defaults['hide_legacy_accounting_menu'] = (bool) ($defaults['hide_legacy_accounting_menu'] ?? false);
        $defaults['uat_statuses'] = array_replace(
            array_fill_keys(array_keys((array) config('vasaccounting.cutover_uat_personas', [])), false),
            (array) ($defaults['uat_statuses'] ?? [])
        );

        return $defaults;
    }

    public function defaultRolloutSettings(): array
    {
        return (array) config('vasaccounting.rollout_defaults', []);
    }

    public function enterpriseDomainConfig(string $domain): array
    {
        $config = $this->enterpriseDomains()[$domain] ?? null;
        if (! is_array($config)) {
            throw new InvalidArgumentException("Unknown VAS enterprise domain [{$domain}].");
        }

        return $config;
    }

    public function navigationItems(int $businessId): array
    {
        $settings = $this->getOrCreateBusinessSettings($businessId);
        $featureFlags = array_replace($this->defaultFeatureFlags(), (array) $settings->feature_flags);

        $items = [
            ['route' => 'vasaccounting.setup.index', 'label' => __('vasaccounting::lang.setup'), 'active' => 'vasaccounting.setup.*'],
            ['route' => 'vasaccounting.dashboard.index', 'label' => __('vasaccounting::lang.dashboard'), 'active' => 'vasaccounting.dashboard.*'],
            ['route' => 'vasaccounting.chart.index', 'label' => __('vasaccounting::lang.chart_of_accounts'), 'active' => 'vasaccounting.chart.*'],
            ['route' => 'vasaccounting.periods.index', 'label' => __('vasaccounting::lang.periods'), 'active' => 'vasaccounting.periods.*'],
            ['route' => 'vasaccounting.vouchers.index', 'label' => __('vasaccounting::lang.vouchers'), 'active' => 'vasaccounting.vouchers.*'],
        ];

        foreach ($this->enterpriseDomains() as $domain => $config) {
            if (($featureFlags[$domain] ?? true) === false) {
                continue;
            }

            $items[] = [
                'route' => (string) $config['route'],
                'label' => (string) $config['nav_label'],
                'permission' => (string) $config['permission'],
                'active' => str_replace('.index', '.*', (string) $config['route']),
            ];
        }

        $items[] = ['route' => 'vasaccounting.closing.index', 'label' => __('vasaccounting::lang.closing'), 'active' => 'vasaccounting.closing.*'];
        $items[] = ['route' => 'vasaccounting.cutover.index', 'label' => __('vasaccounting::lang.cutover'), 'permission' => 'vas_accounting.cutover.manage', 'active' => 'vasaccounting.cutover.*'];
        $items[] = ['route' => 'vasaccounting.reports.index', 'label' => __('vasaccounting::lang.reports'), 'active' => 'vasaccounting.reports.*'];

        return $items;
    }

    public function enterpriseDomainSummary(int $businessId, string $domain): array
    {
        $config = $this->enterpriseDomainConfig($domain);
        $recordTable = (string) ($config['record_table'] ?? '');
        $recordLabel = (string) ($config['record_label'] ?? 'Records');
        $providerConfigKey = (string) ($config['provider_config_key'] ?? '');

        $records = 0;
        if ($recordTable !== '' && Schema::hasTable($recordTable)) {
            $query = DB::table($recordTable)->where('business_id', $businessId);

            if ($recordTable === 'vas_vouchers') {
                $query->where('module_area', $domain);
            }

            $records = (int) $query->count();
        }

        $postedVouchers = VasVoucher::query()
            ->where('business_id', $businessId)
            ->where('module_area', $domain)
            ->where('status', 'posted')
            ->count();

        $workflowBacklog = VasVoucher::query()
            ->where('business_id', $businessId)
            ->where('module_area', $domain)
            ->whereIn('status', ['draft', 'pending_approval', 'approved'])
            ->count();

        $providerCount = $providerConfigKey !== ''
            ? count((array) config("vasaccounting.{$providerConfigKey}", []))
            : 0;

        return [
            'record_label' => $recordLabel,
            'records' => $records,
            'posted_vouchers' => $postedVouchers,
            'workflow_backlog' => $workflowBacklog,
            'provider_count' => $providerCount,
        ];
    }

    public function chartOptions(int $businessId): Collection
    {
        return VasAccount::query()
            ->where('business_id', $businessId)
            ->where('is_active', true)
            ->orderBy('account_code')
            ->get(['id', 'account_code', 'account_name', 'level']);
    }

    public function dashboardMetrics(int $businessId): array
    {
        $openPeriods = VasAccountingPeriod::query()
            ->where('business_id', $businessId)
            ->where('status', 'open')
            ->count();

        $postingFailures = VasPostingFailure::query()
            ->where('business_id', $businessId)
            ->whereNull('resolved_at')
            ->count();

        $draftVouchers = VasVoucher::query()
            ->where('business_id', $businessId)
            ->where('status', 'draft')
            ->count();

        $postedThisMonth = VasVoucher::query()
            ->where('business_id', $businessId)
            ->where('status', 'posted')
            ->whereMonth('posted_at', now()->month)
            ->whereYear('posted_at', now()->year)
            ->count();

        return compact('openPeriods', 'postingFailures', 'draftVouchers', 'postedThisMonth');
    }
}
